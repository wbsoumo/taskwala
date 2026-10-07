@extends('layouts.user')

@section('title', 'Profile & Settings')

@section('content')

<div class="row justify-content-center">
    <div class="col-md-9 col-lg-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-user-cog text-primary mr-2"></i> Account Profile & Security Settings
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('user.profile.update') }}" method="POST">
                    @csrf

                    <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-id-card text-info mr-2"></i> Personal Details</h5>
                    
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark">Email Address</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" disabled readonly>
                            <small class="form-text text-muted">Primary account email cannot be edited.</small>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $user->mobile_number) }}" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="font-weight-bold text-dark mb-0"><i class="fas fa-university text-success mr-2"></i> UPI / Settlement Details</h5>
                        <a href="{{ route('user.upi.index') }}" class="btn btn-xs btn-outline-success font-weight-bold">Dedicated UPI Page</a>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark">UPI ID</label>
                            <input type="text" name="upi_id" class="form-control font-weight-bold" value="{{ old('upi_id', $user->upi_id) }}" placeholder="username@upi">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark">UPI Account Holder Name</label>
                            <input type="text" name="upi_holder_name" class="form-control" value="{{ old('upi_holder_name', $user->upi_holder_name) }}" placeholder="Account Holder Name">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="font-weight-bold text-dark mb-3"><i class="fas fa-lock text-warning mr-2"></i> Security (Change Password)</h5>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Required only if changing password">
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="font-weight-bold text-dark">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" placeholder="Minimum 8 characters">
                        </div>
                    </div>

                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary font-weight-bold px-4 py-2 shadow-sm">
                            <i class="fas fa-save mr-1"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
