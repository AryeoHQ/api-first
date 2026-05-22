<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakeEvent;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Events\Contracts\ForEntity;
use Support\Entities\Models\References\Model;
use Support\Events\Log\Alias\Alias;
use Support\Events\Log\Contracts\Recordable;
use Support\Events\Log\Contracts\RecordableAfterCommit;
use Support\Events\Log\IdentifiesLoggable\IdentifiesLoggable;
use Support\Events\Log\Provides\HasLoggable;
use Tests\Fixtures\Support\Entities\Posts\Post;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeEvent::class)]
final class MakeEventTest extends TestCase
{
    use GeneratesFileTestCases;

    private Model $entity {
        get => Model::fromFqcn(Post::class);
    }

    public Reference $reference {
        get => $this->entity->event('Creating');
    }

    /** @var array<string, mixed> */
    public array $baselineInput {
        get => ['name' => 'Creating', '--entity' => Post::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Composer::fake(['autoload' => ['psr-4' => ['Tests\\Fixtures\\Support\\' => 'tests/Fixtures/Support/']]]);
    }

    #[Test]
    public function it_injects_recordable_contract(): void
    {
        $this->artisan($this->command, [
            ...$this->baselineInput,
            '--recordable' => true,
        ])->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('use '.Recordable::class.';', $contents);
            $this->assertStringContainsString('implements '.class_basename(ForEntity::class).', '.class_basename(Recordable::class), $contents);
            $this->assertStringContainsString('use '.class_basename(HasLoggable::class).';', $contents);
            $this->assertStringContainsString('use '.HasLoggable::class.";\n", $contents);
            $this->assertStringContainsString('#['.class_basename(Alias::class)."('post.creating')]", $contents);
            $this->assertStringContainsString('use '.Alias::class.";\n", $contents);
            $this->assertStringContainsString('#['.class_basename(IdentifiesLoggable::class)."]\n", $contents);
            $this->assertStringContainsString('use '.IdentifiesLoggable::class.";\n", $contents);
        });
    }

    #[Test]
    public function it_injects_recordable_after_commit_contract(): void
    {
        $this->artisan($this->command, [
            ...$this->baselineInput,
            '--recordable-after-commit' => true,
        ])->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringContainsString('use '.RecordableAfterCommit::class.';', $contents);
            $this->assertStringContainsString('implements '.class_basename(ForEntity::class).', '.class_basename(RecordableAfterCommit::class), $contents);
            $this->assertStringContainsString('use '.class_basename(HasLoggable::class).';', $contents);
            $this->assertStringContainsString('#['.class_basename(Alias::class)."('post.creating')]", $contents);
        });
    }

    #[Test]
    public function it_does_not_inject_recordable_by_default(): void
    {
        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents) {
            $this->assertStringNotContainsString(class_basename(Recordable::class), $contents);
            $this->assertStringNotContainsString(class_basename(HasLoggable::class), $contents);
            $this->assertStringNotContainsString('#['.class_basename(Alias::class), $contents);
        });
    }
}
