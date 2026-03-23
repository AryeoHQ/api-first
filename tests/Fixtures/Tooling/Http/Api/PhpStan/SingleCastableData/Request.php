<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan\SingleCastableData;

use Illuminate\Foundation\Http\FormRequest;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Requests\Provides\CastsData;

class Request extends FormRequest implements CastableData
{
    use CastsData;
}
