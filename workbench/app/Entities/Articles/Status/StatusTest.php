<?php

declare(strict_types=1);

namespace Workbench\App\Entities\Articles\Status;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    #[Test]
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}
