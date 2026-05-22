<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Contracts\Loggable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<ModelMustImplementLoggable> */
#[CoversClass(ModelMustImplementLoggable::class)]
class ModelMustImplementLoggableTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new ModelMustImplementLoggable;
    }

    #[Test]
    public function it_passes_when_model_implements_loggable(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ValidEntity.php')], []);
    }

    #[Test]
    public function it_fails_when_model_does_not_implement_loggable(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/EntityWithoutLoggable.php')], [
            [
                '['.class_basename(Model::class).'] must implement the ['.class_basename(Loggable::class).'] contract.',
                8,
            ],
        ]);
    }
}
