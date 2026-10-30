<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\ConditionalGet;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class CreatedNoteController
{
    public function __invoke(Note $note): JsonResponse
    {
        return $note->toSchema(ApiVersion::V1)->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
