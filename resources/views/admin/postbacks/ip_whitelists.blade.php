@extends('layouts.admin')

@section('title', 'Postback IP Whitelist Configuration')

@section('content')

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-shield-alt text-primary mr-2"></i>Allowed IP Addresses & CIDR Ranges</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Provider</th>
                            <th>Allowed IP / Subnet</th>
                            <th>Description</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($whitelists as $ip)
                            <tr>
                                <td>{{ $ip->provider->name ?? 'N/A' }}</td>
                                <td><code>{{ $ip->ip_address }}</code></td>
                                <td>{{ $ip->description ?? '-' }}</td>
                                <td>{{ $ip->created_at ? $ip->created_at->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No IP whitelists configured. All incoming postback IPs allowed if disabled in config.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-plus-circle text-success mr-2"></i>Whitelist IP / Subnet</h3></div>
            <div class="card-body">
                <form action="{{ route('admin.postbacks.ip_whitelists') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Provider <span class="text-danger">*</span></label>
                        <select name="postback_provider_id" class="form-control" required>
                            <option value="">-- Choose Provider --</option>
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>IP Address or CIDR Subnet <span class="text-danger">*</span></label>
                        <input type="text" name="ip_address" class="form-control" placeholder="e.g. 1.2.3.4 or 10.0.0.0/24" required>
                    </div>

                    <div class="form-group">
                        <label>Description / Location</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Server A North America">
                    </div>

                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Add Whitelist IP</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
