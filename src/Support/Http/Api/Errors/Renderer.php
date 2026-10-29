<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use stdClass;
use Support\Http\Api\Errors\Attributes\LegacyErrors;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Throwable;

final class Renderer
{
    private const string MISSING_SCOPE = 'Laravel\Passport\Exceptions\MissingScopeException';

    private const string GENERIC_DETAIL = 'The API cannot complete the request.';

    public function __invoke(Throwable $e, Request $request): null|JsonResponse
    {
        $route = $request->route();

        if (! $route instanceof Route || ! $this->servesApi($route)) {
            return null;
        }

        if ($e instanceof PreconditionFailedHttpException || $e instanceof PreconditionRequiredHttpException) {
            return $this->precondition($e);
        }

        if ($this->isLegacy($route) || $e instanceof HttpResponseException) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => $this->validation($e, $request),
            $e instanceof AuthenticationException => $this->respond(401, [$this->unauthenticated($request)]),
            $e instanceof HttpExceptionInterface => $this->http($e, $request),
            default => $this->respond(500, [$this->error(500, 'The server failed to complete the request.')]),
        };
    }

    private function servesApi(Route $route): bool
    {
        $groups = Arr::wrap(config('api-first.errors.middleware_groups', []));

        return array_any($route->middleware(), fn (mixed $middleware): bool => in_array($middleware, $groups, true));
    }

    private function isLegacy(Route $route): bool
    {
        $controller = $route->getControllerClass();

        return $controller !== null
            && class_exists($controller)
            && new ReflectionClass($controller)->getAttributes(LegacyErrors::class) !== [];
    }

    private function precondition(PreconditionFailedHttpException|PreconditionRequiredHttpException $e): JsonResponse
    {
        if ($e instanceof PreconditionFailedHttpException) {
            $error = $this->error(412, 'The If-Match precondition does not match the current resource.', Source::header('If-Match'));
            $headers = array_filter($e->getHeaders(), fn (string $name): bool => strcasecmp($name, 'ETag') !== 0, ARRAY_FILTER_USE_KEY);

            return $this->respond(412, [$error], $headers);
        }

        $error = $this->error(428, 'This operation requires the If-Match header.', Source::header('If-Match'));

        return $this->respond(428, [$error], $e->getHeaders());
    }

    private function validation(ValidationException $e, Request $request): JsonResponse
    {
        if ($this->isMalformed($request)) {
            return $this->respond(400, [$this->error(400, 'The API cannot parse the request.')]);
        }

        if ($e->status !== 422) {
            return $this->respond($e->status, [$this->error($e->status, self::GENERIC_DETAIL)]);
        }

        $errors = array_map(
            fn (array $failure): ErrorObject => $this->error(422, $failure['detail'], $failure['source']),
            new ValidationErrors($e->validator, $request)->toFailures(),
        );

        return $this->respond(422, $errors);
    }

    private function isMalformed(Request $request): bool
    {
        $content = $request->getContent();

        return $content !== '' && $request->isJson() && ! json_validate($content);
    }

    private function unauthenticated(Request $request): ErrorObject
    {
        return filled($request->bearerToken())
            ? $this->error(401, 'The access token is invalid or expired.')
            : $this->error(401, 'The request carries no access token.');
    }

    private function http(HttpExceptionInterface $e, Request $request): JsonResponse
    {
        $status = $e->getStatusCode();
        $previous = $e->getPrevious();

        if ($status === 404 && $previous instanceof AuthorizationException) {
            $status = 403;
        }

        $detail = match ($status) {
            400 => 'The API cannot parse the request.',
            403 => $this->forbidden($previous),
            404 => 'No resource exists at this path.',
            405 => "The {$request->method()} method is not allowed on this path.",
            415 => 'The request body must use the application/json media type.',
            429 => 'The request exceeds a rate limit.',
            503 => 'The service cannot serve requests at the moment.',
            default => self::GENERIC_DETAIL,
        };

        return $this->respond($status, [$this->error($status, $detail)], $e->getHeaders());
    }

    private function forbidden(null|Throwable $previous): string
    {
        $scope = is_a($previous, self::MISSING_SCOPE)
            ? Arr::first(Arr::wrap($previous->scopes()))
            : null;

        return is_string($scope)
            ? "The access token does not grant the {$scope} scope."
            : 'The caller cannot perform this operation.';
    }

    private function error(int $status, string $detail, null|Source $source = null): ErrorObject
    {
        return new ErrorObject(ErrorCode::fromStatus($status), $detail, $source);
    }

    /**
     * @param  list<ErrorObject>  $errors
     * @param  array<string, string|list<string>>  $headers
     */
    private function respond(int $status, array $errors, array $headers = []): JsonResponse
    {
        return new JsonResponse(['errors' => $errors, 'meta' => new stdClass], $status, $headers);
    }
}
