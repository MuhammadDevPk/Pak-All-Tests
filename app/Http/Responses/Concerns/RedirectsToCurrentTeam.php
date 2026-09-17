<?php

namespace App\Http\Responses\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        $user = $request->user();

        if ($user) {
            $team = $user->currentTeam ?? $user->personalTeam();

            if ($team) {
                URL::defaults(['current_team' => $team->slug]);
            }
        }

        return $redirect;
    }
}
