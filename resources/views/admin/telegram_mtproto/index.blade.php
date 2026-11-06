@extends('layouts.admin')

@section('title', 'Telegram MTProto Integration (Telethon)')

@section('content')
<div class="row">
    <!-- LEFT COLUMN: ACCOUNT STATUS & CONNECTION WIZARD -->
    <div class="col-lg-4 col-12">
        <!-- CARD 1: ACCOUNT CONNECTION STATUS -->
        <div class="card card-outline {{ ($account && $account->is_connected) ? 'card-success' : 'card-primary' }} shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fab fa-telegram-plane text-primary mr-1 fa-lg"></i> Telegram Account
                </h3>
                @if($account && $account->is_connected)
                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Connected</span>
                @else
                    <span class="badge badge-secondary px-2 py-1">Disconnected</span>
                @endif
            </div>

            <div class="card-body">
                @if($account && $account->is_connected)
                    <div class="text-center py-2">
                        <div class="rounded-circle bg-primary text-white d-inline-flex justify-content-center align-items-center shadow-sm mb-2" style="width: 65px; height: 65px; font-size: 24px; font-weight: bold;">
                            {{ strtoupper(substr($account->first_name ?: 'T', 0, 1)) }}
                        </div>
                        <h5 class="font-weight-bold mb-0 text-dark">{{ $account->full_name }}</h5>
                        <p class="text-muted small mb-2">{{ $account->display_username }}</p>

                        <div class="p-2 bg-light rounded text-left small border mb-3">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Phone:</span>
                                <span class="font-weight-bold">{{ $account->phone_number }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Telegram ID:</span>
                                <span class="font-monospace text-dark">{{ $account->telegram_user_id ?: 'N/A' }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Connected:</span>
                                <span class="text-dark">{{ $account->last_connected_at ? $account->last_connected_at->diffForHumans() : 'Active' }}</span>
                            </div>
                        </div>

                        <form action="{{ route('admin.telegram_mtproto.disconnect') }}" method="POST" onsubmit="return confirm('Disconnect this Telegram account? Monitoring will be paused.');">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm btn-block font-weight-bold">
                                <i class="fas fa-sign-out-alt mr-1"></i> Disconnect Account
                            </button>
                        </form>
                    </div>
                @else
                    <!-- CONNECTION WIZARD -->
                    <div id="authWizard">
                        <!-- STEP 1: PHONE & API CREDENTIALS -->
                        <div id="stepPhone">
                            <p class="small text-muted mb-3">
                                Connect your personal Telegram account via MTProto to monitor channels you are joined in, with full access to message history and live media.
                            </p>

                            <div class="form-group">
                                <label class="font-weight-bold small">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" id="inputPhone" class="form-control" placeholder="+919876543210" value="{{ $account->phone_number ?? '' }}">
                                <small class="text-muted">Include country code (e.g. +91 for India).</small>
                            </div>

                            <div class="form-group mb-2">
                                <a class="small text-muted font-weight-bold" data-toggle="collapse" href="#advancedApiCreds" role="button">
                                    <i class="fas fa-sliders-h mr-1"></i> Custom API ID & Hash (Optional) <i class="fas fa-chevron-down ml-1"></i>
                                </a>
                                <div class="collapse mt-2" id="advancedApiCreds">
                                    <div class="p-2 bg-light border rounded small">
                                        <div class="form-group mb-2">
                                            <label class="font-weight-bold mb-1">Telegram API ID</label>
                                            <input type="text" id="inputApiId" class="form-control form-control-sm" placeholder="2040" value="{{ $account->api_id ?? '' }}">
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold mb-1">Telegram API Hash</label>
                                            <input type="password" id="inputApiHash" class="form-control form-control-sm" placeholder="b18441a1ff607e10a989891a5462e627" value="{{ $account->api_hash ?? '' }}">
                                        </div>
                                        <small class="text-muted d-block mt-1">Obtain from <a href="https://my.telegram.org" target="_blank">my.telegram.org</a>. Leave blank to use default.</small>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-block font-weight-bold mt-3 shadow-sm" id="btnSendCode" onclick="submitSendCode()">
                                <i class="fas fa-paper-plane mr-1"></i> Send Telegram OTP
                            </button>
                        </div>

                        <!-- STEP 2: ENTER OTP CODE -->
                        <div id="stepCode" style="display: none;">
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <i class="fas fa-info-circle mr-1"></i> Login code sent to <strong id="sentPhoneDisplay"></strong>. Check your Telegram app.
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small">Telegram OTP Code <span class="text-danger">*</span></label>
                                <input type="text" id="inputOtpCode" class="form-control form-control-lg text-center font-weight-bold" placeholder="12345" maxlength="8" style="letter-spacing: 4px;">
                            </div>

                            <button type="button" class="btn btn-success btn-block font-weight-bold shadow-sm" id="btnVerifyCode" onclick="submitVerifyCode()">
                                <i class="fas fa-check mr-1"></i> Verify & Connect
                            </button>

                            <button type="button" class="btn btn-link btn-block btn-sm text-muted mt-2" onclick="backToPhoneStep()">
                                <i class="fas fa-arrow-left mr-1"></i> Change Phone Number
                            </button>
                        </div>

                        <!-- STEP 3: 2FA PASSWORD -->
                        <div id="step2FA" style="display: none;">
                            <div class="alert alert-warning py-2 px-3 small mb-3">
                                <i class="fas fa-shield-alt mr-1"></i> <strong>2-Step Verification Enabled</strong>: Please enter your Telegram cloud password.
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold small">2FA Cloud Password <span class="text-danger">*</span></label>
                                <input type="password" id="input2FAPassword" class="form-control" placeholder="Enter your 2FA password">
                            </div>

                            <button type="button" class="btn btn-primary btn-block font-weight-bold shadow-sm" id="btnVerify2FA" onclick="submitVerify2FA()">
                                <i class="fas fa-unlock mr-1"></i> Submit 2FA Password
                            </button>
                        </div>

                        <div id="authAlert" class="mt-3" style="display: none;"></div>
                    </div>
                @endif
            </div>
        </div>

        <!-- CARD 2: PYTHON BACKGROUND DAEMON STATUS -->
        <div class="card card-outline {{ ($serviceHealth['online'] ?? false) ? 'card-success' : 'card-warning' }} shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fab fa-python text-warning mr-1"></i> Telethon Daemon
                </h3>
                @if($serviceHealth['online'] ?? false)
                    <span class="badge badge-success px-2 py-1"><i class="fas fa-circle text-white fa-xs mr-1"></i> Online</span>
                @else
                    <span class="badge badge-warning px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i> Offline</span>
                @endif
            </div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Service Engine:</span>
                    <span class="font-weight-bold">Python Telethon 1.45</span>
                </div>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted">Live Listener:</span>
                    @if($serviceHealth['monitoring'] ?? false)
                        <span class="badge badge-success">Active & Streaming</span>
                    @else
                        <span class="badge badge-secondary">Stopped</span>
                    @endif
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-muted">Channels Streamed:</span>
                    <span class="font-weight-bold">{{ count($serviceHealth['monitored_channels'] ?? []) }}</span>
                </div>

                @if(!($serviceHealth['online'] ?? false))
                    <div class="alert alert-light border mt-3 mb-0 small">
                        <strong>To Start Service:</strong> Run in terminal:
                        <div class="bg-dark text-white p-2 rounded mt-1 font-monospace" style="font-family: monospace;">
                            ./python_service/venv/bin/python3 python_service/server.py
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: CHANNELS SELECTION & SCRAPING MANAGEMENT -->
    <div class="col-lg-8 col-12">
        <!-- TOP CONTROL BAR -->
        <div class="card shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-6 col-12 mb-2 mb-md-0">
                        <h5 class="font-weight-bold mb-0 text-dark">
                            <i class="fas fa-satellite-dish text-primary mr-2"></i> Channel Monitoring & Scraping
                        </h5>
                        <small class="text-muted">Select channels to monitor and scrape deals automatically.</small>
                    </div>
                    <div class="col-md-6 col-12 text-md-right">
                        <!-- Fetch Channels Button -->
                        <form action="{{ route('admin.telegram_mtproto.channels.fetch') }}" method="POST" class="d-inline-block mr-1">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm font-weight-bold shadow-sm" {{ !($account && $account->is_connected) ? 'disabled' : '' }}>
                                <i class="fas fa-sync-alt mr-1"></i> Refresh Channels
                            </button>
                        </form>

                        <!-- Start / Stop Monitoring Buttons -->
                        @if($serviceHealth['monitoring'] ?? false)
                            <form action="{{ route('admin.telegram_mtproto.monitoring.stop') }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm font-weight-bold shadow-sm">
                                    <i class="fas fa-stop mr-1"></i> Stop Monitoring
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.telegram_mtproto.monitoring.start') }}" method="POST" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm font-weight-bold shadow-sm" {{ ($stats['monitored_channels'] ?? 0) === 0 ? 'disabled' : '' }}>
                                    <i class="fas fa-play mr-1"></i> Start Monitoring ({{ $stats['monitored_channels'] ?? 0 }})
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.telegram_mtproto.messages') }}" class="btn btn-outline-info btn-sm font-weight-bold ml-1 shadow-sm">
                            <i class="fas fa-stream mr-1"></i> Live Feed
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATS COUNTER TILES -->
        <div class="row mb-3">
            <div class="col-md-3 col-6">
                <div class="info-box bg-white shadow-sm border py-2 mb-3">
                    <span class="info-box-icon bg-primary text-white rounded"><i class="fas fa-bullhorn"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">Total Channels</span>
                        <span class="info-box-number text-dark font-weight-bold">{{ $stats['total_channels'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-box bg-white shadow-sm border py-2 mb-3">
                    <span class="info-box-icon bg-success text-white rounded"><i class="fas fa-eye"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">Monitored</span>
                        <span class="info-box-number text-success font-weight-bold">{{ $stats['monitored_channels'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-box bg-white shadow-sm border py-2 mb-3">
                    <span class="info-box-icon bg-info text-white rounded"><i class="fas fa-envelope"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">Messages Scraped</span>
                        <span class="info-box-number text-info font-weight-bold" id="counterTotalMessages">{{ $stats['total_messages'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="info-box bg-white shadow-sm border py-2 mb-3">
                    <span class="info-box-icon bg-warning text-white rounded"><i class="fas fa-tags"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted small">Deals Captured</span>
                        <span class="info-box-number text-warning font-weight-bold" id="counterTotalDeals">{{ $stats['total_deals'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHANNELS LIST & FILTER -->
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <form action="{{ route('admin.telegram_mtproto.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5 col-12 mb-2 mb-md-0">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Search channels by title or username..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <select name="type" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all">All Channel Types</option>
                            <option value="channel" {{ request('type') === 'channel' ? 'selected' : '' }}>Broadcast Channels</option>
                            <option value="supergroup" {{ request('type') === 'supergroup' ? 'selected' : '' }}>Supergroups</option>
                            <option value="group" {{ request('type') === 'group' ? 'selected' : '' }}>Groups</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-6 mb-2 mb-md-0">
                        <select name="monitored" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="1" {{ request('monitored') === '1' ? 'selected' : '' }}>Monitored Only</option>
                            <option value="0" {{ request('monitored') === '0' ? 'selected' : '' }}>Not Monitored</option>
                        </select>
                    </div>
                    <div class="col-md-1 col-12 text-right">
                        @if(request()->hasAny(['search', 'type', 'monitored']))
                            <a href="{{ route('admin.telegram_mtproto.index') }}" class="btn btn-outline-secondary btn-sm" title="Clear Filters">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- BULK ACTIONS FORM -->
            <form action="{{ route('admin.telegram_mtproto.channels.bulk') }}" method="POST" id="bulkChannelsForm">
                @csrf
                <div class="card-body p-0 table-responsive">
                    <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div class="custom-control custom-checkbox small">
                            <input type="checkbox" class="custom-control-input" id="checkAllChannels" onclick="toggleSelectAll(this)">
                            <label class="custom-control-label font-weight-bold" for="checkAllChannels">Select All on Page</label>
                        </div>
                        <div>
                            <button type="submit" name="action" value="monitor" class="btn btn-xs btn-success font-weight-bold mr-1">
                                <i class="fas fa-check mr-1"></i> Monitor Selected
                            </button>
                            <button type="submit" name="action" value="stop" class="btn btn-xs btn-outline-danger font-weight-bold">
                                <i class="fas fa-ban mr-1"></i> Stop Selected
                            </button>
                        </div>
                    </div>

                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Channel / Group</th>
                                <th>Type</th>
                                <th>Subscribers</th>
                                <th>Messages</th>
                                <th>Deals</th>
                                <th>Monitoring</th>
                                <th class="text-right">History Sync</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($channels as $ch)
                                <tr id="channelRow_{{ $ch->id }}">
                                    <td>
                                        <input type="checkbox" name="channel_ids[]" value="{{ $ch->id }}" class="channel-checkbox">
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark text-truncate" style="max-width: 220px;" title="{{ $ch->title }}">
                                            {{ $ch->title }}
                                        </div>
                                        @if($ch->username)
                                            <a href="{{ $ch->telegram_link }}" target="_blank" class="small text-primary font-weight-bold">
                                                <i class="fab fa-telegram-plane fa-xs"></i> @ {{ $ch->username }}
                                            </a>
                                        @else
                                            <span class="small text-muted">Private / ID: {{ $ch->channel_id }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light border text-muted small">{{ $ch->formatted_type }}</span>
                                    </td>
                                    <td class="small text-muted font-weight-bold">
                                        {{ $ch->participants_count ? number_format($ch->participants_count) : '-' }}
                                    </td>
                                    <td class="small font-weight-bold text-info">
                                        {{ number_format($ch->total_messages_imported) }}
                                    </td>
                                    <td class="small font-weight-bold text-success">
                                        {{ number_format($ch->total_deals_imported) }}
                                    </td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="switchMonitored_{{ $ch->id }}" {{ $ch->is_monitored ? 'checked' : '' }} onchange="toggleSingleChannelMonitoring({{ $ch->id }}, this)">
                                            <label class="custom-control-label" for="switchMonitored_{{ $ch->id }}"></label>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold" onclick="openSyncModal({{ $ch->id }}, '{{ addslashes($ch->title) }}')">
                                            <i class="fas fa-history mr-1"></i> Sync Past
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-bullhorn fa-3x mb-3 text-secondary d-block"></i>
                                        <strong>No channels found.</strong><br>
                                        @if($account && $account->is_connected)
                                            Click <strong>"Refresh Channels"</strong> above to fetch all channels from your Telegram account.
                                        @else
                                            Connect your personal Telegram account on the left to load accessible channels.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if($channels->hasPages())
                <div class="card-footer bg-light py-2">
                    {{ $channels->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- MODAL: SYNC HISTORICAL MESSAGES -->
<div class="modal fade" id="syncHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-history mr-1"></i> Sync Historical Messages
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Taskwala will use Telethon MTProto to fetch past messages from <strong id="syncModalChannelTitle" class="text-dark"></strong>, extract product offers, download images, and convert links to EarnKaro affiliate deals.
                </p>

                <div class="form-group">
                    <label class="font-weight-bold small">Number of Past Messages to Import</label>
                    <select id="syncHistoryLimit" class="form-control">
                        <option value="50">Last 50 Messages (Fast)</option>
                        <option value="100" selected>Last 100 Messages (Recommended)</option>
                        <option value="250">Last 250 Messages</option>
                        <option value="500">Last 500 Messages (Deep Sync)</option>
                    </select>
                </div>

                <div id="syncProgressContainer" class="mt-3" style="display: none;">
                    <div class="d-flex justify-content-between small font-weight-bold mb-1">
                        <span id="syncProgressText">Importing messages...</span>
                        <span class="text-primary"><i class="fas fa-spinner fa-spin"></i></span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                    </div>
                </div>

                <div id="syncModalAlert" class="mt-2" style="display: none;"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm font-weight-bold" id="btnConfirmSync" onclick="executeChannelSync()">
                    <i class="fas fa-play mr-1"></i> Start Import Now
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let activeSyncChannelId = null;

    // Toggle Select All checkboxes
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.channel-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
    }

    // Step 1: Send OTP Code
    function submitSendCode() {
        const phone = document.getElementById('inputPhone').value.trim();
        const apiId = document.getElementById('inputApiId').value.trim();
        const apiHash = document.getElementById('inputApiHash').value.trim();
        const btn = document.getElementById('btnSendCode');
        const alertBox = document.getElementById('authAlert');

        if (!phone) {
            alert('Please enter your phone number with country code (e.g. +919876543210)');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending OTP via Telegram...';
        alertBox.style.display = 'none';

        fetch('{{ route("admin.telegram_mtproto.send_code") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ phone: phone, api_id: apiId, api_hash: apiHash })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Send Telegram OTP';

            if (data.success) {
                document.getElementById('stepPhone').style.display = 'none';
                document.getElementById('stepCode').style.display = 'block';
                document.getElementById('sentPhoneDisplay').innerText = data.phone;
                document.getElementById('inputOtpCode').focus();
            } else {
                alertBox.style.display = 'block';
                alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> ' + data.message + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Send Telegram OTP';
            alertBox.style.display = 'block';
            alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Request failed: ' + err + '</div>';
        });
    }

    // Back to Phone step
    function backToPhoneStep() {
        document.getElementById('stepCode').style.display = 'none';
        document.getElementById('step2FA').style.display = 'none';
        document.getElementById('stepPhone').style.display = 'block';
    }

    // Step 2: Verify Code
    function submitVerifyCode() {
        const code = document.getElementById('inputOtpCode').value.trim();
        const phone = document.getElementById('inputPhone').value.trim();
        const btn = document.getElementById('btnVerifyCode');
        const alertBox = document.getElementById('authAlert');

        if (!code) {
            alert('Please enter the verification code received on Telegram.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Verifying Code...';
        alertBox.style.display = 'none';

        fetch('{{ route("admin.telegram_mtproto.verify_code") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ phone: phone, code: code })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Verify & Connect';

            if (data.success) {
                if (data.needs_2fa) {
                    document.getElementById('stepCode').style.display = 'none';
                    document.getElementById('step2FA').style.display = 'block';
                    document.getElementById('input2FAPassword').focus();
                } else {
                    location.reload();
                }
            } else {
                alertBox.style.display = 'block';
                alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-times-circle mr-1"></i> ' + data.message + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Verify & Connect';
            alertBox.style.display = 'block';
            alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Request failed: ' + err + '</div>';
        });
    }

    // Step 3: Verify 2FA Password
    function submitVerify2FA() {
        const password = document.getElementById('input2FAPassword').value;
        const btn = document.getElementById('btnVerify2FA');
        const alertBox = document.getElementById('authAlert');

        if (!password) {
            alert('Please enter your Two-Step Verification password.');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Verifying 2FA...';
        alertBox.style.display = 'none';

        fetch('{{ route("admin.telegram_mtproto.verify_2fa") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ password: password })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock mr-1"></i> Submit 2FA Password';

            if (data.success) {
                location.reload();
            } else {
                alertBox.style.display = 'block';
                alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-times-circle mr-1"></i> ' + data.message + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-unlock mr-1"></i> Submit 2FA Password';
            alertBox.style.display = 'block';
            alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Request failed: ' + err + '</div>';
        });
    }

    // Toggle Single Channel Monitoring Switch
    function toggleSingleChannelMonitoring(channelId, checkbox) {
        fetch('{{ url("admin/telegram-mtproto/channels") }}/' + channelId + '/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                checkbox.checked = !checkbox.checked;
                alert('Failed to toggle monitoring: ' + (data.message || 'Error'));
            }
        })
        .catch(err => {
            checkbox.checked = !checkbox.checked;
            alert('Error toggling monitoring.');
        });
    }

    // Open Sync Modal
    function openSyncModal(channelId, channelTitle) {
        activeSyncChannelId = channelId;
        document.getElementById('syncModalChannelTitle').innerText = channelTitle;
        document.getElementById('syncProgressContainer').style.display = 'none';
        document.getElementById('syncModalAlert').style.display = 'none';
        document.getElementById('btnConfirmSync').disabled = false;
        $('#syncHistoryModal').modal('show');
    }

    // Execute Channel Sync
    function executeChannelSync() {
        if (!activeSyncChannelId) return;

        const limit = document.getElementById('syncHistoryLimit').value;
        const progressBox = document.getElementById('syncProgressContainer');
        const alertBox = document.getElementById('syncModalAlert');
        const btn = document.getElementById('btnConfirmSync');

        progressBox.style.display = 'block';
        alertBox.style.display = 'none';
        btn.disabled = true;

        fetch('{{ url("admin/telegram-mtproto/channels") }}/' + activeSyncChannelId + '/sync-history', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ limit: limit })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alertBox.style.display = 'block';
                alertBox.innerHTML = '<div class="alert alert-success py-2 px-3 small mb-0"><i class="fas fa-check-circle mr-1"></i> ' + data.message + '</div>';
                setTimeout(() => {
                    $('#syncHistoryModal').modal('hide');
                    location.reload();
                }, 1800);
            } else {
                btn.disabled = false;
                progressBox.style.display = 'none';
                alertBox.style.display = 'block';
                alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">' + data.message + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            progressBox.style.display = 'none';
            alertBox.style.display = 'block';
            alertBox.innerHTML = '<div class="alert alert-danger py-2 px-3 small mb-0">Sync request failed: ' + err + '</div>';
        });
    }

    // Real-time polling for counters & listener status every 10 seconds
    setInterval(function () {
        fetch('{{ route("admin.telegram_mtproto.live_status") }}')
            .then(res => res.json())
            .then(data => {
                if (data.total_messages !== undefined) {
                    const msgEl = document.getElementById('counterTotalMessages');
                    if (msgEl) msgEl.innerText = Number(data.total_messages).toLocaleString();
                }
                if (data.total_deals !== undefined) {
                    const dealEl = document.getElementById('counterTotalDeals');
                    if (dealEl) dealEl.innerText = Number(data.total_deals).toLocaleString();
                }
            })
            .catch(() => {});
    }, 10000);
</script>
@endpush
