<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Support\Http\Api\Errors\Paths;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class RejectMalformedJson
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (new Paths()->cover($request) && $this->isMalformed($request)) {
            throw new BadRequestHttpException('The API cannot parse the request.');
        }

        return $next($request);
    }

    private function isMalformed(Request $request): bool
    {
        if (! $this->isJson($request)) {
            return false;
        }

        $content = $request->getContent();

        return $content !== '' && ! json_validate($content);
    }

    private function isJson(Request $request): bool
    {
        $type = strtolower(trim(Str::before((string) $request->headers->get('Content-Type'), ';')));

        return $type === 'application/json' || str_ends_with($type, '+json');
    }
}
