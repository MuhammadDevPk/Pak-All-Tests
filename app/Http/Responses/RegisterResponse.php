<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    use RedirectsToCurrentTeam;

    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 201);
        }

        $redirect = $request->input('redirect') ?: $request->query('redirect') ?: session()->pull('url.intended');

        if ($redirect && is_string($redirect)) {
            return redirect()->to($redirect);
        }

        return redirect()->intended($this->redirectPathForCurrentTeam($request, Fortify::redirects('register')));
    }
}
