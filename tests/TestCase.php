<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench;
use Support\Database\Query\Grammars\Rfc3339Extended\Providers\Provider as Rfc3339ExtendedProvider;
use Support\Http\Api\Providers\Provider as ApiProvider;

abstract class TestCase extends Testbench\TestCase
{
    use RefreshDatabase {
        refreshDatabase as private refreshDatabaseInTransaction;
    }
    use WithCachedConfig;
    use WithCachedRoutes;

    protected $enablesPackageDiscoveries = true;

    private bool $databaseRefreshed = false;

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

    protected function defineEnvironment($app): void
    {
        $token = ParallelTesting::token();

        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.connections.secondary', [
            ...$app['config']->get('database.connections.pgsql'),
            'database' => 'testing_secondary'.($token === false ? '' : '_test_'.$token),
        ]);
    }

    public function refreshDatabase(): void
    {
        if (! $this->databaseRefreshed) {
            $this->databaseRefreshed = true;

            $this->refreshDatabaseInTransaction();
        }
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->refreshDatabase();

        Schema::create('rfc3339_extended_test_models', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed(): void
    {
        $database = DB::getQueryGrammar()->wrap((string) config('database.connections.secondary.database'));

        DB::unprepared("drop database if exists {$database} with (force)");
        DB::unprepared("create database {$database}");

        Artisan::call('migrate', ['--database' => 'secondary']);
    }
}
