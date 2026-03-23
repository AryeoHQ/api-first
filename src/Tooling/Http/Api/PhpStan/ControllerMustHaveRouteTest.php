<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<ControllerMustHaveRoute> */
#[CoversClass(ControllerMustHaveRoute::class)]
class ControllerMustHaveRouteTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ControllerMustHaveRoute;
    }

    #[Test]
    public function it_passes_when_controller_has_route_attribute(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Controller.php')], []);
    }

    #[Test]
    public function it_fails_when_controller_is_missing_route_attribute(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ControllerWithoutRouteAttribute.php')], [
            [
                'Controllers define their endpoints with the Route attribute.',
                5,
            ],
        ]);
    }
}
