<?php

namespace App\Providers;

use App\Models\MediaAsset;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
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
        RateLimiter::for('admin-login', fn (Request $request) => [
            Limit::perMinute(5)->by(hash('sha256', mb_strtolower((string) $request->input('email')).'|'.$request->ip())),
            Limit::perMinute(30)->by($request->ip()),
        ]);
    }
}
