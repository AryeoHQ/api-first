<?php

declare(strict_types=1);

namespace Support\Http\Api\Request;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;

/** @mixin \Illuminate\Http\Request */
class TokenContext
{
    /** @return Closure(): (Authenticatable|null) */
    public function actor(): Closure
    {
        return fn (): null|Authenticatable => $this->user();
    }

    /** @return Closure(): (Authenticatable|null) */
    public function subject(): Closure
    {
        return fn (): null|Authenticatable => $this->user();
    }
}
