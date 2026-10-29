<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $ip = fn () => ['ip' => request()->ip()];

        Event::listen(Login::class, function (Login $e) use ($ip) {
            $e->user->forceFill(['last_login_at' => now()])->saveQuietly();
            activity('sign-in')->causedBy($e->user)->withProperties($ip())->log('Signed in');
        });
        Event::listen(Logout::class, fn (Logout $e) => $e->user && activity('sign-in')->causedBy($e->user)->withProperties($ip())->log('Signed out'));
        Event::listen(Failed::class, fn (Failed $e) => activity('sign-in')->withProperties($ip() + ['email' => $e->credentials['email'] ?? null])->log('Failed sign-in attempt'));
        Event::listen(Lockout::class, fn () => activity('sign-in')->withProperties($ip())->log('Sign-in locked out after too many attempts'));
        Event::listen(RoleAttachedEvent::class, fn ($e) => activity('users')->causedBy(auth()->user())->performedOn($e->model)->withProperties(['roles' => $this->roleNames($e->rolesOrIds)])->log('Role added'));
        Event::listen(RoleDetachedEvent::class, fn ($e) => activity('users')->causedBy(auth()->user())->performedOn($e->model)->withProperties(['roles' => $this->roleNames($e->rolesOrIds)])->log('Role removed'));
    }

    /** @return array<int, string> */
    private function roleNames(mixed $rolesOrIds): array
    {
        return collect(is_iterable($rolesOrIds) ? $rolesOrIds : [$rolesOrIds])
            ->map(fn ($r) => $r instanceof Role ? $r->name : Role::find($r)?->name)->filter()->values()->all();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
