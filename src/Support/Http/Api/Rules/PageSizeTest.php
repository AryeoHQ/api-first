<?php

declare(strict_types=1);

namespace Support\Http\Api\Rules;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[CoversClass(PageSize::class)]
final class PageSizeTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function sizes(): array
    {
        return [
            'the minimum' => ['1', true],
            'the maximum' => ['100', true],
            'an integer' => [10, true],
            'zero' => ['0', false],
            'above the maximum' => ['101', false],
            'a fraction' => ['1.5', false],
            'a negative integer' => ['-1', false],
            'a boolean' => [true, false],
            'a word' => ['abc', false],
            'a list' => [['10'], false],
        ];
    }

    #[DataProvider('sizes')]
    #[Test]
    public function it_accepts_an_integer_from_1_to_100(mixed $size, bool $passes): void
    {
        $this->assertSame($passes, Validator::make(['paging' => ['size' => $size]], ['paging.size' => [new PageSize]])->passes());
    }

    #[Test]
    public function it_records_a_failure_whose_rule_name_derives_the_page_size_code(): void
    {
        $validator = Validator::make(['paging' => ['size' => '101']], ['paging.size' => [new PageSize]]);

        $validator->fails();

        $this->assertSame(['page_size'], array_map(fn (string $rule): string => Str::snake(class_basename($rule)), array_keys($validator->failed()['paging.size'])));
    }
}
