<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\ConditionalGet;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ListNoteChildrenController
{
    public function __invoke(Note $note): ResourceCollection
    {
        return $note->children()->get()->toSchemaCollection(ApiVersion::V1)
            ->withTag(EntityTag::forNamedRevision($note, NoteRevision::ChildrenOrder, ApiVersion::V1));
    }
}
