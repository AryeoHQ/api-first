<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<PreconditionMustSitBetweenAuthorizerAndValidator> */
#[CoversClass(PreconditionMustSitBetweenAuthorizerAndValidator::class)]
final class PreconditionMustSitBetweenAuthorizerAndValidatorTest extends RuleTestCase
{
    use GetsFixtures;

    private const string MESSAGE = 'Precondition parameters must be declared after authorizer parameters and before validator parameters.';

    protected function getRule(): Rule
    {
        return new PreconditionMustSitBetweenAuthorizerAndValidator;
    }

    #[Test]
    public function it_passes_when_the_precondition_sits_between_the_authorizer_and_the_validator(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/CorrectController.php')], []);
    }

    #[Test]
    public function it_passes_when_the_precondition_precedes_the_validator_without_an_authorizer(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/WithoutAuthorizerController.php')], []);
    }

    #[Test]
    public function it_fails_when_the_precondition_precedes_an_authorizer(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/BeforeAuthorizerController.php')], [
            [self::MESSAGE, 10],
        ]);
    }

    #[Test]
    public function it_fails_when_the_precondition_precedes_any_authorizer(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/BetweenAuthorizersController.php')], [
            [self::MESSAGE, 11],
        ]);
    }

    #[Test]
    public function it_fails_when_a_nullable_precondition_follows_a_validator(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/AfterValidatorController.php')], [
            [self::MESSAGE, 12],
        ]);
    }

    #[Test]
    public function it_ignores_methods_other_than_invoke(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/NonInvokableController.php')], []);
    }
}
