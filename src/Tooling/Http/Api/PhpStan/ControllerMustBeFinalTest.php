<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<ControllerMustBeFinal> */
#[CoversClass(ControllerMustBeFinal::class)]
class ControllerMustBeFinalTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ControllerMustBeFinal;
    }

    #[Test]
    public function it_passes_when_controller_is_final(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Controller.php')], []);
    }

    #[Test]
    public function it_fails_when_controller_is_not_final(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ControllerNotFinal.php')], [
            [
                'Controllers must be final.',
                8,
            ],
        ]);
    }
}
