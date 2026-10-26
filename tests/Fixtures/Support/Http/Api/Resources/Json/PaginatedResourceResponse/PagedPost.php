<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json\PaginatedResourceResponse;

use Illuminate\Database\Eloquent\Model;

class PagedPost extends Model // @phpstan-ignore entities.Model.Entity.required
{
    public $timestamps = false;

    protected $table = 'paged_posts';
}
