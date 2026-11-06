@extends('layouts.admin')

@section('title', 'Deal Synchronization Logs')

@section('content')
<div class="row">
    <!-- Log Stats -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info shadow-sm">
            <div class="inner">
                <h3>{{ number_format($stats['total_runs']) }}</h3>
                <p>Total Sync Cycles</p>
            </div>
            <div class="icon"><i class="fas fa-history"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3>{{ number_format($stats['successful_runs']) }}</h3>
                <p>Successful Cycles</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3 class="text-white">{{ number_format($stats['partial_runs']) }}</h3>
                <p class="text-white">Partial Cycles</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3>{{ number_format($stats['failed_runs']) }}</h3>
                <p>Failed Cycles</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark">
            <i class="fas fa-list-alt text-primary mr-1"></i> Synchronization Execution History
        </h3>
        <div class="card-tools d-flex align-items-center">
            <form action="{{ route('admin.deals.settings.sync_now') }}" method="POST" class="d-inline mr-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-sync-alt mr-1"></i> Run Sync Now
                </button>
            </form>
            @if($logs->count() > 0)
                <form action="{{ route('admin.deals.logs.clear') }}" method="POST" class="d-inline" onsubmit="return confirm('Clear all synchronization logs?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash-alt mr-1"></i> Clear Logs
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="card-body p-0 table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small font-weight-bold text-secondary">
                <tr>
                    <th style="width: 170px;">Date & Time</th>
                    <th>Target Channel</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 120px;">Found</th>
                    <th style="width: 120px;">Added</th>
                    <th style="width: 120px;">Failed</th>
                    <th>Details / Error Log</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small text-muted font-monospace">
                            {{ $log->created_at->format('d M Y, h:i:s A') }}
                        </td>
                        <td class="font-weight-bold text-dark">
                            <i class="fab fa-telegram text-primary mr-1"></i> {{ $log->channel }}
                        </td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Success</span>
                            @elseif($log->status === 'partial')
                                <span class="badge badge-warning px-2 py-1 text-dark"><i class="fas fa-exclamation-triangle mr-1"></i> Partial</span>
                            @else
                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Failed</span>
                            @endif
                        </td>
                        <td class="font-weight-bold">{{ number_format($log->deals_found) }}</td>
                        <td class="font-weight-bold text-success">+{{ number_format($log->deals_added) }}</td>
                        <td class="font-weight-bold {{ $log->deals_failed > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ number_format($log->deals_failed) }}
                        </td>
                        <td class="small">
                            @if($log->error_message)
                                <span class="text-danger"><i class="fas fa-info-circle mr-1"></i> {{ $log->error_message }}</span>
                            @else
                                <span class="text-muted"><i class="fas fa-check text-success mr-1"></i> Clean aggregation cycle</span>
                            @endif
                            @if(!empty($log->raw_response['skipped']))
                                <div class="text-muted small">({{ $log->raw_response['skipped'] }} duplicates skipped)</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-3x mb-3 text-secondary"></i>
                            <h5>No Sync Logs Yet</h5>
                            <p class="small text-muted mb-0">Run a manual sync or wait for the automatic scheduler to populate logs.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <div class="small text-muted">Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} logs</div>
            <div>{{ $logs->links() }}</div>
        </div>
    @endif
</div>
@endsection
