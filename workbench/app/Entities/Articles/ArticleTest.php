<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Log;
use Tests\TestCase;

#[CoversClass(Article::class)]
final class ArticleTest extends TestCase
{
    #[Test]
    public function its_recorded_to_the_event_log(): void
    {
        $article = Article::factory()->create();
        $article->update(['title' => 'Updated Title']);
        $article->syndicate()->now();
        $article->delete();
        Article::find($article->getKey());

        $this->assertCount(8, Log::all());
    }
}
