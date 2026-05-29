<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles\Store;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Controller::class)]
final class ControllerTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $payload = Validator::factory()->make();

        $response = $this->postJson(route('api.v1.articles.store'), $payload->toArray());

        $response->assertCreated()->assertJsonFragment($payload->toArray());
    }

    #[Test]
    public function it_validates(): void
    {
        $payload = Validator::factory()->invalid()->make();

        $response = $this->postJson(route('api.v1.articles.store'), $payload->toArray());

        $response->assertUnprocessable();
    }
}
