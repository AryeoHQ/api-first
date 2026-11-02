<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\ConditionalGet;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ListNoteChildrenController
{
    public function __invoke(Note $note): ResourceCollection
    {
        return DB::transaction(function () use ($note): ResourceCollection {
            DB::statement('set transaction isolation level repeatable read, read only');

            $tag = EntityTag::forNamedRevision($note, NoteRevision::ChildrenOrder, ApiVersion::V1);

            return $note->children()->get()->toSchemaCollection(ApiVersion::V1)->withTag($tag);
        });
    }
}
