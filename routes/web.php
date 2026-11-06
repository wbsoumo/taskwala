<?php

use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\CampaignAllocationController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
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
use App\Http\Controllers\User\Auth\ForgotPasswordController as UserForgotPasswordController;
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
        Route::post('campaigns/{campaign}/collaterals', [AdminCampaignController::class, 'uploadCollateral'])->name('campaigns.collaterals.upload');
        Route::delete('campaigns/collaterals/{id}', [AdminCampaignController::class, 'deleteCollateral'])->name('campaigns.collaterals.delete');

        // Deals Management & Aggregation
        Route::get('/deals', [\App\Http\Controllers\Admin\DealController::class, 'index'])->name('deals.index');
        Route::get('/deals/create', [\App\Http\Controllers\Admin\DealController::class, 'create'])->name('deals.create');
        Route::post('/deals', [\App\Http\Controllers\Admin\DealController::class, 'store'])->name('deals.store');
        Route::get('/deals/{deal}/edit', [\App\Http\Controllers\Admin\DealController::class, 'edit'])->name('deals.edit');
        Route::put('/deals/{deal}', [\App\Http\Controllers\Admin\DealController::class, 'update'])->name('deals.update');
        Route::delete('/deals/{deal}', [\App\Http\Controllers\Admin\DealController::class, 'destroy'])->name('deals.destroy');
        Route::post('/deals/{deal}/toggle-status', [\App\Http\Controllers\Admin\DealController::class, 'toggleStatus'])->name('deals.toggle_status');
        Route::post('/deals/{deal}/retry-conversion', [\App\Http\Controllers\Admin\DealController::class, 'retryConversion'])->name('deals.retry_conversion');

        // Deals Settings & Synchronization
        Route::get('/deals-settings', [\App\Http\Controllers\Admin\DealSettingController::class, 'settings'])->name('deals.settings');
        Route::post('/deals-settings', [\App\Http\Controllers\Admin\DealSettingController::class, 'updateSettings'])->name('deals.settings.update');
        Route::post('/deals-settings/test-telegram', [\App\Http\Controllers\Admin\DealSettingController::class, 'testTelegram'])->name('deals.settings.test_telegram');
        Route::post('/deals-settings/test-channel', [\App\Http\Controllers\Admin\DealSettingController::class, 'testChannel'])->name('deals.settings.test_channel');
        Route::post('/deals-settings/test-earnkaro', [\App\Http\Controllers\Admin\DealSettingController::class, 'testEarnkaro'])->name('deals.settings.test_earnkaro');
        Route::post('/deals-settings/sync-now', [\App\Http\Controllers\Admin\DealSettingController::class, 'syncNow'])->name('deals.settings.sync_now');
        Route::get('/deals-logs', [\App\Http\Controllers\Admin\DealSettingController::class, 'logs'])->name('deals.logs');
        Route::post('/deals-logs/clear', [\App\Http\Controllers\Admin\DealSettingController::class, 'clearLogs'])->name('deals.logs.clear');

        // Telegram MTProto Integration (Telethon)
        Route::get('/telegram-mtproto', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'index'])->name('telegram_mtproto.index');
        Route::post('/telegram-mtproto/send-code', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'sendCode'])->name('telegram_mtproto.send_code');
        Route::post('/telegram-mtproto/verify-code', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'verifyCode'])->name('telegram_mtproto.verify_code');
        Route::post('/telegram-mtproto/verify-2fa', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'verify2FA'])->name('telegram_mtproto.verify_2fa');
        Route::post('/telegram-mtproto/disconnect', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'disconnect'])->name('telegram_mtproto.disconnect');
        Route::post('/telegram-mtproto/channels/fetch', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'fetchChannels'])->name('telegram_mtproto.channels.fetch');
        Route::post('/telegram-mtproto/channels/{id}/toggle', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'toggleChannelMonitoring'])->name('telegram_mtproto.channels.toggle');
        Route::post('/telegram-mtproto/channels/bulk', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'bulkUpdateMonitoring'])->name('telegram_mtproto.channels.bulk');
        Route::post('/telegram-mtproto/channels/{id}/sync-history', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'syncChannelHistory'])->name('telegram_mtproto.channels.sync_history');
        Route::post('/telegram-mtproto/sync-all', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'syncAllMonitored'])->name('telegram_mtproto.sync_all');
        Route::post('/telegram-mtproto/monitoring/start', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'startMonitoring'])->name('telegram_mtproto.monitoring.start');
        Route::post('/telegram-mtproto/monitoring/stop', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'stopMonitoring'])->name('telegram_mtproto.monitoring.stop');
        Route::get('/telegram-mtproto/messages', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'messages'])->name('telegram_mtproto.messages');
        Route::get('/telegram-mtproto/live-status', [\App\Http\Controllers\Admin\TelegramMtprotoController::class, 'liveStatus'])->name('telegram_mtproto.live_status');
        Route::get('/settings/telegram', function () {
            return redirect()->route('admin.telegram_mtproto.index');
        })->name('settings.telegram');

        // Missing Conversion Tickets
        Route::get('/tickets', [\App\Http\Controllers\Admin\MissingTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{id}', [\App\Http\Controllers\Admin\MissingTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{id}/status', [\App\Http\Controllers\Admin\MissingTicketController::class, 'updateStatus'])->name('tickets.update_status');

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
        Route::get('/finance/affiliate-payouts', [AdminFinanceController::class, 'affiliatePayouts'])->name('finance.affiliate_payouts');
        Route::post('/finance/affiliate-payouts/{id}/process', [AdminFinanceController::class, 'processAffiliatePayout'])->name('finance.affiliate_payouts.process');
        Route::get('/finance/wallet-ledger', [AdminFinanceController::class, 'ledger'])->name('finance.ledger');
        Route::get('/finance/customer-payouts', [AdminFinanceController::class, 'customerPayouts'])->name('finance.customer_payouts');
        Route::post('/finance/customer-payouts/{id}/update-status', [AdminFinanceController::class, 'updateCustomerPayoutStatus'])->name('finance.customer_payouts.update_status');
        Route::post('/finance/customer-payouts/bulk-update', [AdminFinanceController::class, 'bulkUpdateCustomerPayoutStatus'])->name('finance.customer_payouts.bulk_update');

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

        // App Push Notifications & Engagement
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/create', [AdminNotificationController::class, 'create'])->name('notifications.create');
        Route::post('/notifications', [AdminNotificationController::class, 'store'])->name('notifications.store');
        Route::get('/notifications/users', [AdminNotificationController::class, 'registeredUsers'])->name('notifications.users');
        Route::get('/notifications/{id}', [AdminNotificationController::class, 'show'])->name('notifications.show');
        Route::post('/notifications/user-notification/{id}/toggle-read', [AdminNotificationController::class, 'toggleUserReadStatus'])->name('notifications.toggle_read');

        // Settings
        Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/migrate', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $output = \Illuminate\Support\Facades\Artisan::output();
                return back()->with('success', 'Migrations completed: ' . $output);
            } catch (\Throwable $e) {
                return back()->with('error', 'Migration failed: ' . $e->getMessage());
            }
        })->name('settings.migrate');
    });
});

