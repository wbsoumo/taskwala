@extends('layouts.admin')

@section('title', 'Authentication Login Logs')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-history text-primary mr-2"></i>Login Attempts Log</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Guard</th>
                        <th>Email Attempted</th>
                        <th>Status</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td><span class="badge badge-secondary">{{ strtoupper($log->guard) }}</span></td>
                            <td class="font-weight-bold">{{ $log->email }}</td>
                            <td><span class="badge badge-{{ $log->status === 'success' ? 'success' : 'danger' }}">{{ strtoupper($log->status) }}</span></td>
                            <td><code>{{ $log->ip_address }}</code></td>
                            <td><small class="text-muted">{{ Str::limit($log->user_agent, 40) }}</small></td>
                            <td>{{ $log->created_at ? $log->created_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No login logs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">{{ $logs->links() }}</div>
</div>

@endsection
