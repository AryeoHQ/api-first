<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;

final readonly class ValidationErrors
{
    public function __construct(
        private ValidatorContract $validator,
        private Request $request,
    ) {}

    /**
     * @return list<array{detail: string, source: Source}>
     */
    public function toFailures(): array
    {
        $failures = [];

        foreach ($this->validator->errors()->messages() as $attribute => $details) {
            $source = $this->source((string) $attribute);

            foreach ($details as $detail) {
                $failures[] = ['detail' => (string) $detail, 'source' => $source];
            }
        }

        return $failures;
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
