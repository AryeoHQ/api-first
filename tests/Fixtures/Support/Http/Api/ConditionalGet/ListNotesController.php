<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\ConditionalGet;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ListNotesController
{
    public function __invoke(): ResourceCollection
    {
        return Note::query()->get()->toSchemaCollection(ApiVersion::V1);
    }
}
