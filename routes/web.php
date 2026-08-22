<?php

use App\Http\Controllers\Admin\AdminSiteController;
use App\Http\Controllers\Admin\AdminSiteContentController;
use App\Http\Controllers\Admin\AdminSiteAdController;
use App\Http\Controllers\Admin\AdminRevenueController;
use App\Http\Controllers\Admin\AdminSiteTypeController;
use App\Http\Controllers\Admin\AdminToolController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminTaskController;
use App\Http\Controllers\Admin\AdminTaskReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWithdrawalController;
use App\Http\Controllers\Admin\AdminCampaignController;
use App\Http\Controllers\Admin\AdminDeployController;
use App\Http\Controllers\Admin\AdminLevelController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ExternalSiteController;
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

// External Site Dynamic CMS, Article & Tool Routes
Route::get('/tools', [ExternalSiteController::class, 'toolsIndex'])->name('external.tools');
Route::get('/tools/{slug}', [ExternalSiteController::class, 'toolShow'])->name('external.tool.show');
Route::get('/articles', [ExternalSiteController::class, 'articles'])->name('external.articles');
Route::get('/blog', [ExternalSiteController::class, 'articles'])->name('external.blog');
Route::get('/promos', [ExternalSiteController::class, 'promosIndex'])->name('external.promos');
Route::get('/p/{slug}', [ExternalSiteController::class, 'page'])->name('external.page');

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

    // User Support Tickets
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::get('/support/create', [SupportTicketController::class, 'create'])->name('support.create');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/support/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');

    // Aliases for /tickets path compatibility
    Route::get('/tickets', [SupportTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [SupportTicketController::class, 'show'])->name('tickets.show');
    // Notifications API
    Route::post('/api/notifications/{notification}/read', [DashboardController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/api/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
});

// Admin Panel Routes
$adminPath = config('app.admin_path', 'secret-panel');
Route::prefix($adminPath)->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Campaigns & Campaign Services Management
    Route::get('/campaigns', [AdminCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/export', [AdminCampaignController::class, 'exportCsv'])->name('campaigns.export');
    Route::post('/campaigns/{campaign}/approve', [AdminCampaignController::class, 'approve'])->name('campaigns.approve');
    Route::post('/campaigns/{campaign}/reject', [AdminCampaignController::class, 'reject'])->name('campaigns.reject');
    Route::delete('/campaigns/{campaign}', [AdminCampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::get('/campaign-services', [AdminCampaignController::class, 'servicesIndex'])->name('campaign-services.index');
    Route::post('/campaign-services', [AdminCampaignController::class, 'storeService'])->name('campaign-services.store');
    Route::put('/campaign-services/{service}', [AdminCampaignController::class, 'updateService'])->name('campaign-services.update');
    Route::delete('/campaign-services/{service}', [AdminCampaignController::class, 'deleteService'])->name('campaign-services.destroy');

    // System Settings & Telegram Test
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/telegram-test', [AdminSettingsController::class, 'testTelegram'])->name('settings.telegram-test');

    // Cron Jobs Setup & Execution
    Route::get('/cron-jobs', [AdminSettingsController::class, 'cronJobs'])->name('cron-jobs.index');
    Route::post('/cron-jobs/run', [AdminSettingsController::class, 'runCronJob'])->name('cron-jobs.run');

    // Level Management
    Route::get('/levels', [AdminLevelController::class, 'index'])->name('levels.index');
    Route::post('/levels', [AdminLevelController::class, 'store'])->name('levels.store');
    Route::put('/levels/{level}', [AdminLevelController::class, 'update'])->name('levels.update');
    Route::delete('/levels/{level}', [AdminLevelController::class, 'destroy'])->name('levels.destroy');

    // Task Review Management
    Route::get('/tasks/reviews', [AdminTaskReviewController::class, 'index'])->name('tasks.reviews.index');
    Route::post('/tasks/reviews/bulk-approve', [AdminTaskReviewController::class, 'bulkApprove'])->name('tasks.reviews.bulk-approve');
    Route::post('/tasks/reviews/{userTask}/approve', [AdminTaskReviewController::class, 'approve'])->name('tasks.reviews.approve');
    Route::post('/tasks/reviews/{userTask}/reject', [AdminTaskReviewController::class, 'reject'])->name('tasks.reviews.reject');

    // Offerwalls Management
    Route::get('/offerwalls', [AdminOfferwallController::class, 'index'])->name('offerwalls.index');
    Route::post('/offerwalls', [AdminOfferwallController::class, 'store'])->name('offerwalls.store');
    Route::put('/offerwalls/{offerwall}', [AdminOfferwallController::class, 'update'])->name('offerwalls.update');
    Route::post('/offerwalls/{offerwall}/toggle', [AdminOfferwallController::class, 'toggleStatus'])->name('offerwalls.toggle');
    Route::delete('/offerwalls/{offerwall}', [AdminOfferwallController::class, 'destroy'])->name('offerwalls.destroy');

    // Tasks Management
    Route::get('/tasks', [AdminTaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [AdminTaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [AdminTaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{task}/edit', [AdminTaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{task}', [AdminTaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{task}/toggle', [AdminTaskController::class, 'toggleStatus'])->name('tasks.toggle');
    Route::delete('/tasks/{task}', [AdminTaskController::class, 'destroy'])->name('tasks.destroy');

    // Withdrawals Management
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/export-csv', [AdminWithdrawalController::class, 'exportCsv'])->name('withdrawals.export-csv');
    Route::post('/withdrawals/bulk-approve', [AdminWithdrawalController::class, 'bulkApprove'])->name('withdrawals.bulk-approve');
    Route::post('/withdrawals/bulk-delete', [AdminWithdrawalController::class, 'bulkDelete'])->name('withdrawals.bulk-delete');
    Route::post('/withdrawals/cleanup', [AdminWithdrawalController::class, 'cleanup'])->name('withdrawals.cleanup');
    Route::post('/withdrawals/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::delete('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'destroy'])->name('withdrawals.destroy');

    // Promo Codes Management
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
    Route::delete('/password-tickets/{ticket}', [AdminPasswordTicketController::class, 'destroy'])->name('password-tickets.destroy');

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

    // AD-SITE ENGINE Site Registry Management
    Route::get('/sites', [AdminSiteController::class, 'index'])->name('sites.index');
    Route::get('/sites/create', [AdminSiteController::class, 'create'])->name('sites.create');
    Route::post('/sites', [AdminSiteController::class, 'store'])->name('sites.store');
    Route::get('/sites/{site}/edit', [AdminSiteController::class, 'edit'])->name('sites.edit');
    Route::put('/sites/{site}', [AdminSiteController::class, 'update'])->name('sites.update');
    Route::post('/sites/{site}/toggle', [AdminSiteController::class, 'toggleStatus'])->name('sites.toggle');
    Route::delete('/sites/{site}', [AdminSiteController::class, 'destroy'])->name('sites.destroy');
    Route::post('/sites/{site}/domains', [AdminSiteController::class, 'storeDomain'])->name('sites.domains.store');
    Route::delete('/domains/{domain}', [AdminSiteController::class, 'destroyDomain'])->name('sites.domains.destroy');
    Route::post('/sites/{site}/settings', [AdminSiteController::class, 'updateSettings'])->name('sites.settings.update');

    // AD-SITE ENGINE Site Content Management
    Route::get('/sites/{site}/content', [AdminSiteContentController::class, 'index'])->name('sites.content.index');
    Route::post('/sites/{site}/pages', [AdminSiteContentController::class, 'storePage'])->name('sites.pages.store');
    Route::put('/sites/{site}/pages/{page}', [AdminSiteContentController::class, 'updatePage'])->name('sites.pages.update');
    Route::delete('/sites/{site}/pages/{page}', [AdminSiteContentController::class, 'destroyPage'])->name('sites.pages.destroy');

    Route::post('/sites/{site}/posts', [AdminSiteContentController::class, 'storePost'])->name('sites.posts.store');
    Route::put('/sites/{site}/posts/{post}', [AdminSiteContentController::class, 'updatePost'])->name('sites.posts.update');
    Route::delete('/sites/{site}/posts/{post}', [AdminSiteContentController::class, 'destroyPost'])->name('sites.posts.destroy');

    Route::post('/sites/{site}/categories', [AdminSiteContentController::class, 'storeCategory'])->name('sites.categories.store');
    Route::put('/sites/{site}/categories/{category}', [AdminSiteContentController::class, 'updateCategory'])->name('sites.categories.update');
    Route::delete('/sites/{site}/categories/{category}', [AdminSiteContentController::class, 'destroyCategory'])->name('sites.categories.destroy');

    // AD-SITE ENGINE Master Tool Registry & Site Attachments
    Route::get('/tools', [AdminToolController::class, 'index'])->name('tools.index');
    Route::post('/tools', [AdminToolController::class, 'storeTool'])->name('tools.store');
    Route::put('/tools/{tool}', [AdminToolController::class, 'updateTool'])->name('tools.update');
    Route::delete('/tools/{tool}', [AdminToolController::class, 'destroyTool'])->name('tools.destroy');

    Route::post('/tool-categories', [AdminToolController::class, 'storeCategory'])->name('tool-categories.store');
    Route::put('/tool-categories/{category}', [AdminToolController::class, 'updateCategory'])->name('tool-categories.update');
    Route::delete('/tool-categories/{category}', [AdminToolController::class, 'destroyCategory'])->name('tool-categories.destroy');

    Route::get('/sites/{site}/tools', [AdminToolController::class, 'siteTools'])->name('sites.tools.index');
    Route::post('/sites/{site}/tools/{tool}/toggle', [AdminToolController::class, 'toggleSiteTool'])->name('sites.tools.toggle');

    // AD-SITE ENGINE Site Ad Placements Control
    Route::get('/sites/{site}/ad-placements', [AdminSiteAdController::class, 'index'])->name('sites.ads.index');
    Route::post('/sites/{site}/ad-placements', [AdminSiteAdController::class, 'store'])->name('sites.ads.store');
    Route::post('/sites/{site}/ad-placements/{adPlacement}/toggle', [AdminSiteAdController::class, 'toggleStatus'])->name('sites.ads.toggle');
    Route::delete('/sites/{site}/ad-placements/{adPlacement}', [AdminSiteAdController::class, 'destroy'])->name('sites.ads.destroy');

    // AD-SITE ENGINE Revenue Engine & Cashflow Audit
    Route::get('/revenue', [AdminRevenueController::class, 'index'])->name('revenue.index');
    Route::post('/revenue/logs', [AdminRevenueController::class, 'storeRevenueLog'])->name('revenue.logs.store');

    Route::get('/revenue/publisher-accounts', [AdminRevenueController::class, 'publisherAccounts'])->name('revenue.publishers.index');
    Route::post('/revenue/publisher-accounts', [AdminRevenueController::class, 'storePublisherAccount'])->name('revenue.publishers.store');
    Route::put('/revenue/publisher-accounts/{account}', [AdminRevenueController::class, 'updatePublisherAccount'])->name('revenue.publishers.update');
    Route::delete('/revenue/publisher-accounts/{account}', [AdminRevenueController::class, 'destroyPublisherAccount'])->name('revenue.publishers.destroy');

    // Site Types
    Route::get('/site-types', [AdminSiteTypeController::class, 'index'])->name('site-types.index');
    Route::post('/site-types', [AdminSiteTypeController::class, 'store'])->name('site-types.store');
    Route::put('/site-types/{siteType}', [AdminSiteTypeController::class, 'update'])->name('site-types.update');
    Route::delete('/site-types/{siteType}', [AdminSiteTypeController::class, 'destroy'])->name('site-types.destroy');

    // Admin Notifications Broadcast Manager
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/send', [AdminNotificationController::class, 'send'])->name('notifications.send');
    Route::delete('/notifications/{notification}', [AdminNotificationController::class, 'destroy'])->name('notifications.destroy');
});
