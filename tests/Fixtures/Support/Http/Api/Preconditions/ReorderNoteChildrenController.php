<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Response;
use Support\Database\Eloquent\Concurrency\ConcurrencyRevision;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ReorderNoteChildrenController
{
    public function __invoke(Authorizer $authorizer, ChildrenOrderPrecondition $precondition, Validator $validator, Note $note): Response
    {
        $parent = $precondition->guard(function () use ($note, $validator): Note {
            $note->update($validator->validated());

            $note->newQuery()->whereKey($note->getKey())->increment(ConcurrencyRevision::namedColumn(NoteRevision::ChildrenOrder->value));

            return $note->newQuery()->findOrFail($note->getKey());
        });

        return response()->noContent()->header('ETag', (string) EntityTag::forNamedRevision($parent, NoteRevision::ChildrenOrder, ApiVersion::V1));
    }
}
