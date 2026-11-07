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

// Universal media asset handler for cPanel shared hosting (storage symlink fallback)
$serveStorageAsset = function ($path) {
    // Strip any leading public/ or storage/ from path parameter
    $cleanPath = ltrim(preg_replace('#^(public/)?(storage/)?#', '', $path), '/');
    $safePath = str_replace(['..', "\0"], '', $cleanPath);

    $candidates = [
        storage_path('app/public/' . $safePath),
        base_path('storage/app/public/' . $safePath),
        public_path('storage/' . $safePath),
        base_path('public/storage/' . $safePath),
        public_path($safePath),
        base_path('public/' . $safePath),
        base_path('storage/' . $safePath),
    ];

    foreach ($candidates as $candidate) {
        if (!empty($candidate) && file_exists($candidate) && is_file($candidate)) {
            $mime = @mime_content_type($candidate) ?: 'application/octet-stream';
            if (str_ends_with(strtolower($candidate), '.webp')) $mime = 'image/webp';
            if (str_ends_with(strtolower($candidate), '.svg')) $mime = 'image/svg+xml';
            if (str_ends_with(strtolower($candidate), '.png')) $mime = 'image/png';
            if (str_ends_with(strtolower($candidate), '.jpg') || str_ends_with(strtolower($candidate), '.jpeg')) $mime = 'image/jpeg';
            if (str_ends_with(strtolower($candidate), '.mp4')) $mime = 'video/mp4';

            return response()->file($candidate, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=604800, immutable',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }
    }

    abort(404, 'Media file not found');
};

Route::get('/storage/{path}', $serveStorageAsset)->where('path', '.*');
Route::get('/public/storage/{path}', $serveStorageAsset)->where('path', '.*');

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
        Route::post('/deals/{deal}/refresh-metadata', [\App\Http\Controllers\Admin\DealController::class, 'refreshMetadata'])->name('deals.refresh_metadata');

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
        if ($request->boolean('pull') || $request->boolean('reset')) {
            $cmd = $request->boolean('reset') ? 'git fetch origin main && git reset --hard origin/main 2>&1' : 'git pull origin main 2>&1';
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

        try {
            // Clean up any double /public/storage/ prefixes in database
            \Illuminate\Support\Facades\DB::statement("UPDATE campaign_collaterals SET file_path = REPLACE(file_path, '/public/storage/', 'storage/') WHERE file_path LIKE '%/public/storage/%'");
            \Illuminate\Support\Facades\DB::statement("UPDATE campaign_collaterals SET file_path = TRIM(LEADING '/' FROM file_path) WHERE file_path LIKE '/%'");
            \Illuminate\Support\Facades\DB::statement("UPDATE campaigns SET logo_url = TRIM(LEADING '/' FROM logo_url) WHERE logo_url LIKE '/%'");
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE affiliate_links MODIFY status VARCHAR(32) NOT NULL DEFAULT 'active'");
            } catch (\Throwable $eAlter) {}

            // Ensure storage directories exist
            @mkdir(storage_path('app/public/collaterals'), 0755, true);
            @mkdir(storage_path('app/public/campaigns'), 0755, true);

            // Attempt symlink if missing
            if (!file_exists(public_path('storage'))) {
                @symlink(storage_path('app/public'), public_path('storage'));
                @\Illuminate\Support\Facades\Artisan::call('storage:link');
            }
        } catch (\Throwable $e) {}

        if ($request->boolean('seed')) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DemoDataSeeder',
                '--force' => true,
            ]);
            $output .= "\n" . \Illuminate\Support\Facades\Artisan::output();
        }

        if ($request->boolean('refresh_deals')) {
            \Illuminate\Support\Facades\Artisan::call('deals:refresh-metadata', [
                '--all' => $request->boolean('force_all'),
                '--limit' => (int) $request->get('limit', 100),
            ]);
            $output .= "\nDeals Metadata Refresh:\n" . \Illuminate\Support\Facades\Artisan::output();
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

        $pyBin = file_exists('/opt/alt/python310/bin/python3') ? '/opt/alt/python310/bin/python3' : 'python3';
        $pythonDiagnostics['git_commit'] = $runCmd('git log -1 --oneline');
        $pythonDiagnostics['py_bin'] = $pyBin;
        $pythonDiagnostics['telethon_cli_status'] = $runCmd($pyBin . ' ' . base_path('python_service/telethon_cli.py check-status'));
        $pythonDiagnostics['socket_test_dc5_443'] = $runCmd($pyBin . ' -c "import socket; s = socket.socket(); s.settimeout(5); s.connect((\'91.108.56.178\', 443)); print(\'connected\')"');
        $pythonDiagnostics['socket_test_dc5_80'] = $runCmd($pyBin . ' -c "import socket; s = socket.socket(); s.settimeout(5); s.connect((\'91.108.56.178\', 80)); print(\'connected\')"');
        $pythonDiagnostics['socket_test_dc2_443'] = $runCmd($pyBin . ' -c "import socket; s = socket.socket(); s.settimeout(5); s.connect((\'149.154.167.51\', 443)); print(\'connected\')"');
        $pythonDiagnostics['socket_test_dc2_80'] = $runCmd($pyBin . ' -c "import socket; s = socket.socket(); s.settimeout(5); s.connect((\'149.154.167.51\', 80)); print(\'connected\')"');

        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $disabledFunctions = array_map('trim', $disabledFunctions);

        return response()->json([
            'success' => true,
            'message' => 'Executed successfully.',
            'git_pull' => $pullOutput,
            'migration_output' => $output,
            'optimize_output' => $optimizeOutput,
            'deals_count' => \App\Models\Deal::count(),
            'deals_with_images' => \App\Models\Deal::whereNotNull('image_url')->where('image_url', '!=', '')->count(),
            'deals_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('deals'),
            'collaterals_diagnostic' => \App\Models\CampaignCollateral::latest()->limit(5)->get()->map(function($c) {
                $clean = ltrim(preg_replace('#^(public/)?(storage/)?#', '', $c->file_path ?? ''), '/');
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'file_path' => $c->file_path,
                    'media_url' => $c->media_url,
                    'file_exists_storage_app' => !empty($clean) && file_exists(storage_path('app/public/' . $clean)),
                    'file_exists_public_storage' => !empty($clean) && file_exists(public_path('storage/' . $clean)),
                ];
            }),
            'disk_storage_files' => @scandir(storage_path('app/public/collaterals')) ?: [],
            'campaigns_diagnostic' => \App\Models\Campaign::limit(5)->get()->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'logo_url' => $c->logo_url,
                    'logo' => $c->logo,
                ];
            }),
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

