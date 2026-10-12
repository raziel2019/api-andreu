<?php

namespace App\Providers;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped('cms.media.options', fn () => MediaAsset::orderByDesc('id')->get());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('components.admin.media-picker', function (View $view): void {
            $view->with('mediaAssets', $this->app->make('cms.media.options'));
        });
        Gate::define('access-admin', fn (User $user): bool => $user->is_admin === true);
        RateLimiter::for('contact-verification', fn (Request $request) => Limit::perMinute(5)->by($request->ip())
            ->response(fn (Request $request, array $headers) => response()->json(['success' => false], 429, $headers))
        );
        RateLimiter::for('contact-submission', function (Request $request): array {
            $email = $request->input('email');
            $emailKey = hash('sha256', is_string($email) ? mb_strtolower(trim($email)) : '');
            $response = fn (Request $request, array $headers): JsonResponse => response()->json([
                'success' => false, 'code' => 'rate_limited', 'message' => __('contact.rate_limited'),
            ], 429, $headers);

            return [
                Limit::perMinute(5)->by('ip-minute:'.$request->ip())->response($response),
                Limit::perHour(20)->by('ip-hour:'.$request->ip())->response($response),
                Limit::perHour(10)->by('email-hour:'.$emailKey)->response($response),
            ];
        });
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by(hash('sha256', mb_strtolower((string) $request->input('email')).'|'.$request->ip())),
            Limit::perMinute(30)->by($request->ip()),
        ]);
    }
}
