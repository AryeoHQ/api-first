<?php

declare(strict_types=1);

namespace Tooling\Http\Api\PhpStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Entities\Models\Concerns\LogsSchemas;
use Support\Events\Log\Contracts\Loggable;
use Tests\Tooling\Concerns\GetsFixtures;

/** @extends RuleTestCase<LoggableMustUseLogsSchemas> */
#[CoversClass(LoggableMustUseLogsSchemas::class)]
class LoggableMustUseLogsSchemasTest extends RuleTestCase
{
    use GetsFixtures;

    protected function getRule(): Rule
    {
        return new LoggableMustUseLogsSchemas;
    }

    #[Test]
    public function it_passes_when_loggable_uses_logs_schemas(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/ValidEntity.php')], []);
    }

    #[Test]
    public function it_fails_when_loggable_does_not_use_logs_schemas(): void
    {
        $this->analyse([$this->getFixturePath('Http/Api/PhpStan/EntityWithoutLogsSchemas.php')], [
            [
                '['.class_basename(Loggable::class).'] must use the ['.class_basename(LogsSchemas::class).'] trait.',
                9,
            ],
        ]);
    }
}
