@extends('layouts.admin')

@section('title', 'Campaign Performance & Reconciliation Report')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-chart-pie text-primary mr-2"></i>Campaign Financial Summary</h3>
        <div class="card-tools">
            <a href="{{ route('admin.reports.campaigns.export') }}" class="btn btn-sm btn-success"><i class="fas fa-file-csv mr-1"></i> Export CSV</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Campaign</th>
                        <th>Advertiser</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>CVR (%)</th>
                        <th>Advertiser Revenue</th>
                        <th>Affiliate Allocation</th>
                        <th>Customer Payout</th>
                        <th>Affiliate Commission</th>
                        <th>Platform Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $c)
                        <tr>
                            <td>#{{ $c->id }}</td>
                            <td class="font-weight-bold">{{ $c->name }}</td>
                            <td>{{ $c->advertiser_name }}</td>
                            <td><span class="badge badge-secondary">{{ $c->clicks_count }}</span></td>
                            <td><span class="badge badge-primary">{{ $c->conversions_count }}</span></td>
                            <td><strong>{{ $c->conversion_rate }}%</strong></td>
                            <td class="text-primary font-weight-bold">₹{{ number_format($c->total_advertiser_revenue, 2) }}</td>
                            <td>₹{{ number_format($c->total_affiliate_allocation, 2) }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($c->total_customer_payout, 2) }}</td>
                            <td class="text-purple font-weight-bold">₹{{ number_format($c->total_affiliate_commission, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($c->total_platform_margin, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No campaign report data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
