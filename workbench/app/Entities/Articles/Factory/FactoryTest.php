<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Workbench\App\Entities\Articles\Article;

#[CoversClass(Factory::class)]
final class FactoryTest extends TestCase
{
    #[Test]
    public function it_makes(): void
    {
        $this->assertInstanceOf(
            Article::class,
            Article::factory()->make()
        );
    }

    #[Test]
    public function it_creates(): void
    {
        $this->assertInstanceOf(
            Article::class,
            Article::factory()->create()
        );
    }
}
