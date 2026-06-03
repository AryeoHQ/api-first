<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Builder;

use Support\Database\Eloquent\Attributes\Filter;
use Support\Database\Eloquent\Contracts\Filterable;
use Support\Database\Eloquent\Contracts\Sortable;
use Support\Database\Eloquent\HasFilters;
use Support\Database\Eloquent\HasSort;
use Support\Primitives\Interval;

/**
 * @template TModel of \Workbench\App\Entities\Articles\Article
 *
 * @extends \Illuminate\Database\Eloquent\Builder<TModel>
 */
final class Builder extends \Illuminate\Database\Eloquent\Builder implements Filterable, Sortable
{
    use HasFilters;
    use HasSort;

    #[Filter('syndicated_between')]
    public function syndicatedBetween(Interval $interval): static
    {
        return $this->whereBetween('syndicated_at', [$interval->min, $interval->max]);
    }
}
