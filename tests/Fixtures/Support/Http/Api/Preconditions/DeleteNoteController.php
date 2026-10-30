<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Http\Response;

final class DeleteNoteController
{
    public function __invoke(Authorizer $authorizer, DeletePrecondition $precondition, Note $note): Response
    {
        $precondition->guard(fn (): null|bool => $note->delete());

        return response()->noContent();
    }
}
