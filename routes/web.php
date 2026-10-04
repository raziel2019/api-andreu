<?php

use App\Http\Controllers\Admin\AboutPageController;
use App\Http\Controllers\Admin\ContactPageController;
use App\Http\Controllers\Admin\ContactServiceOptionController;
use App\Http\Controllers\Admin\ContactServiceOptionOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomePageController;
use App\Http\Controllers\Admin\LegalPageController;
use App\Http\Controllers\Admin\PortfolioPageController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceOrderController;
use App\Http\Controllers\Admin\ServicePageController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Models\LegalPage;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');
    });
    Route::post('/logout', [SessionController::class, 'destroy'])->middleware('auth')->name('logout');
    Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
        Route::get('/legal/{section}', [LegalPageController::class, 'edit'])->whereIn('section', array_keys(LegalPage::TYPES))->name('legal.edit');
        Route::put('/legal/{section}', [LegalPageController::class, 'update'])->whereIn('section', array_keys(LegalPage::TYPES))->name('legal.update');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/home', [HomePageController::class, 'edit'])->name('home.edit');
        Route::put('/home', [HomePageController::class, 'update'])->name('home.update');
        Route::get('/settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SiteSettingController::class, 'update'])->name('settings.update');
        Route::get('/about', [AboutPageController::class, 'edit'])->name('about.edit');
        Route::put('/about', [AboutPageController::class, 'update'])->name('about.update');
        Route::get('/portfolio', [PortfolioPageController::class, 'edit'])->name('portfolio.edit');
        Route::put('/portfolio', [PortfolioPageController::class, 'update'])->name('portfolio.update');
        Route::get('/contact', [ContactPageController::class, 'edit'])->name('contact.edit');
        Route::put('/contact', [ContactPageController::class, 'update'])->name('contact.update');
        Route::put('/services/order', [ServiceOrderController::class, 'update'])->name('services.order');
        Route::get('/services/page', [ServicePageController::class, 'edit'])->name('services.page.edit');
        Route::put('/services/page', [ServicePageController::class, 'update'])->name('services.page.update');
        Route::resource('/services', ServiceController::class)->except('show');
        Route::put('/contact/options/order', [ContactServiceOptionOrderController::class, 'update'])->name('contact.options.order');
        Route::resource('/contact/options', ContactServiceOptionController::class)->except(['index', 'show'])->names('contact.options');
        Route::get('/{section}', [SectionController::class, 'show'])
            ->whereIn('section', ['about', 'services', 'portfolio', 'contact'])->name('sections.show');
    });
});
