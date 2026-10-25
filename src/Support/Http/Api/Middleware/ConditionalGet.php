<?php

declare(strict_types=1);

namespace Support\Http\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Support\Http\Resources\Schemas\Tags\EntityTag;
use Symfony\Component\HttpFoundation\Response;

final class ConditionalGet
{
    private const string WEAK = '#^W/#';

    private const string ELEMENT = '#\G[ \t]*(\*|(?:W/)?"[\x21\x23-\x7E\x80-\xFF]*")[ \t]*(?:,|\z)#';

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
        $tag = (string) preg_replace(self::WEAK, '', trim((string) $response->headers->get('ETag')));

        return EntityTag::parse($tag)?->digest !== null ? $tag : null;
    }

    private function matches(Request $request, string $tag): bool
    {
        $candidates = $this->candidates(implode(', ', $request->headers->all('If-None-Match')));

        return in_array('*', $candidates, true)
            || in_array($tag, array_map(fn (string $candidate): string => (string) preg_replace(self::WEAK, '', $candidate), $candidates), true);
    }

    /**
     * @return list<string>
     */
    private function candidates(string $field): array
    {
        preg_match_all(self::ELEMENT, $field, $elements);

        return strlen(implode('', $elements[0])) === strlen($field) ? $elements[1] : [];
    }
}
