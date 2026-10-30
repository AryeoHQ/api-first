<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Response;

final class DeleteNoteWithParentLockController
{
    public function __invoke(Authorizer $authorizer, Precondition $precondition, NoteWithParentLock $note): Response
    {
        $precondition->guard(fn (): null|bool => $note->delete());

        return response()->noContent();
    }
}
