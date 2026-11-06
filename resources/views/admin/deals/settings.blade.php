@extends('layouts.admin')

@section('title', 'Deals Aggregation & API Settings')

@section('content')
<div class="row">
    <div class="col-lg-8 col-12">
        <form action="{{ route('admin.deals.settings.update') }}" method="POST">
            @csrf

            <!-- CARD 1: TELEGRAM CHANNELS & MTPROTO SCRAPER -->
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fab fa-telegram-plane text-primary mr-2 fa-lg"></i> Telegram Channels & Deals Scraper
                    </h3>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="deals_telegram_enabled" name="deals_telegram_enabled" value="1" {{ ($settings['deals_telegram_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold text-success" for="deals_telegram_enabled">Enabled</label>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Telegram MTProto Connection Status -->
                    @if(isset($telegramAccount) && $telegramAccount->is_connected)
                        <div class="alert alert-success border-0 shadow-sm py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-check-circle mr-1 text-success fa-lg"></i>
                                <strong>Telegram Connected:</strong> <span class="font-weight-bold">{{ $telegramAccount->phone_number }}</span>
                                <div class="small text-muted">
                                    Active via MTProto Scraper &bull; {{ $monitoredChannelsCount ?? 0 }} channel(s) monitored &bull; Personal account linked (No Bot token needed)
                                </div>
                            </div>
                            <a href="{{ route('admin.telegram_mtproto.index') }}" class="btn btn-sm btn-success font-weight-bold shadow-sm ml-2 text-nowrap">
                                <i class="fas fa-satellite-dish mr-1"></i> Manage Monitored Channels &rarr;
                            </a>
                        </div>
                    @else
                        <div class="alert alert-primary border-0 shadow-sm py-2 px-3 mb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fab fa-telegram-plane mr-1 fa-lg"></i>
                                <strong>Telegram MTProto Integration</strong>
                                <div class="small">Connect your personal Telegram account to monitor private channels, groups, and sync past history.</div>
                            </div>
                            <a href="{{ route('admin.telegram_mtproto.index') }}" class="btn btn-sm btn-white font-weight-bold shadow-sm bg-white text-primary text-nowrap ml-2">
                                Connect Telegram Account &rarr;
                            </a>
                        </div>
                    @endif

                    <!-- Configured Channels -->
                    <div class="form-group mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="font-weight-bold mb-0">Quick Public Channels (Fallback) <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-xs btn-outline-success font-weight-bold" id="btnTestChannel" onclick="testChannelScraping()">
                                <i class="fas fa-play mr-1"></i> Test Channel Scraping
                            </button>
                        </div>
                        <textarea name="deals_telegram_channels" id="telegramChannelsInput" rows="2" class="form-control" placeholder="@dealsmagnet, @lootdeals">{{ $settings['deals_telegram_channels'] ?? '@dealsmagnet, @lootdeals' }}</textarea>
                        <small class="text-muted">
                            Enter comma-separated public channel usernames (e.g. <code>@dealsmagnet</code>, <code>@lootdeals</code>).
                        </small>
                        <div id="channelTestResult" class="mt-2" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- CARD 2: EARNKARO API INTEGRATION -->
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fas fa-coins text-success mr-2 fa-lg"></i> EarnKaro Affiliate API Integration
                    </h3>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="deals_earnkaro_enabled" name="deals_earnkaro_enabled" value="1" {{ ($settings['deals_earnkaro_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold text-success" for="deals_earnkaro_enabled">Enabled</label>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Convert extracted e-commerce product links (Amazon, Flipkart, Myntra, Ajio, etc.) into EarnKaro affiliate links to generate revenue on every transaction.
                    </p>

                    <div class="row">
                        <!-- API Key -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">EarnKaro API Key / Bearer Token</label>
                                <div class="input-group">
                                    <input type="password" name="deals_earnkaro_api_key" id="earnkaroApiKey" class="form-control" placeholder="Enter API Key" value="{{ $settings['deals_earnkaro_api_key'] ?? '' }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('earnkaroApiKey', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">EarnKaro developer API token.</small>
                            </div>
                        </div>

                        <!-- Publisher ID -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">EarnKaro Publisher ID</label>
                                <input type="text" name="deals_earnkaro_publisher_id" id="earnkaroPublisherId" class="form-control" placeholder="e.g. EK123456 or 987654" value="{{ $settings['deals_earnkaro_publisher_id'] ?? '' }}">
                                <small class="text-muted">Used for automatic referral tracking fallback attribution.</small>
                            </div>
                        </div>
                    </div>

                    <!-- API Endpoint -->
                    <div class="form-group">
                        <label class="font-weight-bold">API Conversion Endpoint</label>
                        <input type="url" name="deals_earnkaro_api_endpoint" id="earnkaroEndpoint" class="form-control" value="{{ $settings['deals_earnkaro_api_endpoint'] ?? 'https://api.earnkaro.com/api/v1/deals/generate' }}">
                    </div>

                    <!-- Test Button -->
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-success font-weight-bold" id="btnTestEarnkaro" onclick="testEarnkaroConnection()">
                            <i class="fas fa-plug mr-1"></i> Test EarnKaro Connection
                        </button>
                        <div id="earnkaroTestResult" class="mt-2" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- CARD 3: AUTOMATION & SCHEDULER SETTINGS -->
            <div class="card card-outline card-warning shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fas fa-clock text-warning mr-2 fa-lg"></i> Automation & Synchronization Frequency
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Auto Fetch Interval -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Auto-Fetch Interval</label>
                                <select name="deals_auto_fetch_interval" class="form-control">
                                    <option value="5" {{ ($settings['deals_auto_fetch_interval'] ?? '15') == '5' ? 'selected' : '' }}>Every 5 Minutes (Real-time)</option>
                                    <option value="15" {{ ($settings['deals_auto_fetch_interval'] ?? '15') == '15' ? 'selected' : '' }}>Every 15 Minutes (Recommended)</option>
                                    <option value="30" {{ ($settings['deals_auto_fetch_interval'] ?? '15') == '30' ? 'selected' : '' }}>Every 30 Minutes</option>
                                    <option value="60" {{ ($settings['deals_auto_fetch_interval'] ?? '15') == '60' ? 'selected' : '' }}>Every 1 Hour</option>
                                    <option value="120" {{ ($settings['deals_auto_fetch_interval'] ?? '15') == '120' ? 'selected' : '' }}>Every 2 Hours</option>
                                    <option value="360" {{ ($settings['deals_auto_fetch_interval'] ?? '15') == '360' ? 'selected' : '' }}>Every 6 Hours</option>
                                </select>
                                <small class="text-muted">Executed automatically by Laravel Scheduler (<code>deals:sync</code>).</small>
                            </div>
                        </div>

                        <!-- Default Category -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Default Category</label>
                                <input type="text" name="deals_default_category" class="form-control" value="{{ $settings['deals_default_category'] ?? 'General' }}">
                                <small class="text-muted">Fallback category when auto-detection cannot find matching keywords.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-primary font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Save All Deals Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- SIDEBAR ACTIONS & LOGS -->
    <div class="col-lg-4 col-12">
        <!-- Manual Sync Card -->
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-bolt text-warning mr-1"></i> Manual Sync
                </h3>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Trigger an immediate fetch cycle across all configured Telegram channels, detect duplicates, and monetize links via EarnKaro.
                </p>
                <form action="{{ route('admin.deals.settings.sync_now') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Run Sync Now
                    </button>
                </form>
            </div>
        </div>

        <!-- Cron Instructions Card -->
        <div class="card card-outline card-secondary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-terminal text-secondary mr-1"></i> Server Cron Setup
                </h3>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2">
                    Add the following standard Laravel cron entry to your server crontab (e.g. cPanel Cron Jobs):
                </p>
                <div class="bg-dark text-white p-2 rounded small font-monospace mb-2" style="font-family: monospace;">
                    * * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1
                </div>
                <small class="text-muted">Or run the artisan command directly:</small>
                <div class="bg-light border p-2 rounded small mt-1 font-monospace" style="font-family: monospace;">
                    php artisan deals:sync
                </div>
            </div>
        </div>

        <!-- Recent Runs Mini Table -->
        <div class="card card-outline card-info shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-history text-info mr-1"></i> Recent Sync Runs
                </h3>
                <a href="{{ route('admin.deals.logs') }}" class="btn btn-xs btn-outline-info font-weight-bold">
                    View All
                </a>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="bg-light small">
                        <tr>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="small text-truncate" style="max-width: 90px;" title="{{ $log->channel }}">{{ $log->channel }}</td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge badge-success">OK</span>
                                    @elseif($log->status === 'partial')
                                        <span class="badge badge-warning">Partial</span>
                                    @else
                                        <span class="badge badge-danger">Failed</span>
                                    @endif
                                </td>
                                <td class="small font-weight-bold">{{ $log->deals_added }}</td>
                                <td class="small text-muted">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-3">No sync runs logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye';
        }
    }

    function testChannelScraping() {
        const rawChannels = document.getElementById('telegramChannelsInput').value;
        const resultDiv = document.getElementById('channelTestResult');
        const btn = document.getElementById('btnTestChannel');

        // Extract first channel
        const firstChannel = rawChannels.split(',')[0].trim();
        if (!firstChannel) {
            alert('Please enter at least one Telegram channel username (e.g. @dealsmagnet).');
            return;
        }

        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin mr-1"></i> Testing live deal scraping from ' + firstChannel + '...</span>';
        btn.disabled = true;

        fetch('{{ route("admin.deals.settings.test_channel") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                channel: firstChannel
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                let sampleHtml = '';
                if (data.sample) {
                    sampleHtml = '<div class="mt-2 p-2 bg-white rounded border text-dark font-weight-normal">' +
                        '<div><strong>Sample Scraped Deal:</strong> ' + (data.sample.title || 'N/A') + '</div>' +
                        '<div class="text-success font-weight-bold">Price: ' + (data.sample.price ? '₹' + data.sample.price : 'Special Price') +
                        (data.sample.discount_percent ? ' (' + data.sample.discount_percent + '% OFF)' : '') + '</div>' +
                        '<div class="text-muted small text-truncate">Link: ' + (data.sample.original_url || '') + '</div>' +
                        '</div>';
                }
                const alertClass = data.is_info ? 'alert-info' : 'alert-success';
                const iconClass = data.is_info ? 'fa-info-circle' : 'fa-check-circle';
                resultDiv.innerHTML = '<div class="alert ' + alertClass + ' py-2 px-3 small mb-0"><i class="fas ' + iconClass + ' mr-1"></i> <strong>' + data.message + '</strong>' + sampleHtml + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> ' + data.message + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            resultDiv.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-times-circle mr-1"></i> Request failed: ' + err + '</div>';
        });
    }

    function testEarnkaroConnection() {
        const apiKey = document.getElementById('earnkaroApiKey').value;
        const publisherId = document.getElementById('earnkaroPublisherId').value;
        const endpoint = document.getElementById('earnkaroEndpoint').value;
        const resultDiv = document.getElementById('earnkaroTestResult');
        const btn = document.getElementById('btnTestEarnkaro');

        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin mr-1"></i> Validating EarnKaro API & Publisher credentials...</span>';
        btn.disabled = true;

        fetch('{{ route("admin.deals.settings.test_earnkaro") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                api_key: apiKey,
                publisher_id: publisherId,
                endpoint: endpoint
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success py-2 px-3 small mb-0"><i class="fas fa-check-circle mr-1"></i> <strong>Success:</strong> ' + data.message + '</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-times-circle mr-1"></i> <strong>Error:</strong> ' + data.message + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            resultDiv.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-times-circle mr-1"></i> Request failed: ' + err + '</div>';
        });
    }
</script>
@endpush
