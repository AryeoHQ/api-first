<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<FormRequestMustBeFinal> */
#[CoversClass(FormRequestMustBeFinal::class)]
class FormRequestMustBeFinalTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new FormRequestMustBeFinal;
    }

    #[Test]
    public function it_passes_when_form_request_is_final(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Request.php')], []);
    }

    #[Test]
    public function it_fails_when_form_request_is_not_final(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/RequestNotFinal.php')], [
            [
                'Form requests must be final.',
                8,
            ],
        ]);
    }
}
