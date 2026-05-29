<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Builder;

use Support\Database\Eloquent\Contracts\Filterable;
use Support\Database\Eloquent\HasFilters;

/**
 * @template TModel of \Workbench\App\Entities\Articles\Article
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
final class Builder extends \Illuminate\Database\Eloquent\Builder implements Filterable
{
    use HasFilters;
}
