<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Resources\Json\JsonResource;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class UpdateSecondaryNoteController
{
    public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, SecondaryNote $note): JsonResource
    {
        $precondition->guard(fn (): bool => $note->update($validator->validated()));

        return $note->toSchema(ApiVersion::V1);
    }
}
