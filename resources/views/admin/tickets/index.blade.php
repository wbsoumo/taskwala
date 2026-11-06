@extends('layouts.admin')

@section('title', 'Missing Conversion Tickets')

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-ticket-alt text-primary mr-2"></i>Missing Lead & Conversion Dispute Tickets
                </h3>
            </div>

            <!-- Filters -->
            <div class="card-body bg-light border-bottom py-3">
                <form method="GET" action="{{ route('admin.tickets.index') }}" class="row g-2 align-items-center">
                    <div class="col-md-5 col-12">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by Ref ID, User, or Campaign..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-secondary btn-block">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped text-nowrap mb-0 align-middle">
                    <thead class="thead-dark">
                        <tr>
                            <th>Ticket ID</th>
                            <th>Affiliate User</th>
                            <th>Campaign</th>
                            <th>Order / Lead Ref</th>
                            <th>Task Date</th>
                            <th>Proof</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $t)
                        <tr>
                            <td><strong>#{{ $t->id }}</strong></td>
                            <td>
                                <div><strong>{{ $t->user?->name ?? 'N/A' }}</strong></div>
                                <small class="text-muted">{{ $t->user?->email }}</small>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $t->campaign?->category ?? 'General' }}</span>
                                <div class="font-weight-bold">{{ $t->campaign?->name ?? 'N/A' }}</div>
                            </td>
                            <td><code>{{ $t->order_reference_id }}</code></td>
                            <td>{{ $t->task_date?->format('d M, Y') }}</td>
                            <td>
                                @if($t->screenshot_url)
                                    <a href="{{ $t->screenshot_url }}" target="_blank" class="btn btn-xs btn-outline-primary">
                                        <i class="fas fa-image mr-1"></i>View Proof
                                    </a>
                                @else
                                    <span class="text-muted">None</span>
                                @endif
                            </td>
                            <td>
                                @if($t->status === 'approved')
                                    <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Approved</span>
                                @elseif($t->status === 'under_review')
                                    <span class="badge badge-warning px-2 py-1"><i class="fas fa-clock mr-1"></i>Under Review</span>
                                @elseif($t->status === 'rejected')
                                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i>Rejected</span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1"><i class="fas fa-hourglass-start mr-1"></i>Pending</span>
                                @endif
                            </td>
                            <td>{{ $t->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <a href="{{ route('admin.tickets.show', $t->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye mr-1"></i>Review
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-ticket-alt fa-2x mb-2 d-block text-secondary"></i>
                                No missing conversion tickets found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
            <div class="card-footer clearfix">
                {{ $tickets->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
