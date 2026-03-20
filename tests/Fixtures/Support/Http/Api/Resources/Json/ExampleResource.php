<?php

declare(strict_types=1);

namespace Tests\Fixtures\Support\Http\Api\Resources\Json;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Support\Http\Api\Resources\Json\HasResponseMeta;

class ExampleResource extends JsonResource
{
    use HasResponseMeta;

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
