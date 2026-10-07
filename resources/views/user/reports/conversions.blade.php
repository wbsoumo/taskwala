@extends('layouts.user')

@section('title', 'Conversions & UPI Report')

@section('content')

<!-- Filter & Download Header Card -->
<div class="card card-outline card-secondary shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-filter text-secondary mr-2"></i> Report & UPI Filters
        </h3>
        <a href="{{ route('user.reports.export', request()->all()) }}" class="btn btn-sm btn-success font-weight-bold shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-file-csv mr-1"></i> Download CSV Report
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('user.reports.conversions') }}" method="GET" class="row">
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold text-muted">SEARCH UPI ID</label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fas fa-at text-muted"></i></span></div>
                    <input type="text" name="upi_search" class="form-control" value="{{ request('upi_search') }}" placeholder="Search by UPI (e.g. user@upi)...">
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small font-weight-bold text-muted">CAMPAIGN</label>
                <select name="campaign_id" class="form-control form-control-sm">
                    <option value="">All Campaigns</option>
                    @foreach($campaigns as $c)
                        <option value="{{ $c->id }}" {{ request('campaign_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold text-muted">STATUS</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold text-muted">START DATE</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2 mb-2">
                <label class="small font-weight-bold text-muted">END DATE</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>
            <div class="col-12 text-right mt-2">
                <button type="submit" class="btn btn-sm btn-primary font-weight-bold px-3"><i class="fas fa-search mr-1"></i> Filter Report</button>
                <a href="{{ route('user.reports.conversions') }}" class="btn btn-sm btn-default border ml-1"><i class="fas fa-redo mr-1"></i> Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-outline card-success shadow-sm">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-check-double text-success mr-2"></i> UPI & Conversion Activity Logs
        </h3>
    </div>
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>CUSTOMER UPI ID</th>
                        <th>CONVERSION ID</th>
                        <th>CAMPAIGN</th>
                        <th>STATUS</th>
                        <th>CUSTOMER REWARD</th>
                        <th>MY COMMISSION</th>
                        <th>PAYOUT STATUS</th>
                        <th>CONVERSION TIME</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($conversions as $conv)
                        @php
                            $cp = $conv->customerPayout ?? $conv->click?->customerPayout;
                            $upiId = $cp?->upi_id ?? 'N/A';
                            $payoutStatus = $cp ? ucfirst($cp->status) : ($conv->status === 'approved' ? 'Paid' : 'Pending');
                            $payoutBadgeColor = $cp ? ($cp->status === 'paid' ? 'success' : ($cp->status === 'pending' ? 'warning' : 'danger')) : ($conv->status === 'approved' ? 'success' : 'secondary');
                        @endphp
                        <tr>
                            <td class="font-weight-bold text-primary">
                                <i class="fas fa-at text-muted mr-1"></i>{{ $upiId }}
                            </td>
                            <td><code>{{ $conv->public_id }}</code></td>
                            <td class="font-weight-bold text-dark">{{ $conv->campaign->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $conv->status === 'approved' ? 'success' : ($conv->status === 'pending' ? 'warning' : 'danger') }} px-3 py-1 text-uppercase">
                                    {{ ucfirst($conv->status) }}
                                </span>
                            </td>
                            <td class="text-info font-weight-bold">₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ $payoutBadgeColor }} px-2 py-1">
                                    {{ $payoutStatus }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $conv->conversion_time ? $conv->conversion_time->format('d M Y, H:i') : 'N/A' }}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-outline-info font-weight-bold" data-toggle="modal" data-target="#timelineModal_{{ $conv->id }}">
                                    <i class="fas fa-stream mr-1"></i> Timeline
                                </button>

                                <!-- Lifecycle Timeline Modal -->
                                <div class="modal fade" id="timelineModal_{{ $conv->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title font-weight-bold text-dark">
                                                    <i class="fas fa-history text-primary mr-2"></i> UPI Timeline: {{ $upiId }}
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="timeline border-left border-primary pl-3 ml-2">
                                                    <div class="mb-3">
                                                        <i class="fas fa-mouse-pointer text-primary"></i>
                                                        <strong class="d-block text-dark">1. Click Received</strong>
                                                        <small class="text-muted">{{ $conv->click->created_at ? $conv->click->created_at->format('d M Y, H:i:s') : 'N/A' }}</small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <i class="fas fa-at text-info"></i>
                                                        <strong class="d-block text-dark">2. Customer UPI Recorded</strong>
                                                        <small class="text-muted">UPI ID: <code>{{ $upiId }}</code></small>
                                                    </div>
                                                    <div class="mb-3">
                                                        <i class="fas fa-{{ $conv->status === 'approved' ? 'check-double text-success' : ($conv->status === 'pending' ? 'clock text-warning' : 'times-circle text-danger') }}"></i>
                                                        <strong class="d-block text-dark">3. Verification Status: {{ ucfirst($conv->status) }}</strong>
                                                        <small class="text-muted">My Commission: ₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</small>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-wallet text-{{ $conv->status === 'approved' ? 'success' : 'secondary' }}"></i>
                                                        <strong class="d-block text-dark">4. Payout Status: {{ $payoutStatus }}</strong>
                                                        <small class="text-muted">Customer Reward: ₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light py-2">
                                                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No UPI conversion records found matching your filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Stack View -->
        <div class="d-md-none p-3">
            @forelse($conversions as $conv)
                @php
                    $cp = $conv->customerPayout ?? $conv->click?->customerPayout;
                    $upiId = $cp?->upi_id ?? 'N/A';
                    $payoutStatus = $cp ? ucfirst($cp->status) : ($conv->status === 'approved' ? 'Paid' : 'Pending');
                    $payoutBadgeColor = $cp ? ($cp->status === 'paid' ? 'success' : ($cp->status === 'pending' ? 'warning' : 'danger')) : ($conv->status === 'approved' ? 'success' : 'secondary');
                @endphp
                <div class="card card-outline card-{{ $conv->status === 'approved' ? 'success' : ($conv->status === 'pending' ? 'warning' : 'danger') }} shadow-sm mb-3">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="font-weight-bold mb-0 text-primary"><i class="fas fa-at mr-1"></i>{{ $upiId }}</h6>
                        <div>
                            <span class="badge badge-{{ $conv->status === 'approved' ? 'success' : ($conv->status === 'pending' ? 'warning' : 'danger') }} px-2 py-1 text-uppercase mr-1">
                                {{ ucfirst($conv->status) }}
                            </span>
                            <span class="badge badge-{{ $payoutBadgeColor }} px-2 py-1 text-uppercase">
                                {{ $payoutStatus }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Campaign:</span>
                            <span class="font-weight-bold text-dark">{{ $conv->campaign->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Customer Reward:</span>
                            <span class="font-weight-bold text-info">₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">My Commission:</span>
                            <span class="font-weight-bold text-success">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Payout Status:</span>
                            <span class="font-weight-bold text-dark">{{ $payoutStatus }}</span>
                        </div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Conversion Date:</span>
                            <span class="text-dark">{{ $conv->conversion_time ? $conv->conversion_time->format('d M Y, H:i') : '' }}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-white text-right py-2">
                        <button type="button" class="btn btn-xs btn-outline-info font-weight-bold" data-toggle="modal" data-target="#mobTimelineModal_{{ $conv->id }}">
                            <i class="fas fa-stream mr-1"></i> View Timeline
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No UPI conversion records found.</div>
            @endforelse
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $conversions->links() }}
    </div>
</div>

@endsection
