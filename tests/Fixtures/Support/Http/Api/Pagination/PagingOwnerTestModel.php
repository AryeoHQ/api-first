<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Pagination;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Tests\Fixtures\Support\Database\Query\Grammars\Rfc3339Extended\Rfc3339ExtendedTestModel;

class PagingOwnerTestModel extends Rfc3339ExtendedTestModel // @phpstan-ignore entities.Model.CollectedBy.required, entities.Model.UseEloquentBuilder.required, entities.Model.UseFactory.required, entities.Model.UsePolicy.required
{
    protected $table = 'paging_owner_test_models';

    /**
     * @return BelongsToMany<Rfc3339ExtendedTestModel, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Rfc3339ExtendedTestModel::class, 'paging_memberships', 'owner_id', 'member_id')->withPivot('id')->withTimestamps();
    }

    /**
     * @return HasManyThrough<PagingItemTestModel, PagingGroupTestModel, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(PagingItemTestModel::class, PagingGroupTestModel::class, 'owner_id', 'group_id');
    }
}
