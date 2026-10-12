<?php

use App\Http\Controllers\Api\V1\AboutPageController;
use App\Http\Controllers\Api\V1\ContactPageController;
use App\Http\Controllers\Api\V1\ContactVerificationController;
use App\Http\Controllers\Api\V1\HomePageController;
use App\Http\Controllers\Api\V1\LegalPageController;
use App\Http\Controllers\Api\V1\PortfolioPageController;
use App\Http\Controllers\Api\V1\PortfolioProjectController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\SiteSettingController;
use App\Http\Middleware\SetApiLocale;
use App\Models\LegalPage;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(SetApiLocale::class)->group(function (): void {
    Route::post('/contact/verify', ContactVerificationController::class)
        ->middleware('throttle:contact-verification')
        ->name('api.v1.contact.verify');
    Route::get('/legal/{section}', [LegalPageController::class, 'show'])->whereIn('section', array_keys(LegalPage::TYPES));
    Route::get('/site', [SiteSettingController::class, 'show']);
    Route::get('/pages/home', [HomePageController::class, 'show']);
    Route::get('/pages/about', [AboutPageController::class, 'show']);
    Route::get('/pages/portfolio', [PortfolioPageController::class, 'show']);
    Route::get('/pages/contact', [ContactPageController::class, 'show']);
    Route::get('/projects', [PortfolioProjectController::class, 'index'])->name('api.v1.projects.index');
    Route::get('/projects/{slug}', [PortfolioProjectController::class, 'show'])->name('api.v1.projects.show');
    Route::get('/services', [ServiceController::class, 'index']);
});
