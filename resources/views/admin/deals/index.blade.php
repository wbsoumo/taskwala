@extends('layouts.admin')

@section('title', 'Deals Management & Aggregation')

@section('content')
<div class="row">
    <!-- Stat 1: Total Deals -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info shadow-sm">
            <div class="inner">
                <h3>{{ number_format($metrics['total']) }}</h3>
                <p class="font-weight-bold">Total Aggregated Deals</p>
            </div>
            <div class="icon">
                <i class="fas fa-tags"></i>
            </div>
            <span class="small-box-footer"><i class="fas fa-database mr-1"></i> In System Database</span>
        </div>
    </div>

    <!-- Stat 2: Active Deals -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3>{{ number_format($metrics['active']) }}</h3>
                <p class="font-weight-bold">Active Live Deals</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <span class="small-box-footer"><i class="fas fa-eye mr-1"></i> Visible to Affiliates</span>
        </div>
    </div>

    <!-- Stat 3: Converted Links -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary shadow-sm">
            <div class="inner">
                <h3>{{ number_format($metrics['converted']) }}</h3>
                <p class="font-weight-bold">EarnKaro Converted</p>
            </div>
            <div class="icon">
                <i class="fas fa-link"></i>
            </div>
            <span class="small-box-footer"><i class="fas fa-magic mr-1"></i> Monetized Affiliate Links</span>
        </div>
    </div>

    <!-- Stat 4: Total Clicks -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3 class="text-white">{{ number_format($metrics['total_clicks']) }}</h3>
                <p class="font-weight-bold text-white">Total Outbound Clicks</p>
            </div>
            <div class="icon">
                <i class="fas fa-mouse-pointer"></i>
            </div>
            <span class="small-box-footer text-white"><i class="fas fa-chart-line mr-1"></i> User Engagement</span>
        </div>
    </div>
</div>

