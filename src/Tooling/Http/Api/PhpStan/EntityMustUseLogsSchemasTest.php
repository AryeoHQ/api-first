<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<EntityMustUseLogsSchemas> */
#[CoversClass(EntityMustUseLogsSchemas::class)]
class EntityMustUseLogsSchemasTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new EntityMustUseLogsSchemas;
    }

    #[Test]
    public function it_passes_when_entity_uses_logs_schemas(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ValidEntity.php')], []);
    }

    #[Test]
    public function it_fails_when_entity_does_not_use_logs_schemas(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/EntityWithoutLogsSchemas.php')], [
            [
                'Entity models must use the LogsSchemas trait.',
                9,
            ],
        ]);
    }
}
