<?php

declare(strict_types=1);

namespace Laravel\Passport\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

final class MissingScopeException extends AuthorizationException
{
    /**
     * @var list<string>
     */
    private array $scopes;

    /**
     * @param  string|list<string>  $scopes
     */
    public function __construct(string|array $scopes = [], string $message = 'Invalid scope(s) provided.')
    {
        parent::__construct($message);

        $this->scopes = Arr::wrap($scopes);
    }

    /**
     * @return list<string>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
