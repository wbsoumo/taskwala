@extends('layouts.admin')

@section('title', 'Click Log Audit')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-mouse-pointer text-primary mr-2"></i>Visitor Click Logs</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Click ID</th>
                        <th>Campaign</th>
                        <th>Affiliate</th>
                        <th>IP Address</th>
                        <th>Device / OS</th>
                        <th>L3 Customer Payout</th>
                        <th>Affiliate Commission</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clicks as $click)
                        <tr>
                            <td><code>{{ $click->click_id }}</code></td>
                            <td>{{ $click->campaign->name ?? 'N/A' }}</td>
                            <td>{{ $click->user->name ?? 'N/A' }}</td>
                            <td><code>{{ $click->ip_address }}</code></td>
                            <td><span class="badge badge-light border">{{ $click->device_type ?? 'desktop' }} / {{ $click->os ?? 'OS' }}</span></td>
                            <td class="text-info font-weight-bold">₹{{ number_format($click->customer_payout, 2) }}</td>
                            <td class="text-purple font-weight-bold">₹{{ number_format($click->affiliate_commission, 2) }}</td>
                            <td><span class="badge badge-{{ $click->status === 'converted' ? 'success' : 'secondary' }}">{{ ucfirst($click->status) }}</span></td>
                            <td>{{ $click->created_at ? $click->created_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No click logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $clicks->links() }}
    </div>
</div>

@endsection
