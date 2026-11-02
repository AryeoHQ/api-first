<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\ConditionalGet;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Support\Http\Resources\Schemas\Tags\Snapshot;
use Tests\Fixtures\Support\Http\Api\Preconditions\Note;
use Tests\Fixtures\Support\Http\Api\Preconditions\NoteRevision;
use Tests\Fixtures\Support\Schemas\ApiVersion;

final class ListNoteChildrenController
{
    public function __invoke(Note $note): ResourceCollection
    {
        [$tag, $children] = Snapshot::read(fn (): array => [
            EntityTag::forNamedRevision($note, NoteRevision::ChildrenOrder, ApiVersion::V1),
            $note->children()->get(),
        ], $note->getConnection());

        return $children->toSchemaCollection(ApiVersion::V1)->withTag($tag);
    }
}
