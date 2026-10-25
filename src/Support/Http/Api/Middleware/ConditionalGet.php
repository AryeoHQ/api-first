<?php

declare(strict_types=1);

namespace Support\Http\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConditionalGet
{
    private const string OPAQUE_TAG = '#(?:W/)?("[^"]*")#';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->isConditional($request, $response) || ! $this->matches($request, $response)) {
            return $response;
        }

        return $response->setNotModified();
    }

    private function isConditional(Request $request, Response $response): bool
    {
        return ($request->isMethod('GET') || $request->isMethod('HEAD'))
            && $response->getStatusCode() === Response::HTTP_OK
            && $response->headers->has('ETag')
            && $request->headers->has('If-None-Match');
    }

    private function matches(Request $request, Response $response): bool
    {
        $ifNoneMatch = trim((string) $request->headers->get('If-None-Match'));

        if ($ifNoneMatch === '*') {
            return true;
        }

        preg_match_all(self::OPAQUE_TAG, $ifNoneMatch, $candidates);

        return in_array(preg_replace('#^W/#', '', (string) $response->headers->get('ETag')), $candidates[1], true);
    }
}
