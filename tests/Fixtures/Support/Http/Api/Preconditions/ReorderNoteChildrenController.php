<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Response;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ReorderNoteChildrenController
{
    public function __invoke(Authorizer $authorizer, ChildrenOrderPrecondition $precondition, Validator $validator, Note $note): Response
    {
        $precondition->guard(function () use ($note, $validator): void {
            $note->update($validator->validated());

            $note->children()->create(['title' => 'Appended']);
        });

        return response()->noContent()->header('ETag', (string) EntityTag::forNamedRevision($note, NoteRevision::ChildrenOrder, ApiVersion::V1));
    }
}
