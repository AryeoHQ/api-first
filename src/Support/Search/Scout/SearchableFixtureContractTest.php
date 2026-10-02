<?php

declare(strict_types=1);

namespace Support\Search\Scout;

use PHPUnit\Framework\Attributes\Test;
use Support\Search\Scout\Contracts\Searchable;
use Tests\Fixtures\Support\Listings\Listing;
use Tests\TestCase;

/**
 * Verifies that a model scaffolded with make:model --searchable satisfies the
 * Searchable contract and is accepted by the Scout engine pipeline.
 */
final class SearchableFixtureContractTest extends TestCase
{
    #[Test]
    public function fixture_implements_searchable_contract(): void
    {
        $this->assertInstanceOf(Searchable::class, new Listing);
    }

    #[Test]
    public function to_searchable_array_returns_array(): void
    {
        $result = (new Listing)->toSearchableArray();

        $this->assertIsArray($result);
    }

    #[Test]
    public function searchable_as_returns_string_index_name(): void
    {
        $result = (new Listing)->searchableAs();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function fixture_uses_interacts_with_search_engine(): void
    {
        $this->assertContains(
            InteractsWithSearchEngine::class,
            class_uses_recursive(Listing::class),
        );
    }
}
