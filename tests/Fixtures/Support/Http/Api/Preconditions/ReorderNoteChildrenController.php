<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Response;

final class ReorderNoteChildrenController
{
    public function __invoke(Authorizer $authorizer, ChildrenOrderPrecondition $precondition, Validator $validator, Note $note): Response
    {
        $precondition->guard(fn (): bool => $note->update($validator->validated()));

        return response()->noContent();
    }
}
