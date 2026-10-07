@extends('layouts.admin')

@section('title', 'Global S2S Postback Configuration')

@section('content')

<div class="row">
    <div class="col-md-7">
        <!-- Global Postback URL & Integration Code -->
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-globe text-primary mr-2"></i>System Global S2S Webhook URL</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Use this universal Server-to-Server (S2S) postback webhook endpoint for all networks and advertisers when specific offer routing is not required.</p>

                <div class="form-group">
                    <label class="font-weight-bold">Global S2S Postback Endpoint <span class="badge badge-success font-weight-bold ml-1">GET Method Only</span></label>
                    <div class="input-group">
                        <input type="text" id="globalUrlInput" class="form-control font-weight-bold text-primary" value="{{ $globalUrl }}" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-primary" onclick="navigator.clipboard.writeText('{{ $globalUrl }}'); alert('Copied to clipboard!');">
                                <i class="fas fa-copy mr-1"></i> Copy URL
                            </button>
                        </div>
                    </div>
                </div>

                <div class="callout callout-info mt-3">
                    <h5><i class="fas fa-info-circle mr-1"></i> Supported URL Parameters (Macro Tokens)</h5>
                    <ul class="mb-0 small">
                        <li><code>click_id</code> or <code>clickid</code> or <code>sub_id</code> (Required): Unique tracking Click ID (e.g. <code>CLK_01M4BW...</code>).</li>
                        <li><code>conversion_id</code> or <code>txid</code> or <code>transaction_id</code> (Optional): Advertiser / Network transaction ID.</li>
                        <li><code>status</code> (Optional): Conversion status (<code>approved</code>, <code>pending</code>, <code>rejected</code>). Default: <code>approved</code>.</li>
                        <li><code>secret</code> (Optional): Global authentication token if set in environment config.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Sample Integration Macros -->
        <div class="card card-outline card-secondary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-code mr-2"></i>Advertiser S2S Setup Examples</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Network / Platform</th>
                            <th>Sample Postback Target Template</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>HasOffers / Tune</strong></td>
                            <td><code>{{ $globalUrl }}?click_id={aff_sub}&conversion_id={transaction_id}</code></td>
                        </tr>
                        <tr>
                            <td><strong>Cake Platform</strong></td>
                            <td><code>{{ $globalUrl }}?click_id=#s2#&conversion_id=#reqid#</code></td>
                        </tr>
                        <tr>
                            <td><strong>Voluum / Custom PHP</strong></td>
                            <td><code>{{ $globalUrl }}?click_id={cid}&conversion_id={txid}&status=approved</code></td>
                        </tr>
                        <tr>
                            <td><strong>vCommission / Trackier</strong></td>
                            <td><code>{{ $globalUrl }}?click_id={click_id}&conversion_id={conversion_id}</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <!-- Security & Global Secret Status -->
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-key text-success mr-2"></i>Global Secret & Security</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Global Auth Secret</b>
                        <span class="float-right badge badge-{{ !empty($globalSecret) ? 'success' : 'warning' }}">
                            {{ !empty($globalSecret) ? 'ENABLED' : 'DISABLED' }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Secret Token</b>
                        <span class="float-right"><code>{{ !empty($globalSecret) ? Str::limit($globalSecret, 16) : 'None (Public)' }}</code></span>
                    </li>
                    <li class="list-group-item">
                        <b>IP Whitelisting Check</b>
                        <span class="float-right badge badge-info">Active</span>
                    </li>
                </ul>
                <small class="text-muted">Global secret token can be configured in <code>.env</code> file under <code>POSTBACK_SECRET</code> key.</small>
            </div>
        </div>

        <!-- Recent Global Postback Activity -->
        <div class="card card-outline card-dark shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2"></i>Recent Global Postbacks</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>IP</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($globalLogs as $log)
                            <tr>
                                <td class="small">{{ $log->created_at ? $log->created_at->format('H:i:s') : 'N/A' }}</td>
                                <td><code>{{ $log->source_ip }}</code></td>
                                <td><span class="badge badge-{{ $log->response_code === 200 ? 'success' : 'danger' }}">{{ $log->response_code }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3 small">No global postbacks received recently.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
