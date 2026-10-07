@extends('layouts.admin')

@section('title', 'Double-Entry Financial Wallet Ledger')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-wallet text-primary mr-2"></i>Double-Entry Wallet Transactions Master Audit</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Transaction Ref</th>
                        <th>User</th>
                        <th>Conversion</th>
                        <th>Type</th>
                        <th>Direction</th>
                        <th>Amount</th>
                        <th>Balance Before</th>
                        <th>Balance After</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                        <tr>
                            <td><code>{{ $txn->reference }}</code></td>
                            <td class="font-weight-bold">{{ $txn->user->name ?? 'N/A' }}</td>
                            <td>{{ $txn->conversion ? '#' . $txn->conversion->id : '-' }}</td>
                            <td><span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</span></td>
                            <td><span class="badge badge-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">{{ strtoupper($txn->direction) }}</span></td>
                            <td class="font-weight-bold text-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">
                                {{ $txn->direction === 'credit' ? '+' : '-' }}₹{{ number_format($txn->amount, 2) }}
                            </td>
                            <td>₹{{ number_format($txn->balance_before, 2) }}</td>
                            <td class="font-weight-bold">₹{{ number_format($txn->balance_after, 2) }}</td>
                            <td>{{ $txn->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No wallet transactions recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $transactions->links() }}
    </div>
</div>

@endsection
