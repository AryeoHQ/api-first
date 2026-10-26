<?php

declare(strict_types=1);

namespace Support\Http\Api\Pagination;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator as UpstreamCursorPaginator;
use Illuminate\Support\Str;

/**
 * @template TKey of array-key
 * @template TValue
 *
 * @extends UpstreamCursorPaginator<TKey, TValue>
 */
final class CursorPaginator extends UpstreamCursorPaginator
{
    private const string RFC3339_EXTENDED_MICROSECONDS = 'Y-m-d\TH:i:s.uP';

    private null|Model $model = null;

    /**
     * @param  mixed  $item
     * @return array<string, mixed>
     */
    #[\Override]
    public function getParametersForItem($item): array
    {
        $resource = $item instanceof JsonResource ? $item->resource : $item;

        $this->model = $resource instanceof Model ? $resource : null;

        try {
            return parent::getParametersForItem($item);
        } finally {
            $this->model = null;
        }
    }

    /**
     * @param  mixed  $item
     * @param  string  $parameterName
     */
    #[\Override]
    protected function getPivotParameterForItem($item, $parameterName)
    {
        $model = $this->model;

        $this->model = $this->pivotFor($parameterName);

        try {
            return parent::getPivotParameterForItem($item, $parameterName);
        } finally {
            $this->model = $model;
        }
    }

    #[\Override]
    protected function ensureParameterIsPrimitive(mixed $parameter): mixed
    {
        if ($parameter instanceof DateTimeInterface) {
            return $parameter->format($this->dateFormatFor($parameter));
        }

        return parent::ensureParameterIsPrimitive($parameter);
    }

    private function pivotFor(string $parameterName): null|Pivot
    {
        foreach ($this->model?->getRelations() ?? [] as $relation) {
            if ($relation instanceof Pivot && $relation->getTable() === Str::beforeLast($parameterName, '.')) {
                return $relation;
            }
        }

        return null;
    }

    private function dateFormatFor(DateTimeInterface $parameter): string
    {
        $format = $this->model?->getDateFormat() ?? DateTimeInterface::RFC3339_EXTENDED;

        if ($format === DateTimeInterface::RFC3339_EXTENDED && (int) $parameter->format('u') % 1000 !== 0) {
            return self::RFC3339_EXTENDED_MICROSECONDS;
        }

        return $format;
    }
}
