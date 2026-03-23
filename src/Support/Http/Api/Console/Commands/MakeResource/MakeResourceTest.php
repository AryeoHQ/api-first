<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Console\Commands\MakeEntity;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeResource\Listeners\InjectSchemaProperties;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\References\Schema;
use Tests\TestCase;
use Tooling\GeneratorCommands\Testing\Concerns\CleansUpGeneratorCommands;

#[CoversClass(MakeResource::class)]
#[CoversClass(InjectSchemaProperties::class)]
final class MakeResourceTest extends TestCase
{
    use CleansUpGeneratorCommands;

    public Entity $entity {
        get => new Entity(name: class_basename(self::class), baseNamespace: 'Workbench\\App\\');
    }

    public Schema $reference {
        get => new Schema(name: $this->entity->name, baseNamespace: 'Workbench\\App\\Http\\Api\\V1\\'.$this->entity->plural);
    }

    /** @var array<string, mixed> */
    private array $baselineInput {
        get => [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
        ];
    }

    /** @var array<array-key, string> */
    protected array $files {
        get => [
            $this->reference->directory->append('/*')->toString(),
            $this->entity->directory->append('/*')->toString(),
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan(MakeEntity::class, [
            'name' => class_basename(self::class),
            '--namespace' => 'Workbench\\App',
            '--no-model' => true,
            '--no-provider' => true,
            '--no-policy' => true,
            '--force' => true,
        ])->assertSuccessful();
    }

    #[Test]
    public function it_generates_a_schema(): void
    {
        $this->artisan(MakeResource::class, $this->baselineInput)->assertSuccessful();

        $this->assertFileExists($this->reference->filePath->toString());

        tap(file_get_contents($this->reference->filePath->toString()), function (string $contents) {
            $this->assertStringContainsString(
                'namespace '.$this->reference->namespace->after('\\').';',
                $contents,
            );
        });
    }

    #[Test]
    public function it_injects_id_property(): void
    {
        $this->artisan(MakeResource::class, $this->baselineInput)
            ->assertSuccessful();

        tap(file_get_contents($this->reference->filePath->toString()), function (string $contents) {
            $this->assertStringContainsString(
                'public string $id { get => $this->resource->getKey(); }',
                $contents,
            );

            $this->assertStringContainsString(
                "public string \$resourceType = '".$this->entity->name->snake()."';",
                $contents,
            );
        });
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
        $this->artisan(MakeResource::class, [...$this->baselineInput, '--force' => true])->assertSuccessful();

        $this->artisan(MakeResource::class, ['--force' => true])->expectsChoice(
            'What is the API version?',
            'V1',
            ['V1', MakeResource::CREATE_NEW_VERSION],
        )->expectsSearch(
            'Which entity?',
            $this->entity->fqcn->ltrim('\\')->toString(),
            $this->entity->name->toString(),
            [$this->entity->fqcn->ltrim('\\')->toString()],
        )->assertSuccessful();

        $this->assertFileExists($this->reference->filePath->toString());
    }
}
