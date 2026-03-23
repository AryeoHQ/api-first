<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<ControllerMustHaveSingleCastableData> */
#[CoversClass(ControllerMustHaveSingleCastableData::class)]
final class ControllerMustHaveSingleCastableDataTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ControllerMustHaveSingleCastableData;
    }

    #[Test]
    public function it_passes_when_controller_has_no_castable_data_parameter(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Controller.php')], []);
    }

    #[Test]
    public function it_passes_when_controller_has_one_castable_data_parameter(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/SingleCastableData/Controller.php')], []);
    }

    #[Test]
    public function it_fails_when_controller_has_two_castable_data_parameters(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/TwoCastableData/Controller.php')], [
            [
                'Controllers must not have more than one CastableData parameter.',
                10,
            ],
        ]);
    }
}
