<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<SchemaMustHaveResourceType> */
#[CoversClass(SchemaMustHaveResourceType::class)]
class SchemaMustHaveResourceTypeTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new SchemaMustHaveResourceType;
    }

    #[Test]
    public function it_passes_when_schema_has_resource_type(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/SchemaWithRequiredProperties.php')], []);
    }

    #[Test]
    public function it_fails_when_schema_is_missing_resource_type(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/SchemaWithoutRequiredProperties.php')], [
            [
                'Schema must define a public $resourceType property.',
                9,
            ],
        ]);
    }
}
