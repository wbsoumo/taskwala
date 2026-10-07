@extends('layouts.admin')

@section('title', 'Affiliate Payout Requests')

@section('content')

<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-hand-holding-usd text-primary mr-2"></i> Affiliate Payout Requests
        </h3>
        <div class="card-tools">
            <form action="{{ route('admin.finance.affiliate_payouts') }}" method="GET" class="form-inline">
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr class="bg-light text-muted small">
                        <th>REQUEST ID</th>
                        <th>AFFILIATE USER</th>
                        <th>REQUESTED AMOUNT</th>
                        <th>TARGET UPI ID</th>
                        <th>UPI HOLDER</th>
                        <th>STATUS</th>
                        <th>TRANSACTION ID</th>
                        <th>REQUEST DATE</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payoutRequests as $req)
                        <tr>
                            <td><code>REQ_{{ strtoupper(substr($req->public_id, 0, 8)) }}</code></td>
                            <td>
                                <strong class="d-block text-dark">{{ $req->user->name ?? 'N/A' }}</strong>
                                <small class="text-muted">{{ $req->user->email ?? '' }}</small>
                            </td>
                            <td class="text-success font-weight-bold">₹{{ number_format($req->amount, 2) }}</td>
                            <td class="font-weight-bold text-primary"><i class="fas fa-at text-muted mr-1"></i>{{ $req->upi_id }}</td>
                            <td>{{ $req->upi_holder_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $req->status === 'approved' ? 'success' : ($req->status === 'pending' ? 'warning' : 'danger') }} px-2 py-1 text-uppercase">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td><code>{{ $req->transaction_id ?? 'N/A' }}</code></td>
                            <td class="small text-muted">{{ $req->created_at->format('d M Y, H:i') }}</td>
                            <td>
                                @if($req->status === 'pending')
                                    <button type="button" class="btn btn-xs btn-success font-weight-bold" data-toggle="modal" data-target="#processModal_{{ $req->id }}">
                                        <i class="fas fa-check-circle mr-1"></i> Process Payout
                                    </button>

                                    <!-- Process Payout Modal -->
                                    <div class="modal fade" id="processModal_{{ $req->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('admin.finance.affiliate_payouts.process', $req->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title font-weight-bold text-dark">
                                                            <i class="fas fa-money-check-alt text-success mr-2"></i> Process Payout #REQ_{{ strtoupper(substr($req->public_id, 0, 8)) }}
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-left">
                                                        <div class="callout callout-info py-2 mb-3">
                                                            <strong class="d-block text-dark">Affiliate: {{ $req->user->name ?? 'N/A' }}</strong>
                                                            <span class="d-block text-success font-weight-bold h5 mb-0">Amount: ₹{{ number_format($req->amount, 2) }}</span>
                                                            <span class="d-block text-primary small">Target UPI: <code>{{ $req->upi_id }}</code></span>
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold text-dark">Action <span class="text-danger">*</span></label>
                                                            <select name="action" class="form-control" required>
                                                                <option value="approve">Approve & Record Transfer</option>
                                                                <option value="reject">Reject & Refund Balance</option>
                                                            </select>
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="font-weight-bold text-dark">UPI Bank Transaction / UTR ID</label>
                                                            <input type="text" name="transaction_id" class="form-control" placeholder="e.g. UTR1234567890 (Required for Approval)">
                                                        </div>

                                                        <div class="form-group mb-0">
                                                            <label class="font-weight-bold text-dark">Admin Remarks / Notes</label>
                                                            <textarea name="admin_notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3"><i class="fas fa-save mr-1"></i> Submit Decision</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No affiliate payout requests found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix">
        {{ $payoutRequests->links() }}
    </div>
</div>

@endsection
