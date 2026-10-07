@extends('layouts.admin')

@section('title', 'Campaign Detail & Level 2 Allocations')

@section('content')

<div class="row">
    <div class="col-md-6">
        <!-- Campaign Details -->
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-bullhorn text-primary mr-2"></i>{{ $campaign->name }}</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit mr-1"></i> Edit Campaign</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Status</b> 
                        <span class="float-right badge badge-{{ $campaign->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($campaign->status) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Advertiser</b> <span class="float-right">{{ $campaign->advertiser_name }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Category</b> <span class="float-right badge badge-info">{{ $campaign->category }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>LEVEL 1: Advertiser Gross Payout</b> <span class="float-right font-weight-bold text-primary">₹{{ number_format($campaign->advertiser_payout, 2) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>LEVEL 2: Default Affiliate Allocation</b> <span class="float-right font-weight-bold text-success">₹{{ number_format($campaign->default_affiliate_payout, 2) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Landing URL</b> <span class="float-right"><code>{{ Str::limit($campaign->landing_url, 40) }}</code></span>
                    </li>
                    <li class="list-group-item">
                        <b>Conversion Event</b> <span class="float-right">{{ $campaign->conversion_event }}</span>
                    </li>
                </ul>
                @if($campaign->terms)
                    <div class="callout callout-info">
                        <h5>Campaign Terms</h5>
                        <p class="mb-0">{{ $campaign->terms }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Level 2 Custom Affiliate Payout Allocation Form -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-sliders-h text-success mr-2"></i>Level 2 Custom Payout Overrides</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Set custom maximum Level 2 affiliate payout for specific users. (e.g. Affiliate A gets ₹100, Affiliate B gets ₹80).</p>

                <form action="{{ route('admin.campaigns.allocations.update', $campaign) }}" method="POST" class="mb-4 bg-light p-3 rounded border">
                    @csrf
                    <div class="form-group">
                        <label>Select Affiliate User</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">-- Choose User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Allocated Payout (₹)</label>
                            <input type="number" step="0.01" name="affiliate_payout" class="form-control" placeholder="80.00" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Permission</label>
                            <select name="status" class="form-control" required>
                                <option value="allowed">Allowed</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check-circle mr-1"></i> Set Custom Level 2 Payout</button>
                </form>

                <h5 class="font-weight-bold">Current Custom Overrides</h5>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Affiliate Name</th>
                            <th>Custom Level 2 Payout</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaign->affiliateAllocations as $alloc)
                            <tr>
                                <td>{{ $alloc->user->name ?? 'N/A' }}</td>
                                <td class="font-weight-bold text-success">₹{{ number_format($alloc->affiliate_payout, 2) }}</td>
                                <td><span class="badge badge-{{ $alloc->status === 'allowed' ? 'success' : 'danger' }}">{{ ucfirst($alloc->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted small">No custom overrides defined yet. Default payout applies to all users.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
