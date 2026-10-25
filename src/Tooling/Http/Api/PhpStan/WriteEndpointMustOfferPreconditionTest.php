<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<WriteEndpointMustOfferPrecondition> */
#[CoversClass(WriteEndpointMustOfferPrecondition::class)]
final class WriteEndpointMustOfferPreconditionTest extends RuleTestCase
{
    use GetsFixtures;

    private const string MESSAGE = 'A PUT, PATCH, or DELETE endpoint, or a POST action on a bound model, must take a Precondition parameter.';

    protected function getRule(): Rule
    {
        return new WriteEndpointMustOfferPrecondition;
    }

    #[Test]
    public function it_passes_when_a_write_endpoint_takes_a_precondition(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/CorrectController.php')], []);
    }

    #[Test]
    #[TestWith(['CreateController'])]
    #[TestWith(['ResourceActionController'])]
    #[TestWith(['ShowController'])]
    #[TestWith(['NonInvokableController'])]
    public function it_passes_when_the_endpoint_does_not_write_to_a_bound_model(string $fixture): void
    {
        $this->analyse([$this->getFixturePath("Http/Api/PhpStan/Preconditions/{$fixture}.php")], []);
    }

    #[Test]
    public function it_fails_when_a_patch_endpoint_takes_no_precondition(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/MissingPreconditionController.php')], [
            [self::MESSAGE, 17],
        ]);
    }

    #[Test]
    public function it_fails_when_any_listed_method_writes_and_no_precondition_is_taken(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/MissingPreconditionOnMethodsController.php')], [
            [self::MESSAGE, 17],
        ]);
    }

    #[Test]
    public function it_fails_when_an_instance_action_takes_no_precondition(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/MissingPreconditionOnActionController.php')], [
            [self::MESSAGE, 13],
        ]);
    }
}
