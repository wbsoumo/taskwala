@extends('layouts.admin')

@section('title', 'Edit Deal: ' . $deal->title)

@section('content')
<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-edit text-primary mr-1"></i> Edit Deal Information
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.deals.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Deals
                    </a>
                </div>
            </div>

            <form action="{{ route('admin.deals.update', $deal) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- Title -->
                    <div class="form-group">
                        <label class="font-weight-bold">Deal Offer Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $deal->title) }}" required>
                    </div>

                    <!-- Original URL -->
                    <div class="form-group">
                        <label class="font-weight-bold">Original Product URL <span class="text-danger">*</span></label>
                        <input type="url" name="original_url" class="form-control" value="{{ old('original_url', $deal->original_url) }}" required>
                    </div>

                    <!-- Affiliate URL -->
                    <div class="form-group">
                        <label class="font-weight-bold">EarnKaro Affiliate URL</label>
                        <div class="input-group">
                            <input type="url" name="affiliate_url" class="form-control" value="{{ old('affiliate_url', $deal->affiliate_url) }}" placeholder="https://ekaro.in/...">
                            <div class="input-group-append">
                                <a href="{{ $deal->affiliate_url }}" target="_blank" class="btn btn-outline-secondary {{ empty($deal->affiliate_url) ? 'disabled' : '' }}">
                                    <i class="fas fa-external-link-alt"></i> Test
                                </a>
                            </div>
                        </div>
                        <small class="text-muted">Target link when affiliate or user clicks Grab Deal.</small>
                    </div>

                    <div class="row">
                        <!-- Deal Price -->
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Deal Price (₹)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $deal->price) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Original Price -->
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Original Price / MRP (₹)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" step="0.01" name="original_price" class="form-control" value="{{ old('original_price', $deal->original_price) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Discount % -->
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Discount %</label>
                                <div class="input-group">
                                    <input type="number" name="discount_percent" class="form-control" value="{{ old('discount_percent', $deal->discount_percent) }}">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Category -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Category <span class="text-danger">*</span></label>
                                <select name="category" class="form-control" required>
                                    @php $currentCat = old('category', $deal->category); @endphp
                                    <option value="Electronics" {{ $currentCat == 'Electronics' ? 'selected' : '' }}>Electronics & Gadgets</option>
                                    <option value="Fashion" {{ $currentCat == 'Fashion' ? 'selected' : '' }}>Fashion & Apparel</option>
                                    <option value="Beauty & Personal Care" {{ $currentCat == 'Beauty & Personal Care' ? 'selected' : '' }}>Beauty & Personal Care</option>
                                    <option value="Home & Kitchen" {{ $currentCat == 'Home & Kitchen' ? 'selected' : '' }}>Home & Kitchen</option>
                                    <option value="Grocery & Food" {{ $currentCat == 'Grocery & Food' ? 'selected' : '' }}>Grocery & Food</option>
                                    <option value="Recharge & Finance" {{ $currentCat == 'Recharge & Finance' ? 'selected' : '' }}>Recharge & Finance</option>
                                    <option value="General" {{ $currentCat == 'General' ? 'selected' : '' }}>General</option>
                                </select>
                            </div>
                        </div>

                        <!-- Commission Rate -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Affiliate Commission Tag</label>
                                <input type="text" name="commission_rate" class="form-control" value="{{ old('commission_rate', $deal->commission_rate) }}">
                            </div>
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div class="form-group">
                        <label class="font-weight-bold">Product Image URL</label>
                        <input type="url" name="image_url" class="form-control" value="{{ old('image_url', $deal->image_url) }}">
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="font-weight-bold">Description / Deal Highlights</label>
                        <textarea name="description" rows="4" class="form-control">{{ old('description', $deal->description) }}</textarea>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="font-weight-bold">Status</label>
                        <div class="d-flex">
                            @php $st = old('status', $deal->status); @endphp
                            <div class="custom-control custom-radio mr-4">
                                <input class="custom-control-input" type="radio" id="stActive" name="status" value="active" {{ $st == 'active' ? 'checked' : '' }}>
                                <label for="stActive" class="custom-control-label text-success font-weight-bold">Active (Live)</label>
                            </div>
                            <div class="custom-control custom-radio mr-4">
                                <input class="custom-control-input" type="radio" id="stExpired" name="status" value="expired" {{ $st == 'expired' ? 'checked' : '' }}>
                                <label for="stExpired" class="custom-control-label text-secondary font-weight-bold">Expired</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="stHidden" name="status" value="hidden" {{ $st == 'hidden' ? 'checked' : '' }}>
                                <label for="stHidden" class="custom-control-label text-muted font-weight-bold">Hidden</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <a href="{{ route('admin.deals.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Update Deal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deal Meta Information -->
    <div class="col-lg-4 col-12">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-info-circle text-info mr-1"></i> Deal Meta & Origin
                </h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Public UUID:</span>
                        <code class="small text-dark">{{ $deal->public_id }}</code>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Source Channel:</span>
                        <span class="badge badge-secondary">{{ $deal->source_channel }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Message ID:</span>
                        <span class="font-weight-bold">{{ $deal->source_message_id ?: 'N/A' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Total Clicks:</span>
                        <span class="badge badge-warning text-dark font-weight-bold">{{ number_format($deal->clicks_count) }} clicks</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Conversion Status:</span>
                        @if($deal->conversion_status === 'converted')
                            <span class="badge badge-success">EarnKaro Converted</span>
                        @elseif($deal->conversion_status === 'failed')
                            <span class="badge badge-danger">Failed</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Published Date:</span>
                        <span class="text-dark small">{{ $deal->posted_at ? $deal->posted_at->format('d M Y, h:i A') : 'N/A' }}</span>
                    </li>
                </ul>

                @if($deal->conversion_error)
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-circle mr-1"></i> <strong>Conversion Log:</strong><br>
                        {{ $deal->conversion_error }}
                    </div>
                @endif

                <form action="{{ route('admin.deals.retry_conversion', $deal) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-block btn-sm font-weight-bold">
                        <i class="fas fa-sync-alt mr-1"></i> Retry EarnKaro Link Conversion
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
