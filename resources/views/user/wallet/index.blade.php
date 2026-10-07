@extends('layouts.user')

@section('title', 'My Wallet Ledger')

@section('content')

<!-- 2-Column Wallet Balance Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="small-box bg-success elevation-2 mb-0">
            <div class="inner p-3">
                <h3>₹{{ number_format($user->wallet->balance ?? 0, 2) }}</h3>
                <p class="font-weight-bold mb-1">Available Wallet Balance</p>
                <small class="d-block opacity-75">Approved funds ready for payout settlement</small>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
            <a href="{{ route('user.upi.index') }}" class="small-box-footer">
                Update UPI Settlement Account <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="small-box bg-warning elevation-2 mb-0 text-white">
            <div class="inner p-3">
                <h3 class="text-dark">₹{{ number_format($user->wallet->pending_balance ?? 0, 2) }}</h3>
                <p class="font-weight-bold mb-1 text-dark">Pending Balance</p>
                <small class="d-block text-dark opacity-75">Earnings awaiting advertiser validation</small>
            </div>
            <div class="icon">
                <i class="fas fa-clock text-dark"></i>
            </div>
            <a href="{{ route('user.reports.conversions', ['status' => 'pending']) }}" class="small-box-footer text-dark">
                Inspect Pending Conversions <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

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
