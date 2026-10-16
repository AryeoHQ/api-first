<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Resources\Json\JsonResource;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ShowNoteController
{
    public function __invoke(Note $note): JsonResource
    {
        return $note->toSchema(ApiVersion::V1);
    }
}
