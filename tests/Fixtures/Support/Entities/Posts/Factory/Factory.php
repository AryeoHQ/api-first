<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Posts\Factory;

use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Tests\Fixtures\Support\Entities\Posts\Post;

/**
 * @extends EloquentFactory<Post>
 */
final class Factory extends EloquentFactory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [];
    }
}
