<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Index;

final class Factory extends \Support\Http\Requests\Factories\Factory
{
    protected string $request = Validator::class;

    public function definition(): array
    {
        return [];
    }
}
