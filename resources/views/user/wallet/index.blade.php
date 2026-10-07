@extends('layouts.user')

@section('title', 'My Wallet Ledger')

@section('content')

<div class="row mb-4">
    <div class="col-md-6 mb-3 mb-md-0">
        <div class="stat-card stat-card-blue shadow-sm">
            <h6 class="text-uppercase small opacity-75">Approved Wallet Balance</h6>
            <h2 class="font-weight-bold mb-0">₹{{ number_format($user->wallet->balance ?? 0, 2) }}</h2>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card stat-card-amber shadow-sm">
            <h6 class="text-uppercase small opacity-75">Pending Wallet Balance</h6>
            <h2 class="font-weight-bold mb-0">₹{{ number_format($user->wallet->pending_balance ?? 0, 2) }}</h2>
        </div>
    </div>
</div>

<div class="card card-custom border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="font-weight-bold mb-0"><i class="fas fa-list-alt text-primary mr-2"></i>Financial Ledger Transactions</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-muted small bg-light">
                        <th>REFERENCE</th>
                        <th>TYPE</th>
                        <th>DIRECTION</th>
                        <th>AMOUNT</th>
                        <th>BALANCE AFTER</th>
                        <th>DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td><code>{{ $txn->reference }}</code></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</td>
                            <td><span class="badge badge-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">{{ strtoupper($txn->direction) }}</span></td>
                            <td class="font-weight-bold text-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">
                                {{ $txn->direction === 'credit' ? '+' : '-' }}₹{{ number_format($txn->amount, 2) }}
                            </td>
                            <td class="font-weight-bold">₹{{ number_format($txn->balance_after, 2) }}</td>
                            <td>{{ $txn->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No wallet transactions logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-0 py-3">{{ $transactions->links() }}</div>
</div>

@endsection
