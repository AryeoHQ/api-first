<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(UnknownParameter::class)]
final class UnknownParameterTest extends TestCase
{
    #[Test]
    public function it_records_a_failure_whose_rule_name_derives_the_unknown_parameter_code(): void
    {
        $validator = Validator::make(['paging' => ['offset' => '20']], ['paging.offset' => [new UnknownParameter]]);

        $validator->fails();

        $this->assertSame(['unknown_parameter'], array_map(fn (string $rule): string => Str::snake(class_basename($rule)), array_keys($validator->failed()['paging.offset'])));
    }
}
