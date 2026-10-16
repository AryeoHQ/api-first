<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Resources\Json\JsonResource;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ForbiddenUpdateNoteController
{
    public function __invoke(DeniedAuthorizer $authorizer, RequiredPrecondition $precondition, Validator $validator, Note $note): JsonResource
    {
        $precondition->guard(fn (): bool => $note->update($validator->validated()));

        return $note->toSchema(ApiVersion::V1);
    }
}
