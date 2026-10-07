@extends('layouts.user')

@section('title', 'My Wallet & Payout Requests')

@section('content')

<!-- 2-Column Wallet Balance Summary Cards -->
<div class="row mb-4">
    <div class="col-6 col-md-6 mb-3">
        <div class="small-box bg-success elevation-2 mb-0 h-100">
            <div class="inner p-2 p-md-3">
                <h3 class="h4 h3-md">₹{{ number_format($user->wallet->balance ?? 0, 2) }}</h3>
                <p class="font-weight-bold mb-1 small text-truncate">Available Wallet Balance</p>
                <small class="d-none d-md-block opacity-75">Approved funds ready for payout settlement</small>
            </div>
            <div class="icon d-none d-sm-block">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="small-box-footer small py-1">
                @if(empty($user->upi_id))
                    <a href="{{ route('user.upi.index') }}" class="text-white font-weight-bold"><i class="fas fa-exclamation-triangle text-warning mr-1"></i> Set Up UPI ID First</a>
                @else
                    <span class="text-white small"><i class="fas fa-check-circle mr-1"></i> Active UPI: <code>{{ $user->upi_id }}</code></span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-6 col-md-6 mb-3">
        <div class="small-box bg-warning elevation-2 mb-0 h-100 text-white">
            <div class="inner p-2 p-md-3">
                <h3 class="h4 h3-md text-dark">₹{{ number_format($user->wallet->pending_balance ?? 0, 2) }}</h3>
                <p class="font-weight-bold mb-1 text-dark small text-truncate">Pending Balance</p>
                <small class="d-none d-md-block text-dark opacity-75">Earnings awaiting advertiser validation</small>
            </div>
            <div class="icon d-none d-sm-block">
                <i class="fas fa-clock text-dark"></i>
            </div>
            <a href="{{ route('user.reports.conversions', ['status' => 'pending']) }}" class="small-box-footer text-dark small py-1">
                Inspect Pending <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Payout Request Form Card -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-paper-plane text-success mr-2"></i> Request Payout Settlement
        </h3>
    </div>
    <div class="card-body">
        @if(empty($user->upi_id))
            <div class="callout callout-warning bg-light border-left-warning mb-0">
                <h5 class="font-weight-bold text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> UPI ID Not Configured</h5>
                <p class="mb-2 text-muted small">You must save a valid UPI ID before submitting payout requests.</p>
                <a href="{{ route('user.upi.index') }}" class="btn btn-sm btn-warning font-weight-bold shadow-sm">
                    <i class="fas fa-university mr-1"></i> Configure UPI Details
                </a>
            </div>
        @else
            <form action="{{ route('user.wallet.request_payout') }}" method="POST">
                @csrf
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="font-weight-bold text-dark mb-1">Target Payout UPI ID</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-white"><i class="fas fa-at text-muted"></i></span></div>
                            <input type="text" class="form-control font-weight-bold bg-light" value="{{ $user->upi_id }}" disabled readonly>
                            <div class="input-group-append">
                                <a href="{{ route('user.upi.index') }}" class="btn btn-outline-secondary" title="Change UPI ID"><i class="fas fa-edit"></i> Edit</a>
                            </div>
                        </div>
                        <small class="form-text text-muted">Settlement funds will be sent to this saved UPI destination.</small>
                    </div>

                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="font-weight-bold text-dark mb-1">Request Amount (₹) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold">₹</span></div>
                            <input type="number" step="0.01" name="amount" class="form-control font-weight-bold text-success" placeholder="0.00" min="1" max="{{ $user->wallet->balance ?? 0 }}" required>
                        </div>
                        <small class="form-text text-muted">Available: <strong>₹{{ number_format($user->wallet->balance ?? 0, 2) }}</strong></small>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success btn-block font-weight-bold shadow-sm py-2" {{ ($user->wallet->balance ?? 0) <= 0 ? 'disabled' : '' }}>
                            <i class="fas fa-check-circle mr-1"></i> Request Payout
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

<!-- Payout Requests History -->
@if(isset($payoutRequests) && $payoutRequests->count() > 0)
    <div class="card card-outline card-info shadow-sm mb-4">
        <div class="card-header bg-white">
            <h3 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-history text-info mr-2"></i> Payout Requests History
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-muted small bg-light">
                            <th>REQUEST ID</th>
                            <th>AMOUNT</th>
                            <th>TARGET UPI</th>
                            <th>STATUS</th>
                            <th>TRANSACTION ID / REF</th>
                            <th>SUBMITTED DATE</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payoutRequests as $req)
                            <tr>
                                <td><code>REQ_{{ strtoupper(substr($req->public_id, 0, 8)) }}</code></td>
                                <td class="font-weight-bold text-success">₹{{ number_format($req->amount, 2) }}</td>
                                <td class="font-weight-bold text-primary"><i class="fas fa-at text-muted mr-1"></i>{{ $req->upi_id }}</td>
                                <td>
                                    <span class="badge badge-{{ $req->status === 'approved' ? 'success' : ($req->status === 'pending' ? 'warning' : 'danger') }} px-2 py-1 text-uppercase">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </td>
                                <td><code>{{ $req->transaction_id ?? 'Awaiting Processing' }}</code></td>
                                <td class="small text-muted">{{ $req->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-2">
            {{ $payoutRequests->links() }}
        </div>
    </div>
@endif

<!-- Wallet Ledger Transactions -->
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-list-alt text-primary mr-2"></i> Financial Ledger & Wallet Transactions
        </h3>
    </div>
    <div class="card-body p-0">
        <!-- Desktop Table View -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>REFERENCE</th>
                        <th>TRANSACTION TYPE</th>
                        <th>DIRECTION</th>
                        <th>AMOUNT</th>
                        <th>BALANCE AFTER</th>
                        <th>DATE & TIME</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td><code>{{ $txn->reference }}</code></td>
                            <td class="font-weight-bold text-dark">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</td>
                            <td>
                                <span class="badge badge-{{ $txn->direction === 'credit' ? 'success' : 'danger' }} px-2 py-1">
                                    {{ strtoupper($txn->direction) }}
                                </span>
                            </td>
                            <td class="font-weight-bold text-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">
                                {{ $txn->direction === 'credit' ? '+' : '-' }}₹{{ number_format($txn->amount, 2) }}
                            </td>
                            <td class="font-weight-bold text-dark">₹{{ number_format($txn->balance_after, 2) }}</td>
                            <td class="small text-muted">{{ $txn->created_at->format('d M Y, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No wallet ledger transactions logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card Stack View -->
        <div class="d-md-none p-3">
            @forelse($transactions as $txn)
                <div class="card card-outline card-{{ $txn->direction === 'credit' ? 'success' : 'danger' }} shadow-sm mb-3">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between my-1">
                            <span class="font-weight-bold text-dark">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span>
                            <span class="font-weight-bold text-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">
                                {{ $txn->direction === 'credit' ? '+' : '-' }}₹{{ number_format($txn->amount, 2) }}
                            </span>
                        </div>
                        <div class="small text-muted my-1">Ref: <code>{{ $txn->reference }}</code></div>
                        <div class="d-flex justify-content-between my-1 small">
                            <span class="text-muted">Balance After: ₹{{ number_format($txn->balance_after, 2) }}</span>
                            <span class="text-muted">{{ $txn->created_at->format('d M, H:i') }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No wallet transactions logged.</div>
            @endforelse
        </div>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $transactions->links() }}
    </div>
</div>

@endsection
