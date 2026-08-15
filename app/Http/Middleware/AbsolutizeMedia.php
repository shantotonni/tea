<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Rewrites every relative "images/…" path in a public API JSON response into an
 * absolute URL on the media host (the Laravel backend). The storefront then
 * loads images directly from the backend — no fragile SSR proxy, no CORS
 * (image tags are not CORS-restricted for display).
 *
 * Only touches /api/public/* responses so the admin panel (which builds its own
 * asset URLs) is left untouched.
 */
class AbsolutizeMedia
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof JsonResponse) {
            return $response;
        }
        if (! str_starts_with(ltrim($request->path(), '/'), 'api/public')
            && ! str_starts_with(ltrim($request->path(), '/'), 'public')) {
            return $response;
        }

        $data = $response->getData(true);
        $response->setData($this->walk($data));

        return $response;
    }

    private function base(): string
    {
        return rtrim(config('app.media_url') ?: env('MEDIA_URL', 'https://backend.chakunjo.com'), '/');
    }

    private function walk($value)
    {
        if (is_string($value)) {
            return $this->fix($value);
        }
        if (is_array($value)) {
            return array_map(fn ($v) => $this->walk($v), $value);
        }

        return $value;
    }

    private function fix(string $s): string
    {
        // already absolute (http, protocol-relative, data URI) → leave it
        if (preg_match('#^(https?:)?//#', $s) || str_starts_with($s, 'data:')) {
            return $s;
        }
        // only rewrite paths that point at the images folder
        if (preg_match('#^/?images/#', $s)) {
            return $this->base().'/'.ltrim($s, '/');
        }

        return $s;
    }
}
