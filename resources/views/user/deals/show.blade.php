@extends('layouts.user')

@section('title', $deal->title)

@section('content')
<div class="content-header pt-2 pb-3">
    <div class="container-fluid">
        <!-- Breadcrumb Navigation -->
        <div class="mb-3">
            <a href="{{ route('user.deals.index') }}" class="btn btn-sm btn-outline-secondary font-weight-bold">
                <i class="fas fa-arrow-left mr-1"></i> Back to All Deals
            </a>
        </div>

        <div class="row">
            <!-- Left: Deal Visual & Highlights -->
            <div class="col-lg-7 col-12 mb-4">
                <div class="card card-custom shadow-sm border-0 h-100 overflow-hidden">
                    <div class="bg-light p-4 text-center position-relative d-flex align-items-center justify-content-center" style="min-height: 380px;">
                        @if($deal->computed_discount)
                            <span class="badge badge-danger position-absolute font-weight-bold px-3 py-2 shadow-sm" style="top: 16px; right: 16px; font-size: 1rem;">
                                {{ $deal->computed_discount }}% OFF
                            </span>
                        @endif

                        <span class="badge badge-light border position-absolute" style="top: 16px; left: 16px; font-size: 0.85rem;">
                            <i class="fas fa-folder mr-1 text-primary"></i> {{ $deal->category }}
                        </span>

                        @if($deal->image_url)
                            <img src="{{ $deal->image_url }}" alt="{{ $deal->title }}" class="img-fluid rounded" style="max-height: 340px; object-fit: contain;">
                        @else
                            <div class="text-muted py-5">
                                <i class="fas fa-box-open fa-4x text-secondary mb-2" style="opacity: 0.6;"></i>
                                <h5>Special Offer Available</h5>
                            </div>
                        @endif
                    </div>

                    <div class="card-body p-4">
                        <h4 class="font-weight-bold text-dark mb-3">{{ $deal->title }}</h4>

                        @if($deal->description)
                            <h6 class="font-weight-bold text-secondary text-uppercase small mb-2">Deal Information & Highlights</h6>
                            <div class="p-3 bg-light rounded text-dark mb-3" style="white-space: pre-line; line-height: 1.6;">
                                {{ $deal->description }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Pricing, Sharing, and Grab Deal -->
            <div class="col-lg-5 col-12 mb-4">
                <div class="card card-custom shadow-sm border-0 mb-4 sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <!-- Pricing Banner -->
                        <div class="d-flex align-items-baseline mb-2">
                            @if($deal->price)
                                <span class="h2 font-weight-bold text-success mb-0 mr-3">
                                    ₹{{ number_format($deal->price, 2) }}
                                </span>
                            @else
                                <span class="h3 font-weight-bold text-primary mb-0 mr-3">Hot Deal</span>
                            @endif

                            @if($deal->original_price)
                                <del class="text-muted h5 mb-0 mr-2">
                                    ₹{{ number_format($deal->original_price, 2) }}
                                </del>
                            @endif
                        </div>

                        <!-- Commission Tag -->
                        <div class="alert alert-warning border-0 d-flex align-items-center mb-4 py-2 px-3">
                            <i class="fas fa-coins text-warning fa-2x mr-3"></i>
                            <div>
                                <div class="font-weight-bold text-dark">Affiliate Profit Potential</div>
                                <div class="small text-muted">{{ $deal->commission_rate ?: 'Earn High Commission on purchases' }}</div>
                            </div>
                        </div>

                        <!-- Outbound Grab Deal Button -->
                        <a href="{{ route('user.deals.redirect', $deal->public_id) }}" target="_blank" class="btn btn-primary btn-block btn-lg font-weight-bold py-3 shadow mb-3">
                            <i class="fas fa-bolt mr-2"></i> Grab This Deal Now
                        </a>

                        <div class="text-center text-muted small mb-4">
                            <i class="fas fa-shield-alt text-success mr-1"></i> Verified safe link • Instant redirect
                        </div>

                        <hr>

                        <!-- Affiliate Link & Share Section -->
                        <h6 class="font-weight-bold text-dark mb-2">Share & Earn</h6>
                        <p class="small text-muted mb-3">
                            Share this offer link with your friends or Telegram / WhatsApp groups. When anyone shops using your link, commission will be credited directly to your Taskwala wallet!
                        </p>

                        <!-- Monetized Link Input -->
                        <div class="input-group mb-3">
                            <input type="text" id="dealShareLink" class="form-control" value="{{ url('/deals/' . $deal->public_id . '/out') }}" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" onclick="copyShareLink(this)" title="Copy Link">
                                    <i class="fas fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                        </div>

                        <!-- Share Buttons -->
                        @php
                            $shareMsg = rawurlencode("🔥 *HOT DEAL ALERT!* \n\n*" . $deal->title . "*\n\n💰 *Offer Price:* ₹" . number_format($deal->price ?? 0) . " (" . ($deal->computed_discount ?? 0) . "% OFF)\n\n👉 *Order Online Here:* " . url('/deals/' . $deal->public_id . '/out'));
                        @endphp
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="https://api.whatsapp.com/send?text={{ $shareMsg }}" target="_blank" class="btn btn-success btn-block font-weight-bold">
                                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="https://t.me/share/url?url={{ urlencode(url('/deals/' . $deal->public_id . '/out')) }}&text={{ urlencode('🔥 ' . $deal->title . ' at ₹' . number_format($deal->price ?? 0)) }}" target="_blank" class="btn btn-info btn-block font-weight-bold">
                                    <i class="fab fa-telegram-plane mr-1"></i> Telegram
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Deals Section -->
        @if($relatedDeals->count() > 0)
            <div class="mt-4">
                <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-fire text-danger mr-1"></i> Similar Deals in {{ $deal->category }}</h5>
                <div class="row">
                    @foreach($relatedDeals as $rel)
                        <div class="col-lg-3 col-md-6 col-12 mb-3">
                            <div class="card card-custom h-100 shadow-sm border-0 p-3">
                                <div class="text-center mb-2" style="height: 120px;">
                                    @if($rel->image_url)
                                        <img src="{{ $rel->image_url }}" alt="{{ $rel->title }}" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    @else
                                        <i class="fas fa-tag fa-2x text-muted mt-4"></i>
                                    @endif
                                </div>
                                <h6 class="font-weight-bold text-truncate mb-1">
                                    <a href="{{ route('user.deals.show', $rel->public_id) }}" class="text-dark">{{ $rel->title }}</a>
                                </h6>
                                <div class="text-success font-weight-bold mb-2">₹{{ number_format($rel->price ?? 0) }}</div>
                                <a href="{{ route('user.deals.show', $rel->public_id) }}" class="btn btn-xs btn-outline-primary font-weight-bold">View Deal</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyShareLink(btn) {
        const link = document.getElementById('dealShareLink').value;
        navigator.clipboard.writeText(link).then(function() {
            const old = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-white"></i> Copied!';
            setTimeout(() => { btn.innerHTML = old; }, 1800);
        });
    }
</script>
@endpush
