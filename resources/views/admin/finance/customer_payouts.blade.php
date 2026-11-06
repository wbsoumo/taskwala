@extends('layouts.admin')

@section('title', 'End-Customer Reward Payouts')

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<!-- Summary Statistics Cards -->
<div class="row">
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="info-box shadow-sm border">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-wallet"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted text-uppercase small font-weight-bold">Total Rewards</span>
                <span class="info-box-number text-primary font-weight-bold">₹{{ number_format($stats['total_amount'], 2) }}</span>
                <span class="text-muted small">{{ number_format($stats['total_count']) }} records</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="info-box shadow-sm border">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-hourglass-half text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted text-uppercase small font-weight-bold">Pending</span>
                <span class="info-box-number text-warning font-weight-bold">₹{{ number_format($stats['pending_amount'], 2) }}</span>
                <span class="text-muted small">{{ number_format($stats['pending_count']) }} records</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="info-box shadow-sm border">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-paper-plane text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted text-uppercase small font-weight-bold">Initiated</span>
                <span class="info-box-number text-info font-weight-bold">₹{{ number_format($stats['initiated_amount'], 2) }}</span>
                <span class="text-muted small">{{ number_format($stats['initiated_count']) }} records</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="info-box shadow-sm border">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-double text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted text-uppercase small font-weight-bold">Done / Paid</span>
                <span class="info-box-number text-success font-weight-bold">₹{{ number_format($stats['done_amount'], 2) }}</span>
                <span class="text-muted small">{{ number_format($stats['done_count']) }} records</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="info-box shadow-sm border">
            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-times-circle text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted text-uppercase small font-weight-bold">Failed</span>
                <span class="info-box-number text-danger font-weight-bold">₹{{ number_format($stats['failed_amount'], 2) }}</span>
                <span class="text-muted small">{{ number_format($stats['failed_count']) }} records</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 col-12">
        <div class="info-box shadow-sm border">
            <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-ban text-white"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted text-uppercase small font-weight-bold">Cancelled</span>
                <span class="info-box-number text-secondary font-weight-bold">₹{{ number_format($stats['cancel_amount'], 2) }}</span>
                <span class="text-muted small">{{ number_format($stats['cancel_count']) }} records</span>
            </div>
        </div>
    </div>
</div>

