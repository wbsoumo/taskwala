@extends('layouts.user')

@section('title', 'Clicks Report')

@section('content')

<!-- Filter Card -->
<div class="card card-outline card-secondary shadow-sm mb-4">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-filter text-secondary mr-2"></i> Filter Click Logs
        </h3>
    </div>
    <div class="card-body">
        <form action="{{ route('user.reports.clicks') }}" method="GET" class="row">
            <div class="col-md-4 mb-2">
                <label class="small font-weight-bold text-muted">CAMPAIGN</label>
                <select name="campaign_id" class="form-control form-control-sm">
                    <option value="">All Campaigns</option>
                    @foreach($campaigns as $c)
                        <option value="{{ $c->id }}" {{ request('campaign_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold text-muted">START DATE</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold text-muted">END DATE</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary btn-block font-weight-bold"><i class="fas fa-search mr-1"></i> Apply</button>
                <a href="{{ route('user.reports.clicks') }}" class="btn btn-sm btn-default ml-1 border"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-mouse-pointer text-primary mr-2"></i> Visitor Click History
        </h3>
    </div>
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>CLICK ID</th>
                        <th>CAMPAIGN</th>
                        <th>CUSTOMER PAYOUT</th>
                        <th>MY COMMISSION</th>
                        <th>DEVICE / OS</th>
                        <th>DATE & TIME</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clicks as $click)
                        <tr>
                            <td><code>{{ $click->click_id }}</code></td>
                            <td class="font-weight-bold text-dark">{{ $click->campaign->name ?? 'N/A' }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($click->customer_payout, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($click->affiliate_commission, 2) }}</td>
                            <td><span class="badge badge-light border">{{ ucfirst($click->device_type ?? 'desktop') }} / {{ $click->os ?? 'OS' }}</span></td>
                            <td class="small text-muted">{{ $click->created_at ? $click->created_at->format('d M Y, H:i') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No click traffic recorded matching your filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Stack View -->
        <div class="d-md-none p-3">
            @forelse($clicks as $click)
                <div class="card card-outline card-info shadow-sm mb-3">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between my-1">
                            <span class="font-weight-bold text-dark">{{ $click->campaign->name ?? 'N/A' }}</span>
                            <small class="text-muted">{{ $click->created_at ? $click->created_at->format('d M, H:i') : '' }}</small>
                        </div>
                        <div class="small text-muted my-1">ID: <code>{{ $click->click_id }}</code></div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span>Customer / Commission:</span>
                            <span class="font-weight-bold text-info">₹{{ number_format($click->customer_payout, 2) }}</span> / 
                            <span class="font-weight-bold text-success">₹{{ number_format($click->affiliate_commission, 2) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No click traffic recorded.</div>
            @endforelse
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $clicks->links() }}
    </div>
</div>

@endsection
