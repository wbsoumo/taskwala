@extends('layouts.admin')

@section('title', 'Affiliate Performance Report')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-user-chart text-primary mr-2"></i>Affiliate Performance Summary</h3></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>Affiliate User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Total Clicks</th>
                        <th>Total Conversions</th>
                        <th>Wallet Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($affiliates as $user)
                        <tr>
                            <td class="font-weight-bold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge badge-{{ $user->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($user->status) }}</span></td>
                            <td>{{ $user->clicks_count }}</td>
                            <td>{{ $user->conversions_count }}</td>
                            <td class="text-success font-weight-bold">₹{{ number_format($user->wallet->balance ?? 0, 2) }}</td>
                            <td><a href="{{ route('admin.users.show', $user) }}" class="btn btn-xs btn-primary">Profile</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No affiliate report data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
