<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Preconditions;

use Illuminate\Auth\Access\HandlesAuthorization;

final class NotePolicy
{
    use HandlesAuthorization;
}
