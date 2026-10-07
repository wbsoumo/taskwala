@extends('layouts.admin')

@section('title', 'Generated Affiliate Links Audit')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-link text-primary mr-2"></i>Affiliate Links Master Audit</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Affiliate User</th>
                        <th>Public Link Token</th>
                        <th>Level 2 Payout</th>
                        <th>Customer Payout (L3)</th>
                        <th>Affiliate Commission</th>
                        <th>Clicks</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($links as $link)
                        <tr>
                            <td class="font-weight-bold">{{ $link->campaign->name ?? 'N/A' }}</td>
                            <td>{{ $link->user->name ?? 'N/A' }} ({{ $link->user->email }})</td>
                            <td><code>{{ $link->secure_token }}</code></td>
                            <td>₹{{ number_format($link->allocated_affiliate_payout, 2) }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($link->customer_payout, 2) }}</td>
                            <td class="text-purple font-weight-bold">₹{{ number_format($link->affiliate_commission, 2) }}</td>
                            <td><span class="badge badge-primary">{{ $link->click_count }}</span></td>
                            <td><span class="badge badge-{{ $link->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($link->status) }}</span></td>
                            <td>{{ $link->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No affiliate links created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $links->links() }}
    </div>
</div>

@endsection
