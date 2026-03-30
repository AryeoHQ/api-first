<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeResource;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\References\Entity;
use Support\Http\Api\Console\Commands\MakeResource\Listeners\InjectSchemaProperties;
use Support\Http\Resources\Schemas\Console\Commands\MakeResource\References\Schema;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\Entities\Composer\ClassMap\Collectors\Entities as EntitiesCollector;
use Tooling\Http\Api\Composer\ClassMap\Collectors\ApiVersions;

#[CoversClass(MakeResource::class)]
#[CoversClass(InjectSchemaProperties::class)]
final class MakeResourceTest extends TestCase
{
    public Entity $entity {
        get => new Entity(name: class_basename(self::class), baseNamespace: 'App\\');
    }

    public Schema $reference {
        get => new Schema(name: $this->entity->name, baseNamespace: 'App\\Http\\Api\\V1\\'.$this->entity->plural);
    }

    /** @var array<string, mixed> */
    private array $baselineInput {
        get => [
            '--api-version' => 'V1',
            '--entity' => $this->entity->fqcn->toString(),
        ];
    }

    #[Test]
    public function it_generates_a_schema(): void
    {
        Composer::fake();

        $this->artisan(MakeResource::class, $this->baselineInput)->assertSuccessful();

        $this->assertTrue(File::exists($this->reference->filePath->toString()));

        tap(File::get($this->reference->filePath->toString()), function (string $contents) {
            $this->assertStringContainsString(
                'namespace '.$this->reference->namespace->after('\\').';',
                $contents,
            );
        });
    }

    #[Test]
    public function it_injects_id_property(): void
    {
        Composer::fake();

        $this->artisan(MakeResource::class, $this->baselineInput)
            ->assertSuccessful();

        tap(File::get($this->reference->filePath->toString()), function (string $contents) {
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
        Composer::fake();

        $this->artisan(MakeResource::class, $this->baselineInput)
            ->assertSuccessful();

        $this->artisan(MakeResource::class, [
            ...$this->baselineInput,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertTrue(File::exists($this->reference->filePath->toString()));
    }

    #[Test]
    public function it_prompts_for_entity_when_option_is_not_provided(): void
    {
        Composer::fake();
        EntitiesCollector::fake([$this->entity->fqcn->ltrim('\\')->toString()]);

        $this->artisan(MakeResource::class, ['--api-version' => 'V1'])
            ->expectsSearch(
                'Which entity?',
                $this->entity->fqcn->ltrim('\\')->toString(),
                $this->entity->name->toString(),
                [$this->entity->fqcn->ltrim('\\')->toString()],
            )
            ->assertSuccessful();

        $this->assertTrue(File::exists($this->reference->filePath->toString()));
    }

    #[Test]
    public function it_prompts_for_api_version_when_option_is_not_provided(): void
    {
        Composer::fake();
        ApiVersions::fake(['App\Http\Api\V1']);

        $this->artisan(MakeResource::class, ['--entity' => $this->entity->fqcn->toString(), '--force' => true])
            ->expectsChoice(
                'What is the API version?',
                'V1',
                ['V1', MakeResource::CREATE_NEW_VERSION],
            )
            ->assertSuccessful();

        $this->assertTrue(File::exists($this->reference->filePath->toString()));
    }
}
