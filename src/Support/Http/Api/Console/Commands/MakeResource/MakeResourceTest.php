<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Api\Console\Commands\MakeResource\Listeners\InjectSchemaProperties;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\References\Schema;
use Tests\TestCase;
use Tooling\GeneratorCommands\Testing\Concerns\CleansUpGeneratorCommands;

#[CoversClass(MakeResource::class)]
#[CoversClass(InjectSchemaProperties::class)]
final class MakeResourceTest extends TestCase
{
    use CleansUpGeneratorCommands;

    private string $entityFqcn {
        get => 'Workbench\\App\\Entities\\Bananas\\Banana';
    }

    public Schema $reference {
        get => new Schema(name: 'Banana', baseNamespace: 'Workbench\\App\\Http\\Api\\V1\\Bananas');
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

        $contents = file_get_contents($this->reference->filePath->toString());

        $this->assertStringContainsString(
            'namespace '.$this->reference->namespace->after('\\').';',
            $contents,
        );
    }

    #[Test]
    public function it_injects_id_property(): void
    {
        $this->artisan(MakeResource::class, $this->baselineInput)
            ->assertSuccessful();

        $contents = file_get_contents($this->reference->filePath->toString());

        $this->assertStringContainsString(
            'public string $id { get => $this->resource->getKey(); }',
            $contents,
        );

        $this->assertStringContainsString(
            "public string \$resourceType = 'banana';",
            $contents,
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

    #[Test]
    public function it_prompts_for_api_version_and_entity_when_options_are_not_provided(): void
    {
        $this->artisan(MakeResource::class, ['--force' => true])
            ->expectsChoice(
                'What is the API version?',
                'V1',
                ['V1', MakeResource::NEW_API_VERSION_OPTION],
            )
            ->expectsSearch(
                'Which entity?',
                $this->entityFqcn,
                'Banana',
                [$this->entityFqcn],
            )
            ->assertSuccessful();

        $this->assertFileExists($this->reference->filePath->toString());
    }
}
