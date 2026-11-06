@extends('layouts.admin')

@section('title', 'Notification Delivery & Read Status')

@section('content')
<!-- Notification Overview Card -->
<div class="card card-outline card-primary elevation-2 mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-info-circle text-primary mr-2"></i>Notification #{{ $notification->id }} Details
        </h3>
        <div class="card-tools ml-auto">
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-sm font-weight-bold">
                <i class="fas fa-paper-plane mr-1"></i> Send Another
            </a>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm ml-1">
                <i class="fas fa-arrow-left mr-1"></i> Back to Sent List
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <h4 class="font-weight-bold text-dark mb-2">{{ $notification->title }}</h4>
                <p class="text-secondary mb-3" style="font-size: 1.05rem; line-height: 1.5;">{{ $notification->body }}</p>
                
                <div class="d-flex flex-wrap gap-2 text-muted small align-items-center">
                    <span class="mr-3">
                        <i class="fas fa-calendar-alt text-primary mr-1"></i> Sent: {{ $notification->created_at->format('M d, Y • h:i A') }}
                    </span>
                    <span class="mr-3">
                        <i class="fas fa-user-shield text-info mr-1"></i> Admin: {{ $notification->admin->name ?? 'System' }}
                    </span>
                    @if($notification->deeplink)
                        <span class="mr-3 badge badge-light border text-monospace text-primary px-2 py-1">
                            <i class="fas fa-external-link-alt mr-1"></i> {{ $notification->deeplink }}
                        </span>
                    @endif
                </div>
            </div>
            @if($notification->image_url)
                <div class="col-md-4 text-center mt-3 mt-md-0">
                    <img src="{{ $notification->image_url }}" alt="Banner" class="img-fluid rounded border shadow-sm" style="max-height: 140px; object-fit: cover;">
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-3">
    <div class="col-lg-2 col-md-4 col-6">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted small">Total Targeted</span>
                <span class="info-box-number">{{ number_format($stats['total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-success"><i class="fas fa-envelope-open"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted small">Read by User</span>
                <span class="info-box-number text-success">{{ number_format($stats['read']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-warning"><i class="fas fa-envelope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted small">Unread</span>
                <span class="info-box-number text-warning">{{ number_format($stats['unread']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-primary"><i class="fas fa-paper-plane"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted small">FCM Sent</span>
                <span class="info-box-number text-primary">{{ number_format($stats['fcm_sent']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted small">FCM Failed</span>
                <span class="info-box-number text-danger">{{ number_format($stats['fcm_failed']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
        <div class="info-box bg-light elevation-1">
            <span class="info-box-icon bg-secondary"><i class="fas fa-mobile"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted small">No Token</span>
                <span class="info-box-number text-muted">{{ number_format($stats['fcm_no_token']) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Recipients Table Card -->
<div class="card card-outline card-secondary elevation-2">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-list-alt text-secondary mr-2"></i>User Delivery & Read Log
        </h3>
        <div class="card-tools">
            <form action="{{ route('admin.notifications.show', $notification->id) }}" method="GET" class="form-inline">
                <div class="input-group input-group-sm mr-2">
                    <select name="status" class="form-control" onchange="this.form.submit()">
                        <option value="">All Read/Unread</option>
                        <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read Only ({{ $stats['read'] }})</option>
                        <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread Only ({{ $stats['unread'] }})</option>
                    </select>
                </div>
                <div class="input-group input-group-sm mr-2">
                    <select name="fcm_status" class="form-control" onchange="this.form.submit()">
                        <option value="">All FCM Status</option>
                        <option value="sent" {{ request('fcm_status') === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('fcm_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="no_token" {{ request('fcm_status') === 'no_token' ? 'selected' : '' }}>No Device Token</option>
                    </select>
                </div>
                <div class="input-group input-group-sm" style="width: 200px;">
                    <input type="text" name="search" class="form-control" placeholder="Search partner..." value="{{ request('search') }}">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped text-nowrap mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Partner Name & Contact</th>
                    <th>Mobile</th>
                    <th>FCM Push Delivery</th>
                    <th>Read Status</th>
                    <th>Read At</th>
                    <th class="text-right">Admin Mark</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $item)
                    <tr>
                        <td class="font-weight-bold text-muted">{{ $item->id }}</td>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $item->user->name ?? 'Unknown User' }}</div>
                            <div class="small text-muted">{{ $item->user->email ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <span class="badge badge-light border text-monospace">
                                {{ $item->user->mobile_number ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            @if($item->fcm_status === 'sent')
                                <span class="badge badge-success px-2 py-1">
                                    <i class="fas fa-check-circle mr-1"></i> Delivered (FCM)
                                </span>
                            @elseif($item->fcm_status === 'failed')
                                <span class="badge badge-danger px-2 py-1" title="{{ $item->fcm_error }}">
                                    <i class="fas fa-times-circle mr-1"></i> Failed
                                </span>
                                @if($item->fcm_error)
                                    <small class="d-block text-danger text-truncate" style="max-width: 160px;">{{ $item->fcm_error }}</small>
                                @endif
                            @elseif($item->fcm_status === 'no_token')
                                <span class="badge badge-secondary px-2 py-1">
                                    <i class="fas fa-minus-circle mr-1"></i> No App Token
                                </span>
                            @else
                                <span class="badge badge-warning px-2 py-1">{{ ucfirst($item->fcm_status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($item->is_read)
                                <span class="badge badge-success px-2 py-1 font-weight-bold">
                                    <i class="fas fa-check mr-1"></i> Read
                                </span>
                            @else
                                <span class="badge badge-warning px-2 py-1 font-weight-bold">
                                    <i class="fas fa-clock mr-1"></i> Unread
                                </span>
                            @endif
                        </td>
                        <td class="small">
                            @if($item->is_read && $item->read_at)
                                {{ $item->read_at->format('M d, Y • h:i A') }}<br>
                                <span class="text-muted">({{ $item->read_at->diffForHumans() }})</span>
                            @else
                                <span class="text-muted">Not opened yet</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <form action="{{ route('admin.notifications.toggle_read', $item->id) }}" method="POST" class="d-inline">
                                @csrf
                                @if($item->is_read)
                                    <button type="submit" class="btn btn-outline-warning btn-xs font-weight-bold" title="Mark this notification as Unread">
                                        <i class="fas fa-envelope mr-1"></i> Mark Unread
                                    </button>
                                @else
                                    <button type="submit" class="btn btn-outline-success btn-xs font-weight-bold" title="Mark this notification as Read">
                                        <i class="fas fa-check-circle mr-1"></i> Mark Read
                                    </button>
                                @endif
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fas fa-users-slash mb-2 fa-2x text-secondary"></i>
                            <p class="mb-0 font-weight-bold">No delivery records match your filter.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($deliveries->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing {{ $deliveries->firstItem() ?? 0 }} to {{ $deliveries->lastItem() ?? 0 }} of {{ $deliveries->total() }} users
                </span>
                <div>
                    {{ $deliveries->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
