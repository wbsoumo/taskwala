@extends('layouts.admin')

@section('title', 'Financial Master Report & Reconciliation')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-file-invoice-dollar text-success mr-2"></i>Platform Financial Ledger Summary</h3></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2 border-right">
                        <h5 class="text-muted">Total Gross Revenue</h5>
                        <h3 class="text-primary font-weight-bold">₹{{ number_format($totalRevenue, 2) }}</h3>
                    </div>
                    <div class="col-md-3 border-right">
                        <h5 class="text-muted">Affiliate Allocation</h5>
                        <h3 class="font-weight-bold">₹{{ number_format($totalAffiliateAllocation, 2) }}</h3>
                    </div>
                    <div class="col-md-2 border-right">
                        <h5 class="text-muted">Customer Payouts</h5>
                        <h3 class="text-info font-weight-bold">₹{{ number_format($totalCustomerPayout, 2) }}</h3>
                    </div>
                    <div class="col-md-2 border-right">
                        <h5 class="text-muted">Affiliate Commission</h5>
                        <h3 class="text-purple font-weight-bold">₹{{ number_format($totalAffiliateCommission, 2) }}</h3>
                    </div>
                    <div class="col-md-3">
                        <h5 class="text-muted">Platform Gross Margin</h5>
                        <h3 class="text-success font-weight-bold">₹{{ number_format($totalMargin, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
