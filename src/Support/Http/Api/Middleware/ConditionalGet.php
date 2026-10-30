<?php

declare(strict_types=1);

namespace Support\Http\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Support\Http\Resources\Schemas\Tags\EntityTagList;
use Symfony\Component\HttpFoundation\Response;

final class ConditionalGet
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isConditional($request, $response) || ! $this->matches($request, (string) $this->representationTag($response))) {
            return $response;
        }

        return $response->setNotModified();
    }

    private function isConditional(Request $request, Response $response): bool
    {
        return ($request->isMethod('GET') || $request->isMethod('HEAD'))
            && $response->getStatusCode() === Response::HTTP_OK
            && $request->headers->has('If-None-Match')
            && $this->representationTag($response) !== null;
    }

    private function representationTag(Response $response): null|string
    {
        $tag = Str::chopStart(trim((string) $response->headers->get('ETag')), 'W/');

        return EntityTag::parse($tag)?->digest !== null ? $tag : null;
    }

    private function matches(Request $request, string $tag): bool
    {
        return EntityTagList::parse($request->headers->all('If-None-Match'))?->matchesWeakly($tag) ?? false;
    }
}
