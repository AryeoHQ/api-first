<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Database\Eloquent\StateMachines\Attributes\Transitions\Exceptions\Invalid;
use Support\Events\Log\Logs\Log;
use Tests\TestCase;
use Workbench\App\Entities\Articles\Status\Status;

#[CoversClass(Article::class)]
final class ArticleTest extends TestCase
{
    #[Test]
    public function it_can_transition_from_draft_to_published(): void
    {
        $article = Article::factory()->draft()->create();

        $article->status->publish()->now();

        $this->assertSame(Status::Published, $article->status->enum);
    }

    #[Test]
    public function it_can_transition_from_published_to_draft(): void
    {
        $article = Article::factory()->published()->create();

        $article->status->draft()->now();

        $this->assertSame(Status::Draft, $article->status->enum);
    }

    #[Test]
    public function it_cannot_transition_from_published_to_draft_when_syndicated(): void
    {
        $article = Article::factory()->published()->syndicated()->create();

        $this->expectException(Invalid::class);

        $article->status->draft()->now();
    }

    #[Test]
    public function its_recorded_to_the_event_log(): void
    {
        $article = Article::factory()->create(); // 2
        $article->status->publish()->now(); // 4
        $article->status->draft()->now(); // 4
        $article->syndicate()->now(); // 2

        $this->assertCount(12, Log::all());
    }
}
