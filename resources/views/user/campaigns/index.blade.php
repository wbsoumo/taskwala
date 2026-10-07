@extends('layouts.user')

@section('title', 'Available Campaigns')

@section('content')

<div class="row mb-4">
    <div class="col-12">
        <h4 class="font-weight-bold text-dark mb-1">Explore Active Campaigns</h4>
        <p class="text-muted">Generate tracking links and split payouts with your customers transparently.</p>
    </div>
</div>

<div class="row">
    @forelse($campaigns as $c)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-custom h-100 border-0">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge badge-primary px-3 py-1 rounded-pill">{{ $c->category }}</span>
                        <span class="badge badge-success px-3 py-1 rounded-pill">Active</span>
                    </div>

                    <h5 class="font-weight-bold mb-2">{{ $c->name }}</h5>
                    <p class="text-muted small mb-4 flex-grow-1">{{ $c->short_description ?? 'Earn payouts per successful conversion event.' }}</p>

                    <div class="bg-light p-3 rounded-lg mb-3 border">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small font-weight-bold">YOUR ALLOCATED PAYOUT:</span>
                            <span class="h4 font-weight-bold text-success mb-0">₹{{ number_format($c->allocated_affiliate_payout, 2) }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <a href="{{ route('user.campaigns.show', $c->public_id) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Details</a>
                        <a href="{{ route('user.links.generator', ['campaign_id' => $c->id]) }}" class="btn btn-primary btn-sm rounded-pill px-3 font-weight-bold">
                            <i class="fas fa-magic mr-1"></i> Generate Link
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5 text-muted">No active campaigns available right now.</div>
    @endforelse
</div>

@endsection
