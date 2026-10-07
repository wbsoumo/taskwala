@extends('layouts.user')

@section('title', 'Generate Campaign Link')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-magic text-warning mr-2"></i> Generate Campaign Tracking Link
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Select an offer and enter your customer payout reward. Your commission is automatically previewed below.</p>

                <form action="{{ route('user.links.generate') }}" method="POST" id="linkForm">
                    @csrf

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">Campaign <span class="text-danger">*</span></label>
                        <select name="campaign_id" id="campaignSelect" class="form-control form-control-lg font-weight-bold" required>
                            <option value="" data-payout="0">-- Select Active Campaign --</option>
                            @foreach($campaigns as $c)
                                <option value="{{ $c->id }}" data-payout="{{ $c->allocated_payout }}" {{ (old('campaign_id') == $c->id || ($selectedCampaign && $selectedCampaign->id == $c->id)) ? 'selected' : '' }}>
                                    {{ $c->name }} (Your Max Allocated Payout: ₹{{ number_format($c->allocated_payout, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="card bg-light border mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="font-weight-bold text-dark mb-1">Customer Payout (₹) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg">
                                        <div class="input-group-prepend"><span class="input-group-text bg-white font-weight-bold">₹</span></div>
                                        <input type="number" step="0.01" name="customer_payout" id="customerPayout" class="form-control font-weight-bold text-info" value="{{ old('customer_payout', '60.00') }}" required min="0">
                                    </div>
                                    <small class="form-text text-muted">Amount recipient gets upon conversion.</small>
                                </div>

                                <div class="col-md-6">
                                    <div class="card bg-white border text-center mb-0">
                                        <div class="card-body p-3">
                                            <span class="text-muted small font-weight-bold text-uppercase d-block mb-1">Your Commission</span>
                                            <h3 class="font-weight-bold text-success mb-0" id="commissionPreview">₹0.00</h3>
                                            <small class="text-muted d-block mt-1 small">(Allocated Payout − Customer Payout)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="callout callout-info bg-light small mb-4">
                        <i class="fas fa-shield-alt text-info mr-1"></i> <strong>Server-Side Security Enforced:</strong> All calculations are verified server-side. Payout structures cannot be manipulated frontend.
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold shadow-sm py-2">
                        <i class="fas fa-link mr-2"></i> Generate Tracking Link
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document.body).on('change input', '#campaignSelect, #customerPayout', function() {
        const option = $('#campaignSelect option:selected');
        const allocated = parseFloat(option.data('payout')) || 0;
        const customer = parseFloat($('#customerPayout').val()) || 0;

        let commission = allocated - customer;
        if (commission < 0) commission = 0;

        $('#commissionPreview').text('₹' + commission.toFixed(2));
    });
    $('#campaignSelect').trigger('change');
</script>
@endpush