// Dedicated endpoint to sync all old deals images, MRP, and prices
Route::get('/system/sync-deals-metadata/{token}', function ($token, \Illuminate\Http\Request $request) {
    if ($token !== 'taskwala_migrate_sec_9981') {
        abort(403, 'Unauthorized');
    }

    try {
        $limit = (int) $request->get('limit', 15);
        $force = $request->boolean('all');
        $missingOnly = $request->boolean('missing_only', true);
        $aggregator = app(\App\Services\Deals\DealAggregatorService::class);

        if ($request->boolean('reset_all')) {
            \App\Models\Deal::query()->update(['updated_at' => now()->subDays(10)]);
        }

        if ($request->boolean('fix_remaining', true)) {
            $d92 = \App\Models\Deal::find(92);
            if ($d92 && empty($d92->image_url)) {
                $d92->update([
                    'title' => 'MAYBELLINE NEW YORK Call Me Colossal Bold Eye Kit',
                    'image_url' => 'https://rukminim2.flixcart.com/image/832/832/xif0q/combo-kit/8/9/d/-original-imahfr8akqjhdhnu.jpeg',
                    'price' => 199.00,
                    'original_price' => 399.00,
                    'discount_percent' => 50,
                ]);
            }

            $d16 = \App\Models\Deal::find(16);
            if ($d16 && empty($d16->image_url)) {
                $d16->update([
                    'title' => 'MOTOROLA Pad 60 Neo 8 GB RAM 128 GB ROM 11 inch Wi-Fi+5G Tablet',
                    'image_url' => 'https://rukminim2.flixcart.com/image/832/832/xif0q/tablet/2/3/h/-enriched-transparent-original-imahfsrkzrhehhyy.png',
                    'price' => 23999.00,
                    'original_price' => 60000.00,
                    'discount_percent' => 60,
                ]);
            }

            $d62 = \App\Models\Deal::find(62);
            if ($d62 && empty($d62->image_url)) {
                $d62->update([
                    'title' => 'Get ₹100-300 Cashback On Amazon Fresh Orders',
                    'image_url' => 'https://m.media-amazon.com/images/I/41K8L8UfBVL.jpg',
                    'price' => 100.00,
                ]);
            }
        }

        $query = \App\Models\Deal::query();
        if ($request->filled('deal_id')) {
            $query->where('id', (int) $request->get('deal_id'));
        } elseif (!$force) {
            if ($missingOnly) {
                $query->where(function ($q) {
                    $q->whereNull('image_url')
                      ->orWhere('image_url', '');
                });
            } else {
                $query->where(function ($q) {
                    $q->whereNull('image_url')
                      ->orWhere('image_url', '')
                      ->orWhereNull('original_price')
                      ->orWhereNull('price');
                });
            }
        }

        $totalMatching = $query->count();
        // Order by updated_at ASC to cycle through deals that haven't been attempted yet
        $deals = $query->orderBy('updated_at', 'asc')->take($limit)->get();

        $updated = [];
        $failed = [];

        foreach ($deals as $deal) {
            $res = $aggregator->enrichDealMetadata($deal, $force);
            if ($res['success'] && !empty($res['deal']->image_url)) {
                $fresh = $res['deal'];
                $updated[] = [
                    'id' => $fresh->id,
                    'title' => $fresh->title,
                    'merchant' => $fresh->merchant_name,
                    'image_url' => $fresh->image_url,
                    'price' => $fresh->price,
                    'original_price' => $fresh->original_price,
                    'discount_percent' => $fresh->discount_percent,
                ];
            } else {
                // Touch the deal so it moves to the back of the queue and doesn't get stuck in a loop
                $deal->touch();
                $failed[] = [
                    'id' => $deal->id,
                    'title' => $deal->title,
                    'original_url' => $deal->original_url,
                    'error' => $res['error'] ?? 'No image extracted or link inaccessible',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'total_deals_in_database' => \App\Models\Deal::count(),
            'total_deals_with_images' => \App\Models\Deal::whereNotNull('image_url')->where('image_url', '!=', '')->count(),
            'total_deals_without_images' => \App\Models\Deal::where(function ($q) {
                $q->whereNull('image_url')->orWhere('image_url', '');
            })->count(),
            'total_deals_needing_enrichment' => $totalMatching,
            'processed_count' => $deals->count(),
            'updated_count' => count($updated),
            'failed_count' => count($failed),
            'updated' => $updated,
            'failed' => $failed,
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

        $totalDeals = \App\Models\Deal::count();
        $dealsWithImages = \App\Models\Deal::whereNotNull('image_url')->where('image_url', '!=', '')->count();
        $dealsWithoutImages = \App\Models\Deal::where(function($q) {
            $q->whereNull('image_url')->orWhere('image_url', '');
        })->count();

        return response()->json([
            'tg_api_reachability' => $tgApi,
            'tg_web_reachability' => $tgWeb,
            'deals_table_exists' => \Illuminate\Support\Facades\Schema::hasTable('deals'),
            'total_deals' => $totalDeals,
            'deals_with_images' => $dealsWithImages,
            'deals_without_images' => $dealsWithoutImages,
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
