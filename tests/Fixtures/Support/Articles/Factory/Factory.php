<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Articles\Factory;

use Tests\Fixtures\Support\Articles\Article;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Article>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [];
    }
}
