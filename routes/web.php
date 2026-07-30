<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminTaskController;
use App\Http\Controllers\Admin\AdminTaskReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWithdrawalController;
use App\Http\Controllers\Admin\AdminDeployController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OfferwallPostbackController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WheelSpinController;
use App\Http\Controllers\Admin\AdminOfferwallController;
use App\Http\Controllers\Admin\AdminPasswordTicketController;
use App\Http\Controllers\Admin\AdminSupportTicketController;
use App\Http\Controllers\Auth\PasswordResetTicketController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\PwaController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// PWA Manifest Route
Route::get('/manifest.json', [PwaController::class, 'manifest'])->name('manifest');

// Public Routes (no auth required)
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/cookie-policy', [HomeController::class, 'cookiePolicy'])->name('cookie-policy');

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    // Max 5 registrations per day per IP to prevent spam
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1440');
    Route::post('/recover-account', [AuthController::class, 'recoverAccount'])->name('recover-account');

    // Password Reset Support Ticket (Guest)
    Route::post('/password-tickets/submit', [PasswordResetTicketController::class, 'submitTicket'])->name('password-tickets.submit')->middleware('throttle:5,60');
    Route::post('/password-tickets/check', [PasswordResetTicketController::class, 'checkTicketStatus'])->name('password-tickets.check')->middleware('throttle:15,60');
    Route::post('/password-tickets/reset', [PasswordResetTicketController::class, 'resetPasswordWithTicket'])->name('password-tickets.reset')->middleware('throttle:10,60');

    // Google OAuth
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

});

// Offerwall S2S Postback Webhook Route (Public API / S2S)
Route::any('/postback/{provider}', [OfferwallPostbackController::class, 'handlePostback'])->name('offerwall.postback');

