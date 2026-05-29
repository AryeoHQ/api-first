<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Syndicate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Workbench\App\Entities\Articles\Actions\Syndicate;
use Workbench\App\Entities\Articles\Article;

#[CoversClass(Controller::class)]
final class ControllerTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        Syndicate::fake()->andReturn(
            $syndicated = Article::factory()->syndicated()->create()->fresh()
        );

        $this->postJson(
            route('api.v1.articles.actions.syndicate', ['article' => $syndicated->getKey()])
        )->assertOk()->assertJsonFragment([
            'syndicated_at' => $syndicated->syndicated_at,
        ]);

        Syndicate::assertFiredTimes(1);
    }
}
