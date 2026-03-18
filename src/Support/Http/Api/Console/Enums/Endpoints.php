<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Enums;

use Support\Routing\Enums\Method;

enum Endpoints: string
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
            self::Update => Method::Put,
            self::Delete => Method::Delete,
        };
    }

    public function isSingleResource(): bool
    {
        return in_array($this, [self::Show, self::Update, self::Delete], true);
    }
}