// Secure cPanel migration and maintenance helper
Route::get('/system/run-migrations/{token}', function ($token, \Illuminate\Http\Request $request) {
    if ($token !== 'taskwala_migrate_sec_9981') {
        abort(403, 'Unauthorized');
    }
    try {
        $pullOutput = '';
        if ($request->boolean('pull')) {
            $cmd = 'git pull origin main 2>&1';
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = @proc_open($cmd, $descriptors, $pipes, base_path());
            if (is_resource($proc)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($proc);
                $pullOutput = trim($stdout . "\n" . $stderr) . " (exit code {$exitCode})";
            } elseif (function_exists('shell_exec')) {
                $pullOutput = @shell_exec($cmd) ?? 'git pull executed.';
            }
        }

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $optimizeOutput = \Illuminate\Support\Facades\Artisan::output();

        if ($request->boolean('seed')) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoDataSeeder',
                '--force' => true,
            ]);
            $output .= "\n" . \Illuminate\Support\Facades\Artisan::output();
        }

        // Test python candidates
        $pythonDiagnostics = [];
        $runCmd = function ($commandLine) {
            $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = @proc_open($commandLine, $descriptors, $pipes, base_path());
            if (!is_resource($proc)) return 'proc_open failed';
            fclose($pipes[0]);
            $out = stream_get_contents($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($proc);
            return trim($out . ' ' . $err);
        };

        $pythonDiagnostics['python3_version'] = $runCmd('python3 --version');
        $pythonDiagnostics['usr_bin_python3'] = $runCmd('/usr/bin/python3 --version');
        $pythonDiagnostics['which_python3'] = $runCmd('which python3');
        $pythonDiagnostics['pip3_version'] = $runCmd('pip3 --version');
        $pythonDiagnostics['telethon_check'] = $runCmd('python3 -c "import telethon; print(telethon.__version__)"');
        $pythonDiagnostics['cli_status'] = $runCmd('python3 ' . base_path('python_service/telethon_cli.py check-status'));

        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $disabledFunctions = array_map('trim', $disabledFunctions);

        return response()->json([
            'success' => true,
            'message' => 'Executed successfully.',
            'git_pull' => $pullOutput,
            'migration_output' => $output,
            'optimize_output' => $optimizeOutput,
            'deals_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('deals'),
            'python_diagnostics' => $pythonDiagnostics,
            'cpanel_env' => [
                'php_version' => PHP_VERSION,
                'user' => get_current_user(),
                'base_path' => base_path(),
                'proc_open_enabled' => function_exists('proc_open') && !in_array('proc_open', $disabledFunctions),
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

Route::get('/system/debug-status/{token}', function ($token, \Illuminate\Http\Request $request) {
    if ($token !== 'taskwala_migrate_sec_9981') {
        abort(403, 'Unauthorized');
    }

    try {
        $logFile = storage_path('logs/laravel.log');
        $lastLog = '';
        if (file_exists($logFile) && filesize($logFile) > 0) {
            $fp = fopen($logFile, 'r');
            $size = filesize($logFile);
            $offset = max(0, $size - 6000);
            fseek($fp, $offset);
            $lastLog = fread($fp, 6000);
            fclose($fp);
        }

        $tgApi = 'untested';
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->get('https://api.telegram.org');
            $tgApi = 'HTTP ' . $res->status();
        } catch (\Throwable $e) {
            $tgApi = 'Error: ' . $e->getMessage();
        }

        $tgWeb = 'untested';
        try {
            $res = \Illuminate\Support\Facades\Http::timeout(3)->get('https://t.me/s/lootdeals');
            $tgWeb = 'HTTP ' . $res->status();
        } catch (\Throwable $e) {
            $tgWeb = 'Error: ' . $e->getMessage();
        }

        $tgAccount = \App\Models\TelegramAccount::first();

        return response()->json([
            'tg_api_reachability' => $tgApi,
            'tg_web_reachability' => $tgWeb,
            'deals_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('deals'),
            'deal_sync_logs_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('deal_sync_logs'),
            'campaign_count' => \App\Models\Campaign::count(),
            'user_count' => \App\Models\User::count(),
            'telegram_account' => $tgAccount ? [
                'id' => $tgAccount->id,
                'phone' => $tgAccount->phone_number,
                'status' => $tgAccount->status,
                'phone_code_hash' => $tgAccount->phone_code_hash,
                'has_session_string' => !empty($tgAccount->session_string),
                'is_connected' => $tgAccount->is_connected,
                'last_error' => $tgAccount->last_error,
            ] : null,
            'latest_log' => $lastLog,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

// 4. USER / AFFILIATE AUTHENTICATION & DASHBOARD REALM
Route::middleware(['web'])->group(function () {
    Route::get('/login', [UserLoginController::class, 'showLoginForm'])->name('user.login');
    Route::post('/login', [UserLoginController::class, 'login']);
    Route::get('/register', [UserRegisterController::class, 'showRegistrationForm'])->name('user.register');
    Route::post('/register', [UserRegisterController::class, 'register']);
    Route::post('/logout', [UserLoginController::class, 'logout'])->name('user.logout');
    Route::get('/forgot-password', [UserForgotPasswordController::class, 'showLinkRequestForm'])->name('user.password.request');
    Route::post('/forgot-password', [UserForgotPasswordController::class, 'sendResetOtp'])->name('user.password.email');
    Route::get('/reset-password', [UserForgotPasswordController::class, 'showResetForm'])->name('user.password.reset');
    Route::post('/reset-password', [UserForgotPasswordController::class, 'reset'])->name('user.password.update');

    Route::middleware(['auth.user'])->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');

        // Deals Aggregation & Browsing
        Route::get('/deals', [\App\Http\Controllers\User\DealController::class, 'index'])->name('user.deals.index');
        Route::get('/deals/{public_id}', [\App\Http\Controllers\User\DealController::class, 'show'])->name('user.deals.show');
        Route::get('/deals/{public_id}/out', [\App\Http\Controllers\User\DealController::class, 'redirect'])->name('user.deals.redirect');

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
        Route::post('/wallet/request-payout', [UserWalletController::class, 'requestPayout'])->name('user.wallet.request_payout');
        Route::get('/upi', [\App\Http\Controllers\User\UpiController::class, 'index'])->name('user.upi.index');
        Route::post('/upi', [\App\Http\Controllers\User\UpiController::class, 'update'])->name('user.upi.update');
        Route::get('/profile', [ProfileController::class, 'index'])->name('user.profile.index');
        Route::post('/profile', [ProfileController::class, 'update'])->name('user.profile.update');
    });
});
