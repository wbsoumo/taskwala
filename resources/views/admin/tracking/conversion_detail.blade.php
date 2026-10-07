@extends('layouts.admin')

@section('title', 'Conversion Full Attribution Audit')

@section('content')

<div class="row">
    <div class="col-md-8">
        <!-- Master Attribution Summary -->
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-fingerprint text-primary mr-2"></i>Attribution & Financial Snapshot</h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $conversion->status === 'approved' ? 'success' : 'warning' }} p-2">{{ strtoupper($conversion->status) }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h5 class="text-primary font-weight-bold">Attribution Entities</h5>
                        <ul class="list-unstyled mt-3">
                            <li class="mb-2"><strong>Conversion UUID:</strong> <code>{{ $conversion->public_id }}</code></li>
                            <li class="mb-2"><strong>Click ID:</strong> <code>{{ $conversion->click_id }}</code></li>
                            <li class="mb-2"><strong>Campaign:</strong> {{ $conversion->campaign->name ?? 'N/A' }}</li>
                            <li class="mb-2"><strong>Advertiser:</strong> {{ $conversion->campaign->advertiser_name ?? 'N/A' }}</li>
                            <li class="mb-2"><strong>Affiliate User:</strong> {{ $conversion->user->name ?? 'N/A' }} ({{ $conversion->user->email }})</li>
                            <li class="mb-2"><strong>Tracking Token:</strong> <code>{{ $conversion->link->secure_token ?? 'N/A' }}</code></li>
                            <li class="mb-2"><strong>Postback Provider:</strong> {{ $conversion->provider->name ?? 'System/Manual' }}</li>
                            <li class="mb-2"><strong>Provider Conversion ID:</strong> <code>{{ $conversion->provider_conversion_id ?? 'N/A' }}</code></li>
                            <li class="mb-2"><strong>IP / Device:</strong> <code>{{ $conversion->ip_address }}</code></li>
                            <li class="mb-2"><strong>Timestamp:</strong> {{ $conversion->conversion_time }}</li>
                        </ul>
                    </div>

                    <div class="col-md-6">
                        <h5 class="text-success font-weight-bold">Frozen Immutable Financial Snapshot</h5>
                        <div class="bg-light p-3 rounded border mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span>LEVEL 1: Advertiser Payout</span>
                                <strong class="text-primary">₹{{ number_format($conversion->payoutSnapshot->advertiser_payout ?? 0, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>LEVEL 2: Affiliate Allocation</span>
                                <strong>₹{{ number_format($conversion->payoutSnapshot->affiliate_allocated_payout ?? 0, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>LEVEL 3: Customer Payout</span>
                                <strong class="text-info">₹{{ number_format($conversion->payoutSnapshot->customer_payout ?? 0, 2) }}</strong>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Affiliate Retained Commission</span>
                                <strong class="text-purple font-weight-bold" style="font-size: 1.1rem;">₹{{ number_format($conversion->payoutSnapshot->affiliate_commission ?? 0, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Platform Gross Margin</span>
                                <strong class="text-success font-weight-bold" style="font-size: 1.1rem;">₹{{ number_format($conversion->payoutSnapshot->platform_margin ?? 0, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversion Status History & Audit Logs -->
        <div class="card shadow-sm">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-history mr-2"></i>Status History Audit Log</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Previous Status</th>
                            <th>New Status</th>
                            <th>Changed By</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($conversion->statusHistories as $history)
                            <tr>
                                <td>{{ $history->created_at ? $history->created_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                                <td><span class="badge badge-secondary">{{ $history->previous_status ?? 'NULL' }}</span></td>
                                <td><span class="badge badge-info">{{ $history->new_status }}</span></td>
                                <td>{{ ucfirst($history->changed_by_type) }}</td>
                                <td>{{ $history->reason ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No status history records.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Manual Status Override Form -->
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-user-shield text-warning mr-2"></i>Admin Manual Status Action</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.tracking.conversions.status', $conversion) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Update Status</label>
                        <select name="status" class="form-control" required>
                            <option value="approved" {{ $conversion->status === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="pending" {{ $conversion->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="rejected" {{ $conversion->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="reversed" {{ $conversion->status === 'reversed' ? 'selected' : '' }}>Reversed</option>
                            <option value="cancelled" {{ $conversion->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reason / Audit Note</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Reason for manual status modification..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block font-weight-bold"><i class="fas fa-save mr-1"></i> Update Status & Adjust Ledger</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
