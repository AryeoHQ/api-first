<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench;

abstract class TestCase extends Testbench\TestCase
{
    use RefreshDatabase;
    use WithCachedConfig;
    use WithCachedRoutes;

    protected $enablesPackageDiscoveries = true;

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->timestampsTz();
        });
    }
}
