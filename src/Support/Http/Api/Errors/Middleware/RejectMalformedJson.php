<?php

declare(strict_types=1);

namespace Support\Http\Api\Errors\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        $content = $request->getContent();

        return $content !== '' && $request->isJson() && ! json_validate($content);
    }
}
