<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeCollection;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Models\References\Model;
use Support\Http\Resources\Schemas\Concerns\TransformsToSchemaCollection;
use Support\Http\Resources\Schemas\Contracts\SchemableCollection;
use Tests\Fixtures\Support\Entities\Posts\Post;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeCollection::class)]
final class MakeCollectionTest extends TestCase
{
    use GeneratesFileTestCases;

    private Model $entity {
        get => Model::fromFqcn(Post::class);
    }

    public Reference $reference {
        get => $this->entity->collection;
    }

    /** @var array<string, mixed> */
    public array $baselineInput {
        get => ['entity' => Post::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Composer::fake(['autoload' => ['psr-4' => ['Tests\\Fixtures\\Support\\' => 'tests/Fixtures/Support/']]]);
    }

    #[Test]
    public function it_injects_schemable_collection_interface(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('implements '.class_basename(SchemableCollection::class), $contents);
            $this->assertStringContainsString('use '.SchemableCollection::class.';', $contents);
        });
    }

    #[Test]
    public function it_injects_transforms_to_schema_collection_trait(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('use '.class_basename(TransformsToSchemaCollection::class).';', $contents);
            $this->assertStringContainsString('use '.TransformsToSchemaCollection::class.';', $contents);
        });
    }
}
