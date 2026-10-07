@extends('layouts.admin')

@section('title', 'Postback Attempt Logs Audit')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-list-alt text-primary mr-2"></i>Postback Attempt Audit Trail</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Provider</th>
                        <th>Source IP</th>
                        <th>Auth</th>
                        <th>IP Whitelist</th>
                        <th>Click Valid</th>
                        <th>Conversion</th>
                        <th>Response</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td><code>{{ $log->request_id }}</code></td>
                            <td>{{ $log->provider->name ?? 'Unknown' }}</td>
                            <td><code>{{ $log->source_ip }}</code></td>
                            <td><span class="badge badge-{{ $log->auth_result ? 'success' : 'danger' }}">{{ $log->auth_result ? 'PASS' : 'FAIL' }}</span></td>
                            <td><span class="badge badge-{{ $log->ip_whitelist_result ? 'success' : 'danger' }}">{{ $log->ip_whitelist_result ? 'PASS' : 'FAIL' }}</span></td>
                            <td><span class="badge badge-{{ $log->click_validation_result ? 'success' : 'danger' }}">{{ $log->click_validation_result ? 'PASS' : 'FAIL' }}</span></td>
                            <td><span class="badge badge-{{ $log->conversion_result ? 'success' : 'danger' }}">{{ $log->conversion_result ? 'PASS' : 'FAIL' }}</span></td>
                            <td>
                                <span class="badge badge-{{ $log->response_code === 200 ? 'success' : 'warning' }}">{{ $log->response_code }}</span>
                                <small class="d-block text-muted">{{ Str::limit($log->rejection_reason, 25) }}</small>
                            </td>
                            <td>{{ $log->created_at ? $log->created_at->format('M d, H:i:s') : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No postback log attempts recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $logs->links() }}
    </div>
</div>

@endsection
