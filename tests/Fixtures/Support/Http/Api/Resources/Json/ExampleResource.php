<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Resources\Schemas\Contracts\Schema;
use Support\Http\Resources\Schemas\Provides\AsSchema;

class ExampleResource extends JsonResource implements Schema
{
    use AsSchema;

    public string $id { get => $this->resource['id']; }

    public string $resourceType = 'example';

    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'],
        ];
    }
}
