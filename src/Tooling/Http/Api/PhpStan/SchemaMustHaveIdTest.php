<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<SchemaMustHaveId> */
#[CoversClass(SchemaMustHaveId::class)]
class SchemaMustHaveIdTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new SchemaMustHaveId;
    }

    #[Test]
    public function it_passes_when_schema_has_id(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/SchemaWithRequiredProperties.php')], []);
    }

    #[Test]
    public function it_fails_when_schema_is_missing_id(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/SchemaWithoutRequiredProperties.php')], [
            [
                'Schema must define a public $id property.',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_fails_when_schema_has_only_resource_type(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/SchemaWithOnlyResourceType.php')], [
            [
                'Schema must define a public $id property.',
                9,
            ],
        ]);
    }
}
