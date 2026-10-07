@extends('layouts.user')

@section('title', 'Click Statistics')

@section('content')

<div class="card card-custom border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="font-weight-bold mb-0"><i class="fas fa-mouse-pointer text-primary mr-2"></i>Visitor Click History</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>CLICK ID</th>
                        <th>CAMPAIGN</th>
                        <th>CUSTOMER PAYOUT</th>
                        <th>MY COMMISSION</th>
                        <th>DEVICE / OS</th>
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clicks as $click)
                        <tr>
                            <td><code>{{ $click->click_id }}</code></td>
                            <td class="font-weight-bold">{{ $click->campaign->name ?? 'N/A' }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($click->customer_payout, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($click->affiliate_commission, 2) }}</td>
                            <td><span class="badge badge-light border">{{ $click->device_type ?? 'desktop' }} / {{ $click->os ?? 'OS' }}</span></td>
                            <td>{{ $click->created_at ? $click->created_at->format('M d, Y H:i') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No click statistics recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">{{ $clicks->links() }}</div>
</div>

@endsection
