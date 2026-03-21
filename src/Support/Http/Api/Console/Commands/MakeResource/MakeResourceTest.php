<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\References\Schema;
use Tests\TestCase;
use Tooling\GeneratorCommands\Testing\Concerns\CleansUpGeneratorCommands;

#[CoversClass(MakeResource::class)]
final class MakeResourceTest extends TestCase
{
    use CleansUpGeneratorCommands;

    private string $entityFqcn {
        get => 'Workbench\\App\\Entities\\Bananas\\Banana';
    }

    public Schema $reference {
        get => new Schema(name: 'Banana', baseNamespace: 'Workbench\\App\\Http\\Api\\V1\\Schemas\\Bananas');
    }

    /** @var array<string, mixed> */
    private array $baselineInput {
        get => [
            '--api-version' => 'V1',
            '--entity' => $this->entityFqcn,
        ];
    }

    /** @var array<array-key, string> */
    protected array $files {
        get => [
            $this->reference->directory->append('/*')->toString(),
        ];
    }

    #[Test]
    public function it_generates_a_schema(): void
    {
        $this->artisan(MakeResource::class, $this->baselineInput)
            ->assertSuccessful();

        $this->assertFileExists($this->reference->filePath->toString());

        $this->assertStringContainsString(
            'namespace '.$this->reference->namespace->after('\\').';',
            file_get_contents($this->reference->filePath->toString()),
        );
    }

    #[Test]
    public function it_passes_force_option_to_upstream(): void
    {
        $this->artisan(MakeResource::class, $this->baselineInput)
            ->assertSuccessful();

        $this->artisan(MakeResource::class, [
            ...$this->baselineInput,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertFileExists($this->reference->filePath->toString());
    }
}
