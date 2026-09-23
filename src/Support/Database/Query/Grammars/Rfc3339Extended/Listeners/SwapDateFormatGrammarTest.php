<?php

declare(strict_types=1);

namespace Support\Database\Query\Grammars\Rfc3339Extended\Listeners;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(SwapDateFormatGrammar::class)]
final class SwapDateFormatGrammarTest extends TestCase
{
    /** @return array<int, array{class-string, class-string}> */
    public static function grammars(): array
    {
        $map = (new SwapDateFormatGrammar)->map;

        return array_map(
            static fn (string $laravel, string $ours): array => [$laravel, $ours],
            $map->keys()->all(),
            $map->values()->all(),
        );
    }

    #[Test]
    #[DataProvider('grammars')]
    public function it_replaces_laravels_grammar_with_ours(string $laravel, string $ours): void
    {
        $connection = DB::connection();
        $connection->setQueryGrammar(new $laravel($connection));

        (new SwapDateFormatGrammar)->handle(new ConnectionEstablished($connection));

        $this->assertInstanceOf($ours, $connection->getQueryGrammar());
    }
}
