@extends('layouts.admin')

@section('title', 'System Audit Logs')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-user-shield text-primary mr-2"></i>Sensitive Operation Audit Trail</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Target Entity</th>
                        <th>IP Address</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>#{{ $log->id }}</td>
                            <td><span class="badge badge-info">{{ ucfirst($log->actor_type) }} #{{ $log->actor_id ?? 'System' }}</span></td>
                            <td class="font-weight-bold"><code>{{ $log->action }}</code></td>
                            <td>{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                            <td><code>{{ $log->ip_address }}</code></td>
                            <td>{{ $log->created_at ? $log->created_at->format('M d, Y H:i:s') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No audit logs recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">{{ $logs->links() }}</div>
</div>

@endsection
