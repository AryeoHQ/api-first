<?php

declare(strict_types=1);

namespace Support\Entities\Models\Concerns;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\Logs\Data\Data;
use Support\Events\Log\Logs\Data\Variant;
use Tests\Fixtures\Support\Articles;
use Tests\Fixtures\Support\Schemas\ApiVersion;
use Tests\TestCase;

#[CoversTrait(LogsSchemas::class)]
final class LogsSchemasTest extends TestCase
{
    #[Test]
    public function it_returns_loggable_data_with_variants(): void
    {
        $article = Articles\Article::factory()->make();

        $data = $article->toLoggable();

        $this->assertInstanceOf(Data::class, $data);
        $this->assertCount(1, $data->variants);

        $variant = $data->variants->first();
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertSame(ApiVersion::V1, $variant->version);
        $this->assertSame($article->getKey(), $variant->payload['id']);
        $this->assertSame('article', $variant->payload['resource_type']);
    }
}
