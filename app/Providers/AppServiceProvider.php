<?php

namespace App\Providers;

use App\Services\Mail\BrevoMailService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrevoMailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthRateLimiters();
    }

    /**
     * Rate limiters for the unauthenticated auth endpoints, keyed per email and per IP.
     */
    private function configureAuthRateLimiters(): void
    {
        $email = fn (Request $request): string => Str::lower((string) $request->input('email'));

        RateLimiter::for('auth-code-send', fn (Request $request): array => [
            Limit::perMinute(1)->by('send:minute:'.$email($request)),
            Limit::perHour(5)->by('send:hour:'.$email($request)),
            Limit::perMinute(10)->by('send:ip:'.$request->ip()),
        ]);

        RateLimiter::for('auth-code-verify', fn (Request $request): array => [
            Limit::perMinute(5)->by('verify:'.$email($request).'|'.$request->ip()),
            Limit::perMinute(20)->by('verify:ip:'.$request->ip()),
        ]);
    }
}
