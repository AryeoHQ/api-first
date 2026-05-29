<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Actions;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Workbench\App\Entities\Articles\Article;

#[CoversClass(Syndicate::class)]
final class SyndicateTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $article = Article::factory()->create();

        // Fake the LaravelNews API response...

        Syndicate::make($article)->now();

        $this->assertNotNull($article->syndicated_at);
    }
}
