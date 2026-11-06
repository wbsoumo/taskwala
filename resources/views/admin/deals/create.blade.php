@extends('layouts.admin')

@section('title', 'Add New Deal')

@section('content')
<div class="row">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-plus-circle text-success mr-1"></i> Deal Offer Details
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.deals.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Deals
                    </a>
                </div>
            </div>

            <form action="{{ route('admin.deals.store') }}" method="POST">
                @csrf
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
                        <input type="text" name="title" id="inputTitle" class="form-control @error('title') is-invalid @enderror" placeholder="e.g. boAt Rockerz 255 Pro+ Wireless Bluetooth Neckband" value="{{ old('title') }}" required>
                        <small class="text-muted">A clear, engaging headline for affiliates and shoppers.</small>
                    </div>

                    <!-- Original URL -->
                    <div class="form-group">
                        <label class="font-weight-bold">Original Product URL <span class="text-danger">*</span></label>
                        <input type="url" name="original_url" id="inputUrl" class="form-control @error('original_url') is-invalid @enderror" placeholder="https://www.amazon.in/dp/B08L5WHJ43" value="{{ old('original_url') }}" required>
                        <small class="text-muted">The direct product link on Amazon, Flipkart, Myntra, Ajio, etc. It will automatically convert via EarnKaro on submission.</small>
                    </div>

                    <div class="row">
                        <!-- Deal Price -->
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Deal Price (₹)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" step="0.01" name="price" id="inputPrice" class="form-control" placeholder="899" value="{{ old('price') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Original Price -->
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Original Price / MRP (₹)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" step="0.01" name="original_price" id="inputOriginalPrice" class="form-control" placeholder="3990" value="{{ old('original_price') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Discount % -->
                        <div class="col-md-4 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Discount %</label>
                                <div class="input-group">
                                    <input type="number" name="discount_percent" id="inputDiscount" class="form-control" placeholder="Auto-calculated" value="{{ old('discount_percent') }}">
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
                                    <option value="Electronics" {{ old('category') == 'Electronics' ? 'selected' : '' }}>Electronics & Gadgets</option>
                                    <option value="Fashion" {{ old('category') == 'Fashion' ? 'selected' : '' }}>Fashion & Apparel</option>
                                    <option value="Beauty & Personal Care" {{ old('category') == 'Beauty & Personal Care' ? 'selected' : '' }}>Beauty & Personal Care</option>
                                    <option value="Home & Kitchen" {{ old('category') == 'Home & Kitchen' ? 'selected' : '' }}>Home & Kitchen</option>
                                    <option value="Grocery & Food" {{ old('category') == 'Grocery & Food' ? 'selected' : '' }}>Grocery & Food</option>
                                    <option value="Recharge & Finance" {{ old('category') == 'Recharge & Finance' ? 'selected' : '' }}>Recharge & Finance</option>
                                    <option value="General" {{ old('category') == 'General' ? 'selected' : '' }}>General</option>
                                </select>
                            </div>
                        </div>

                        <!-- Commission Rate -->
                        <div class="col-md-6 col-12">
                            <div class="form-group">
                                <label class="font-weight-bold">Affiliate Commission Tag</label>
                                <input type="text" name="commission_rate" class="form-control" placeholder="e.g. Up to 8.5% Profit or ₹50 Flat" value="{{ old('commission_rate', 'Up to 9% Profit') }}">
                            </div>
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div class="form-group">
                        <label class="font-weight-bold">Product Image URL</label>
                        <input type="url" name="image_url" id="inputImage" class="form-control" placeholder="https://m.media-amazon.com/images/I/..." value="{{ old('image_url') }}">
                        <small class="text-muted">Direct HTTPS image link for the product card preview.</small>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="font-weight-bold">Description / Deal Highlights</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Key specs, features, promo codes, or bank discount highlights...">{{ old('description') }}</textarea>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="font-weight-bold">Initial Status</label>
                        <div class="d-flex">
                            <div class="custom-control custom-radio mr-4">
                                <input class="custom-control-input" type="radio" id="statusActive" name="status" value="active" {{ old('status', 'active') == 'active' ? 'checked' : '' }}>
                                <label for="statusActive" class="custom-control-label text-success font-weight-bold">Active (Visible)</label>
                            </div>
                            <div class="custom-control custom-radio mr-4">
                                <input class="custom-control-input" type="radio" id="statusExpired" name="status" value="expired" {{ old('status') == 'expired' ? 'checked' : '' }}>
                                <label for="statusExpired" class="custom-control-label text-secondary font-weight-bold">Expired</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="statusHidden" name="status" value="hidden" {{ old('status') == 'hidden' ? 'checked' : '' }}>
                                <label for="statusHidden" class="custom-control-label text-muted font-weight-bold">Hidden</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <a href="{{ route('admin.deals.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-check mr-1"></i> Save & Convert Deal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Preview Column -->
    <div class="col-lg-4 col-12">
        <div class="card card-outline card-info shadow-sm sticky-top" style="top: 20px;">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-eye text-info mr-1"></i> Live Affiliate Card Preview
                </h3>
            </div>
            <div class="card-body">
                <div class="card border rounded-lg shadow-sm overflow-hidden mb-0">
                    <div class="position-relative bg-light text-center p-3" style="min-height: 180px;">
                        <span class="badge badge-danger position-absolute font-weight-bold" style="top: 10px; right: 10px;" id="previewDiscountBadge">70% OFF</span>
                        <img src="https://placehold.co/400x300/e2e8f0/1e293b?text=Deal+Image" id="previewImg" class="img-fluid rounded" style="max-height: 160px; object-fit: contain;">
                    </div>
                    <div class="p-3">
                        <div class="text-uppercase small text-muted font-weight-bold mb-1" id="previewCategory">Electronics</div>
                        <h6 class="font-weight-bold text-dark text-truncate-2 mb-2" id="previewTitle" style="min-height: 42px;">
                             boAt Rockerz 255 Pro+ Wireless Neckband
                        </h6>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="h5 font-weight-bold text-success mb-0 mr-2" id="previewPrice">₹899</span>
                            <del class="text-muted small" id="previewOriginalPrice">₹3,990</del>
                        </div>
                        <div class="badge badge-warning text-dark font-weight-bold mb-3 d-inline-block">
                            <i class="fas fa-coins mr-1"></i> Up to 9% Profit
                        </div>
                        <button type="button" class="btn btn-primary btn-block btn-sm font-weight-bold" disabled>
                            <i class="fas fa-bolt mr-1"></i> Grab Deal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('inputTitle');
        const priceInput = document.getElementById('inputPrice');
        const origPriceInput = document.getElementById('inputOriginalPrice');
        const discountInput = document.getElementById('inputDiscount');
        const imgInput = document.getElementById('inputImage');

        function updatePreview() {
            if (titleInput && titleInput.value) {
                document.getElementById('previewTitle').innerText = titleInput.value;
            }
            if (priceInput && priceInput.value) {
                document.getElementById('previewPrice').innerText = '₹' + Number(priceInput.value).toLocaleString('en-IN');
            }
            if (origPriceInput && origPriceInput.value) {
                document.getElementById('previewOriginalPrice').innerText = '₹' + Number(origPriceInput.value).toLocaleString('en-IN');
            }
            if (imgInput && imgInput.value) {
                document.getElementById('previewImg').src = imgInput.value;
            }

            // Auto calculate discount
            const p = parseFloat(priceInput.value);
            const op = parseFloat(origPriceInput.value);
            if (p > 0 && op > p) {
                const disc = Math.round(((op - p) / op) * 100);
                if (!discountInput.value) {
                    discountInput.value = disc;
                }
                document.getElementById('previewDiscountBadge').innerText = disc + '% OFF';
            }
        }

        [titleInput, priceInput, origPriceInput, imgInput].forEach(el => {
            if (el) el.addEventListener('input', updatePreview);
        });
    });
</script>
@endpush
