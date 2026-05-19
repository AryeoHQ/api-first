<?php

declare(strict_types=1);

namespace Support\Http\Api\Providers;

use Carbon\FactoryImmutable;
use DateTime;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;
use ReflectionNamedType;
use Support\Entities\Database\Query\Grammars\PostgresGrammar;
use Support\Entities\Database\Query\Grammars\SQLiteGrammar;
use Support\Http\Api\Console\Commands\MakeCollection\MakeCollection;
use Support\Http\Api\Console\Commands\MakeController\MakeController;
use Support\Http\Api\Console\Commands\MakeEvent\MakeEvent;
use Support\Http\Api\Console\Commands\MakeModel\MakeModel;
use Support\Http\Api\Console\Commands\MakeResource\Listeners\InjectSchemaProperties;
use Support\Http\Api\Console\Commands\MakeResource\MakeResource;
use Support\Http\Api\Request\TokenContext;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\Events\BuildingSchema;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\References;

class Provider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerQueryGrammars();
        $this->registerBindings();
        $this->registerCursorResolver();
        $this->registerMixins();
    }

    public function boot(): void
    {
        $this->bootCarbonConfiguration();
        $this->bootSchemaConfiguration();
        $this->bootListeners();
        $this->bootCommands();
    }

    private function registerMixins(): void
    {
        Request::mixin(new TokenContext);
    }

    private function registerCursorResolver(): void
    {
        CursorPaginator::currentCursorResolver(function () {
            return Cursor::fromEncoded(request()->input('paging.cursor'));
        });
    }

    private function registerBindings(): void
    {
        $this->registerCastableDataBinding();
        $this->registerMakeCommands();
        $this->registerReferences();
    }

    private function registerCastableDataBinding(): void
    {
        $this->app->scoped(CastableData::class, function (): null|CastableData {
            $type = collect(request()->route()?->signatureParameters(['subClass' => CastableData::class]))
                ->map(fn ($p) => $p->getType())
                ->first(fn ($type) => $type instanceof ReflectionNamedType && ! $type->isBuiltin());

            return $type ? resolve($type->getName()) : null;
        });
    }

    private function registerMakeCommands(): void
    {
        $this->app->bind(
            \Support\Entities\Models\Console\Commands\MakeCollection::class,
            MakeCollection::class,
        );
    }

    private function registerReferences(): void
    {
        $this->app->bind(References\Schema::class, function ($app, array $params) {
            $name = str(data_get($params, 'name', ''));
            $baseNamespace = str(data_get($params, 'baseNamespace', ''))->when(
                fn ($s) => ! $s->endsWith('\\'.$name->plural()->toString()),
                fn ($s) => $s->append('\\', $name->plural()->toString()),
            );

            return new References\Schema(name: $name, baseNamespace: $baseNamespace);
        });
    }

    private function bootCarbonConfiguration(): void
    {
        FactoryImmutable::getDefaultInstance()->mergeSettings([ // @phpstan-ignore staticMethod.internal
            'toJsonFormat' => DateTime::RFC3339_EXTENDED,
            'toStringFormat' => DateTime::RFC3339_EXTENDED,
        ]);
    }

    private function bootSchemaConfiguration(): void
    {
        Facades\Schema::defaultTimePrecision(3);
    }

    private function registerQueryGrammars(): void
    {
        $grammars = [
            \Illuminate\Database\Query\Grammars\PostgresGrammar::class => PostgresGrammar::class,
            \Illuminate\Database\Query\Grammars\SQLiteGrammar::class => SQLiteGrammar::class,
        ];

        Facades\Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) use ($grammars): void {
            if (! $event->connection->getConfig('date_format')) {
                return;
            }

            $grammarClass = $event->connection->getQueryGrammar()::class;

            if (! array_key_exists($grammarClass, $grammars)) {
                return;
            }

            $event->connection->setQueryGrammar(new $grammars[$grammarClass]($event->connection));
        });
    }

    private function bootListeners(): void
    {
        Facades\Event::listen(BuildingSchema::class, InjectSchemaProperties::class);
    }

    private function bootCommands(): void
    {
        $this->commands([
            MakeController::class,
        ]);

        $this->app->booted(function () {
            $this->commands([
                MakeCollection::class,
                MakeEvent::class,
                MakeModel::class,
                MakeResource::class,
            ]);
        });
    }
}
