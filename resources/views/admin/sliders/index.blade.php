@extends('layouts.admin')

@section('title', 'App Sliders & Banners')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">
                    <i class="fas fa-images text-primary mr-2"></i> App Sliders & Banners
                </h1>
                <small class="text-muted">Manage dynamic image and video promotional banners displayed in the Taskwala mobile app.</small>
            </div>
            <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary font-weight-bold shadow-sm">
                    <i class="fas fa-plus mr-1"></i> Add New Slider
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- STATS ROW -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm border border-left-primary">
                    <div class="inner p-3">
                        <h3 class="font-weight-bold text-dark">{{ $stats['total'] }}</h3>
                        <p class="text-muted mb-0 font-weight-bold">Total Sliders</p>
                    </div>
                    <div class="icon" style="color: rgba(2, 132, 199, 0.15);">
                        <i class="fas fa-film"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm border border-left-success">
                    <div class="inner p-3">
                        <h3 class="font-weight-bold text-success">{{ $stats['active'] }}</h3>
                        <p class="text-muted mb-0 font-weight-bold">Active in App</p>
                    </div>
                    <div class="icon" style="color: rgba(16, 185, 129, 0.15);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm border border-left-danger">
                    <div class="inner p-3">
                        <h3 class="font-weight-bold text-danger">{{ $stats['videos'] }}</h3>
                        <p class="text-muted mb-0 font-weight-bold">Video Banners</p>
                    </div>
                    <div class="icon" style="color: rgba(239, 68, 68, 0.15);">
                        <i class="fas fa-video"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-white shadow-sm border border-left-info">
                    <div class="inner p-3">
                        <h3 class="font-weight-bold text-info">{{ $stats['images'] }}</h3>
                        <p class="text-muted mb-0 font-weight-bold">Image Banners</p>
                    </div>
                    <div class="icon" style="color: rgba(6, 182, 212, 0.15);">
                        <i class="fas fa-image"></i>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <!-- MAIN TABLE CARD -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title font-weight-bold mb-0 text-dark">
                    <i class="fas fa-list text-secondary mr-2"></i> Configured Sliders
                </h5>
                <div>
                    <form method="GET" action="{{ route('admin.sliders.index') }}" class="form-inline">
                        <select name="type" class="custom-select custom-select-sm mr-2" onchange="this.form.submit()">
                            <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
                            <option value="image" {{ request('type') == 'image' ? 'selected' : '' }}>Images Only</option>
                            <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>Videos Only</option>
                        </select>
                        <select name="status" class="custom-select custom-select-sm" onchange="this.form.submit()">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Only</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 70px;">Order</th>
                            <th style="width: 140px;">Media Preview</th>
                            <th>Details & Type</th>
                            <th>Click Destination</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 120px;" class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sliders as $slider)
                            <tr>
                                <td class="font-weight-bold text-muted text-center">
                                    <span class="badge badge-light border">{{ $slider->order }}</span>
                                </td>
                                <td>
                                    <div class="position-relative rounded overflow-hidden shadow-sm border bg-dark" style="width: 120px; height: 68px;">
                                        @if($slider->media_type === 'video')
                                            @if($slider->thumbnail_url)
                                                <img src="{{ $slider->thumbnail_full_url }}" alt="thumb" class="w-100 h-100" style="object-fit: cover;">
                                            @else
                                                <video src="{{ $slider->media_full_url }}" class="w-100 h-100" style="object-fit: cover;" muted preload="metadata"></video>
                                            @endif
                                            <div class="position-absolute top-50 start-50 translate-middle text-white" style="top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none;">
                                                <i class="fas fa-play-circle fa-2x drop-shadow"></i>
                                            </div>
                                        @else
                                            <img src="{{ $slider->media_full_url }}" alt="banner" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='https://placehold.co/120x68/1e293b/ffffff?text=Image'">
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center mb-1">
                                        @if($slider->media_type === 'video')
                                            <span class="badge badge-danger mr-2 px-2 py-1"><i class="fas fa-video mr-1"></i> VIDEO</span>
                                        @else
                                            <span class="badge badge-info mr-2 px-2 py-1"><i class="fas fa-image mr-1"></i> IMAGE</span>
                                        @endif

                                        @if($slider->badge_text)
                                            <span class="badge badge-warning text-dark font-weight-bold px-2 py-1">{{ $slider->badge_text }}</span>
                                        @endif
                                    </div>

                                    <h6 class="font-weight-bold mb-0 text-dark">
                                        {{ $slider->title ?: 'Untitled Banner #' . $slider->id }}
                                    </h6>
                                    @if($slider->subtitle)
                                        <small class="text-muted d-block line-clamp-1" style="max-width: 320px;">
                                            {{ $slider->subtitle }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($slider->action_type === 'campaign')
                                        @php $camp = $slider->target_campaign; @endphp
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-briefcase text-primary mr-2 fa-lg"></i>
                                            <div>
                                                <div class="small font-weight-bold text-primary">Opens Offer / Campaign:</div>
                                                <div class="font-weight-bold">{{ $camp ? $camp->name : 'Offer #' . $slider->action_value }}</div>
                                            </div>
                                        </div>
                                    @elseif($slider->action_type === 'deal')
                                        @php $d = $slider->target_deal; @endphp
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-tag text-success mr-2 fa-lg"></i>
                                            <div>
                                                <div class="small font-weight-bold text-success">Opens Deal:</div>
                                                <div class="font-weight-bold text-truncate" style="max-width: 200px;">{{ $d ? $d->title : 'Deal #' . $slider->action_value }}</div>
                                            </div>
                                        </div>
                                    @elseif($slider->action_type === 'app_route')
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-mobile-alt text-info mr-2 fa-lg"></i>
                                            <div>
                                                <div class="small font-weight-bold text-info">Opens App Page:</div>
                                                <code>{{ $slider->action_value }}</code>
                                            </div>
                                        </div>
                                    @elseif($slider->action_type === 'external_url')
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-external-link-alt text-warning mr-2 fa-lg"></i>
                                            <div>
                                                <div class="small font-weight-bold text-warning">Opens External Link:</div>
                                                <a href="{{ $slider->action_value }}" target="_blank" class="small text-truncate d-inline-block" style="max-width: 200px;">
                                                    {{ $slider->action_value }}
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge badge-secondary">None (Informational)</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input slider-toggle" id="toggle-{{ $slider->id }}" data-id="{{ $slider->id }}" {{ $slider->is_active ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold {{ $slider->is_active ? 'text-success' : 'text-muted' }}" for="toggle-{{ $slider->id }}" id="label-{{ $slider->id }}">
                                            {{ $slider->is_active ? 'Active' : 'Paused' }}
                                        </label>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.sliders.edit', $slider->id) }}" class="btn btn-sm btn-outline-primary mr-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.sliders.destroy', $slider->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this slider banner?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-images fa-3x mb-3 text-secondary" style="opacity: 0.4;"></i>
                                    <h5 class="font-weight-bold text-dark">No App Sliders Configured</h5>
                                    <p class="small mb-3">Add image or video carousel banners to engage users on the mobile dashboard.</p>
                                    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary btn-sm font-weight-bold">
                                        <i class="fas fa-plus mr-1"></i> Add First Slider
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($sliders->hasPages())
                <div class="card-footer bg-white border-top py-3 d-flex justify-content-center">
                    {{ $sliders->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.slider-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const label = document.getElementById('label-' + id);
            
            fetch(`/admin/sliders/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.is_active) {
                        label.textContent = 'Active';
                        label.classList.remove('text-muted');
                        label.classList.add('text-success');
                    } else {
                        label.textContent = 'Paused';
                        label.classList.remove('text-success');
                        label.classList.add('text-muted');
                    }
                }
            })
            .catch(() => {
                this.checked = !this.checked;
                alert('Could not update slider status.');
            });
        });
    });
</script>
@endpush
