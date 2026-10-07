@extends('layouts.admin')

@section('title', 'Offer-Wise S2S Postback Routing')

@section('content')

<div class="card card-outline card-primary shadow-sm">
    <div class="card-header">
        <h3 class="card-title font-weight-bold"><i class="fas fa-bullhorn text-primary mr-2"></i>Offer & Campaign Specific Postback Endpoints</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Campaign ID</th>
                        <th>Offer / Campaign Name</th>
                        <th>Advertiser Network</th>
                        <th>Assigned Postback Provider</th>
                        <th>Offer S2S Postback Target URL</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $camp)
                        @php
                            $slug = $camp->postbackProvider->slug ?? 'global';
                            $secret = $camp->postback_secret_key ?? 'None';
                            $url = url('/api/v1/postback/' . $slug) . '?click_id={click_id}' . ($camp->postback_secret_key ? '&secret=' . $camp->postback_secret_key : '');
                        @endphp
                        <tr>
                            <td>#{{ $camp->id }}</td>
                            <td>
                                <strong>{{ $camp->name }}</strong><br>
                                <span class="badge badge-info">{{ $camp->category }}</span>
                            </td>
                            <td><span class="badge badge-light border">{{ $camp->advertiser_name }}</span></td>
                            <td>
                                @if($camp->postbackProvider)
                                    <span class="badge badge-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> {{ $camp->postbackProvider->name }}</span>
                                @else
                                    <span class="badge badge-secondary">Global System Default</span>
                                @endif
                                @if($camp->postback_secret_key)
                                    <div class="mt-1"><small class="text-muted">Secret:</small> <code>{{ $camp->postback_secret_key }}</code></div>
                                @endif
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control form-control-sm font-weight-bold text-primary" value="{{ $url }}" readonly id="url_{{ $camp->id }}">
                                    <div class="input-group-append">
                                        <button class="btn btn-sm btn-outline-primary" onclick="navigator.clipboard.writeText('{{ $url }}'); alert('Copied Offer Postback URL!');">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('admin.campaigns.edit', $camp) }}" class="btn btn-xs btn-primary">
                                    <i class="fas fa-cog mr-1"></i> Configure
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No campaign offers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix bg-light">
        <div class="float-right">
            {{ $campaigns->links() }}
        </div>
    </div>
</div>

@endsection
