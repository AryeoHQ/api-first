<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json;

use Illuminate\Http\Resources\Json\JsonResource;

class ExampleResource extends JsonResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource['id'],
        ];
    }
}
