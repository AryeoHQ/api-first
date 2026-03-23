<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Orchestra\Testbench;

abstract class TestCase extends Testbench\TestCase
{
    use RefreshDatabase;
    use WithCachedConfig;
    use WithCachedRoutes;

    protected $enablesPackageDiscoveries = true;
}
