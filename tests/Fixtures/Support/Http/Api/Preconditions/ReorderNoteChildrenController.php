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
        $tag = $precondition->guard(function () use ($note, $validator): EntityTag {
            $note->update($validator->validated());

            $note->children()->create(['title' => 'Appended']);

            return EntityTag::forNamedRevision($note, NoteRevision::ChildrenOrder, ApiVersion::V1);
        });

        return response()->noContent()->header('ETag', (string) $tag);
    }
}
