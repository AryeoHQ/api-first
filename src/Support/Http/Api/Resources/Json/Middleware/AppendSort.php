<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Support\Http\Api\Resources\Json\Sort;
use Symfony\Component\HttpFoundation\Response;

final class AppendSort
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse || ! $request->has('sort')) {
            return $response;
        }

        return tap($response, function (JsonResponse $response) use ($request) {
            /** @var array<string, mixed> $data */
            $data = $response->getData(assoc: true);
            $data['meta']['sort'] = Sort::from($request);
            $response->setData($data);
        });
    }
}
