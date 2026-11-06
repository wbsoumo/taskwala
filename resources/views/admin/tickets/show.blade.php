@extends('layouts.admin')

@section('title', 'Review Ticket #' . $ticket->id)

@section('content')
<div class="row">
    <div class="col-md-7 col-12">
        <div class="card card-outline card-primary elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-ticket-alt text-primary mr-2"></i>Ticket #{{ $ticket->id }} Details
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Tickets
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%">Affiliate User</th>
                        <td>
                            <strong>{{ $ticket->user?->name }}</strong> (ID: {{ $ticket->user_id }})<br>
                            <small class="text-muted">{{ $ticket->user?->email }} • {{ $ticket->user?->mobile ?? 'No Mobile' }}</small><br>
                            <span class="badge badge-success">Wallet: ₹{{ number_format($ticket->user?->wallet?->balance ?? 0, 2) }}</span>
                        </td>
                    </tr>
                    <tr>
                        <th>Campaign</th>
                        <td>
                            <strong>{{ $ticket->campaign?->name }}</strong><br>
                            <small class="text-muted">Default Affiliate Payout: ₹{{ number_format($ticket->campaign?->default_affiliate_payout ?? 0, 2) }}</small>
                        </td>
                    </tr>
                    <tr>
                        <th>Order / Lead Ref</th>
                        <td><code>{{ $ticket->order_reference_id }}</code></td>
                    </tr>
                    <tr>
                        <th>Task Date</th>
                        <td>{{ $ticket->task_date?->format('d F Y') }}</td>
                    </tr>
                    @if($ticket->click_id)
                    <tr>
                        <th>Click ID</th>
                        <td><code>{{ $ticket->click_id }}</code></td>
                    </tr>
                    @endif
                    <tr>
                        <th>User Explanation / Notes</th>
                        <td>{{ $ticket->notes ?: 'No additional notes provided.' }}</td>
                    </tr>
                    <tr>
                        <th>Current Status</th>
                        <td>
                            @if($ticket->status === 'approved')
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Approved (₹{{ number_format($ticket->approved_payout ?? 0, 2) }})</span>
                            @elseif($ticket->status === 'under_review')
                                <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i>Under Review</span>
                            @elseif($ticket->status === 'rejected')
                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>Rejected</span>
                            @else
                                <span class="badge badge-secondary px-2 py-1"><i class="fas fa-hourglass-start mr-1"></i>Pending</span>
                            @endif
                        </td>
                    </tr>
                    @if($ticket->admin_notes)
                    <tr>
                        <th>Admin Notes</th>
                        <td>{{ $ticket->admin_notes }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th>Submitted At</th>
                        <td>{{ $ticket->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                </table>

                @if($ticket->screenshot_url)
                <div class="mt-4">
                    <h5 class="font-weight-bold"><i class="fas fa-image text-primary mr-1"></i> Uploaded Proof Screenshot</h5>
                    <div class="border rounded p-2 text-center bg-light">
                        <a href="{{ $ticket->screenshot_url }}" target="_blank">
                            <img src="{{ $ticket->screenshot_url }}" class="img-fluid rounded shadow-sm" style="max-height: 400px;" alt="Proof">
                        </a>
                        <div class="mt-2">
                            <a href="{{ $ticket->screenshot_url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-external-link-alt mr-1"></i> Open Full Image
                            </a>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-5 col-12">
        <div class="card card-outline card-success elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-tasks text-success mr-2"></i>Review & Process Ticket
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.tickets.update_status', $ticket->id) }}">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Decision Status</label>
                        <select name="status" id="ticketStatus" class="form-control" required onchange="togglePayoutField(this.value)">
                            <option value="under_review" {{ $ticket->status == 'under_review' ? 'selected' : '' }}>Mark Under Review</option>
                            <option value="approved" {{ $ticket->status == 'approved' ? 'selected' : '' }}>Approve & Credit Wallet</option>
                            <option value="rejected" {{ $ticket->status == 'rejected' ? 'selected' : '' }}>Reject Claim</option>
                        </select>
                    </div>

                    <div class="form-group mb-3" id="payoutFieldGroup">
                        <label class="font-weight-bold">Amount to Credit Affiliate Wallet (₹)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">₹</span>
                            </div>
                            <input type="number" step="0.01" name="approved_payout" class="form-control"
                                value="{{ old('approved_payout', $ticket->approved_payout ?? $ticket->campaign?->default_affiliate_payout ?? 0.00) }}">
                        </div>
                        <small class="form-text text-muted">Upon approval, this amount will be immediately added to affiliate's active wallet balance.</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold">Admin Response / Notes to User</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Provide notes or reason for affiliate...">{{ old('admin_notes', $ticket->admin_notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-block font-weight-bold shadow-sm py-2">
                        <i class="fas fa-check mr-1"></i> Update Ticket & Notify User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePayoutField(status) {
    const payoutGroup = document.getElementById('payoutFieldGroup');
    if (status === 'approved') {
        payoutGroup.style.display = 'block';
    } else {
        payoutGroup.style.display = 'none';
    }
}
document.addEventListener('DOMContentLoaded', function() {
    togglePayoutField(document.getElementById('ticketStatus').value);
});
</script>
@endsection
