<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Collection;

use Illuminate\Database\Eloquent\Collection;
use Support\Http\Resources\Schemas\Attributes\UseSchemaCollection\UseSchemaCollection;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;
use Workbench\App\Http\Api\V1;

/**
 * @extends Collection<int, \Workbench\App\Entities\Articles\Article>
 */
#[UseSchemaCollection(V1\Articles\Articles::class)]
final class Articles extends Collection implements SchemableCollection
{
    use TransformsToSchemaCollection;
}
