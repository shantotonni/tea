<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Adds defensive HTTP response headers to every request.
 * Guards against clickjacking, MIME sniffing, referrer leakage, etc.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $headers = [
            // stop the admin from being framed by another site (clickjacking)
            'X-Frame-Options' => 'SAMEORIGIN',
            // browsers must not guess content types
            'X-Content-Type-Options' => 'nosniff',
            // don't leak full URLs to other origins
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // lock down powerful browser features we don't use
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            // hide the framework/server fingerprint
            'X-Powered-By' => 'Cha Kunjo',
        ];

        // HSTS only makes sense over HTTPS — enable once you serve via TLS
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        if (strtolower($request->getHost()) === 'backend.chakunjo.com') {
            $headers['X-Robots-Tag'] = 'noindex, nofollow';
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
