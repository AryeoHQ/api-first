<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Tags\Factory;

use Tests\Fixtures\Support\Entities\Tags\Tag;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Tag>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [];
    }
}
