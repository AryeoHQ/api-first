<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

final readonly class ValidationErrors
{
    private const array SIZE_RULES = ['Size', 'Between', 'Min', 'Max', 'Gt', 'Lt', 'Gte', 'Lte'];

    public function __construct(
        private ValidatorContract $validator,
        private Request $request,
    ) {}

    /**
     * @return list<array{code: null|string, detail: string, source: Source, rule: null|string}>
     */
    public function toFailures(): array
    {
        $failed = $this->validator->failed();
        $failures = [];

        foreach ($this->validator->errors()->messages() as $attribute => $details) {
            $attribute = (string) $attribute;
            $rules = array_keys($failed[$attribute] ?? []);
            $paired = count($rules) === count($details);

            foreach (array_values($details) as $index => $detail) {
                $rule = $paired ? (string) $rules[$index] : null;

                $failures[] = [
                    'code' => $rule === null ? null : $this->code($attribute, $rule),
                    'detail' => (string) $detail,
                    'source' => $this->source($attribute),
                    'rule' => $rule,
                ];
            }
        }

        return $failures;
    }

    private function code(string $attribute, string $rule): string
    {
        $reason = Str::snake(class_basename($rule));

        if (in_array($rule, self::SIZE_RULES, true)) {
            $reason .= '_'.$this->type($attribute);
        }

        return "validation.{$reason}";
    }

    private function type(string $attribute): string
    {
        if (! $this->validator instanceof Validator) {
            return 'string';
        }

        return match (true) {
            $this->validator->hasRule($attribute, ['Numeric', 'Integer', 'Decimal']) => 'numeric',
            $this->validator->hasRule($attribute, ['Array', 'List']) => 'array',
            $this->validator->getValue($attribute) instanceof UploadedFile => 'file',
            default => 'string',
        };
    }

    private function source(string $attribute): Source
    {
        $segments = explode('.', $attribute);
        $key = $segments[0];
        $inBody = $this->request->request->has($key) || $this->request->files->has($key);

        if ($this->request->isMethodSafe() || (! $inBody && $this->request->query->has($key))) {
            return Source::parameter(
                $key.implode('', array_map(fn (string $segment): string => "[{$segment}]", array_slice($segments, 1)))
            );
        }

        return Source::pointer(
            implode('', array_map(fn (string $segment): string => '/'.strtr($segment, ['~' => '~0', '/' => '~1']), $segments))
        );
    }
}
