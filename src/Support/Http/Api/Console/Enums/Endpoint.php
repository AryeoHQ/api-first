<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Enums;

use Support\Routing\Enums\Method;

enum Endpoint: string
{
    case Index = 'index';
    case Store = 'store';
    case Show = 'show';
    case Update = 'update';
    case Delete = 'delete';
    case Search = 'search';

    public function httpMethod(): Method
    {
        return match ($this) {
            self::Index, self::Show => Method::Get,
            self::Store, self::Search => Method::Post,
            self::Update => Method::Patch,
            self::Delete => Method::Delete,
        };
    }

    public function scope(): Scope
    {
        return match ($this) {
            self::Show, self::Update, self::Delete => Scope::Instance,
            self::Index, self::Store, self::Search => Scope::Resource,
        };
    }
}
