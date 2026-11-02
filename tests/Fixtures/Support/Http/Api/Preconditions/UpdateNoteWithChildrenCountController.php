<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\JsonResponse;

final class UpdateNoteWithChildrenCountController
{
    public function __invoke(Authorizer $authorizer, Precondition $precondition, Validator $validator, NoteWithChildrenCount $note): JsonResponse
    {
        $precondition->guard(fn (): bool => $note->update($validator->validated()));

        return response()->json(['title' => $note->getAttribute('title'), 'children_count' => $note->getAttribute('children_count')]);
    }
}
