<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

final readonly class ValidationErrors
{
    private const array SIZE_RULES = ['Size', 'Between', 'Min', 'Max', 'Gt', 'Lt', 'Gte', 'Lte'];

    public function __construct(
        private Validator $validator,
        private Request $request,
    ) {}

    /**
     * @return null|list<ErrorObject>
     */
    public function toErrorObjects(): null|array
    {
        $messages = $this->validator->errors();
        $errors = [];

        foreach ($this->validator->failed() as $attribute => $rules) {
            $details = $messages->get($attribute);

            if (count($details) !== count($rules)) {
                return null;
            }

            foreach (array_keys($rules) as $index => $rule) {
                $errors[] = new ErrorObject($this->code($attribute, $rule), $details[$index], $this->source($attribute));
            }
        }

        return count($errors) === $messages->count() ? $errors : null;
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