// Authenticated User Routes
Route::middleware(['auth', 'not_banned'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Referral History API & Page
    Route::get('/referrals/history', [DashboardController::class, 'referralHistory'])->name('referrals.history');
    Route::get('/reffer', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referrals.page');
    Route::get('/referrals', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referral-contest', [\App\Http\Controllers\ReferralContestController::class, 'index'])->name('referrals.contest');

    // Tasks & Offerwalls
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks-history', [TaskController::class, 'history'])->name('tasks.history');
    Route::post('/tasks/{task}/social-proof', [TaskController::class, 'submitSocialProof'])->name('tasks.social-proof');

    // Withdrawals
    Route::get('/withdraw', [WithdrawalController::class, 'index'])->name('withdraw.index');
    Route::get('/withdraw-history', [WithdrawalController::class, 'history'])->name('withdraw.history');
    Route::post('/withdraw', [WithdrawalController::class, 'requestWithdrawal'])->name('withdraw.request');
    Route::post('/withdraw/wallet', [WithdrawalController::class, 'updatePaymentDetails'])->name('withdraw.wallet');

    // Spin the Wheel
    Route::get('/wheel/config', [WheelSpinController::class, 'config'])->name('wheel.config');
    Route::post('/wheel/spin', [WheelSpinController::class, 'spin'])
        ->name('wheel.spin')
        ->middleware('throttle:10,1'); // Limit to 10 requests per minute to prevent DoS

    // Promo Codes
    Route::post('/promo/redeem', [PromoCodeController::class, 'redeem'])->name('promo.redeem');

    // Micro-Campaigns
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns-history', [CampaignController::class, 'history'])->name('campaigns.history');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::post('/campaigns/{campaign}/click', [CampaignController::class, 'click'])->name('campaigns.click');

    // Notifications API
    Route::get('/api/notifications', [DashboardController::class, 'notifications'])->name('notifications.api');
    Route::post('/api/notifications/{notification}/read', [DashboardController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/api/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead'])->name('notifications.read-all');

    // User Support Tickets
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store')->middleware('throttle:5,10');
    Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply')->middleware('throttle:10,60');
});

// Admin Control Panel Routes
Route::middleware(['auth', 'admin'])->prefix(env('ADMIN_PATH', 'admin'))->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Task Reviews
    Route::get('/reviews', [AdminTaskReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{userTask}/approve', [AdminTaskReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{userTask}/reject', [AdminTaskReviewController::class, 'reject'])->name('reviews.reject');
    Route::post('/reviews/bulk-approve', [AdminTaskReviewController::class, 'bulkApprove'])->name('reviews.bulk-approve');

    // Withdrawals
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::post('/withdrawals/bulk-approve', [AdminWithdrawalController::class, 'bulkApprove'])->name('withdrawals.bulk-approve');
    Route::delete('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'destroy'])->name('withdrawals.destroy');
    Route::post('/withdrawals/bulk-delete', [AdminWithdrawalController::class, 'bulkDelete'])->name('withdrawals.bulk-delete');
    Route::post('/withdrawals/cleanup', [AdminWithdrawalController::class, 'cleanup'])->name('withdrawals.cleanup');
    Route::get('/withdrawals/export-csv', [AdminWithdrawalController::class, 'exportCsv'])->name('withdrawals.export-csv');

    // System Settings
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'updateSettings'])->name('admin.settings.update');
    Route::post('/settings/telegram-test', [AdminSettingsController::class, 'testTelegram'])->name('settings.telegram-test');

    // Admin Levels
    Route::get('/levels', [App\Http\Controllers\Admin\AdminLevelController::class, 'index'])->name('admin.levels.index');
    Route::post('/levels', [App\Http\Controllers\Admin\AdminLevelController::class, 'store'])->name('admin.levels.store');
    Route::put('/levels/{level}', [App\Http\Controllers\Admin\AdminLevelController::class, 'update'])->name('admin.levels.update');
    Route::delete('/levels/{level}', [App\Http\Controllers\Admin\AdminLevelController::class, 'destroy'])->name('admin.levels.destroy');
    Route::get('/cron-jobs', [AdminSettingsController::class, 'cronJobs'])->name('cron-jobs.index');
    Route::post('/cron-jobs/run', [AdminSettingsController::class, 'runCronJob'])->name('cron-jobs.run');
    Route::get('/campaign-services', [AdminSettingsController::class, 'campaignServices'])->name('campaign-services.index');
    Route::post('/campaign-services', [AdminSettingsController::class, 'storeService'])->name('campaign-services.store');
    Route::put('/campaign-services/{service}', [AdminSettingsController::class, 'updateService'])->name('campaign-services.update');
    Route::delete('/campaign-services/{service}', [AdminSettingsController::class, 'deleteService'])->name('campaign-services.delete');

    // Task Manager (CRUD)
    Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [AdminTaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{task}/toggle', [AdminTaskController::class, 'toggleStatus'])->name('tasks.toggle');
    Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

    // Offerwalls Manager (CRUD)
    Route::get('/offerwalls', [AdminOfferwallController::class, 'index'])->name('offerwalls.index');
    Route::post('/offerwalls', [AdminOfferwallController::class, 'store'])->name('offerwalls.store');
    Route::put('/offerwalls/{offerwall}', [AdminOfferwallController::class, 'update'])->name('offerwalls.update');
    Route::delete('/offerwalls/{offerwall}', [AdminOfferwallController::class, 'destroy'])->name('offerwalls.destroy');

    // Campaign Management
    Route::get('/campaigns', [AdminDashboardController::class, 'campaigns'])->name('campaigns.index');
    Route::get('/campaigns/export', [AdminDashboardController::class, 'exportCampaigns'])->name('campaigns.export');
    Route::post('/campaigns/{campaign}/approve', [AdminDashboardController::class, 'approveCampaign'])->name('campaigns.approve');
    Route::post('/campaigns/{campaign}/reject', [AdminDashboardController::class, 'rejectCampaign'])->name('campaigns.reject');
    Route::delete('/campaigns/{campaign}', [AdminDashboardController::class, 'deleteCampaign'])->name('campaigns.destroy');

    // Promo Codes
    Route::get('/promo-codes', [AdminDashboardController::class, 'promoCodes'])->name('promo-codes.index');
    Route::post('/promo-codes', [AdminDashboardController::class, 'storePromoCode'])->name('promo-codes.store');
    Route::post('/promo-codes/{promoCode}/toggle', [AdminDashboardController::class, 'togglePromoCode'])->name('promo-codes.toggle');
    Route::delete('/promo-codes/{promoCode}', [AdminDashboardController::class, 'deletePromoCode'])->name('promo-codes.destroy');

    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/history', [AdminUserController::class, 'history'])->name('users.history');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/ban', [AdminDashboardController::class, 'banUser'])->name('users.ban');
    Route::post('/users/{user}/risk-score', [AdminDashboardController::class, 'setRiskScore'])->name('users.risk-score');
    Route::post('/users/{user}/health', [AdminDashboardController::class, 'setHealth'])->name('users.health');

    // Deployment Center
    Route::get('/deploy', [AdminDeployController::class, 'index'])->name('deploy.index');
    Route::post('/deploy/run', [AdminDeployController::class, 'runCommand'])->name('deploy.run');

    // Password Reset Tickets Management
    Route::get('/password-tickets', [AdminPasswordTicketController::class, 'index'])->name('password-tickets.index');
    Route::post('/password-tickets/{ticket}/approve', [AdminPasswordTicketController::class, 'approve'])->name('password-tickets.approve');
    Route::post('/password-tickets/{ticket}/reject', [AdminPasswordTicketController::class, 'reject'])->name('password-tickets.reject');

    // Referral Contests Management
    Route::get('/referral-contests', [\App\Http\Controllers\Admin\AdminReferralContestController::class, 'index'])->name('referral-contests.index');
    Route::post('/referral-contests', [\App\Http\Controllers\Admin\AdminReferralContestController::class, 'store'])->name('referral-contests.store');
    Route::post('/referral-contests/{contest}/distribute', [\App\Http\Controllers\Admin\AdminReferralContestController::class, 'distribute'])->name('referral-contests.distribute');
    Route::post('/referral-contests/{contest}/cancel', [\App\Http\Controllers\Admin\AdminReferralContestController::class, 'cancel'])->name('referral-contests.cancel');

    // General User Support Tickets Management
    Route::get('/support-tickets', [AdminSupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('/support-tickets/{ticket}', [AdminSupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('/support-tickets/{ticket}/reply', [AdminSupportTicketController::class, 'reply'])->name('support-tickets.reply');
    Route::post('/support-tickets/{ticket}/status', [AdminSupportTicketController::class, 'updateStatus'])->name('support-tickets.status');
});

