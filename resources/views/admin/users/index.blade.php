@extends('layouts.admin')

@section('title', 'Affiliates & Users Management')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-users text-primary mr-2"></i>Affiliate Users List</h3>
        <div class="card-tools d-flex">
            <form action="{{ route('admin.users.index') }}" method="GET" class="form-inline mr-2">
                <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Search name, email, mobile..." value="{{ request('search') }}">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i> Filter</button>
            </form>
            <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus mr-1"></i> Add New User</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email / Mobile</th>
                        <th>Status</th>
                        <th>UPI ID</th>
                        <th>Links</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><code>{{ Str::limit($user->public_id, 8) }}</code></td>
                            <td class="font-weight-bold">{{ $user->name }}</td>
                            <td>
                                <div>{{ $user->email }}</div>
                                <small class="text-muted">{{ $user->mobile_number ?? 'No mobile' }}</small>
                            </td>
                            <td>
                                @if($user->status === 'active')
                                    <span class="badge badge-success">Active</span>
                                @elseif($user->status === 'suspended')
                                    <span class="badge badge-warning">Suspended</span>
                                @else
                                    <span class="badge badge-danger">Blocked</span>
                                @endif
                            </td>
                            <td>{{ $user->upi_id ?? '-' }}</td>
                            <td><span class="badge badge-info">{{ $user->links_count }}</span></td>
                            <td><span class="badge badge-secondary">{{ $user->clicks_count }}</span></td>
                            <td><span class="badge badge-primary">{{ $user->conversions_count }}</span></td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-xs btn-info"><i class="fas fa-eye"></i> Profile</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-xs btn-primary"><i class="fas fa-edit"></i> Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No users found matching query.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $users->links() }}
    </div>
</div>

@endsection
