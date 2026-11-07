@extends('layouts.admin')

@section('title', 'Campaign Detail & Level 2 Allocations')

@section('content')

<div class="row">
    <div class="col-md-6">
        <!-- Campaign Details -->
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-bullhorn text-primary mr-2"></i>{{ $campaign->name }}</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit mr-1"></i> Edit Campaign</a>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Status</b> 
                        <span class="float-right badge badge-{{ $campaign->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($campaign->status) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Advertiser</b> <span class="float-right">{{ $campaign->advertiser_name }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Category</b> <span class="float-right badge badge-info">{{ $campaign->category }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>LEVEL 1: Advertiser Gross Payout</b> <span class="float-right font-weight-bold text-primary">₹{{ number_format($campaign->advertiser_payout, 2) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>LEVEL 2: Default Affiliate Allocation</b> <span class="float-right font-weight-bold text-success">₹{{ number_format($campaign->default_affiliate_payout, 2) }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Landing URL</b> <span class="float-right"><code>{{ Str::limit($campaign->landing_url, 40) }}</code></span>
                    </li>
                    <li class="list-group-item">
                        <b>Conversion Event</b> <span class="float-right">{{ $campaign->conversion_event }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Duplicate IP Protection</b> 
                        <span class="float-right">
                            @if($campaign->check_duplicate_ip)
                                <span class="badge badge-success"><i class="fas fa-shield-alt mr-1"></i> Active (Duplicate IP Checked)</span>
                            @else
                                <span class="badge badge-secondary"><i class="fas fa-times-circle mr-1"></i> Disabled (Skipped)</span>
                            @endif
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Offer Secret Key</b> 
                        <span class="float-right font-weight-bold text-dark">
                            <code>{{ $campaign->postback_secret_key ?? 'N/A' }}</code>
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Offer S2S Postback GET Webhook Target</b> 
                        @php
                            $slug = $campaign->postbackProvider->slug ?? 'global';
                            $postbackUrl = url('/api/v1/postback/' . $slug) . '?click_id={click_id}' . ($campaign->postback_secret_key ? '&secret=' . $campaign->postback_secret_key : '');
                        @endphp
                        <div class="mt-2">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control font-weight-bold text-primary" value="{{ $postbackUrl }}" readonly id="campaignPostbackUrl">
                                <div class="input-group-append">
                                    <button class="btn btn-sm btn-primary" onclick="navigator.clipboard.writeText('{{ $postbackUrl }}'); alert('Copied S2S Postback Webhook URL!');">
                                        <i class="fas fa-copy mr-1"></i> Copy Webhook URL
                                    </button>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                @if($campaign->terms)
                    <div class="callout callout-info">
                        <h5>Campaign Terms</h5>
                        <p class="mb-0">{{ $campaign->terms }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <!-- Level 2 Custom Affiliate Payout Allocation Form -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold"><i class="fas fa-sliders-h text-success mr-2"></i>Level 2 Custom Payout Overrides</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Set custom maximum Level 2 affiliate payout for specific users. (e.g. Affiliate A gets ₹100, Affiliate B gets ₹80).</p>

                <form action="{{ route('admin.campaigns.allocations.store', $campaign) }}" method="POST" class="mb-4 bg-light p-3 rounded border">
                    @csrf
                    <div class="form-group">
                        <label>Select Affiliate User</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">-- Choose User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Allocated Payout (₹)</label>
                            <input type="number" step="0.01" name="affiliate_payout" class="form-control" placeholder="80.00" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Permission</label>
                            <select name="status" class="form-control" required>
                                <option value="allowed">Allowed</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check-circle mr-1"></i> Set Custom Level 2 Payout</button>
                </form>

                <h5 class="font-weight-bold">Current Custom Overrides</h5>
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Affiliate Name</th>
                            <th>Custom Level 2 Payout</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaign->affiliateAllocations as $alloc)
                            <tr>
                                <td>{{ $alloc->user->name ?? 'N/A' }}</td>
                                <td class="font-weight-bold text-success">₹{{ number_format($alloc->affiliate_payout, 2) }}</td>
                                <td><span class="badge badge-{{ $alloc->status === 'allowed' ? 'success' : 'danger' }}">{{ ucfirst($alloc->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted small">No custom overrides defined yet. Default payout applies to all users.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Marketing Collaterals & WhatsApp Posters Card -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-photo-video text-info mr-2"></i>Marketing Collaterals & Promotional Media (Images & Videos)
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Upload Collateral Form -->
                    <div class="col-md-4 col-12 border-right">
                        <h5 class="font-weight-bold mb-3"><i class="fas fa-cloud-upload-alt mr-1"></i> Upload Collateral / Template</h5>
                        <form method="POST" action="{{ route('admin.campaigns.collaterals.upload', $campaign) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Template Title / Theme</label>
                                <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Loot Deal Story or Cashback Alert" required>
                            </div>
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Promotional Category / Preset</label>
                                <select name="category_preset" class="form-control form-control-sm" required>
                                    <option value="special_deal">🔥 Special Deal / Loot Offer</option>
                                    <option value="friends_share">👋 Friends Share / Referral</option>
                                    <option value="cashback_alert">⚡ Cashback Alert / Earnings</option>
                                    <option value="general" selected>🎯 General Promotion</option>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small">Media Type</label>
                                <select name="media_type" id="collateral_media_type" class="form-control form-control-sm" required onchange="toggleMediaFileInput(this.value)">
                                    <option value="image">Poster / Banner Image (PNG, JPG, WEBP)</option>
                                    <option value="video">Promo Video (MP4)</option>
                                    <option value="text">Text-Only Message Template (No Media Required)</option>
                                </select>
                            </div>
                            <div class="form-group mb-2" id="media_file_group">
                                <label class="font-weight-bold small">Media File (Max 50MB)</label>
                                <input type="file" name="media_file" id="media_file_input" class="form-control-file" required>
                            </div>
                            <div class="form-group mb-3">
                                <label class="font-weight-bold small">Pre-Written WhatsApp / Promotional Copy</label>
                                <textarea name="default_caption" class="form-control form-control-sm" rows="3" placeholder="e.g. 🔥 Exclusive offer! Get instant cashback when you sign up here:"></textarea>
                                <small class="text-muted font-italic">Affiliates will see this pre-filled when sharing with their personalized tracking link.</small>
                            </div>
                            <button type="submit" class="btn btn-info btn-block btn-sm font-weight-bold shadow-sm">
                                <i class="fas fa-upload mr-1"></i> Save Collateral / Template
                            </button>
                        </form>
                    </div>

                    <!-- Collaterals List -->
                    <div class="col-md-8 col-12">
                        <h5 class="font-weight-bold mb-3"><i class="fas fa-layer-group mr-1"></i> Active Collaterals & Templates ({{ $campaign->collaterals->count() }})</h5>
                        <div class="row">
                            @forelse($campaign->collaterals as $col)
                                <div class="col-md-4 col-sm-6 col-12 mb-3">
                                    <div class="card h-100 shadow-sm border">
                                        @if($col->media_type === 'video')
                                            <div class="bg-dark text-white text-center py-4 rounded-top">
                                                <i class="fas fa-play-circle fa-3x text-info"></i>
                                                <div class="small mt-1">Video Promo</div>
                                            </div>
                                        @elseif($col->media_type === 'text')
                                            <div class="bg-light text-center py-4 rounded-top border-bottom" style="height: 120px; display: flex; flex-direction: column; justify-content: center; align-items: center; background: linear-gradient(135deg, #e0f2fe 0%, #f0fdf4 100%);">
                                                <i class="fas fa-comment-dots fa-2x text-primary mb-1"></i>
                                                <span class="badge badge-light border text-primary">Text Copy Template</span>
                                            </div>
                                        @else
                                            <img src="{{ $col->media_url }}" class="card-img-top" style="height: 120px; object-fit: cover;" alt="{{ $col->title }}" onerror="this.onerror=null; this.src='https://placehold.co/400x250/e2e8f0/475569?text=Media+Preview';">
                                        @endif
                                        <div class="card-body p-2">
                                            <div class="mb-1">
                                                @if(($col->category_preset ?? 'general') === 'special_deal')
                                                    <span class="badge badge-warning text-dark"><i class="fas fa-fire mr-1"></i>Special Deal</span>
                                                @elseif(($col->category_preset ?? '') === 'friends_share')
                                                    <span class="badge badge-success"><i class="fas fa-user-friends mr-1"></i>Friends Share</span>
                                                @elseif(($col->category_preset ?? '') === 'cashback_alert')
                                                    <span class="badge badge-info"><i class="fas fa-bolt mr-1"></i>Cashback Alert</span>
                                                @else
                                                    <span class="badge badge-secondary"><i class="fas fa-tag mr-1"></i>General</span>
                                                @endif
                                                <span class="badge badge-light border text-uppercase">{{ $col->media_type }}</span>
                                            </div>
                                            <div class="font-weight-bold small text-truncate">{{ $col->title }}</div>
                                            @if($col->default_caption)
                                                <small class="text-muted d-block text-truncate mt-1" title="{{ $col->default_caption }}">{{ $col->default_caption }}</small>
                                            @endif
                                        </div>
                                        <div class="card-footer p-2 bg-light d-flex justify-content-between align-items-center">
                                            @if($col->media_url)
                                                <a href="{{ $col->media_url }}" target="_blank" class="btn btn-xs btn-outline-primary">
                                                    <i class="fas fa-external-link-alt mr-1"></i>View
                                                </a>
                                            @else
                                                <span class="badge badge-light text-muted">Copy Only</span>
                                            @endif
                                            <form method="POST" action="{{ route('admin.campaigns.collaterals.delete', $col->id) }}" onsubmit="return confirm('Delete this collateral?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-outline-danger">
                                                    <i class="fas fa-trash-alt mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 py-4 text-center text-muted">
                                    <i class="fas fa-photo-video fa-2x mb-2 d-block text-secondary"></i>
                                    No marketing collaterals or promotional templates uploaded yet. Upload posters, videos, or pre-written message templates above!
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMediaFileInput(mediaType) {
    const group = document.getElementById('media_file_group');
    const input = document.getElementById('media_file_input');
    if (mediaType === 'text') {
        group.style.display = 'none';
        input.removeAttribute('required');
    } else {
        group.style.display = 'block';
        input.setAttribute('required', 'required');
    }
}
</script>
@endsection
