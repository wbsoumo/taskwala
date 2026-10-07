@extends('layouts.admin')

@section('title', 'End-Customer Reward Payouts')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-hand-holding-usd text-primary mr-2"></i>Customer Payout Records</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Conversion</th>
                        <th>Campaign</th>
                        <th>Affiliate User</th>
                        <th>Customer UPI</th>
                        <th>UPI Holder</th>
                        <th>Payout Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payouts as $payout)
                        <tr>
                            <td><code>#{{ $payout->conversion_id }}</code></td>
                            <td>{{ $payout->conversion->campaign->name ?? 'N/A' }}</td>
                            <td>{{ $payout->conversion->user->name ?? 'N/A' }}</td>
                            <td><code>{{ $payout->upi_id ?? 'N/A' }}</code></td>
                            <td>{{ $payout->upi_holder_name ?? 'N/A' }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($payout->payout_amount, 2) }}</td>
                            <td><span class="badge badge-{{ $payout->status === 'processed' ? 'success' : 'warning' }}">{{ ucfirst($payout->status) }}</span></td>
                            <td>{{ $payout->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No customer payouts recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $payouts->links() }}
    </div>
</div>

@endsection
