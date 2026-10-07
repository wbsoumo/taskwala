@extends('layouts.user')

@section('title', 'Available Campaigns')

@section('content')

<div class="row mb-3">
    <div class="col-12">
        <div class="callout callout-info bg-white shadow-sm">
            <h5 class="font-weight-bold text-dark mb-1"><i class="fas fa-bullhorn text-info mr-2"></i> Explore Active Campaigns</h5>
            <p class="text-muted small mb-0">Select an active offer to generate your tracking link and transparently allocate customer payouts.</p>
        </div>
    </div>
</div>

<div class="row">
    @forelse($campaigns as $c)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-outline card-primary shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-info px-3 py-1 text-uppercase">{{ $c->category }}</span>
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Active</span>
                    </div>
                    <h5 class="card-title font-weight-bold text-dark w-100 mb-0">{{ $c->name }}</h5>
                </div>
                <div class="card-body d-flex flex-column pt-2">
                    <p class="text-muted small flex-grow-1 mb-3">{{ $c->short_description ?? 'Earn affiliate payouts upon verified user conversion.' }}</p>

                    <div class="card bg-light border mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small font-weight-bold text-uppercase">Allocated Payout:</span>
                                <h4 class="font-weight-bold text-success mb-0">₹{{ number_format($c->allocated_affiliate_payout, 2) }}</h4>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                <span>Conversion Event:</span>
                                <span class="font-weight-bold text-dark">{{ ucfirst(str_replace('_', ' ', $c->conversion_event)) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('user.campaigns.show', $c->public_id) }}" class="btn btn-sm btn-default border">
                            <i class="fas fa-info-circle mr-1"></i> Details
                        </a>
                        <a href="{{ route('user.links.generator', ['campaign_id' => $c->id]) }}" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                            <i class="fas fa-magic mr-1"></i> Generate Link
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 py-5 text-center text-muted">
            <i class="fas fa-folder-open fa-3x mb-3 text-secondary"></i>
            <h5>No Active Campaigns Available</h5>
            <p class="small">Check back later for new advertiser offers.</p>
        </div>
    @endforelse
</div>

@endsection
