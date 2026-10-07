@extends('layouts.admin')

@section('title', 'Edit User: ' . $user->name)

@section('content')

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-user-edit text-primary mr-2"></i>Edit Affiliate Profile</h3>
            </div>
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Mobile Number</label>
                            <input type="text" name="mobile_number" class="form-control" value="{{ old('mobile_number', $user->mobile_number) }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Reset Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                            <small class="form-text text-muted">Passwords are always hashed securely.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Account Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="blocked" {{ old('status', $user->status) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>UPI ID</label>
                            <input type="text" name="upi_id" class="form-control" value="{{ old('upi_id', $user->upi_id) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>UPI Holder Name</label>
                            <input type="text" name="upi_holder_name" class="form-control" value="{{ old('upi_holder_name', $user->upi_holder_name) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $user->notes) }}</textarea>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-secondary mr-2">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
