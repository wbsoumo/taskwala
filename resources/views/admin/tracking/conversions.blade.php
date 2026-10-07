@extends('layouts.admin')

@section('title', 'Conversions Master List')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-check-double text-primary mr-2"></i>Conversions Master Records</h3>
        <div class="card-tools">
            <form action="{{ route('admin.tracking.conversions') }}" method="GET" class="form-inline">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Click ID or TX ID..." value="{{ request('search') }}">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All Statuses</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>Reversed</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i> Filter</button>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Conversion ID</th>
                        <th>Click ID</th>
                        <th>Campaign</th>
                        <th>Affiliate</th>
                        <th>Status</th>
                        <th>Advertiser Revenue</th>
                        <th>Affiliate Commission</th>
                        <th>Customer Reward</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversions as $conv)
                        <tr>
                            <td><code>{{ Str::limit($conv->public_id, 12) }}</code></td>
                            <td><code>{{ $conv->click_id }}</code></td>
                            <td class="font-weight-bold">{{ $conv->campaign->name ?? 'N/A' }}</td>
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
                            <td class="text-primary font-weight-bold">₹{{ number_format($conv->payoutSnapshot->advertiser_payout ?? 0, 2) }}</td>
                            <td class="text-purple font-weight-bold">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                            <td class="text-info font-weight-bold">₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</td>
                            <td>{{ $conv->conversion_time ? $conv->conversion_time->format('M d, Y H:i') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('admin.tracking.conversions.detail', $conv) }}" class="btn btn-xs btn-primary"><i class="fas fa-search mr-1"></i> Full Audit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No conversions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $conversions->links() }}
    </div>
</div>

@endsection
