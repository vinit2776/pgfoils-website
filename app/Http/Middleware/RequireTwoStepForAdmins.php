<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Admins must set up the authenticator app before using the admin area. Editors and Reviewers may, but are not forced
 * to: anyone who has set it up is still asked for the code at every sign-in.
 */
class RequireTwoStepForAdmins extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Filament::auth()->user()?->hasRole(User::ADMIN)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
