@extends('layouts.admin')

@section('title', 'Campaigns Management')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-bullhorn text-primary mr-2"></i>Campaign Offers List</h3>
        <div class="card-tools d-flex">
            <form action="{{ route('admin.campaigns.index') }}" method="GET" class="form-inline mr-2">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search campaign, advertiser..." value="{{ request('search') }}">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="paused" {{ request('status') === 'paused' ? 'selected' : '' }}>Paused</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i> Filter</button>
            </form>
            <a href="{{ route('admin.campaigns.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i> Add Campaign</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Advertiser</th>
                        <th>Category</th>
                        <th>Advertiser Payout (L1)</th>
                        <th>Default Affiliate Payout (L2)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td><code>#{{ $campaign->id }}</code></td>
                            <td class="font-weight-bold">{{ $campaign->name }}</td>
                            <td>{{ $campaign->advertiser_name }}</td>
                            <td><span class="badge badge-info">{{ $campaign->category }}</span></td>
                            <td class="text-primary font-weight-bold">₹{{ number_format($campaign->advertiser_payout, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($campaign->default_affiliate_payout, 2) }}</td>
                            <td>
                                @if($campaign->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($campaign->status === 'draft')
                                    <span class="badge badge-secondary">Draft</span>
                                @else
                                    <span class="badge badge-warning">{{ ucfirst($campaign->status) }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-xs btn-info"><i class="fas fa-cog"></i> View & Allocations</a>
                                <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-xs btn-primary"><i class="fas fa-edit"></i> Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No campaigns found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $campaigns->links() }}
    </div>
</div>

@endsection
