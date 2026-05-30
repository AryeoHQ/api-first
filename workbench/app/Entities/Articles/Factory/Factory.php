<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Factory;

use Workbench\App\Entities\Articles\Article;
use Workbench\App\Entities\Articles\Status\Status;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Article>
 */
final class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    /**
     * @var class-string<Article>
     */
    protected $model = Article::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
        ];
    }

    public function syndicated(): self
    {
        return $this->state(fn () => [
            'syndicated_at' => now(),
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn () => [
            'status' => Status::Draft,
        ]);
    }

    public function published(): self
    {
        return $this->state(fn () => [
            'status' => Status::Published,
        ]);
    }
}
