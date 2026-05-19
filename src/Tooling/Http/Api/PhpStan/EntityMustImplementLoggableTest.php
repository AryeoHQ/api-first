<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<EntityMustImplementLoggable> */
#[CoversClass(EntityMustImplementLoggable::class)]
class EntityMustImplementLoggableTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new EntityMustImplementLoggable;
    }

    #[Test]
    public function it_passes_when_entity_implements_loggable(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ValidEntity.php')], []);
    }

    #[Test]
    public function it_fails_when_entity_does_not_implement_loggable(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/EntityWithoutLoggable.php')], [
            [
                'Entity models must implement the Loggable contract.',
                8,
            ],
        ]);
    }
}
