<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use ReflectionClass;
use stdClass;
use Support\Http\Api\Errors\Attributes\LegacyErrors;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;
use Support\Http\Api\Errors\Exceptions\PreconditionRequired;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class Renderer
{
    public function __invoke(Throwable $e, Request $request): null|JsonResponse
    {
        $route = $request->route();

        if (! $route instanceof Route || ! $this->servesApi($route)) {
            return null;
        }

        if ($e instanceof PreconditionFailed || $e instanceof PreconditionRequired) {
            return $this->precondition($e);
        }

        if ($this->isLegacy($route) || $e instanceof HttpResponseException) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => $this->validation($e, $request),
            $e instanceof AuthenticationException => $this->respond(401, [$this->unauthenticated($request)]),
            $e instanceof HttpExceptionInterface => $this->http($e, $request),
            default => $this->respond(500, [new ErrorObject('server.internal_error', 'The server failed to complete the request.')]),
        };
    }

    private function servesApi(Route $route): bool
    {
        $groups = Config::array('api-first.errors.middleware_groups');

        return array_any($route->middleware(), fn (mixed $middleware): bool => in_array($middleware, $groups, true));
    }

    private function isLegacy(Route $route): bool
    {
        $controller = $route->getControllerClass();

        return $controller !== null
            && class_exists($controller)
            && new ReflectionClass($controller)->getAttributes(LegacyErrors::class) !== [];
    }

    private function precondition(PreconditionFailed|PreconditionRequired $e): JsonResponse
    {
        $error = $e instanceof PreconditionFailed
            ? new ErrorObject('precondition.failed', 'The If-Match precondition does not match the current resource.', Source::header('If-Match'))
            : new ErrorObject('precondition.required', 'This operation requires the If-Match header.', Source::header('If-Match'));

        return $this->respond($e->getStatusCode(), [$error], $e->getHeaders());
    }

    private function validation(ValidationException $e, Request $request): null|JsonResponse
    {
        if (! $e->validator instanceof Validator) {
            return null;
        }

        $errors = new ValidationErrors($e->validator, $request)->toErrorObjects();

        return $errors === null ? null : $this->respond($e->status, $errors);
    }

    private function unauthenticated(Request $request): ErrorObject
    {
        return $request->bearerToken() === null
            ? new ErrorObject('auth.missing_token', 'The request carries no access token.')
            : new ErrorObject('auth.invalid_token', 'The access token is invalid or expired.');
    }

    private function http(HttpExceptionInterface $e, Request $request): null|JsonResponse
    {
        $error = match ($e->getStatusCode()) {
            403 => new ErrorObject('permission.denied', 'The caller cannot perform this operation.'),
            404 => new ErrorObject('not_found.resource', 'No resource exists at this path.'),
            405 => new ErrorObject('method.not_allowed', "The {$request->method()} method is not allowed on this path."),
            429 => new ErrorObject('rate_limit.exceeded', 'The request exceeds a rate limit.'),
            default => null,
        };

        return $error === null ? null : $this->respond($e->getStatusCode(), [$error], $e->getHeaders());
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
