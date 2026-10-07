@extends('layouts.admin')

@section('title', 'Affiliate Profile: ' . $user->name)

@section('content')

<div class="row">
    <div class="col-md-4">
        <!-- Profile Card -->
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-body box-profile text-center">
                <h3 class="profile-username font-weight-bold">{{ $user->name }}</h3>
                <p class="text-muted">{{ $user->email }}</p>

                <ul class="list-group list-group-unbordered mb-3 text-left">
                    <li class="list-group-item">
                        <b>Status</b> 
                        <span class="float-right badge badge-{{ $user->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($user->status) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Mobile</b> <span class="float-right">{{ $user->mobile_number ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>UPI ID</b> <span class="float-right font-weight-bold text-primary">{{ $user->upi_id ?? 'Not set' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>UPI Holder</b> <span class="float-right">{{ $user->upi_holder_name ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Wallet Balance</b> <span class="float-right font-weight-bold text-success">₹{{ number_format($user->wallet->balance ?? 0, 2) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Pending Balance</b> <span class="float-right text-warning">₹{{ number_format($user->wallet->pending_balance ?? 0, 2) }}</span>
                    </li>
                </ul>

                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-block"><i class="fas fa-edit mr-1"></i> Edit User Profile</a>
            </div>
        </div>

        @if($user->notes)
            <div class="card card-info shadow-sm">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Admin Notes</h3></div>
                <div class="card-body"><p class="mb-0">{{ $user->notes }}</p></div>
            </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#links" data-toggle="tab"><i class="fas fa-link mr-1"></i> Generated Links</a></li>
                    <li class="nav-item"><a class="nav-link" href="#conversions" data-toggle="tab"><i class="fas fa-check-double mr-1"></i> Conversions</a></li>
                    <li class="nav-item"><a class="nav-link" href="#wallet" data-toggle="tab"><i class="fas fa-wallet mr-1"></i> Wallet Ledger</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="active tab-pane" id="links">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Campaign</th>
                                    <th>Link Token</th>
                                    <th>Customer Payout</th>
                                    <th>Commission</th>
                                    <th>Clicks</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->links as $link)
                                    <tr>
                                        <td>{{ $link->campaign->name ?? 'N/A' }}</td>
                                        <td><code>{{ Str::limit($link->secure_token, 10) }}</code></td>
                                        <td>₹{{ number_format($link->customer_payout, 2) }}</td>
                                        <td class="text-purple font-weight-bold">₹{{ number_format($link->affiliate_commission, 2) }}</td>
                                        <td>{{ $link->click_count }}</td>
                                        <td><span class="badge badge-{{ $link->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($link->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No links generated yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane" id="conversions">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Conversion ID</th>
                                    <th>Campaign</th>
                                    <th>Status</th>
                                    <th>Commission</th>
                                    <th>Customer Reward</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->conversions as $conv)
                                    <tr>
                                        <td><code>{{ Str::limit($conv->public_id, 10) }}</code></td>
                                        <td>{{ $conv->campaign->name ?? 'N/A' }}</td>
                                        <td><span class="badge badge-{{ $conv->status === 'approved' ? 'success' : 'warning' }}">{{ ucfirst($conv->status) }}</span></td>
                                        <td>₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                                        <td>₹{{ number_format($conv->payoutSnapshot->customer_payout ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No conversions recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="tab-pane" id="wallet">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Type</th>
                                    <th>Direction</th>
                                    <th>Amount</th>
                                    <th>Balance After</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($user->walletTransactions as $txn)
                                    <tr>
                                        <td><code>{{ $txn->reference }}</code></td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</td>
                                        <td><span class="badge badge-{{ $txn->direction === 'credit' ? 'success' : 'danger' }}">{{ strtoupper($txn->direction) }}</span></td>
                                        <td class="font-weight-bold">₹{{ number_format($txn->amount, 2) }}</td>
                                        <td>₹{{ number_format($txn->balance_after, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No ledger transactions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
