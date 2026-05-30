<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench;
use Support\Routing\DirectoryConfig;
use Workbench\App\Entities\Articles\Provider\Provider as ArticlesProvider;

abstract class TestCase extends Testbench\TestCase
{
    use RefreshDatabase;
    use WithCachedConfig;
    use WithCachedRoutes;

    protected $enablesPackageDiscoveries = true;

    protected $afterCommitCallbacksShouldBeExecuted = true;

    protected function getPackageProviders($app): array
    {
        return [
            ArticlesProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('routing.directories', [
            new DirectoryConfig(
                path: dirname(__DIR__).'/workbench/app/Http',
            ),
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('body');
            $table->string('status');
            $table->timestamps();
            $table->timestamp('syndicated_at')->nullable();
        });
    }
}
