@extends('layouts.admin')

@section('title', 'S2S Postback Live Tester')

@section('content')

<div class="row">
    <div class="col-md-6">
        <!-- Test Postback Simulator Form -->
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-vial text-warning mr-2"></i>Simulate Incoming S2S Postback</h3>
            </div>
            <form action="{{ route('admin.postbacks.test.send') }}" method="POST">
                @csrf
                <div class="card-body">
                    <p class="text-muted small">Use this tool to simulate real-time S2S webhook postbacks from advertisers to verify conversion recording, ledger crediting, and response status codes.</p>

                    <div class="form-group">
                        <label>Target Postback Route / Endpoint <span class="text-danger">*</span></label>
                        <select name="provider_slug" class="form-control" required>
                            <option value="global">Global Postback Route (/api/v1/postback/global)</option>
                            @foreach($providers as $p)
                                <option value="{{ $p->slug }}">{{ $p->name }} (/api/v1/postback/{{ $p->slug }})</option>
                            @endforeach
                        </select>
                    </div>

                    <input type="hidden" name="http_method" value="GET">

                    <div class="form-group">
                        <label>Target Click ID (click_id) <span class="text-danger">*</span></label>
                        <input type="text" name="click_id" class="form-control" placeholder="e.g. CLK_01M4BWCX4XA0B85AMGSNBQ3AN0" required>
                        <small class="form-text text-muted">Copy a recent Click ID from the table on the right.</small>
                    </div>

                    <div class="form-group">
                        <label>Provider Conversion / Transaction ID</label>
                        <input type="text" name="conversion_id" class="form-control" placeholder="e.g. TX_88923011">
                    </div>

                    <div class="form-group">
                        <label>Conversion Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="approved">Approved (Triggers Earning Credit)</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Auth Secret Key (If required)</label>
                        <input type="text" name="secret" class="form-control" placeholder="Secret key if required by endpoint">
                    </div>
                </div>
                <div class="card-footer text-right bg-light">
                    <button type="submit" class="btn btn-warning font-weight-bold"><i class="fas fa-paper-plane mr-1"></i> Fire S2S Test Postback</button>
                </div>
            </form>
        </div>

        @if(session('test_result'))
            @php $res = session('test_result'); @endphp
            <div class="card card-outline card-info shadow-sm mt-3">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-terminal mr-2"></i>Postback Execution Output Result</h3>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Target URL:</strong> <code>{{ $res['target_url'] }}</code></p>
                    <p class="mb-1"><strong>HTTP Method:</strong> <span class="badge badge-primary">{{ $res['method'] }}</span></p>
                    <p class="mb-1"><strong>HTTP Status Code:</strong> <span class="badge badge-{{ $res['status_code'] === 200 ? 'success' : 'danger' }}">{{ $res['status_code'] }}</span></p>
                    <div class="mt-2">
                        <label class="font-weight-bold">Response Body JSON:</label>
                        <pre class="bg-dark text-success p-3 rounded mb-0"><code>{{ is_array($res['body']) ? json_encode($res['body'], JSON_PRETTY_PRINT) : $res['body'] }}</code></pre>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-6">
        <!-- Recent Clicks Helper -->
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-mouse-pointer text-info mr-2"></i>Recent Generated Click IDs for Testing</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Click ID</th>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentClicks as $clk)
                            <tr>
                                <td><code>{{ $clk->click_id }}</code></td>
                                <td>{{ $clk->campaign->name ?? 'N/A' }}</td>
                                <td><span class="badge badge-{{ $clk->status === 'converted' ? 'success' : 'secondary' }}">{{ ucfirst($clk->status) }}</span></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-warning" onclick="$('input[name=click_id]').val('{{ $clk->click_id }}');">
                                        <i class="fas fa-arrow-left mr-1"></i> Select
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No recent clicks logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