<!-- Action Bar & Filter Card -->
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between py-2">
        <h3 class="card-title font-weight-bold text-dark my-1">
            <i class="fas fa-filter text-primary mr-1"></i> Filter & Search Deals
        </h3>
        <div class="card-tools d-flex align-items-center my-1">
            <a href="{{ route('admin.deals.create') }}" class="btn btn-sm btn-success mr-2 shadow-sm font-weight-bold">
                <i class="fas fa-plus mr-1"></i> Add Deal Manually
            </a>
            <form action="{{ route('admin.deals.settings.sync_now') }}" method="POST" class="d-inline mr-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary shadow-sm font-weight-bold">
                    <i class="fas fa-sync-alt mr-1"></i> Sync Telegram Deals Now
                </button>
            </form>
            <a href="{{ route('admin.deals.settings') }}" class="btn btn-sm btn-outline-secondary font-weight-bold">
                <i class="fas fa-cogs mr-1"></i> Settings
            </a>
        </div>
    </div>

    <div class="card-body bg-light py-3">
        <form method="GET" action="{{ route('admin.deals.index') }}" class="row g-2 align-items-end">
            <!-- Search -->
            <div class="col-md-3 col-12 mb-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Search Deals</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search title, description, channel..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-md-2 col-6 mb-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Category</label>
                <select name="category" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="all">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-md-2 col-6 mb-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Status</label>
                <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="all" {{ request('status') === 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Hidden</option>
                </select>
            </div>

            <!-- Source Channel -->
            <div class="col-md-2 col-6 mb-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Source Channel</label>
                <select name="channel" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="all">All Sources</option>
                    @foreach($channels as $ch)
                        <option value="{{ $ch }}" {{ request('channel') === $ch ? 'selected' : '' }}>{{ $ch }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sort By -->
            <div class="col-md-2 col-6 mb-2">
                <label class="form-label small font-weight-bold text-muted mb-1">Sort By</label>
                <select name="sort" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Newest First</option>
                    <option value="discount" {{ request('sort') === 'discount' ? 'selected' : '' }}>Highest Discount %</option>
                    <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="clicks" {{ request('sort') === 'clicks' ? 'selected' : '' }}>Most Clicked</option>
                </select>
            </div>

            <!-- Reset -->
            <div class="col-md-1 col-12 mb-2">
                <a href="{{ route('admin.deals.index') }}" class="btn btn-sm btn-outline-secondary btn-block" title="Reset Filters">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Deals Table Card -->
<div class="card card-custom shadow-sm border-0">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small font-weight-bold text-secondary">
                <tr>
                    <th style="width: 50px;">Img</th>
                    <th>Offer Title & Details</th>
                    <th style="width: 140px;">Pricing & Discount</th>
                    <th style="width: 130px;">Commission</th>
                    <th style="width: 200px;">Affiliate & Source URLs</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 100px;">Clicks</th>
                    <th style="width: 110px;">Posted</th>
                    <th style="width: 130px;" class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deals as $deal)
                    <tr>
                        <!-- Image -->
                        <td>
                            @if($deal->image_url)
                                <img src="{{ $deal->image_url }}" alt="{{ $deal->title }}" class="img-thumbnail rounded" style="width: 48px; height: 48px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted border" style="width: 48px; height: 48px;">
                                    <i class="fas fa-image fa-lg"></i>
                                </div>
                            @endif
                        </td>

                        <!-- Title & Category & Channel -->
                        <td>
                            <div class="font-weight-bold text-dark text-truncate" style="max-width: 320px;" title="{{ $deal->title }}">
                                {{ $deal->title }}
                            </div>
                            <div class="small mt-1 d-flex flex-wrap gap-1">
                                <span class="badge badge-info mr-1"><i class="fas fa-folder mr-1"></i>{{ $deal->category }}</span>
                                <span class="badge badge-secondary mr-1"><i class="fab fa-telegram-plane mr-1"></i>{{ $deal->source_channel }}</span>
                                @if($deal->conversion_status === 'converted')
                                    <span class="badge badge-success" title="Affiliate link converted via EarnKaro"><i class="fas fa-check-circle mr-1"></i>EarnKaro</span>
                                @elseif($deal->conversion_status === 'failed')
                                    <span class="badge badge-danger" title="{{ $deal->conversion_error }}"><i class="fas fa-exclamation-triangle mr-1"></i>Failed Conversion</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                                @endif
                            </div>
                        </td>

                        <!-- Price & Discount -->
                        <td>
                            @if($deal->price)
                                <div class="font-weight-bold text-success" style="font-size: 1.05rem;">
                                    ₹{{ number_format($deal->price, 2) }}
                                </div>
                            @else
                                <span class="text-muted small">Special Deal</span>
                            @endif

                            @if($deal->original_price)
                                <div class="small text-muted text-decoration-line-through">
                                    <del>₹{{ number_format($deal->original_price, 2) }}</del>
                                </div>
                            @endif

                            @if($deal->computed_discount)
                                <span class="badge badge-danger font-weight-bold mt-1">
                                    {{ $deal->computed_discount }}% OFF
                                </span>
                            @endif
                        </td>

                        <!-- Commission -->
                        <td>
                            <span class="badge badge-light border text-dark font-weight-bold px-2 py-1">
                                <i class="fas fa-coins text-warning mr-1"></i> {{ $deal->commission_rate ?: 'Standard Profit' }}
                            </span>
                        </td>

                        <!-- Links -->
                        <td>
                            @if($deal->affiliate_url)
                                <div class="d-flex align-items-center mb-1">
                                    <span class="badge badge-success mr-1">Affiliate</span>
                                    <a href="{{ $deal->affiliate_url }}" target="_blank" class="text-primary small text-truncate mr-2" style="max-width: 110px;" title="{{ $deal->affiliate_url }}">
                                        {{ $deal->affiliate_url }}
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $deal->affiliate_url }}'); alert('Affiliate Link Copied!');" title="Copy Affiliate Link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            @endif
                            <div class="d-flex align-items-center">
                                <span class="badge badge-light border mr-1">Source</span>
                                <a href="{{ $deal->original_url }}" target="_blank" class="text-muted small text-truncate mr-2" style="max-width: 120px;" title="{{ $deal->original_url }}">
                                    {{ $deal->original_url }}
                                </a>
                                <a href="{{ $deal->original_url }}" target="_blank" class="btn btn-xs btn-link p-0 text-secondary" title="Open Original URL">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </td>

                        <!-- Status Toggle -->
                        <td>
                            <form action="{{ route('admin.deals.toggle_status', $deal) }}" method="POST" class="d-inline">
                                @csrf
                                @if($deal->status === 'active')
                                    <button type="submit" class="btn btn-xs btn-success font-weight-bold shadow-sm" title="Click to Expire">
                                        <i class="fas fa-check mr-1"></i> Active
                                    </button>
                                @elseif($deal->status === 'expired')
                                    <button type="submit" class="btn btn-xs btn-secondary font-weight-bold" title="Click to Reactivate">
                                        <i class="fas fa-times mr-1"></i> Expired
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-xs btn-light border text-muted" title="Click to Reactivate">
                                        <i class="fas fa-eye-slash mr-1"></i> Hidden
                                    </button>
                                @endif
                            </form>
                        </td>

                        <!-- Clicks -->
                        <td>
                            <span class="badge badge-warning text-dark font-weight-bold px-2 py-1">
                                <i class="fas fa-mouse-pointer mr-1"></i> {{ number_format($deal->clicks_count) }}
                            </span>
                        </td>

                        <!-- Posted Date -->
                        <td>
                            <small class="text-muted" title="{{ $deal->posted_at }}">
                                <i class="far fa-clock mr-1"></i> {{ $deal->posted_at ? $deal->posted_at->diffForHumans() : 'Recently' }}
                            </small>
                        </td>

                        <!-- Actions -->
                        <td class="text-right">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.deals.edit', $deal) }}" class="btn btn-default" title="Edit Deal">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @if($deal->conversion_status !== 'converted')
                                    <form action="{{ route('admin.deals.retry_conversion', $deal) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-default" title="Retry EarnKaro Link Conversion">
                                            <i class="fas fa-sync text-warning"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.deals.destroy', $deal) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this deal permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-default" title="Delete Deal">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-tag fa-3x mb-3 text-secondary"></i>
                                <h5>No Deals Found</h5>
                                <p class="small text-muted mb-3">No aggregated deals match your current search/filter criteria.</p>
                                <form action="{{ route('admin.deals.settings.sync_now') }}" method="POST" class="d-inline mr-2">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold">
                                        <i class="fas fa-sync-alt mr-1"></i> Fetch Telegram Deals Now
                                    </button>
                                </form>
                                <a href="{{ route('admin.deals.create') }}" class="btn btn-outline-success btn-sm font-weight-bold">
                                    <i class="fas fa-plus mr-1"></i> Add Manual Deal
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($deals->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <div class="small text-muted">
                Showing {{ $deals->firstItem() ?? 0 }} to {{ $deals->lastItem() ?? 0 }} of {{ $deals->total() }} deals
            </div>
            <div>
                {{ $deals->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
