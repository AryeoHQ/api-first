<?php

namespace Tests\Fixtures\Tooling\Http\Api\PhpStan;

use Illuminate\Foundation\Http\FormRequest;
use Support\Http\Requests\Contracts\CastableData;
use Support\Http\Requests\Provides\CastsData;

class ControllerWithTwoCastableDataRequestA extends FormRequest implements CastableData
{
    use CastsData;
}
