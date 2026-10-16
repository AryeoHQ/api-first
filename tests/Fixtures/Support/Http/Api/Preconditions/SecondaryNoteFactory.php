<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecondaryNote>
 */
final class SecondaryNoteFactory extends Factory
{
    protected $model = SecondaryNote::class;

    public function definition(): array
    {
        return ['title' => 'Draft'];
    }
}
