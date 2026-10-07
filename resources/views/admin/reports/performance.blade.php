@extends('layouts.admin')

@section('title', 'Performance Report & Analytics')

@section('content')

<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header bg-light">
        <h3 class="card-title font-weight-bold"><i class="fas fa-filter text-primary mr-2"></i>Advanced Performance Filters</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.performance') }}">
            <div class="row">
                <!-- Global Search -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">Search Query</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
                        <input type="text" name="search" class="form-control" placeholder="Click ID, UPI, Offer, Affiliate..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Campaign Filter -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">Filter by Offer / Campaign</label>
                    <select name="campaign_id" class="form-control form-control-sm">
                        <option value="">-- All Campaigns --</option>
                        @foreach($campaigns as $camp)
                            <option value="{{ $camp->id }}" {{ request('campaign_id') == $camp->id ? 'selected' : '' }}>{{ $camp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Affiliate Filter -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">Filter by Affiliate</label>
                    <select name="user_id" class="form-control form-control-sm">
                        <option value="">-- All Affiliates --</option>
                        @foreach($users as $usr)
                            <option value="{{ $usr->id }}" {{ request('user_id') == $usr->id ? 'selected' : '' }}>{{ $usr->name }} ({{ $usr->email }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">Conversion Status</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- All Statuses --</option>
                        <option value="clicked" {{ request('status') === 'clicked' ? 'selected' : '' }}>Clicked (Unconverted)</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved (Converted)</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <!-- Date Range -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">From Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">To Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>

                <!-- Sort By -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">Sort By</label>
                    <select name="sort_by" class="form-control form-control-sm">
                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Date & Time</option>
                        <option value="click_id" {{ request('sort_by') === 'click_id' ? 'selected' : '' }}>Click ID</option>
                        <option value="customer_payout" {{ request('sort_by') === 'customer_payout' ? 'selected' : '' }}>Customer Payout Amount</option>
                        <option value="allocated_affiliate_payout" {{ request('sort_by') === 'allocated_affiliate_payout' ? 'selected' : '' }}>Affiliate Allocation</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-md-3 form-group">
                    <label class="small font-weight-bold">Sort Order</label>
                    <select name="sort_order" class="form-control form-control-sm">
                        <option value="desc" {{ request('sort_order') === 'desc' ? 'selected' : '' }}>Descending (Newest First)</option>
                        <option value="asc" {{ request('sort_order') === 'asc' ? 'selected' : '' }}>Ascending (Oldest First)</option>
                    </select>
                </div>
            </div>

            <div class="text-right mt-2">
                <a href="{{ route('admin.reports.performance') }}" class="btn btn-sm btn-secondary mr-2"><i class="fas fa-undo mr-1"></i> Reset Filters</a>
                <a href="{{ route('admin.reports.performance.export', request()->query()) }}" class="btn btn-sm btn-success mr-2"><i class="fas fa-file-excel mr-1"></i> Export Filtered CSV</a>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Performance Log Records Table -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-list text-success mr-2"></i>Performance Audit Records</h3>
        <div class="card-tools">
            <span class="badge badge-primary px-3 py-2">Total Total Records: {{ $records->total() }}</span>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0 text-nowrap">
            <thead class="thead-dark">
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Click ID</th>
                    <th>Offer Name</th>
                    <th>Affiliate User</th>
                    <th>Registered Customer UPI</th>
                    <th>Status</th>
                    <th>L1 Advertiser Gross</th>
                    <th>L2 Affiliate Alloc.</th>
                    <th>L3 Customer Payout</th>
                    <th>Affiliate Commission</th>
                    <th>Margin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    @php
                        $conv = $rec->conversion;
                        $snap = $conv?->payoutSnapshot;
                        $upi = $rec->customerPayout?->upi_id ?? $conv?->customerPayout?->upi_id ?? 'N/A';
                    @endphp
                    <tr>
                        <td class="small">{{ $rec->created_at ? $rec->created_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                        <td><code>{{ $rec->click_id }}</code></td>
                        <td>
                            <strong class="text-primary">{{ $rec->campaign->name ?? 'N/A' }}</strong><br>
                            <span class="badge badge-light border">{{ $rec->campaign->advertiser_name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <span class="font-weight-bold">{{ $rec->user->name ?? 'N/A' }}</span><br>
                            <span class="text-muted small">{{ $rec->user->email ?? 'N/A' }}</span>
                        </td>
                        <td>
                            @if($upi !== 'N/A')
                                <span class="badge badge-success font-weight-bold" style="font-size: 0.9rem;"><i class="fas fa-wallet mr-1"></i> {{ $upi }}</span>
                            @else
                                <span class="text-muted small">Not Registered</span>
                            @endif
                        </td>
                        <td>
                            @if($conv)
                                <span class="badge badge-{{ $conv->status === 'approved' ? 'success' : ($conv->status === 'pending' ? 'warning' : 'danger') }} px-2 py-1">
                                    {{ ucfirst($conv->status) }}
                                </span>
                            @else
                                <span class="badge badge-secondary px-2 py-1">Clicked</span>
                            @endif
                        </td>
                        <td class="font-weight-bold text-dark">₹{{ number_format($snap->advertiser_payout ?? 0, 2) }}</td>
                        <td class="font-weight-bold text-info">₹{{ number_format($snap->affiliate_allocated_payout ?? 0, 2) }}</td>
                        <td class="font-weight-bold text-success">₹{{ number_format($snap->customer_payout ?? 0, 2) }}</td>
                        <td class="font-weight-bold text-primary">₹{{ number_format($snap->affiliate_commission ?? 0, 2) }}</td>
                        <td class="font-weight-bold text-purple">₹{{ number_format($snap->platform_margin ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">No performance log records found matching your filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix bg-light">
        <div class="float-right">
            {{ $records->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

@endsection
