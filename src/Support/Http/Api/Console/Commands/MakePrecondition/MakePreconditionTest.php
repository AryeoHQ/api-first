<?php

declare(strict_types=1);

namespace Support\Http\Api\Console\Commands\MakePrecondition;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\Attributes\WithConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Support\Database\Eloquent\Concurrency\Contracts\Revisioned;
use Support\Entities\References\Entity;
use Support\Http\Api\References\Precondition;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions\Entities\Gadgets\Gadget;
use Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions\Entities\MarketingKitTemplates\MarketingKitTemplate;
use Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions\Entities\Widgets\Widget;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\GeneratorCommands\References\Contracts\Reference;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;
use Tooling\GeneratorCommands\Testing\Concerns\RetrievesNamespaceTestCases;

#[CoversClass(MakePrecondition::class)]
#[WithConfig('api-resource-schema.version', ApiVersion::class)]
final class MakePreconditionTest extends TestCase
{
    use GeneratesFileTestCases;
    use RetrievesNamespaceTestCases;

    private Entity $entity {
        get => Entity::fromFqcn(Widget::class);
    }

    public Precondition $reference {
        get => new Precondition(name: 'Precondition', baseNamespace: 'App\\Http\\Api\\V1\\Widgets\\Update');
    }

    private Reference $nestedReference {
        get => new Precondition(name: 'Precondition', baseNamespace: 'App\\Http\\Api\\V1\\Widgets\\Update\\Nested');
    }

    /** @var array<string, mixed> */
    public array $baselineInput {
        get => $this->input('App\\Http\\Api\\V1\\Widgets\\Update\\');
    }

    /** @var array<string, mixed> */
    public array $withNamespaceBackslashInput {
        get => $this->baselineInput;
    }

    /** @var array<string, mixed> */
    public array $withoutNamespaceBackslashInput {
        get => $this->input('App\\Http\\Api\\V1\\Widgets\\Update');
    }

    /** @var array<string, mixed> */
    public array $withNestedNamespaceInput {
        get => $this->input('App\\Http\\Api\\V1\\Widgets\\Update\\Nested');
    }

    protected string $expectedNestedFilePath {
        get => $this->nestedReference->filePath->toString();
    }

    #[Test]
    public function it_generates_a_precondition_that_targets_the_route_bound_entity(): void
    {
        Composer::fake();

        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        $this->assertSame(<<<'PHP'
            <?php

            declare(strict_types=1);

            namespace App\Http\Api\V1\Widgets\Update;

            use Support\Http\Api\Preconditions\Contracts\Target;
            use Support\Http\Api\Preconditions\Precondition as BasePrecondition;
            use Support\Http\Api\Preconditions\Targets\EntityTarget;
            use Tests\Fixtures\Support\Schemas\ApiVersion;
            use Tests\Fixtures\Tooling\Http\Api\PhpStan\Preconditions\Entities\Widgets\Widget;

            final class Precondition extends BasePrecondition
            {
                public function target(): Target
                {
                    /** @var Widget $widget */
                    $widget = $this->route('widget');

                    return EntityTarget::for($widget, ApiVersion::V1)->comparingSelf();
                }
            }

            PHP, File::get($this->expectedFilePath));
    }

    #[Test]
    public function it_names_the_route_parameter_of_a_multi_word_entity(): void
    {
        Composer::fake();

        $this->artisan($this->command, [
            ...$this->baselineInput,
            '--entity' => MarketingKitTemplate::class,
        ])->assertSuccessful();

        tap(File::get($this->expectedFilePath), function (string $contents): void {
            $this->assertStringContainsString('use '.MarketingKitTemplate::class.';', $contents);
            $this->assertStringContainsString('/** @var MarketingKitTemplate $marketingKitTemplate */', $contents);
            $this->assertStringContainsString("\$marketingKitTemplate = \$this->route('marketing_kit_template');", $contents);
            $this->assertStringContainsString('return EntityTarget::for($marketingKitTemplate, ApiVersion::V1)->comparingSelf();', $contents);
        });
    }

    #[Test]
    public function it_prompts_for_the_entity_when_the_option_is_missing(): void
    {
        Composer::fake();

        $input = $this->baselineInput;
        unset($input['--entity']);

        $this->artisan($this->command, $input)
            ->expectsQuestion('Which entity? (FQCN)', $this->entity->fqcn->ltrim('\\')->toString())
            ->assertSuccessful();

        $this->assertStringContainsString('use '.Widget::class.';', File::get($this->expectedFilePath));
    }

    #[Test]
    #[TestWith([Gadget::class])]
    #[TestWith(['App\\Entities\\Orders\\Order'])]
    public function it_refuses_an_entity_that_is_not_revisioned(string $entity): void
    {
        Composer::fake();

        $this->artisan($this->command, [...$this->baselineInput, '--entity' => $entity])
            ->expectsOutputToContain(Revisioned::class)
            ->assertFailed();

        $this->assertFalse(File::exists($this->expectedFilePath));
        $this->assertFalse(File::exists($this->reference->test->filePath->toString()));
    }

    #[Test]
    public function it_prompts_for_the_api_version_when_the_option_is_missing(): void
    {
        Composer::fake();

        $input = $this->baselineInput;
        unset($input['--api-version']);

        $this->artisan($this->command, $input)
            ->expectsChoice('Select a version.', 'v1', ['v1' => 'v1'])
            ->assertSuccessful();

        $this->assertStringContainsString('ApiVersion::V1', File::get($this->expectedFilePath));
    }

    /**
     * @return array<string, mixed>
     */
    private function input(string $namespace): array
    {
        return [
            'name' => 'Precondition',
            '--namespace' => $namespace,
            '--entity' => $this->entity->fqcn->toString(),
            '--api-version' => 'V1',
        ];
    }
}
