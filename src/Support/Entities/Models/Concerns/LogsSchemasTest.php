<?php

declare(strict_types=1);

namespace Support\Entities\Models\Concerns;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Tests\Fixtures\Support\Entities\Posts\Post;
use Tests\Fixtures\Support\Entities\Tags\Tag;
use Tests\Fixtures\Support\Http\Api\V1;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\TestCase;

#[CoversTrait(LogsSchemas::class)]
final class LogsSchemasTest extends TestCase
{
    #[Test]
    public function it_returns_a_collection_of_schema_instances(): void
    {
        $model = Post::factory()->create();

        $result = $model->toLoggable();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(Schema::class, $result);
    }

    #[Test]
    public function each_schema_resolves_with_the_model_as_resource(): void
    {
        $model = Post::factory()->create();

        $result = $model->toLoggable();

        $schema = $result->first();

        $this->assertInstanceOf(V1\Posts\Post::class, $schema);
        $this->assertSame($model->id, $schema->id);
        $this->assertSame('post', $schema->resourceType);
        $this->assertSame(ApiVersion::V1, $schema->resourceVersion);
    }

    #[Test]
    public function it_returns_an_empty_collection_when_no_schemas_are_registered(): void
    {
        $model = Tag::factory()->make();

        $result = $model->toLoggable();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
