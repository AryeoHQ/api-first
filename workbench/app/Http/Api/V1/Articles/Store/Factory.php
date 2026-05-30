<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Store;

final class Factory extends \Support\Http\Requests\Factories\Factory
{
    protected string $request = Validator::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
        ];
    }

    public function withoutTitle(): self
    {
        return $this->state(fn () => [
            'title' => null,
        ]);
    }

    public function withoutBody(): self
    {
        return $this->state(fn () => [
            'body' => null,
        ]);
    }

    public function invalid(): self
    {
        $state = collect(['withoutTitle', 'withoutBody'])->random();

        return $this->$state();
    }
}
