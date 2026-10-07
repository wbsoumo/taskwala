@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header"><h3 class="card-title font-weight-bold"><i class="fas fa-cogs text-primary mr-2"></i>Global Platform Settings</h3></div>
            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Platform Name</label>
                        <input type="text" name="platform_name" class="form-control" value="{{ $settings['platform_name'] ?? config('platform.name') }}">
                    </div>

                    <div class="form-group">
                        <label>Default Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" value="{{ $settings['currency_symbol'] ?? '₹' }}">
                    </div>

                    <div class="form-group">
                        <label>Minimum Customer Payout Allowed (₹)</label>
                        <input type="number" step="0.01" name="min_customer_payout" class="form-control" value="{{ $settings['min_customer_payout'] ?? '0.00' }}">
                    </div>

                    <div class="form-group">
                        <label>Enable Postback IP Whitelisting</label>
                        <select name="postback_ip_whitelist_enabled" class="form-control">
                            <option value="1" {{ ($settings['postback_ip_whitelist_enabled'] ?? '1') == '1' ? 'selected' : '' }}>Enabled (Strict Security)</option>
                            <option value="0" {{ ($settings['postback_ip_whitelist_enabled'] ?? '') == '0' ? 'selected' : '' }}>Disabled (Allow All IPs)</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
