@extends('layouts.admin')

@section('title', 'Postback Integration Providers')

@section('content')

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-network-wired text-primary mr-2"></i>Configured Integration Networks</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name / Slug</th>
                            <th>Auth Method</th>
                            <th>Postback URL Webhook Target</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($providers as $prov)
                            <tr>
                                <td>#{{ $prov->id }}</td>
                                <td>
                                    <strong class="d-block">{{ $prov->name }}</strong>
                                    <code>{{ $prov->slug }}</code>
                                </td>
                                <td><span class="badge badge-info">{{ strtoupper(str_replace('_', ' ', $prov->auth_method)) }}</span></td>
                                <td><code>{{ url('/api/v1/postback/' . $prov->slug) }}</code></td>
                                <td><span class="badge badge-{{ $prov->status === 'active' ? 'success' : 'danger' }}">{{ ucfirst($prov->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No postback providers configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-plus-circle text-success mr-2"></i>Add Postback Provider</h3></div>
            <div class="card-body">
                <form action="{{ route('admin.postbacks.providers') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Network / Provider Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Network A" required>
                    </div>

                    <div class="form-group">
                        <label>Provider Slug (URL key)</label>
                        <input type="text" name="slug" class="form-control" placeholder="network-a">
                    </div>

                    <div class="form-group">
                        <label>Authentication Method <span class="text-danger">*</span></label>
                        <select name="auth_method" class="form-control" required>
                            <option value="shared_secret">Shared Secret</option>
                            <option value="api_key">API Key</option>
                            <option value="hmac_signature">HMAC Signature</option>
                            <option value="ip_only">IP Whitelist Only</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Secret Key / API Token</label>
                        <input type="password" name="secret_key" class="form-control" placeholder="Secret key stored securely">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Save Provider</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
