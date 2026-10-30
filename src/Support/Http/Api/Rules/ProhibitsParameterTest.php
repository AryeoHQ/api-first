<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(ProhibitsParameter::class)]
final class ProhibitsParameterTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function pagings(): array
    {
        return [
            'before alone' => [['before' => 'x'], true],
            'before with an empty after' => [['before' => 'x', 'after' => ''], true],
            'before with a null after' => [['before' => 'x', 'after' => null], true],
            'before with after' => [['before' => 'x', 'after' => 'y'], false],
        ];
    }

    /**
     * @param  array<string, mixed>  $paging
     */
    #[DataProvider('pagings')]
    #[Test]
    public function it_fails_when_the_prohibited_parameter_is_also_present(array $paging, bool $passes): void
    {
        $this->assertSame($passes, Validator::make(['paging' => $paging], ['paging.before' => [new ProhibitsParameter('paging.after')]])->passes());
    }

    #[Test]
    public function it_names_the_prohibited_parameter_as_a_query_parameter(): void
    {
        $validator = Validator::make(['paging' => ['before' => 'x', 'after' => 'y']], ['paging.before' => [new ProhibitsParameter('paging.after')]]);

        $this->assertSame(['The paging.before cannot be sent with paging[after].'], $validator->errors()->get('paging.before'));
    }

    #[Test]
    public function it_records_a_failure_whose_rule_name_derives_the_prohibits_parameter_code(): void
    {
        $validator = Validator::make(['paging' => ['before' => 'x', 'after' => 'y']], ['paging.before' => [new ProhibitsParameter('paging.after')]]);

        $validator->fails();

        $this->assertSame(['prohibits_parameter'], array_map(fn (string $rule): string => Str::snake(class_basename($rule)), array_keys($validator->failed()['paging.before'])));
    }
}
