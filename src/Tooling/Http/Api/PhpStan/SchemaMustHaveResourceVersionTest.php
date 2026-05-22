<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<SchemaMustHaveResourceVersion> */
#[CoversClass(SchemaMustHaveResourceVersion::class)]
class SchemaMustHaveResourceVersionTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new SchemaMustHaveResourceVersion(ApiVersion::class);
    }

    #[Test]
    public function it_passes_when_schema_has_typed_resource_version(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Schema.php')], []);
    }

    #[Test]
    public function it_fails_when_schema_is_missing_resource_version(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/EmptySchema.php')], [
            [
                '['.class_basename(Schema::class).'] must define a public $resourceVersion property typed as ['.class_basename(ApiVersion::class).'].',
                9,
            ],
        ]);
    }

    #[Test]
    public function it_fails_when_resource_version_has_wrong_type(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/InvalidSchemaResourceVersionType.php')], [
            [
                '['.class_basename(Schema::class).'] must define a public $resourceVersion property typed as ['.class_basename(ApiVersion::class).'].',
                9,
            ],
        ]);
    }
}
