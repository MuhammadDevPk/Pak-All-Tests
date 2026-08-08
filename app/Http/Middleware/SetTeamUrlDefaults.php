<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetTeamUrlDefaults
{
    /**
     * Set the default URL parameters for team-based routes.
     *
     * Skip entirely on Filament admin routes — they do not use team-scoped
     * URL parameters, and resolving `->currentTeam` would fire a DB query
     * against Supabase adding ~1,500ms to every admin page load.
     *
     * On non-admin routes, the User's currentTeam relation will already be
     * loaded and cached by CachingUserProvider, so this is zero-cost.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Bail immediately for admin panel — no team URL defaults needed
        if ($request->is('admin*')) {
            return $next($request);
        }

        if ($currentTeam = $request->user()?->currentTeam) {
            URL::defaults([
                'current_team' => $currentTeam->slug,
                'team' => $currentTeam->slug,
            ]);
        }

        return $next($request);
    }
}
