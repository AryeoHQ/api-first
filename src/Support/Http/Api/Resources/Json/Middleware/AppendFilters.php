<?php

declare(strict_types=1);

namespace Support\Http\Api\Resources\Json\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Support\Http\Api\Resources\Json\Filters;
use Symfony\Component\HttpFoundation\Response;

final class AppendFilters
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse || ! $request->has('filters')) {
            return $response;
        }

        $filters = new Filters;
        $resolved = $filters($request);

        /** @var array<string, mixed> $data */
        $data = $response->getData(assoc: true);
        $data['meta'] ??= [];
        $data['meta']['filters'] = $resolved !== [] ? $resolved : null;
        $response->setData($data);

        return $response;
    }
}
