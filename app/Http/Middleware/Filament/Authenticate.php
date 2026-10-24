<?php

declare(strict_types=1);

namespace App\Http\Middleware\Filament;

use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate as BaseAuthenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Authenticate extends BaseAuthenticate
{
    /**
     * Authenticate the incoming request for the Filament admin panel.
     *
     * @param  Request  $request
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $guard = Filament::auth();

        // 1. If panel guard is unauthenticated, check if the user is already authenticated
        // on the web guard as a legitimate non-guest administrator (Single Sign-On bridge).
        if (! $guard->check()) {
            $webUser = auth()->guard('web')->user();

            if ($webUser instanceof FilamentUser && ! $webUser->is_guest && $webUser->canAccessPanel($panel)) {
                $guard->login($webUser);
            }
        }

        // 2. If still unauthenticated, redirect cleanly to the panel's login form.
        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);
        }

        /** @var Model $user */
        $user = $guard->user();

        // 3. If an ephemeral guest user reaches this point, NEVER abort with a 403 dead-end.
        // Immediately log them out of the panel guard and redirect to the admin login page.
        if (isset($user->is_guest) && $user->is_guest) {
            $guard->logout();
            $this->unauthenticated($request, $guards);
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        // 4. Panel authorization check for authenticated permanent accounts.
        abort_if(
            $user instanceof FilamentUser ?
                (! $user->canAccessPanel($panel)) :
                (config('app.env') !== 'local'),
            403,
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     */
    protected function redirectTo($request): ?string
    {
        return Filament::getLoginUrl();
    }
}
