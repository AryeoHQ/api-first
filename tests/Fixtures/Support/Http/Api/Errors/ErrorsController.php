<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Errors;

use RuntimeException;
use Support\Http\Api\Errors\Exceptions\PreconditionFailed;
use Support\Http\Api\Errors\Exceptions\PreconditionRequired;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
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

    public function throttled(): void
    {
        //
    }

    public function preconditionFailed(): never
    {
        throw new PreconditionFailed('"33a64df5"');
    }

    public function preconditionRequired(): never
    {
        throw new PreconditionRequired;
    }

    public function fail(): never
    {
        throw new RuntimeException('Connection refused by db.internal:5432 for user aryeo.');
    }
}
