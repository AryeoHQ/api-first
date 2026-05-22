<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeModel;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Contracts\Entity;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Models\Concerns\LogsSchemas;
use Support\Entities\Models\References\Model;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Loggable;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Http\Resources\Schemas\Contracts\Schemable;
use Tests\Fixtures\Support\Entities\Posts\Post;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeModel::class)]
final class MakeModelTest extends TestCase
{
    use GeneratesFileTestCases;

    private Model $entity {
        get => Model::fromFqcn(Post::class);
    }

    public Reference $reference {
        get => $this->entity;
    }

    /** @var array<string, mixed> */
    public array $baselineInput {
        get => [
            'name' => 'Post',
            '--namespace' => 'Tests\\Fixtures\\Support',
            '--factory' => false,
            '--policy' => false,
            '--builder' => false,
            '--collection' => false,
            '--events' => false,
            '--provider' => false,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Composer::fake(['autoload' => ['psr-4' => ['Tests\\Fixtures\\Support\\' => 'tests/Fixtures/Support/']]]);
    }

    #[Test]
    public function it_injects_loggable_imports(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('use '.LogsSchemas::class.';', $contents);
            $this->assertStringContainsString('use '.Loggable::class.';', $contents);
            $this->assertStringContainsString('use '.Schemable::class.';', $contents);
        });
    }

    #[Test]
    public function it_injects_loggable_interfaces(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('implements '.class_basename(Entity::class).', '.class_basename(Schemable::class).', '.class_basename(Loggable::class), $contents);
        });
    }

    #[Test]
    public function it_injects_logs_schemas_trait(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('use '.class_basename(LogsSchemas::class).';', $contents);
        });
    }

    #[Test]
    public function it_regenerates_events_with_recordable(): void
    {
        $this->artisan($this->command, [
            'name' => 'Post',
            '--namespace' => 'Tests\\Fixtures\\Support',
            '--factory' => false,
            '--policy' => false,
            '--builder' => false,
            '--collection' => false,
            '--events' => true,
            '--provider' => false,
        ])->assertSuccessful();

        $creatingEventPath = $this->entity->event('Creating')->filePath->toString();
        $createdEventPath = $this->entity->event('Created')->filePath->toString();

        $this->assertTrue(File::exists($creatingEventPath));
        $this->assertTrue(File::exists($createdEventPath));

        tap(File::get($creatingEventPath), function (string $contents) {
            $this->assertStringContainsString('implements '.class_basename(ForEntity::class).', '.class_basename(Recordable::class), $contents);
            $this->assertStringContainsString('#['.class_basename(Alias::class)."('post.creating')]", $contents);
        });

        tap(File::get($createdEventPath), function (string $contents) {
            $this->assertStringContainsString('implements '.class_basename(ForEntity::class).', '.class_basename(RecordableAfterCommit::class), $contents);
            $this->assertStringContainsString('#['.class_basename(Alias::class)."('post.created')]", $contents);
        });
    }
}
