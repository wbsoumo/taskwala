@extends('layouts.user')

@section('title', 'Dashboard')

@section('content')

<div class="row mb-4">
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="stat-card stat-card-blue shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase small mb-1 opacity-75">Available Balance</h6>
                    <h3 class="font-weight-bold mb-0">₹{{ number_format($availableBalance, 2) }}</h3>
                </div>
                <i class="fas fa-wallet fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="stat-card stat-card-green shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase small mb-1 opacity-75">My Retained Commission</h6>
                    <h3 class="font-weight-bold mb-0">₹{{ number_format($totalAffiliateCommission, 2) }}</h3>
                </div>
                <i class="fas fa-coins fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3 mb-md-0">
        <div class="stat-card stat-card-purple shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase small mb-1 opacity-75">Customer Rewards Sent</h6>
                    <h3 class="font-weight-bold mb-0">₹{{ number_format($totalCustomerRewardsGenerated, 2) }}</h3>
                </div>
                <i class="fas fa-gift fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-card-amber shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-uppercase small mb-1 opacity-75">Total Conversions</h6>
                    <h3 class="font-weight-bold mb-0">{{ $totalConversions }}</h3>
                    <small class="d-block mt-1">Approved: {{ $approvedConversions }} | Pending: {{ $pendingConversions }}</small>
                </div>
                <i class="fas fa-check-circle fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="font-weight-bold mb-0"><i class="fas fa-link text-primary mr-2"></i>My Campaign Links</h5>
                <a href="{{ route('user.links.generator') }}" class="btn btn-sm btn-primary rounded-pill"><i class="fas fa-plus mr-1"></i> New Link</a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>CAMPAIGN</th>
                                <th>CUSTOMER PAYOUT</th>
                                <th>MY COMMISSION</th>
                                <th>CLICKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLinks as $link)
                                <tr>
                                    <td class="font-weight-bold">{{ $link->campaign->name ?? 'N/A' }}</td>
                                    <td class="text-info font-weight-bold">₹{{ number_format($link->customer_payout, 2) }}</td>
                                    <td class="text-success font-weight-bold">₹{{ number_format($link->affiliate_commission, 2) }}</td>
                                    <td><span class="badge badge-light border">{{ $link->click_count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No tracking links created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card card-custom h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="font-weight-bold mb-0"><i class="fas fa-history text-success mr-2"></i>Recent Conversions</h5>
                <a href="{{ route('user.reports.conversions') }}" class="btn btn-sm btn-outline-secondary rounded-pill">View All</a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th>CAMPAIGN</th>
                                <th>STATUS</th>
                                <th>MY COMMISSION</th>
                                <th>CUSTOMER REWARD</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentConversions as $conv)
                                <tr>
                                    <td class="font-weight-bold">{{ $conv->campaign->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $conv->status === 'approved' ? 'success' : 'warning' }} rounded-pill px-3 py-1">
                                            {{ ucfirst($conv->status) }}
                                        </span>
                                    </td>
                                    <td class="text-success font-weight-bold">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                                    <td class="text-info font-weight-bold">₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No conversions logged yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
