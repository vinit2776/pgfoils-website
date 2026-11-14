<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a staging deployment private. Active only when STAGING_PASSWORD is set and the app is
 * not running locally. Browsers show a standard sign-in prompt; any user name works.
 */
class StagingGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $password = (string) config('site.staging_password');

        if ($password === '' || app()->environment('local', 'testing') || $request->is('up')) {
            return $next($request);
        }

        if (hash_equals($password, (string) $request->getPassword())) {
            $response = $next($request);
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

            return $response;
        }

        return response('This site is not public yet.', 401, [
            'WWW-Authenticate' => 'Basic realm="PG Foils staging", charset="UTF-8"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
