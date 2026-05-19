<?php

declare(strict_types=1);

namespace Support\Entities\Models\Concerns;

use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Tests\Fixtures\Support\Entities\Models\Concerns\LogsSchemasModel;
use Tests\Fixtures\Support\Entities\Models\Concerns\LogsSchemasSchema;
use Tests\TestCase;

#[CoversTrait(LogsSchemas::class)]
final class LogsSchemasTest extends TestCase
{
    #[Test]
    public function it_returns_a_collection_of_schema_instances(): void
    {
        $model = (new LogsSchemasModel)->forceFill(['id' => 'test-uuid']);

        $result = $model->toLoggable();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotEmpty($result);
        $this->assertContainsOnlyInstancesOf(Schema::class, $result);
    }

    #[Test]
    public function each_schema_resolves_with_the_model_as_resource(): void
    {
        $model = (new LogsSchemasModel)->forceFill(['id' => 'test-uuid']);

        $result = $model->toLoggable();

        $schema = $result->first();

        $this->assertInstanceOf(LogsSchemasSchema::class, $schema);
        $this->assertSame('test-uuid', $schema->id);
    }
}
