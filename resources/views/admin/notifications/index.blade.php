@extends('layouts.admin')

@section('title', 'Push Notifications')

@section('content')
<div class="row mb-3">
    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-info elevation-2">
            <div class="inner">
                <h3>{{ number_format($totalUsers) }}</h3>
                <p>Total Registered Users</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.notifications.users') }}" class="small-box-footer">View App Users <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-success elevation-2">
            <div class="inner">
                <h3>{{ number_format($usersWithToken) }}</h3>
                <p>Users with FCM Push Token</p>
            </div>
            <div class="icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <a href="{{ route('admin.notifications.users', ['token_status' => 'has_token']) }}" class="small-box-footer">Filter Connected <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 col-12">
        <div class="small-box bg-primary elevation-2">
            <div class="inner">
                <h3>{{ number_format($totalSentNotifications) }}</h3>
                <p>Total Push Messages Sent</p>
            </div>
            <div class="icon">
                <i class="fas fa-paper-plane"></i>
            </div>
            <a href="{{ route('admin.notifications.create') }}" class="small-box-footer">Compose New <i class="fas fa-plus-circle"></i></a>
        </div>
    </div>
</div>

<div class="card card-outline card-primary elevation-2">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-bell text-primary mr-2"></i>Sent Notifications & Read Statistics
        </h3>
        <div class="card-tools ml-auto">
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-sm font-weight-bold shadow-sm">
                <i class="fas fa-paper-plane mr-1"></i> Send New Notification
            </a>
            <a href="{{ route('admin.notifications.users') }}" class="btn btn-outline-secondary btn-sm font-weight-bold ml-1">
                <i class="fas fa-users mr-1"></i> Registered Users
            </a>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle text-nowrap mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 70px;">#ID</th>
                    <th>Date & Time</th>
                    <th>Title & Message</th>
                    <th>Target</th>
                    <th>Deeplink</th>
                    <th>FCM Delivery</th>
                    <th>Read / Unread</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $notif)
                    <tr>
                        <td class="font-weight-bold text-muted">#{{ $notif->id }}</td>
                        <td class="small">
                            {{ $notif->created_at->format('M d, Y') }}<br>
                            <span class="text-muted">{{ $notif->created_at->format('h:i A') }}</span>
                        </td>
                        <td style="white-space: normal; min-width: 260px; max-width: 380px;">
                            <div class="font-weight-bold text-dark">{{ $notif->title }}</div>
                            <div class="small text-muted text-truncate" style="max-width: 360px;">
                                {{ $notif->body }}
                            </div>
                        </td>
                        <td>
                            @if($notif->target_type === 'all')
                                <span class="badge badge-info px-2 py-1"><i class="fas fa-globe mr-1"></i> All Users ({{ $notif->total_targeted }})</span>
                            @else
                                <span class="badge badge-secondary px-2 py-1"><i class="fas fa-user-check mr-1"></i> Targeted ({{ $notif->total_targeted }})</span>
                            @endif
                        </td>
                        <td>
                            @if($notif->deeplink)
                                <span class="badge badge-light border text-monospace text-primary px-2 py-1">
                                    <i class="fas fa-external-link-alt mr-1"></i>{{ Str::limit($notif->deeplink, 24) }}
                                </span>
                            @else
                                <span class="text-muted small">None</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-success px-2 py-1" title="FCM sent successfully">
                                <i class="fas fa-check-circle mr-1"></i> {{ $notif->sent_count }}
                            </span>
                            @if($notif->failed_count > 0)
                                <span class="badge badge-danger px-2 py-1 ml-1" title="FCM delivery failed">
                                    <i class="fas fa-times-circle mr-1"></i> {{ $notif->failed_count }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge badge-success px-2 py-1" title="Read by users">
                                    <i class="fas fa-envelope-open mr-1"></i> {{ $notif->read_count }} Read
                                </span>
                                <span class="badge badge-warning px-2 py-1 ml-1" title="Unread by users">
                                    <i class="fas fa-envelope mr-1"></i> {{ $notif->unread_count }} Unread
                                </span>
                            </div>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.notifications.show', $notif->id) }}" class="btn btn-outline-primary btn-sm font-weight-bold">
                                <i class="fas fa-eye mr-1"></i> View Deliveries
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-bell-slash fa-3x mb-3 text-secondary"></i>
                            <p class="mb-1 font-weight-bold">No Push Notifications Sent Yet</p>
                            <p class="small">Click "Send New Notification" above to send push alerts to app users.</p>
                            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-paper-plane mr-1"></i> Send Your First Notification
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($notifications->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <span class="small text-muted">
                    Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} notifications
                </span>
                <div>
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
