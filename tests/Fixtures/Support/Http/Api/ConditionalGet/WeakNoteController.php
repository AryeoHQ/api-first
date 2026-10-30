<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\ConditionalGet;

use Illuminate\Http\JsonResponse;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class WeakNoteController
{
    public function __invoke(Note $note): JsonResponse
    {
        $response = $note->toSchema(ApiVersion::V1)->response();

        return $response->setEtag((string) $response->headers->get('ETag'), weak: true);
    }
}
