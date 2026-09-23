<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended\Providers;

use Carbon\FactoryImmutable;
use DateTime;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Support\Database\Query\Grammars\Rfc3339Extended\Listeners\SwapDateFormatGrammar;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerListeners();
    }

    public function boot(): void
    {
        $this->bootSchemaConfiguration();
        $this->bootCarbonSerialization();
    }

    /**
     * Must be registered here rather than in boot() — the default connection is
     * established during bootstrap, before any provider's boot() runs.
     */
    private function registerListeners(): void
    {
        Event::listen(ConnectionEstablished::class, SwapDateFormatGrammar::class);
    }

    private function bootSchemaConfiguration(): void
    {
        Schema::defaultTimePrecision(3);
    }

    private function bootCarbonSerialization(): void
    {
        FactoryImmutable::getDefaultInstance()->serializeUsing(
            fn (\DateTimeInterface $date): string => $date->format(DateTime::RFC3339_EXTENDED)
        );
    }
}
