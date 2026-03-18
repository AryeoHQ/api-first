<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<RouteAttributeOnlyOnInvoke> */
#[CoversClass(RouteAttributeOnlyOnInvoke::class)]
final class RouteAttributeOnlyOnInvokeTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new RouteAttributeOnlyOnInvoke;
    }

    #[Test]
    public function it_passes_when_route_is_only_on_invoke(): void
    {
        $this->analyse(
            [$this->getFixturePath('Http/Api/PhpStan/Controller.php')],
            [],
        );
    }

    #[Test]
    public function it_fails_when_route_is_on_other_method(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ControllerWithRouteOnOtherMethod.php')], [
            [
                'The #[Route] attribute must only be applied to __invoke(), not index().',
                17,
            ],
        ]);
    }
}
