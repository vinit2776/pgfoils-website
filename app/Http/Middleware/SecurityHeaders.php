<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ];

        if (app()->environment('production')) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        // The public site is plain HTML + CSS with self-hosted fonts and images. The admin
        // (Filament/Livewire) needs inline scripts, so the strict policy applies to public pages only.
        if (! app()->environment('local') && ! $request->is('admin', 'admin/*', 'livewire/*', 'livewire-*/*')) {
            $headers['Content-Security-Policy'] = "default-src 'self'; img-src 'self' data:; "
                ."style-src 'self'; script-src 'self'; font-src 'self'; "
                ."frame-src https://www.google.com/maps/; form-action 'self'; base-uri 'self'; object-src 'none'";
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