<!-- Filters and Search Card -->
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-filter text-primary mr-2"></i> Filter & Search Records
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.finance.customer_payouts') }}" class="row align-items-end">
            <!-- Search Input -->
            <div class="col-lg-4 col-md-6 col-12 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-1">SEARCH KEYWORD</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-light border-right-0"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control border-left-0" 
                           placeholder="UPI, Holder Name, Mobile, Conversion #..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Status Selector -->
            <div class="col-lg-3 col-md-6 col-12 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-1">STAGE / STATUS</label>
                <select name="status" class="form-control">
                    <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending (Unprocessed)</option>
                    <option value="initiated" {{ request('status') === 'initiated' ? 'selected' : '' }}>Initiated (Processing)</option>
                    <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Done (Payment Completed)</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed (Payment Bounced)</option>
                    <option value="cancel" {{ request('status') === 'cancel' ? 'selected' : '' }}>Cancel (Cancelled by Admin)</option>
                </select>
            </div>

            <!-- Date Range From -->
            <div class="col-lg-2 col-md-4 col-6 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-1">FROM DATE</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>

            <!-- Date Range To -->
            <div class="col-lg-2 col-md-4 col-6 mb-3 mb-lg-0">
                <label class="small font-weight-bold text-muted mb-1">TO DATE</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>

            <!-- Action Buttons -->
            <div class="col-lg-1 col-md-4 col-12 d-flex">
                <button type="submit" class="btn btn-primary btn-block mr-1 font-weight-bold" title="Apply Filters">
                    <i class="fas fa-search"></i>
                </button>
                <a href="{{ route('admin.finance.customer_payouts') }}" class="btn btn-outline-secondary" title="Reset Filters">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Main Table Card with Bulk Processing Bar -->
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center py-3">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-hand-holding-usd text-primary mr-2"></i> End-Customer Reward Payout Records
            <span class="badge badge-pill badge-light border text-muted ml-2">{{ $payouts->total() }} records</span>
        </h3>
        <div class="card-tools d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" id="openBulkModalBtn" disabled data-toggle="modal" data-target="#bulkActionModal">
                <i class="fas fa-layer-group mr-1"></i> Bulk Process (<span id="bulkCountBadge">0</span>)
            </button>
        </div>
    </div>

    <!-- Active Selection Info Alert Bar -->
    <div id="selectionAlertBar" class="alert alert-info border-0 rounded-0 mb-0 py-2 px-3 d-none align-items-center justify-content-between">
        <div>
            <i class="fas fa-check-square mr-2"></i>
            <strong><span id="selectedCountText">0</span> customer payout(s) selected</strong> for bulk status update.
        </div>
        <div>
            <button type="button" class="btn btn-sm btn-primary font-weight-bold mr-2" data-toggle="modal" data-target="#bulkActionModal">
                <i class="fas fa-edit mr-1"></i> Apply Status to Selected
            </button>
            <button type="button" class="btn btn-sm btn-light border" id="clearSelectionBtn">
                Deselect All
            </button>
        </div>
    </div>

    <form id="bulkProcessForm" action="{{ route('admin.finance.customer_payouts.bulk_update') }}" method="POST">
        @csrf

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="selectAllCheckbox">
                                    <label class="custom-control-label" for="selectAllCheckbox"></label>
                                </div>
                            </th>
                            <th>#ID / Conv.</th>
                            <th>Campaign</th>
                            <th>Affiliate Partner</th>
                            <th>Customer UPI & Holder</th>
                            <th>Mobile</th>
                            <th>Reward Amount</th>
                            <th>Current Stage</th>
                            <th>UTR / Ref ID</th>
                            <th>Created Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $payout)
                            @php
                                $statusBadgeClass = match($payout->status) {
                                    'done', 'processed' => 'badge-success',
                                    'initiated' => 'badge-info',
                                    'pending' => 'badge-warning',
                                    'failed' => 'badge-danger',
                                    'cancel' => 'badge-secondary',
                                    default => 'badge-light border'
                                };
                                $statusLabel = match($payout->status) {
                                    'done', 'processed' => 'Done',
                                    'initiated' => 'Initiated',
                                    'pending' => 'Pending',
                                    'failed' => 'Failed',
                                    'cancel' => 'Cancel',
                                    default => ucfirst($payout->status)
                                };
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="selected_ids[]" value="{{ $payout->id }}" 
                                               class="custom-control-input row-checkbox" id="check_{{ $payout->id }}">
                                        <label class="custom-control-label" for="check_{{ $payout->id }}"></label>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark">#{{ $payout->id }}</span>
                                    @if($payout->conversion_id)
                                        <div class="small text-muted">Conv #{{ $payout->conversion_id }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark">{{ $payout->conversion->campaign->name ?? 'Direct Offer' }}</span>
                                    @if($payout->click_id)
                                        <div class="small text-muted text-truncate" style="max-width: 140px;" title="{{ $payout->click_id }}">
                                            <code>{{ $payout->click_id }}</code>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($payout->conversion && $payout->conversion->user)
                                        <span class="font-weight-bold d-block text-dark">{{ $payout->conversion->user->name }}</span>
                                        <small class="text-muted">{{ $payout->conversion->user->email }}</small>
                                    @else
                                        <span class="text-muted small">Direct / Organic</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-weight-bold text-primary">
                                        <i class="fas fa-at text-muted mr-1"></i>{{ $payout->upi_id ?? 'No UPI provided' }}
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-user text-muted mr-1"></i>{{ $payout->upi_holder_name ?: ($payout->customer_name ?: 'N/A') }}
                                    </small>
                                </td>
                                <td>
                                    <small class="text-muted font-weight-bold">{{ $payout->mobile_number ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="text-success font-weight-bold" style="font-size: 1.05rem;">
                                        ₹{{ number_format($payout->payout_amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $statusBadgeClass }} px-2 py-1 text-uppercase font-weight-bold">
                                        {{ $statusLabel }}
                                    </span>
                                    @if($payout->processed_at)
                                        <div class="small text-muted" style="font-size: 0.75rem;">
                                            {{ $payout->processed_at->format('d M H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($payout->transaction_id)
                                        <code class="text-dark bg-light px-1 py-0.5 rounded border">{{ $payout->transaction_id }}</code>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $payout->created_at->format('d M Y') }}
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $payout->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-xs btn-outline-primary font-weight-bold" data-toggle="modal" data-target="#editModal_{{ $payout->id }}">
                                        <i class="fas fa-edit mr-1"></i> Update
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                    <p class="font-weight-bold mb-1">No customer payouts matching your filter.</p>
                                    <small class="text-muted">Try resetting search keywords or status filters above.</small>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <span class="small text-muted">Showing page {{ $payouts->currentPage() }} of {{ $payouts->lastPage() }} (Total {{ $payouts->total() }} records)</span>
            <div>
                {{ $payouts->links() }}
            </div>
        </div>

        <!-- Bulk Action Modal -->
        <div class="modal fade" id="bulkActionModal" tabindex="-1" role="dialog" aria-labelledby="bulkActionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold" id="bulkActionModalLabel">
                            <i class="fas fa-layer-group mr-2"></i> Bulk Process Selected Payouts
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border mb-3">
                            <i class="fas fa-info-circle text-primary mr-1"></i> You have selected 
                            <strong><span id="bulkModalCount">0</span> customer payout(s)</strong>.
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Select New Status / Stage <span class="text-danger">*</span></label>
                            <select name="bulk_status" class="form-control" required>
                                <option value="" disabled selected>-- Select Status Stage --</option>
                                <option value="initiated">Initiated (Payment in progress)</option>
                                <option value="done">Done (Payment Transferred & Completed)</option>
                                <option value="failed">Failed (Payment Transfer Bounced / Failed)</option>
                                <option value="cancel">Cancel (Disbursement Cancelled)</option>
                                <option value="pending">Reset to Pending</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Batch Reference / UTR ID <small class="text-muted">(Optional)</small></label>
                            <input type="text" name="bulk_transaction_id" class="form-control" placeholder="e.g., BATCH_UPI_998822">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Admin Processing Notes <small class="text-muted">(Optional)</small></label>
                            <textarea name="bulk_admin_notes" class="form-control" rows="2" placeholder="e.g., Bulk UPI disbursement processed via corporate banking portal."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">
                            <i class="fas fa-check-circle mr-1"></i> Confirm & Update Selected
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Individual Update Modals for Each Record -->
@foreach($payouts as $payout)
    <div class="modal fade" id="editModal_{{ $payout->id }}" tabindex="-1" role="dialog" aria-labelledby="editModalLabel_{{ $payout->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow">
                <form action="{{ route('admin.finance.customer_payouts.update_status', $payout->id) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold text-dark" id="editModalLabel_{{ $payout->id }}">
                            <i class="fas fa-edit text-primary mr-2"></i> Update Payout #{{ $payout->id }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Payout Details Box -->
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">REWARD AMOUNT</small>
                                    <span class="text-success font-weight-bold" style="font-size: 1.2rem;">₹{{ number_format($payout->payout_amount, 2) }}</span>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">CURRENT STAGE</small>
                                    <span class="badge {{ $statusBadgeClass }} text-uppercase">{{ $statusLabel }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">TARGET UPI ID</small>
                                    <span class="font-weight-bold text-primary">{{ $payout->upi_id ?? 'N/A' }}</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">UPI HOLDER NAME</small>
                                    <span class="font-weight-bold text-dark">{{ $payout->upi_holder_name ?: ($payout->customer_name ?: 'N/A') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Stage Selector -->
                        <div class="form-group">
                            <label class="font-weight-bold">Select Status / Stage <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="initiated" {{ $payout->status === 'initiated' ? 'selected' : '' }}>Initiated (Disbursement in progress)</option>
                                <option value="done" {{ in_array($payout->status, ['done', 'processed']) ? 'selected' : '' }}>Done (Payment completed)</option>
                                <option value="failed" {{ $payout->status === 'failed' ? 'selected' : '' }}>Failed (Invalid UPI / transfer bounced)</option>
                                <option value="cancel" {{ $payout->status === 'cancel' ? 'selected' : '' }}>Cancel (Cancelled by admin)</option>
                                <option value="pending" {{ $payout->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>

                        <!-- Transaction / UTR ID -->
                        <div class="form-group">
                            <label class="font-weight-bold">Bank Reference / UPI UTR Number</label>
                            <input type="text" name="transaction_id" class="form-control" 
                                   placeholder="e.g. UPI489201948201 or IMPS reference" 
                                   value="{{ $payout->transaction_id }}">
                            <small class="form-text text-muted">Enter the banking transfer reference for audit tracking.</small>
                        </div>

                        <!-- Admin Notes -->
                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Admin Processing Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="2" 
                                      placeholder="Notes or reason (e.g. Transferred via Paytm Business / Rejection reason)...">{{ $payout->admin_notes }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary font-weight-bold">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const alertBar = document.getElementById('selectionAlertBar');
    const selectedCountText = document.getElementById('selectedCountText');
    const bulkCountBadge = document.getElementById('bulkCountBadge');
    const bulkModalCount = document.getElementById('bulkModalCount');
    const openBulkModalBtn = document.getElementById('openBulkModalBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');

    function updateSelectionState() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const count = checked.length;

        bulkCountBadge.textContent = count;
        bulkModalCount.textContent = count;
        selectedCountText.textContent = count;

        if (count > 0) {
            alertBar.classList.remove('d-none');
            alertBar.classList.add('d-flex');
            openBulkModalBtn.removeAttribute('disabled');
        } else {
            alertBar.classList.remove('d-flex');
            alertBar.classList.add('d-none');
            openBulkModalBtn.setAttribute('disabled', 'disabled');
        }

        if (selectAll) {
            selectAll.checked = rowCheckboxes.length > 0 && count === rowCheckboxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateSelectionState();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectionState);
    });

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function () {
            rowCheckboxes.forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateSelectionState();
        });
    }
});
</script>
@endpush
