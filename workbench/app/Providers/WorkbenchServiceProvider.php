<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Tests\Fixtures\Support\Schemas\ApiVersion;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        config()->set('api-resource-schema.version', ApiVersion::class);
    }
}
