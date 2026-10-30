<?php

declare(strict_types=1);

namespace Support\Http\Api\References;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\References\ReferenceTestCases;
use Tooling\GeneratorCommands\Testing\Contracts\TestsReference;

#[CoversClass(Precondition::class)]
final class PreconditionTest extends TestCase implements TestsReference
{
    use ReferenceTestCases;

    public Reference $subject {
        get => new Precondition(name: 'Precondition', baseNamespace: 'Workbench\\App\\Http\\Api\\V1\\Orders\\Update');
    }

    public string $expectedName = 'Precondition';

    #[Test]
    public function it_sits_beside_its_controller(): void
    {
        $this->assertSame('\\Workbench\\App\\Http\\Api\\V1\\Orders\\Update\\Precondition', $this->subject->fqcn->toString());
        $this->assertStringEndsWith('workbench/app/Http/Api/V1/Orders/Update/Precondition.php', $this->subject->filePath->toString());
    }

    #[Test]
    public function it_builds_from_the_precondition_stub(): void
    {
        $this->assertStringEndsWith('References/stubs/precondition.stub', $this->subject->stubPath->toString());
        $this->assertFileExists($this->subject->stubPath->toString());
    }
}
