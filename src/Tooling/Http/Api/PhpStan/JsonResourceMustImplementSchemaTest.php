<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<JsonResourceMustImplementSchema> */
#[CoversClass(JsonResourceMustImplementSchema::class)]
class JsonResourceMustImplementSchemaTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new JsonResourceMustImplementSchema;
    }

    #[Test]
    public function it_passes_when_resource_implements_schema(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Schema.php')], []);
    }

    #[Test]
    public function it_fails_when_resource_does_not_implement_schema(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/JsonResourceWithoutSchema.php')], [
            [
                'JSON resources must implement the Schema contract.',
                7,
            ],
        ]);
    }
}
