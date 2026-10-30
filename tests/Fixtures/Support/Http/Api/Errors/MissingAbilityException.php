<?php

declare(strict_types=1);

namespace Laravel\Sanctum\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

final class MissingAbilityException extends AuthorizationException
{
    /**
     * @var list<string>
     */
    private array $abilities;

    /**
     * @param  string|list<string>  $abilities
     */
    public function __construct(string|array $abilities = [], string $message = 'Invalid ability provided.')
    {
        parent::__construct($message);

        $this->abilities = Arr::wrap($abilities);
    }

    /**
     * @return list<string>
     */
    public function abilities(): array
    {
        return $this->abilities;
    }
}
