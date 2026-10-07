@extends('layouts.user')

@section('title', 'Profile & Settings')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-custom p-4 shadow-sm">
            <h4 class="font-weight-bold text-dark mb-4"><i class="fas fa-user-edit text-primary mr-2"></i>My Profile & Payout Info</h4>

            <form action="{{ route('user.profile.update') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="font-weight-bold text-muted small">FULL NAME</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label class="font-weight-bold text-muted small">EMAIL ADDRESS</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled readonly>
                        <small class="form-text text-muted">Email address cannot be changed.</small>
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label class="font-weight-bold text-muted small">MOBILE NUMBER</label>
                        <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $user->mobile_number) }}" required>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-university text-success mr-2"></i>UPI Payment Info</h5>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label class="font-weight-bold text-muted small">UPI ID</label>
                        <input type="text" name="upi_id" class="form-control" value="{{ old('upi_id', $user->upi_id) }}" placeholder="user@upi">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label class="font-weight-bold text-muted small">UPI HOLDER NAME</label>
                        <input type="text" name="upi_holder_name" class="form-control" value="{{ old('upi_holder_name', $user->upi_holder_name) }}">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-lock text-warning mr-2"></i>Change Password (Optional)</h5>

                <div class="row">
                    <div class="col-md-6 form-group mb-3">
                        <label class="font-weight-bold text-muted small">CURRENT PASSWORD</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>
                    <div class="col-md-6 form-group mb-3">
                        <label class="font-weight-bold text-muted small">NEW PASSWORD</label>
                        <input type="password" name="new_password" class="form-control" minlength="8">
                    </div>
                </div>

                <div class="text-right mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill font-weight-bold px-4 py-2"><i class="fas fa-save mr-1"></i> Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
