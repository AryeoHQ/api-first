<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Tags\Collection;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;

/**
 * @extends Collection<int, \Tests\Fixtures\Support\Entities\Tags\Tag>
 */
final class Tags extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
