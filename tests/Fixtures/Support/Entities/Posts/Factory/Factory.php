<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Posts\Factory;

use Tests\Fixtures\Support\Entities\Posts\Post;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Post>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [];
    }
}
