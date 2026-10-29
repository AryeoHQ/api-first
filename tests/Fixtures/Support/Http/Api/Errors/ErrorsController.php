<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use RuntimeException;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;
use Support\Http\Api\Errors\Exceptions\PreconditionRequired;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Tests\Fixtures\Support\Articles\Article;

final class ErrorsController
{
    public function authenticated(): void
    {
        //
    }

    public function forbidden(Authorizer $authorizer): void
    {
        //
    }

    public function show(Article $article): void
    {
        //
    }

    public function hidden(Article $article): void
    {
        Gate::authorize('view', $article);
    }

    public function missingScope(): never
    {
        require_once __DIR__.'/MissingScopeException.php';

        throw new MissingScopeException(['articles:write']);
    }

    public function methodNotAllowed(): never
    {
        throw new MethodNotAllowedHttpException(['GET']);
    }

    public function validate(Validator $validator): void
    {
        //
    }

    public function rules(RulesValidator $validator): void
    {
        //
    }

    public function after(AfterValidator $validator): void
    {
        //
    }

    public function validationStatus(): never
    {
        throw new ValidationException(ValidatorFactory::make([], ['title' => ['required']]))->status(400);
    }

    public function badRequest(): never
    {
        throw new BadRequestHttpException;
    }

    public function unsupportedMediaType(): never
    {
        throw new UnsupportedMediaTypeHttpException;
    }

    public function unavailable(): never
    {
        throw new ServiceUnavailableHttpException(30);
    }

    public function abort(int $status): never
    {
        abort($status, 'Tenant A owns secret X.');
    }

    public function customRules(CustomRulesValidator $validator): void
    {
        //
    }

    public function withMessages(): never
    {
        throw ValidationException::withMessages(['window' => 'The booking window is closed.']);
    }

    public function throttled(): void
    {
        //
    }

    public function preconditionFailed(): never
    {
        throw new PreconditionFailed;
    }

    public function preconditionRequired(): never
    {
        throw new PreconditionRequired;
    }

    public function preconditionFailedWithHeaders(): never
    {
        throw new PreconditionFailed(headers: ['etag' => '"33a64df5"', 'X-Request-Trace' => 'trace-1']);
    }

    public function symfonyPreconditionFailed(): never
    {
        throw new PreconditionFailedHttpException(headers: ['ETag' => '"33a64df5"']);
    }

    public function symfonyPreconditionRequired(): never
    {
        throw new PreconditionRequiredHttpException;
    }

    public function fail(): never
    {
        throw new RuntimeException('Connection refused by db.internal:5432 for user aryeo.');
    }
}
