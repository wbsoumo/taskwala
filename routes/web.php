<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CampaignAllocationController;
use App\Http\Controllers\Admin\CampaignController as AdminCampaignController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinanceController as AdminFinanceController;
use App\Http\Controllers\Admin\PostbackController as AdminPostbackController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TrackingController as AdminTrackingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Tracking\RedirectController;
use App\Http\Controllers\User\Auth\LoginController as UserLoginController;
use App\Http\Controllers\User\Auth\RegisterController as UserRegisterController;
use App\Http\Controllers\User\CampaignController as UserCampaignController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\LinkGeneratorController;
use App\Http\Controllers\User\LinkManagementController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ReportController as UserReportController;
use App\Http\Controllers\User\WalletController as UserWalletController;
use Illuminate\Support\Facades\Route;

// 1. PUBLIC TRACKING & OFFER PAGE ROUTES
Route::get('/go/{token}', [RedirectController::class, 'redirect'])->name('tracking.redirect');
Route::post('/go/{token}/submit', [RedirectController::class, 'submitTask'])->name('tracking.submit');

// Fallback media asset handler for cPanel shared hosting
Route::get('/storage/campaigns/{filename}', function ($filename) {
    $path = storage_path('app/public/campaigns/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});

// 2. ROOT ROUTE REDIRECT
Route::get('/', function () {
    return redirect()->route('user.login');
});

// 3. ADMIN AUTHENTICATION & DASHBOARD REALM (/admin)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.login');
    });
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::middleware(['auth.admin'])->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Users Management
        Route::resource('users', AdminUserController::class);

        // Campaigns Management
        Route::resource('campaigns', AdminCampaignController::class);
        Route::post('campaigns/{campaign}/allocations', [CampaignAllocationController::class, 'update'])->name('campaigns.allocations.store');

        // Tracking & Conversions
        Route::get('/tracking/links', [AdminTrackingController::class, 'links'])->name('tracking.links');
        Route::get('/tracking/clicks', [AdminTrackingController::class, 'clicks'])->name('tracking.clicks');
        Route::get('/tracking/conversions', [AdminTrackingController::class, 'conversions'])->name('tracking.conversions');
        Route::get('/tracking/conversions/{conversion}', [AdminTrackingController::class, 'conversionDetail'])->name('tracking.conversions.detail');
        Route::post('/tracking/conversions/{conversion}/status', [AdminTrackingController::class, 'updateConversionStatus'])->name('tracking.conversions.status');

        // Postbacks Management
        Route::get('/postbacks/global', [AdminPostbackController::class, 'globalPostback'])->name('postbacks.global');
        Route::get('/postbacks/offer-wise', [AdminPostbackController::class, 'offerWisePostback'])->name('postbacks.offer_wise');
        Route::get('/postbacks/test', [AdminPostbackController::class, 'testPostbackForm'])->name('postbacks.test');
        Route::post('/postbacks/test', [AdminPostbackController::class, 'sendTestPostback'])->name('postbacks.test.send');
        Route::get('/postbacks/providers', [AdminPostbackController::class, 'providers'])->name('postbacks.providers');
        Route::post('/postbacks/providers', [AdminPostbackController::class, 'storeProvider']);
        Route::get('/postbacks/ip-whitelists', [AdminPostbackController::class, 'ipWhitelists'])->name('postbacks.ip_whitelists');
        Route::post('/postbacks/ip-whitelists', [AdminPostbackController::class, 'storeIpWhitelist']);
        Route::get('/postbacks/logs', [AdminPostbackController::class, 'logs'])->name('postbacks.logs');

        // Finance
        Route::get('/finance/wallet-ledger', [AdminFinanceController::class, 'ledger'])->name('finance.ledger');
        Route::get('/finance/customer-payouts', [AdminFinanceController::class, 'customerPayouts'])->name('finance.customer_payouts');

        // Reports
        Route::get('/reports/performance', [AdminReportController::class, 'performance'])->name('reports.performance');
        Route::get('/reports/performance/export', [AdminReportController::class, 'exportPerformanceCsv'])->name('reports.performance.export');
        Route::get('/reports/campaigns', [AdminReportController::class, 'campaigns'])->name('reports.campaigns');
        Route::get('/reports/campaigns/export', [AdminReportController::class, 'exportCampaignsCsv'])->name('reports.campaigns.export');
        Route::get('/reports/affiliates', [AdminReportController::class, 'affiliates'])->name('reports.affiliates');
        Route::get('/reports/financial', [AdminReportController::class, 'financial'])->name('reports.financial');

        // Security Logs
        Route::get('/security/audit-logs', [AdminSecurityController::class, 'auditLogs'])->name('security.audit_logs');
        Route::get('/security/login-logs', [AdminSecurityController::class, 'loginLogs'])->name('security.login_logs');

        // Settings
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    });
});

// 4. USER / AFFILIATE AUTHENTICATION & DASHBOARD REALM
Route::middleware(['web'])->group(function () {
    Route::get('/login', [UserLoginController::class, 'showLoginForm'])->name('user.login');
    Route::post('/login', [UserLoginController::class, 'login']);
    Route::get('/register', [UserRegisterController::class, 'showRegistrationForm'])->name('user.register');
    Route::post('/register', [UserRegisterController::class, 'register']);
    Route::post('/logout', [UserLoginController::class, 'logout'])->name('user.logout');

    Route::middleware(['auth.user'])->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');

        // User Campaigns
        Route::get('/campaigns', [UserCampaignController::class, 'index'])->name('user.campaigns.index');
        Route::get('/campaigns/{public_id}', [UserCampaignController::class, 'show'])->name('user.campaigns.show');

        // Link Generator & Management
        Route::get('/link-generator', [LinkGeneratorController::class, 'index'])->name('user.links.generator');
        Route::post('/link-generator/generate', [LinkGeneratorController::class, 'generate'])->name('user.links.generate');
        Route::get('/my-links', [LinkManagementController::class, 'index'])->name('user.links.index');
        Route::post('/my-links/{public_id}/disable', [LinkManagementController::class, 'disable'])->name('user.links.disable');

        // Reports
        Route::get('/clicks', [UserReportController::class, 'clicks'])->name('user.reports.clicks');
        Route::get('/conversions', [UserReportController::class, 'conversions'])->name('user.reports.conversions');
        Route::get('/reports/export', [UserReportController::class, 'exportCsv'])->name('user.reports.export');

        // Wallet & UPI & Profile
        Route::get('/wallet', [UserWalletController::class, 'index'])->name('user.wallet.index');
        Route::get('/upi', [\App\Http\Controllers\User\UpiController::class, 'index'])->name('user.upi.index');
        Route::post('/upi', [\App\Http\Controllers\User\UpiController::class, 'update'])->name('user.upi.update');
        Route::get('/profile', [ProfileController::class, 'index'])->name('user.profile.index');
        Route::post('/profile', [ProfileController::class, 'update'])->name('user.profile.update');
    });
});
