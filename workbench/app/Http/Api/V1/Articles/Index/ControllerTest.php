<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Index;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Workbench\App\Entities\Articles\Article;

#[CoversClass(Controller::class)]
final class ControllerTest extends TestCase
{
    #[Test]
    public function it_filters(): void
    {
        Article::factory()->count(3)->sequence(
            ['syndicated_at' => null],
            ['syndicated_at' => now()->subDay()],
            ['syndicated_at' => now()->addDay()],
        )->create();

        $between = now()->subDays(2)->toDateTimeString().'...'.now()->addDays(2)->toDateTimeString();
        $request = Validator::factory()->make([
            'filters' => [
                'syndicated_between' => $between,
            ],
            'sort' => '-created_at',
        ]);

        $response = $this->getJson(
            route('api.v1.articles.index', $request->input())
        );

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
