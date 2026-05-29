<?php

declare(strict_types=1);

namespace Workbench\App\Http\Api\V1\Articles;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Articles::class)]
final class ArticlesTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}
