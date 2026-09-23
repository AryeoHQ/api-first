<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended\Listeners;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Collection;
use Support\Database\Query\Grammars\Rfc3339Extended\MariaDbGrammar;
use Support\Database\Query\Grammars\Rfc3339Extended\MySqlGrammar;
use Support\Database\Query\Grammars\Rfc3339Extended\PostgresGrammar;
use Support\Database\Query\Grammars\Rfc3339Extended\SQLiteGrammar;
use Support\Database\Query\Grammars\Rfc3339Extended\SqlServerGrammar;

class SwapDateFormatGrammar
{
    /**
     * @var Collection<class-string, class-string>
     */
    public Collection $map {
        get => collect([
            MySqlGrammar::class,
            MariaDbGrammar::class,
            PostgresGrammar::class,
            SQLiteGrammar::class,
            SqlServerGrammar::class,
        ])->mapWithKeys($this->parentEntry(...));
    }

    /**
     * @return array<class-string, class-string>
     */
    private function parentEntry(string $grammar): array
    {
        return [get_parent_class($grammar) => $grammar];
    }

    public function handle(ConnectionEstablished $event): void
    {
        $replacement = $this->map->get($event->connection->getQueryGrammar()::class);

        if ($replacement === null) {
            return;
        }

        $event->connection->setQueryGrammar(new $replacement($event->connection));
    }
}
