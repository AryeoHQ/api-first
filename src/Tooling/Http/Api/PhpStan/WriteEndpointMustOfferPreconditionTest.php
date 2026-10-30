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

    private const string UNGUARDED = 'A controller that takes a Precondition must call guard() on it in __invoke().';

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
    #[TestWith(['AnyMethodOnCollectionController'])]
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

    #[Test]
    #[TestWith(['OptionalPreconditionController'])]
    #[TestWith(['NullablePreconditionController'])]
    public function it_fails_when_the_precondition_parameter_is_not_required(string $fixture): void
    {
        $this->analyse([$this->getFixturePath("Http/Api/PhpStan/Preconditions/{$fixture}.php")], [
            [self::MESSAGE, 17],
        ]);
    }

    #[Test]
    public function it_fails_when_a_bound_endpoint_accepts_any_method_and_takes_no_precondition(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/AnyMethodController.php')], [
            [self::MESSAGE, 17],
        ]);
    }

    #[Test]
    public function it_fails_when_the_controller_never_guards_its_precondition(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/UnguardedPreconditionController.php')], [
            [self::UNGUARDED, 17],
        ]);
    }

    #[Test]
    public function it_fails_for_each_precondition_the_controller_never_guards(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/GuardedOtherPreconditionController.php')], [
            [self::UNGUARDED, 17],
        ]);
    }

    #[Test]
    public function it_reports_each_unguarded_precondition_on_its_own_line(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/Preconditions/UnguardedPreconditionsController.php')], [
            [self::UNGUARDED, 19],
            [self::UNGUARDED, 20],
        ]);
    }

    #[Test]
    #[TestWith(['BeforeAuthorizerController', 10])]
    #[TestWith(['WithoutAuthorizerController', 9])]
    public function it_fails_when_an_invokable_without_a_route_never_guards_its_precondition(string $fixture, int $line): void
    {
        $this->analyse([$this->getFixturePath("Http/Api/PhpStan/Preconditions/{$fixture}.php")], [
            [self::UNGUARDED, $line],
        ]);
    }
}
