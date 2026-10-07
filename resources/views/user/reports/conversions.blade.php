@extends('layouts.user')

@section('title', 'My Conversions')

@section('content')

<div class="card card-custom border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="font-weight-bold mb-0"><i class="fas fa-check-double text-success mr-2"></i>My Earned Conversions</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>CONVERSION UUID</th>
                        <th>CAMPAIGN</th>
                        <th>STATUS</th>
                        <th>MY COMMISSION</th>
                        <th>CUSTOMER REWARD</th>
                        <th>CONVERSION TIME</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversions as $conv)
                        <tr>
                            <td><code>{{ Str::limit($conv->public_id, 12) }}</code></td>
                            <td class="font-weight-bold">{{ $conv->campaign->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $conv->status === 'approved' ? 'success' : 'warning' }} rounded-pill px-3 py-1">
                                    {{ ucfirst($conv->status) }}
                                </span>
                            </td>
                            <td class="text-success font-weight-bold">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</td>
                            <td>{{ $conv->conversion_time ? $conv->conversion_time->format('M d, Y H:i') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No conversions logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">{{ $conversions->links() }}</div>
</div>

@endsection
