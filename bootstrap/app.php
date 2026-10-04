<?php

use App\Http\Middleware\CaptureReferralCode;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'referral_code', 'referral_domain']);

        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->filled('ref')) {
                $ref = (string) $request->query('ref');
                $domain = $request->route('domainSlug') ?? $request->query('domain') ?? $request->query('domain_slug');

                return route('register', array_filter(['ref' => $ref, 'domain' => $domain]));
            }

            return route('login');
        });

        $middleware->web(append: [
            CaptureReferralCode::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            SetTeamUrlDefaults::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
