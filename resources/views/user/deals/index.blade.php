@extends('layouts.user')

@section('title', 'Trending Deals & Loot Offers')

@push('styles')
<style>
    .deal-card {
        transition: transform 0.22s ease, box-shadow 0.22s ease;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .deal-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08) !important;
        border-color: #cbd5e1;
    }
    .deal-img-wrapper {
        height: 190px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        padding: 12px;
    }
    .deal-img {
        max-height: 100%;
        max-width: 100%;
        object-fit: contain;
        transition: transform 0.3s ease;
    }
    .deal-card:hover .deal-img {
        transform: scale(1.04);
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .cat-pill {
        border-radius: 50px;
        padding: 6px 16px;
        font-weight: 600;
        font-size: 0.84rem;
        transition: all 0.2s ease;
    }
    .cat-pill.active {
        background: #0284c7 !important;
        color: #fff !important;
        box-shadow: 0 4px 10px rgba(2, 132, 199, 0.28);
    }
</style>
@endpush

@section('content')
<div class="content-header pt-2 pb-3">
    <div class="container-fluid">
        <!-- Hero Banner -->
        <div class="bg-gradient-primary rounded-lg p-4 text-white shadow-sm mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0369a1 100%);">
            <div class="row align-items-center">
                <div class="col-lg-8 col-12">
                    <span class="badge badge-warning text-dark font-weight-bold px-3 py-1 mb-2 text-uppercase letter-spacing-1">
                        <i class="fas fa-fire mr-1 text-danger"></i> High Commission Loot Deals
                    </span>
                    <h2 class="font-weight-bold mb-2">Curated Deals & Affiliate Offers</h2>
                    <p class="text-light mb-3" style="opacity: 0.9; max-width: 620px;">
                        Share verified deals directly with your audience, friends, or social groups. Every purchase through your monetized link earns direct profits in your Taskwala wallet!
                    </p>
                </div>
                <div class="col-lg-4 col-12 text-lg-right mt-3 mt-lg-0">
                    <div class="bg-white p-3 rounded-lg text-dark d-inline-block text-left shadow-sm">
                        <div class="small text-muted font-weight-bold">YOUR WALLET BALANCE</div>
                        <div class="h4 font-weight-bold text-success mb-0">₹{{ number_format(auth('web')->user()->wallet->balance ?? 0, 2) }}</div>
                        <a href="{{ route('user.wallet.index') }}" class="small font-weight-bold text-primary">View Wallet & Payouts <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>

            <!-- Search Bar inside Hero -->
            <form action="{{ route('user.deals.index') }}" method="GET" class="mt-3">
                <div class="input-group bg-white rounded-pill p-1 shadow">
                    <div class="input-group-prepend pl-3">
                        <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" name="search" class="form-control border-0 bg-transparent text-dark" placeholder="Search smartphones, headphones, fashion, discounts..." value="{{ request('search') }}">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 font-weight-bold">
                            Search Deals
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Category Filter Pills & Sort Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <!-- Category Pills -->
            <div class="d-flex flex-wrap gap-2 mb-2 mb-md-0" style="gap: 8px;">
                <a href="{{ route('user.deals.index', array_merge(request()->except('category', 'page'), ['category' => 'all'])) }}" 
                   class="btn btn-sm cat-pill {{ !request('category') || request('category') === 'all' ? 'active' : 'btn-light border text-dark' }}">
                    <i class="fas fa-th-large mr-1"></i> All Deals
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('user.deals.index', array_merge(request()->except('category', 'page'), ['category' => $cat])) }}" 
                       class="btn btn-sm cat-pill {{ request('category') === $cat ? 'active' : 'btn-light border text-dark' }}">
                        {{ $cat }}
                    </a>
                @endforeach
            </div>

            <!-- Sort Selection -->
            <div class="d-flex align-items-center">
                <span class="small text-muted font-weight-bold mr-2 d-none d-sm-inline">Sort By:</span>
                <form action="{{ route('user.deals.index') }}" method="GET" class="d-inline">
                    @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                    @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                    <select name="sort" class="form-control form-control-sm rounded-pill font-weight-bold border" onchange="this.form.submit()">
                        <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>🔥 Newest Deals</option>
                        <option value="discount" {{ request('sort') === 'discount' ? 'selected' : '' }}>💥 Highest Discount</option>
                        <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>🏷️ Lowest Price</option>
                        <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>💎 Highest Price</option>
                        <option value="popular" {{ request('sort') === 'popular' ? 'selected' : '' }}>⭐ Most Popular</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Deals Grid -->
        <div class="row">
            @forelse($deals as $deal)
                <div class="col-xl-3 col-lg-4 col-md-6 col-12 mb-4">
                    <div class="card deal-card h-100 bg-white shadow-sm border-0">
                        <!-- Card Image -->
                        <div class="deal-img-wrapper">
                            @if($deal->computed_discount)
                                <span class="badge badge-danger position-absolute font-weight-bold px-2 py-1 shadow-sm" style="top: 10px; right: 10px; font-size: 0.8rem; z-index: 2;">
                                    {{ $deal->computed_discount }}% OFF
                                </span>
                            @endif
                            <span class="badge badge-light border position-absolute small font-weight-bold" style="top: 10px; left: 10px; z-index: 2;">
                                <i class="fas fa-store mr-1 text-primary"></i> {{ $deal->merchant_name }}
                            </span>

                            @if($deal->image_url)
                                <img src="{{ $deal->image_url }}" alt="{{ $deal->title }}" class="deal-img" loading="lazy">
                            @else
                                <div class="text-center text-muted p-4">
                                    <i class="fas fa-shopping-bag fa-3x mb-2 text-secondary" style="opacity: 0.5;"></i>
                                    <div class="small font-weight-bold">Exclusive Deal</div>
                                </div>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                            <div>
                                <!-- Commission Tag -->
                                <div class="mb-2">
                                    <span class="badge badge-warning text-dark font-weight-bold px-2 py-1 small">
                                        <i class="fas fa-coins mr-1"></i> {{ $deal->commission_rate ?: 'Earn Profit' }}
                                    </span>
                                    <small class="text-muted float-right">
                                        <i class="far fa-clock mr-1"></i> {{ $deal->posted_at ? $deal->posted_at->diffForHumans(null, true) : 'New' }}
                                    </small>
                                </div>

                                <!-- Title -->
                                <h6 class="font-weight-bold text-dark line-clamp-2 mb-2" title="{{ $deal->title }}" style="min-height: 42px;">
                                    <a href="{{ route('user.deals.show', $deal->public_id) }}" class="text-dark text-decoration-none">
                                        {{ $deal->title }}
                                    </a>
                                </h6>

                                <!-- Price Section -->
                                <div class="d-flex align-items-baseline mb-3">
                                    @if($deal->price)
                                        <span class="h4 font-weight-bold text-success mb-0 mr-2">
                                            ₹{{ number_format($deal->price) }}
                                        </span>
                                    @else
                                        <span class="h5 font-weight-bold text-primary mb-0 mr-2">Special Offer</span>
                                    @endif

                                    @if($deal->original_price)
                                        <del class="text-muted small">
                                            MRP ₹{{ number_format($deal->original_price) }}
                                        </del>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-2 border-top">
                                <div class="row g-2">
                                    <div class="col-7 pr-1">
                                        <a href="{{ route('user.deals.redirect', $deal->public_id) }}" target="_blank" class="btn btn-primary btn-sm btn-block font-weight-bold shadow-sm py-2">
                                            <i class="fas fa-bolt mr-1"></i> Grab Deal
                                        </a>
                                    </div>
                                    <div class="col-5 pl-1 d-flex">
                                        <!-- Copy Link -->
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill mr-1" onclick="copyAffiliateLink('{{ url('/deals/' . $deal->public_id . '/out') }}', this)" title="Copy Link">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <!-- WhatsApp Share -->
                                        @php
                                            $shareText = rawurlencode("🔥 *HOT DEAL:* " . $deal->title . "\n💰 *Offer Price:* ₹" . number_format($deal->price ?? 0) . " (" . ($deal->computed_discount ?? 0) . "% OFF)\n👉 *Grab Deal Here:* " . url('/deals/' . $deal->public_id . '/out'));
                                        @endphp
                                        <a href="https://api.whatsapp.com/send?text={{ $shareText }}" target="_blank" class="btn btn-success btn-sm flex-fill" title="Share on WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <div class="card card-custom p-5 mx-auto shadow-sm" style="max-width: 500px;">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h4 class="font-weight-bold text-dark">No Active Deals Found</h4>
                        <p class="text-muted small mb-4">We are fetching fresh discounts from top e-commerce channels. Please check back shortly or try searching for another term.</p>
                        <a href="{{ route('user.deals.index') }}" class="btn btn-primary btn-sm font-weight-bold mx-auto px-4 rounded-pill">
                            <i class="fas fa-redo mr-1"></i> Reset Filters
                        </a>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($deals->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $deals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function copyAffiliateLink(url, btn) {
        navigator.clipboard.writeText(url).then(function() {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 1800);
        }).catch(function() {
            prompt('Copy your monetized deal link:', url);
        });
    }
</script>
@endpush
