@extends('layouts.user')

@section('title', 'UPI / Payout Details')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-university text-success mr-2"></i> UPI / Payout Details
                </h3>
            </div>
            <div class="card-body">
                <!-- Status Callout -->
                <div class="callout callout-info bg-light border-left-info mb-4">
                    <h5><i class="fas fa-info-circle text-info mr-1"></i> Default Payout Account</h5>
                    <p class="mb-0 text-muted small">
                        This UPI ID is your primary receiving account for affiliate payout settlements. Please ensure it is accurate and matches your bank records.
                    </p>
                </div>

                <div class="card bg-light border-0 mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small font-weight-bold d-block text-uppercase">Current UPI Status</span>
                            @if($user->upi_id)
                                <h4 class="font-weight-bold text-success mb-0"><i class="fas fa-check-circle mr-1"></i> Active / Configured</h4>
                            @else
                                <h4 class="font-weight-bold text-warning mb-0"><i class="fas fa-exclamation-triangle mr-1"></i> Not Configured</h4>
                            @endif
                        </div>
                        <span class="badge badge-{{ $user->upi_id ? 'success' : 'warning' }} px-3 py-2 text-uppercase">
                            {{ $user->upi_id ? 'Verified Destination' : 'Action Required' }}
                        </span>
                    </div>
                </div>

                <form action="{{ route('user.upi.update') }}" method="POST">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">UPI ID <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i class="fas fa-at text-muted"></i></span>
                            </div>
                            <input type="text" name="upi_id" class="form-control form-control-lg font-weight-bold" value="{{ old('upi_id', $user->upi_id) }}" placeholder="e.g. mobile@upi, username@okaxis" required>
                        </div>
                        <small class="form-text text-muted">Enter your valid Virtual Payment Address (VPA) / UPI ID.</small>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark">UPI Holder Name (Optional)</label>
                        <input type="text" name="upi_holder_name" class="form-control" value="{{ old('upi_holder_name', $user->upi_holder_name) }}" placeholder="As per bank / UPI app">
                        <small class="form-text text-muted">Account holder name associated with the UPI ID.</small>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-success px-4 py-2 font-weight-bold shadow-sm">
                            <i class="fas fa-save mr-1"></i> Update UPI Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
