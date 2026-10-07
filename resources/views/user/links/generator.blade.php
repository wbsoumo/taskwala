@extends('layouts.user')

@section('title', 'Generate Campaign Link')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-custom p-4 shadow-sm">
            <h4 class="font-weight-bold text-dark mb-1"><i class="fas fa-magic text-primary mr-2"></i>Generate Campaign Tracking Link</h4>
            <p class="text-muted small mb-4">Choose how much of your allocated payout you want to give to your customer. The rest is retained as your commission.</p>

            <form action="{{ route('user.links.generate') }}" method="POST" id="linkForm">
                @csrf
                <div class="form-group mb-4">
                    <label class="font-weight-bold text-dark">1. SELECT CAMPAIGN <span class="text-danger">*</span></label>
                    <select name="campaign_id" id="campaignSelect" class="form-control form-control-lg rounded-lg border-secondary" required>
                        <option value="" data-payout="0">-- Select Active Campaign --</option>
                        @foreach($campaigns as $c)
                            <option value="{{ $c->id }}" data-payout="{{ $c->allocated_payout }}" {{ (old('campaign_id') == $c->id || ($selectedCampaign && $selectedCampaign->id == $c->id)) ? 'selected' : '' }}>
                                {{ $c->name }} (Your Allocated Payout: ₹{{ number_format($c->allocated_payout, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-light p-4 rounded-lg border mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="font-weight-bold text-dark mb-1">2. SET CUSTOMER PAYOUT (₹)</label>
                            <div class="input-group input-group-lg">
                                <div class="input-group-prepend"><span class="input-group-text bg-white">₹</span></div>
                                <input type="number" step="0.01" name="customer_payout" id="customerPayout" class="form-control font-weight-bold text-info" value="{{ old('customer_payout', '60.00') }}" required min="0">
                            </div>
                            <small class="form-text text-muted">Amount customer will receive upon conversion.</small>
                        </div>

                        <div class="col-md-6">
                            <div class="bg-white p-3 rounded border text-center">
                                <span class="text-muted small font-weight-bold d-block">YOUR CALCULATED COMMISSION</span>
                                <h3 class="font-weight-bold text-success mb-0" id="commissionPreview">₹0.00</h3>
                                <small class="text-muted d-block mt-1">(Allocated Payout − Customer Payout)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small mb-4">
                    <i class="fas fa-shield-alt mr-1"></i> <strong>Security Guarantee:</strong> Payout constraints are strictly calculated and validated on the server. Public URLs never expose financial logic or internal IDs.
                </div>

                <button type="submit" class="btn btn-primary btn-lg btn-block rounded-pill font-weight-bold py-3">
                    <i class="fas fa-link mr-2"></i> Generate Secure Unique Link
                </button>
            </form>
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
