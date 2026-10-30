<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench;
use Support\Database\Query\Grammars\Rfc3339Extended\Providers\Provider as Rfc3339ExtendedProvider;
use Support\Http\Api\Providers\Provider as ApiProvider;

abstract class TestCase extends Testbench\TestCase
{
    use RefreshDatabase;
    use WithCachedConfig;
    use WithCachedRoutes;

    protected $enablesPackageDiscoveries = true;

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            Rfc3339ExtendedProvider::class,
            ApiProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('rfc3339_extended_test_models', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }
}
