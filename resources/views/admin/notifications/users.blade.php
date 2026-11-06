@extends('layouts.admin')

@section('title', 'Registered App Users & Device Tokens')

@section('content')
<div class="row mb-3">
    <div class="col-lg-4 col-12">
        <div class="small-box bg-info elevation-2">
            <div class="inner">
                <h3>{{ number_format($stats['total_registered']) }}</h3>
                <p>Total Registered Partners</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.notifications.users') }}" class="small-box-footer">All Partners <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-success elevation-2">
            <div class="inner">
                <h3>{{ number_format($stats['with_fcm_token']) }}</h3>
                <p>App Push Token Active</p>
            </div>
            <div class="icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <a href="{{ route('admin.notifications.users', ['token_status' => 'has_token']) }}" class="small-box-footer">Filter Active Device <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-warning elevation-2">
            <div class="inner">
                <h3>{{ number_format($stats['without_token']) }}</h3>
                <p>Pending App Login / No Token</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <a href="{{ route('admin.notifications.users', ['token_status' => 'no_token']) }}" class="small-box-footer">Filter Pending <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="card card-outline card-primary elevation-2">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-address-book text-primary mr-2"></i>Registered Users & Push Readiness
        </h3>
        <div class="card-tools ml-auto">
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-sm font-weight-bold shadow-sm mr-2">
                <i class="fas fa-paper-plane mr-1"></i> Send Push Notification
            </a>
            <form action="{{ route('admin.notifications.users') }}" method="GET" class="form-inline d-inline-block">
                <div class="input-group input-group-sm mr-2">
                    <select name="token_status" class="form-control" onchange="this.form.submit()">
                        <option value="">All App Status</option>
                        <option value="has_token" {{ request('token_status') === 'has_token' ? 'selected' : '' }}>Connected ({{ $stats['with_fcm_token'] }})</option>
                        <option value="no_token" {{ request('token_status') === 'no_token' ? 'selected' : '' }}>No Device Token ({{ $stats['without_token'] }})</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <input type="text" name="search" class="form-control" placeholder="Search name, email, mobile..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle text-nowrap mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 60px;">#ID</th>
                    <th>User Details</th>
                    <th>Mobile</th>
                    <th>App Push Status</th>
                    <th>Last Active / Token Update</th>
                    <th>Registered At</th>
                    <th>Notifications</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="font-weight-bold text-muted">{{ $user->id }}</td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $user->name }}</div>
                            <div class="small text-muted">{{ $user->email }}</div>
                            @if($user->email_verified_at)
                                <span class="badge badge-success px-1" style="font-size: 10px;"><i class="fas fa-check-circle"></i> Verified</span>
                            @else
                                <span class="badge badge-warning px-1" style="font-size: 10px;"><i class="fas fa-clock"></i> Unverified</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-light border text-monospace font-weight-bold">
                                {{ $user->mobile_number }}
                            </span>
                        </td>
                        <td>
                            @if($user->fcm_token)
                                <span class="badge badge-success px-2 py-1">
                                    <i class="fas fa-check-circle mr-1"></i> App Connected
                                </span>
                            @else
                                <span class="badge badge-secondary px-2 py-1">
                                    <i class="fas fa-minus-circle mr-1"></i> No App Token
                                </span>
                            @endif
                        </td>
                        <td class="small">
                            @if($user->fcm_token_updated_at)
                                <span class="font-weight-bold text-dark">{{ $user->fcm_token_updated_at->format('M d, Y') }}</span><br>
                                <span class="text-muted">{{ $user->fcm_token_updated_at->diffForHumans() }}</span>
                            @elseif($user->last_login_at)
                                <span class="text-muted">Login: {{ $user->last_login_at->diffForHumans() }}</span>
                            @else
                                <span class="text-muted">Not active recently</span>
                            @endif
                        </td>
                        <td class="small">
                            {{ $user->created_at->format('M d, Y') }}<br>
                            <span class="text-muted">{{ $user->created_at->format('h:i A') }}</span>
                        </td>
                        <td class="small">
                            <span class="badge badge-light border">
                                {{ $user->total_received }} Received / {{ $user->total_read }} Read
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.notifications.create', ['user_id' => $user->id]) }}" class="btn btn-outline-primary btn-sm font-weight-bold" title="Send direct push notification to this user">
                                <i class="fas fa-paper-plane mr-1"></i> Send Push
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-users-slash fa-3x mb-3 text-secondary"></i>
                            <p class="mb-0 font-weight-bold">No registered users found matching the search criteria.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users
                </span>
                <div>
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
