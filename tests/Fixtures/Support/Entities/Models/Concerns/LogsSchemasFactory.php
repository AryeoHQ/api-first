<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Entities\Models\Concerns;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<LogsSchemasModel>
 */
final class LogsSchemasFactory extends \Illuminate\Database\Eloquent\Factories\Factory
{
    /** @var class-string<LogsSchemasModel> */
    protected $model = LogsSchemasModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [];
    }
}
