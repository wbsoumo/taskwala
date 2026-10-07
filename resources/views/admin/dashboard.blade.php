@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')

<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info elevation-2">
            <div class="inner">
                <h3>{{ number_format($totalCampaigns) }}</h3>
                <p>Active Campaigns: {{ $activeCampaigns }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-bullhorn"></i>
            </div>
            <a href="{{ route('admin.campaigns.index') }}" class="small-box-footer">Manage Campaigns <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success elevation-2">
            <div class="inner">
                <h3>{{ number_format($totalAffiliates) }}</h3>
                <p>Active Affiliates: {{ $activeAffiliates }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer">Manage Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning elevation-2">
            <div class="inner">
                <h3>{{ number_format($totalClicks) }}</h3>
                <p>Total Clicks Recorded</p>
            </div>
            <div class="icon">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <a href="{{ route('admin.tracking.clicks') }}" class="small-box-footer">View Clicks <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger elevation-2">
            <div class="inner">
                <h3>{{ number_format($totalConversions) }}</h3>
                <p>Approved: {{ $approvedConversions }} | Pending: {{ $pendingConversions }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-double"></i>
            </div>
            <a href="{{ route('admin.tracking.conversions') }}" class="small-box-footer">View Conversions <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<!-- Financial Breakdown Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-primary"><i class="fas fa-building"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Advertiser Revenue</span>
                <span class="info-box-number">₹{{ number_format($totalAdvertiserRevenue, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-info"><i class="fas fa-gift"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Customer Payouts</span>
                <span class="info-box-number">₹{{ number_format($totalCustomerPayout, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-purple"><i class="fas fa-percentage"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Affiliate Commission</span>
                <span class="info-box-number">₹{{ number_format($totalAffiliateCommission, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Platform Gross Margin</span>
                <span class="info-box-number">₹{{ number_format($totalPlatformMargin, 2) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Conversions Table -->
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header border-0">
        <h3 class="card-title font-weight-bold"><i class="fas fa-history text-primary mr-2"></i>Recent Conversions Attribution</h3>
        <div class="card-tools">
            <a href="{{ route('admin.tracking.conversions') }}" class="btn btn-tool btn-sm"><i class="fas fa-bars"></i> View All</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-valign-middle">
                <thead>
                    <tr>
                        <th>Conversion ID</th>
                        <th>Campaign</th>
                        <th>Affiliate</th>
                        <th>Status</th>
                        <th>Advertiser</th>
                        <th>Affiliate Commission</th>
                        <th>Customer Reward</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentConversions as $conv)
                        <tr>
                            <td><code>{{ Str::limit($conv->public_id, 12) }}</code></td>
                            <td>{{ $conv->campaign->name ?? 'N/A' }}</td>
                            <td>{{ $conv->user->name ?? 'N/A' }}</td>
                            <td>
                                @if($conv->status === 'approved')
                                    <span class="badge badge-success">Approved</span>
                                @elseif($conv->status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @else
                                    <span class="badge badge-danger">{{ ucfirst($conv->status) }}</span>
                                @endif
                            </td>
                            <td>₹{{ number_format($conv->payoutSnapshot->advertiser_payout ?? 0, 2) }}</td>
                            <td class="text-purple font-weight-bold">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</td>
                            <td>{{ $conv->conversion_time ? $conv->conversion_time->diffForHumans() : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.tracking.conversions.detail', $conv) }}" class="btn btn-xs btn-primary">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No conversions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
