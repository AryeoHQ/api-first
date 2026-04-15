<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\PaginatedResourceResponse\PaginationInformation\Provides;

use PHPUnit\Framework\Attributes\CoversTrait;
use Tests\TestCase;

#[CoversTrait(WithStructuredMeta::class)]
final class WithStructuredMetaTest extends TestCase
{
    use WithStructuredMetaFiltersTestCases;
    use WithStructuredMetaPagingTestCases;
    use WithStructuredMetaSortTestCases;
}
