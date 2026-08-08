<?php

namespace App\Providers;

use App\Auth\CachingUserProvider;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerCachingUserProvider();
        $this->registerPerformanceLogging();
    }

    /**
     * Register a Redis-backed caching user provider.
     *
     * Eliminates the ~1,700ms Supabase round-trip that Laravel's default
     * EloquentUserProvider makes on every authenticated HTTP request to
     * re-hydrate the User model from the session's stored user ID.
     */
    protected function registerCachingUserProvider(): void
    {
        Auth::provider('caching-eloquent', function ($app, array $config) {
            return new CachingUserProvider(
                $app['hash'],
                $config['model'] ?? User::class,
            );
        });
    }

    /**
     * Log DB queries and Redis Cache Hits/Misses in real-time.
     * Skips noisy auth user cache events to keep logs focused on data queries.
     */
    protected function registerPerformanceLogging(): void
    {
        if (app()->isLocal()) {
            DB::listen(function ($query) {
                if ($query->time > 50) {
                    Log::warning(sprintf(
                        '[SLOW DB QUERY] %0.2fms | SQL: %s | Bindings: %s',
                        $query->time,
                        $query->sql,
                        json_encode($query->bindings)
                    ));
                } else {
                    Log::debug(sprintf(
                        '[DB Query] %0.2fms | SQL: %s',
                        $query->time,
                        $query->sql
                    ));
                }
            });

            Event::listen(CacheHit::class, function ($event) {
                // Skip internal auth / session noise from polluting logs
                if (str_starts_with($event->key, 'auth_user:') || str_starts_with($event->key, 'auth_team:')) {
                    return;
                }
                Log::info("[Redis Cache HIT] Key: {$event->key}");
            });

            Event::listen(CacheMissed::class, function ($event) {
                if (str_starts_with($event->key, 'auth_user:') || str_starts_with($event->key, 'auth_team:')) {
                    return;
                }
                Log::info("[Redis Cache MISS] Key: {$event->key}");
            });

            Event::listen(KeyWritten::class, function ($event) {
                if (str_starts_with($event->key, 'auth_user:') || str_starts_with($event->key, 'auth_team:')) {
                    return;
                }
                Log::info("[Redis Cache WRITE] Key: {$event->key} | TTL: {$event->seconds}s");
            });
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
