<?php

use App\Http\Controllers\Admin\AdController as AdminAdController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuthorController as AdminAuthorController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\SiteController as AdminSiteController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Api\TaskRewardController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PostController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\Frontend\TagController;
use App\Http\Controllers\Frontend\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', fn() => redirect()->route('admin.login'))->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Admin
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Authenticated Admin
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/switch-site/{id}', [AdminDashboardController::class, 'switchSite'])->name('switch-site');

        // Sites Manager
        Route::resource('sites', AdminSiteController::class);

        // Posts Manager
        Route::resource('posts', AdminPostController::class);

        // Categories Manager
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        // Tags Manager
        Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
        Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
        Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

        // Authors Manager
        Route::get('/authors', [AdminAuthorController::class, 'index'])->name('authors.index');
        Route::post('/authors', [AdminAuthorController::class, 'store'])->name('authors.store');
        Route::put('/authors/{author}', [AdminAuthorController::class, 'update'])->name('authors.update');
        Route::delete('/authors/{author}', [AdminAuthorController::class, 'destroy'])->name('authors.destroy');

        // Ad Engine
        Route::get('/ads', [AdminAdController::class, 'index'])->name('ads.index');
        Route::post('/ads', [AdminAdController::class, 'store'])->name('ads.store');
        Route::post('/ads/save', [AdminAdController::class, 'save'])->name('ads.save');
        Route::delete('/ads/{ad}', [AdminAdController::class, 'destroy'])->name('ads.destroy');

        // Static & Legal Pages
        Route::resource('pages', AdminPageController::class);

        // Site Verification & ads.txt Manager
        Route::get('/verification', [AdminVerificationController::class, 'index'])->name('verification.index');
        Route::post('/verification/ads-txt', [AdminVerificationController::class, 'saveAdsTxt'])->name('verification.ads-txt');
        Route::post('/verification/root-files', [AdminVerificationController::class, 'storeRootFile'])->name('verification.root-files.store');
        Route::delete('/verification/root-files/{rootFile}', [AdminVerificationController::class, 'destroyRootFile'])->name('verification.root-files.destroy');

        // Site Settings
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

        // Media Manager
        Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
        Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
        Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Task Reward & Verification APIs
|--------------------------------------------------------------------------
*/
Route::prefix('api/task')->name('api.task.')->group(function () {
    Route::post('/start-session', [TaskRewardController::class, 'startSession'])->name('start-session');
    Route::post('/claim-code', [TaskRewardController::class, 'claimCode'])->name('claim-code');
    Route::match(['GET', 'POST'], '/verify-code', [TaskRewardController::class, 'verifyCode'])->name('verify-code');
});

/*
|--------------------------------------------------------------------------
| SEO, ads.txt & System Routes
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/ads.txt', [VerificationController::class, 'adsTxt'])->name('ads.txt');

// Dynamic Root Verification Files (e.g. sw.js, google1234.html, monetag.html)
Route::get('/{filename}', [VerificationController::class, 'rootFile'])
    ->where('filename', '.*\\.(js|html|txt|json|xml)')
    ->name('root.file');

/*
|--------------------------------------------------------------------------
| Public Blog Frontend Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/tag/{slug}', [TagController::class, 'show'])->name('tag.show');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
Route::get('/{slug}', [PostController::class, 'show'])->name('post.show');
