@extends('layouts.user')

@section('title', 'Affiliate Dashboard')

@section('content')

<!-- Welcome Banner -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card bg-gradient-primary text-white shadow-sm border-0">
            <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="font-weight-bold mb-1"><i class="fas fa-hand-wave mr-2"></i> Welcome back, {{ $user->name }}!</h3>
                    <p class="mb-0 text-white-50">Here is your performance snapshot and real-time campaign earnings overview.</p>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="{{ route('user.links.generator') }}" class="btn btn-light font-weight-bold shadow-sm rounded-pill px-4">
                        <i class="fas fa-magic text-primary mr-1"></i> Generate Link
                    </a>
                    <a href="{{ route('user.upi.index') }}" class="btn btn-outline-light font-weight-bold rounded-pill px-4 ml-2">
                        <i class="fas fa-university mr-1"></i> My UPI ID
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2-Column Summary Cards Layout -->
<div class="row mb-4">
    <!-- Row 1: Traffic & Conversions -->
    <div class="col-md-6 mb-3">
        <div class="small-box bg-info elevation-2 mb-0 h-100">
            <div class="inner p-3">
                <h3>{{ number_format($totalClicks) }}</h3>
                <p class="font-weight-bold mb-1">Total Clicks Generated</p>
                <small class="d-block opacity-75">All traffic directed across active affiliate campaigns</small>
            </div>
            <div class="icon">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <a href="{{ route('user.reports.clicks') }}" class="small-box-footer">
                View Click Details <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="small-box bg-success elevation-2 mb-0 h-100">
            <div class="inner p-3">
                <h3>{{ number_format($totalConversions) }}</h3>
                <p class="font-weight-bold mb-1">Total Conversions</p>
                <small class="d-block">Approved: <strong>{{ $approvedConversions }}</strong> | Pending: <strong>{{ $pendingConversions }}</strong></small>
            </div>
            <div class="icon">
                <i class="fas fa-check-double"></i>
            </div>
            <a href="{{ route('user.reports.conversions') }}" class="small-box-footer">
                View Conversions <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Row 2: Earnings Lifecycle -->
    <div class="col-md-6 mb-3">
        <div class="small-box bg-warning elevation-2 mb-0 h-100 text-white">
            <div class="inner p-3">
                <h3>₹{{ number_format($pendingEarnings, 2) }}</h3>
                <p class="font-weight-bold mb-1 text-dark">Pending Earnings</p>
                <small class="d-block text-dark opacity-75">Commissions awaiting advertiser validation</small>
            </div>
            <div class="icon">
                <i class="fas fa-clock text-dark"></i>
            </div>
            <a href="{{ route('user.reports.conversions', ['status' => 'pending']) }}" class="small-box-footer text-dark">
                View Pending <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="small-box bg-teal elevation-2 mb-0 h-100">
            <div class="inner p-3 text-white">
                <h3>₹{{ number_format($approvedEarnings, 2) }}</h3>
                <p class="font-weight-bold mb-1">Approved Earnings</p>
                <small class="d-block opacity-75">Verified commissions credited to your account</small>
            </div>
            <div class="icon">
                <i class="fas fa-coins"></i>
            </div>
            <a href="{{ route('user.reports.conversions', ['status' => 'approved']) }}" class="small-box-footer">
                View Approved <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Row 3: Available Balance & Paid Payouts -->
    <div class="col-md-6 mb-3">
        <div class="small-box bg-primary elevation-2 mb-0 h-100">
            <div class="inner p-3">
                <h3>₹{{ number_format($availableBalance, 2) }}</h3>
                <p class="font-weight-bold mb-1">Available Wallet Balance</p>
                <small class="d-block opacity-75">Ready for UPI settlement disbursement</small>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
            <a href="{{ route('user.wallet.index') }}" class="small-box-footer">
                Manage Wallet <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="small-box bg-purple elevation-2 mb-0 h-100">
            <div class="inner p-3 text-white">
                <h3>₹{{ number_format($paidEarnings, 2) }}</h3>
                <p class="font-weight-bold mb-1">Paid Earnings</p>
                <small class="d-block opacity-75">Total payouts successfully transferred to your bank/UPI</small>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <a href="{{ route('user.wallet.index') }}" class="small-box-footer">
                Payout History <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Performance Chart Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-chart-line text-primary mr-2"></i> Performance Analytics
                </h3>
                <div class="btn-group btn-group-toggle mt-2 mt-sm-0" data-toggle="buttons">
                    <a href="{{ route('user.dashboard', ['range' => '7_days']) }}" class="btn btn-xs btn-outline-primary {{ $range === '7_days' ? 'active' : '' }}">Last 7 Days</a>
                    <a href="{{ route('user.dashboard', ['range' => '30_days']) }}" class="btn btn-xs btn-outline-primary {{ $range === '30_days' ? 'active' : '' }}">Last 30 Days</a>
                </div>
            </div>
            <div class="card-body">
                <div style="height: 300px; position: relative;">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Top Links Section -->
<div class="row">
    <!-- Top Active Links -->
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-info shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-link text-info mr-2"></i> My Recent Links
                </h3>
                <a href="{{ route('user.links.index') }}" class="btn btn-xs btn-outline-info">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small bg-light">
                                <th>CAMPAIGN</th>
                                <th>CUSTOMER PAYOUT</th>
                                <th>MY COMMISSION</th>
                                <th>CLICKS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLinks as $link)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $link->campaign->name ?? 'N/A' }}</td>
                                    <td class="text-info font-weight-bold">₹{{ number_format($link->customer_payout, 2) }}</td>
                                    <td class="text-success font-weight-bold">₹{{ number_format($link->affiliate_commission, 2) }}</td>
                                    <td><span class="badge badge-light border px-2 py-1">{{ $link->click_count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No campaign links created yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Conversions -->
    <div class="col-lg-6 mb-4">
        <div class="card card-outline card-success shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-history text-success mr-2"></i> Recent Activity Log
                </h3>
                <a href="{{ route('user.reports.conversions') }}" class="btn btn-xs btn-outline-success">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small bg-light">
                                <th>CAMPAIGN</th>
                                <th>STATUS</th>
                                <th>COMMISSION</th>
                                <th>TIME</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentConversions as $conv)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $conv->campaign->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $conv->status === 'approved' ? 'success' : ($conv->status === 'pending' ? 'warning' : 'danger') }} px-2 py-1">
                                            {{ ucfirst($conv->status) }}
                                        </span>
                                    </td>
                                    <td class="text-success font-weight-bold">₹{{ number_format($conv->payoutSnapshot->affiliate_commission ?? 0, 2) }}</td>
                                    <td class="small text-muted">{{ $conv->conversion_time ? $conv->conversion_time->format('d M H:i') : $conv->created_at->format('d M H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No recent activity recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Clicks',
                        data: {!! json_encode($chartClicks) !!},
                        borderColor: '#17a2b8',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Conversions',
                        data: {!! json_encode($chartConversions) !!},
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Earnings (₹)',
                        data: {!! json_encode($chartEarnings) !!},
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
