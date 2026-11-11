<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToCurrentTeam;

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }

        $redirect = $request->input('redirect') ?: $request->query('redirect') ?: session()->pull('url.intended');

        if ($redirect && is_string($redirect)) {
            return redirect()->to($redirect);
        }

        return redirect()->intended($this->redirectPathForCurrentTeam($request, Fortify::redirects('login')));
    }
}
