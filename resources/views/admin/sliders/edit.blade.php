@extends('layouts.admin')

@section('title', 'Edit App Slider #' . $slider->id)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">
                    <i class="fas fa-edit text-warning mr-2"></i> Edit Slider #{{ $slider->id }}
                </h1>
                <small class="text-muted">Update media, video settings, and click navigation destination.</small>
            </div>
            <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                <a href="{{ route('admin.sliders.index') }}" class="btn btn-outline-secondary font-weight-bold">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Sliders
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <form action="{{ route('admin.sliders.update', $slider->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- LEFT COLUMN: MEDIA & CONTENT -->
                <div class="col-lg-7 col-12">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title font-weight-bold mb-0 text-dark">
                                <i class="fas fa-film text-primary mr-2"></i> Media & Banner Content
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- CURRENT MEDIA PREVIEW -->
                            <div class="mb-4 p-3 bg-light rounded border text-center">
                                <label class="font-weight-bold text-muted small text-uppercase d-block mb-2">Current Media Preview</label>
                                @if($slider->media_type === 'video')
                                    <div class="position-relative d-inline-block" style="max-width: 420px; width: 100%;">
                                        <video src="{{ $slider->media_full_url }}" controls style="width: 100%; max-height: 220px; border-radius: 12px; background: #000;"></video>
                                        <div class="mt-2 text-muted small">
                                            <span class="badge badge-danger"><i class="fas fa-video mr-1"></i> Video</span>
                                            <span class="ml-2 font-mono">{{ $slider->media_full_url }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-inline-block" style="max-width: 420px; width: 100%;">
                                        <img src="{{ $slider->media_full_url }}" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 12px; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                                        <div class="mt-2 text-muted small">
                                            <span class="badge badge-info"><i class="fas fa-image mr-1"></i> Image</span>
                                            <span class="ml-2 font-mono">{{ $slider->media_full_url }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- MEDIA TYPE SELECTION -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-dark d-block mb-2">MEDIA TYPE <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                    <label class="btn btn-outline-info {{ old('media_type', $slider->media_type) === 'image' ? 'active' : '' }} font-weight-bold py-2">
                                        <input type="radio" name="media_type" id="type_image" value="image" {{ old('media_type', $slider->media_type) === 'image' ? 'checked' : '' }} onchange="handleMediaTypeChange('image')">
                                        <i class="fas fa-image mr-1"></i> Image Banner (PNG, JPG, WEBP)
                                    </label>
                                    <label class="btn btn-outline-danger {{ old('media_type', $slider->media_type) === 'video' ? 'active' : '' }} font-weight-bold py-2">
                                        <input type="radio" name="media_type" id="type_video" value="video" {{ old('media_type', $slider->media_type) === 'video' ? 'checked' : '' }} onchange="handleMediaTypeChange('video')">
                                        <i class="fas fa-video mr-1"></i> Video Banner (MP4, WEBM)
                                    </label>
                                </div>
                            </div>

                            <!-- REPLACE FILE UPLOAD -->
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark" id="mediaFileLabel">
                                    Replace Media File (Optional)
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="media_file" class="custom-file-input @error('media_file') is-invalid @enderror" id="media_file" accept="image/*,video/mp4,video/webm">
                                    <label class="custom-file-label" for="media_file" id="media_file_name">Choose replacement file (leave empty to keep current)...</label>
                                </div>
                                <small class="form-text text-muted" id="mediaHelpText">
                                    Supported formats: PNG, JPG, WEBP, GIF, MP4, WEBM. Max 50MB.
                                </small>
                                @error('media_file')
                                    <span class="text-danger small font-weight-bold">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="text-center my-2 text-muted small font-weight-bold">
                                — OR REPLACE WITH DIRECT MEDIA URL —
                            </div>

                            <div class="form-group mb-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                                    </div>
                                    <input type="url" name="media_url_input" class="form-control" placeholder="https://example.com/banner.jpg or https://example.com/video.mp4" value="{{ old('media_url_input', (str_starts_with($slider->media_url ?? '', 'http') ? $slider->media_url : '')) }}">
                                </div>
                            </div>

                            <!-- VIDEO THUMBNAIL (POSTER) -->
                            <div class="form-group mb-4" id="videoThumbnailGroup" style="display: {{ old('media_type', $slider->media_type) === 'video' ? 'block' : 'none' }};">
                                <label class="font-weight-bold text-dark">
                                    Video Thumbnail / Poster Image (Optional)
                                </label>
                                @if($slider->thumbnail_full_url)
                                    <div class="mb-2">
                                        <img src="{{ $slider->thumbnail_full_url }}" style="height: 50px; border-radius: 6px;" alt="Current Thumbnail">
                                        <span class="text-muted small ml-2">Current thumbnail attached</span>
                                    </div>
                                @endif
                                <div class="custom-file">
                                    <input type="file" name="thumbnail_file" class="custom-file-input" id="thumbnail_file" accept="image/*">
                                    <label class="custom-file-label" for="thumbnail_file" id="thumbnail_file_name">Choose replacement thumbnail...</label>
                                </div>
                                <small class="form-text text-muted">Cover image displayed before/while the video buffers.</small>
                            </div>

                            <hr>

                            <!-- TEXT OVERLAYS -->
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">BANNER TITLE (OPTIONAL)</label>
                                <input type="text" name="title" class="form-control font-weight-bold" placeholder="e.g. Kotak811 Zero Balance Account" value="{{ old('title', $slider->title) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">SUBTITLE / HIGHLIGHT TEXT (OPTIONAL)</label>
                                <textarea name="subtitle" rows="2" class="form-control" placeholder="e.g. Earn flat ₹300 on every successful account opening">{{ old('subtitle', $slider->subtitle) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold text-dark">BADGE TAG (OPTIONAL)</label>
                                        <input type="text" name="badge_text" class="form-control" placeholder="e.g. HOT DEAL, 50% OFF, WATCH" value="{{ old('badge_text', $slider->badge_text) }}">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold text-dark">DISPLAY ORDER</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', $slider->order) }}" min="0">
                                        <small class="text-muted">Lower numbers appear first in the slider.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: CLICK DESTINATION & BEHAVIOR -->
                <div class="col-lg-5 col-12">
                    <!-- CLICK ACTION CARD -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title font-weight-bold mb-0 text-dark">
                                <i class="fas fa-hand-pointer text-success mr-2"></i> Click Destination Settings
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-3">
                                Specify where the Taskwala mobile app should navigate when a user taps this slider banner.
                            </p>

                            <!-- ACTION TYPE SELECTOR -->
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">ACTION ON CLICK <span class="text-danger">*</span></label>
                                <select name="action_type" id="action_type" class="custom-select font-weight-bold" onchange="handleActionTypeChange(this.value)">
                                    <option value="none" {{ old('action_type', $slider->action_type) == 'none' ? 'selected' : '' }}>None (Informational banner only)</option>
                                    <option value="campaign" {{ old('action_type', $slider->action_type) == 'campaign' ? 'selected' : '' }}>🎯 Open Specific Offer / Campaign</option>
                                    <option value="deal" {{ old('action_type', $slider->action_type) == 'deal' ? 'selected' : '' }}>⚡ Open Specific Deal</option>
                                    <option value="app_route" {{ old('action_type', $slider->action_type) == 'app_route' ? 'selected' : '' }}>📱 Open App Page / Screen</option>
                                    <option value="external_url" {{ old('action_type', $slider->action_type) == 'external_url' ? 'selected' : '' }}>🌐 Open External Website URL</option>
                                </select>
                            </div>

                            <!-- CAMPAIGN SELECTOR -->
                            <div class="form-group mb-3" id="campaignActionGroup" style="display: none;">
                                <label class="font-weight-bold text-dark">SELECT CAMPAIGN / OFFER <span class="text-danger">*</span></label>
                                <select name="campaign_id" class="custom-select select2">
                                    <option value="">-- Choose Campaign --</option>
                                    @foreach($campaigns as $camp)
                                        <option value="{{ $camp->id }}" {{ (old('campaign_id', ($slider->action_type === 'campaign' ? $slider->action_value : '')) == $camp->id) ? 'selected' : '' }}>
                                            {{ $camp->name }} ({{ $camp->category }} - ₹{{ number_format((float) ($camp->default_affiliate_payout ?? $camp->affiliate_payout ?? 0)) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Tapping the slider will immediately open the Link Generator for this offer.</small>
                            </div>

                            <!-- DEAL SELECTOR -->
                            <div class="form-group mb-3" id="dealActionGroup" style="display: none;">
                                <label class="font-weight-bold text-dark">SELECT DEAL <span class="text-danger">*</span></label>
                                <select name="deal_id" class="custom-select">
                                    <option value="">-- Choose Deal --</option>
                                    @foreach($deals as $dl)
                                        <option value="{{ $dl->id }}" {{ (old('deal_id', ($slider->action_type === 'deal' ? $slider->action_value : '')) == $dl->id) ? 'selected' : '' }}>
                                            #{{ $dl->id }} - {{ \Illuminate\Support\Str::limit($dl->title, 45) }} (₹{{ number_format($dl->price ?? 0) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Tapping the slider will open the deal detail and grab-link popup.</small>
                            </div>

                            <!-- APP ROUTE SELECTOR -->
                            <div class="form-group mb-3" id="appRouteActionGroup" style="display: none;">
                                <label class="font-weight-bold text-dark">SELECT APP SCREEN <span class="text-danger">*</span></label>
                                <select name="app_route" class="custom-select">
                                    <option value="">-- Choose Screen --</option>
                                    @foreach($commonRoutes as $cr)
                                        <option value="{{ $cr['route'] }}" {{ (old('app_route', ($slider->action_type === 'app_route' ? $slider->action_value : '')) == $cr['route']) ? 'selected' : '' }}>
                                            {{ $cr['label'] }} ({{ $cr['route'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- EXTERNAL URL -->
                            <div class="form-group mb-3" id="externalUrlActionGroup" style="display: none;">
                                <label class="font-weight-bold text-dark">EXTERNAL WEBSITE URL <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                    </div>
                                    <input type="url" name="external_url" class="form-control" placeholder="https://example.com/special-deal" value="{{ old('external_url', ($slider->action_type === 'external_url' ? $slider->action_value : '')) }}">
                                </div>
                                <small class="text-muted">Tapping the slider will open this URL in the user's mobile browser.</small>
                            </div>
                        </div>
                    </div>

                    <!-- VIDEO BEHAVIOR & STATUS -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white border-bottom py-3">
                            <h5 class="card-title font-weight-bold mb-0 text-dark">
                                <i class="fas fa-sliders-h text-info mr-2"></i> Playback & Visibility
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="videoPlaybackSettings" style="display: {{ old('media_type', $slider->media_type) === 'video' ? 'block' : 'none' }};">
                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" class="custom-control-input" id="auto_play_video" name="auto_play_video" value="1" {{ old('auto_play_video', $slider->auto_play_video) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="auto_play_video">
                                        Autoplay Video Muted
                                    </label>
                                    <small class="d-block text-muted">Automatically starts video playing muted when visible.</small>
                                </div>

                                <div class="custom-control custom-switch mb-3">
                                    <input type="checkbox" class="custom-control-input" id="loop_video" name="loop_video" value="1" {{ old('loop_video', $slider->loop_video) ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold" for="loop_video">
                                        Loop Video Continuously
                                    </label>
                                    <small class="d-block text-muted">Repeats video smoothly without stopping.</small>
                                </div>
                                <hr>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $slider->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-success" for="is_active">
                                    Slider Active in Mobile App
                                </label>
                                <small class="d-block text-muted">Turn off to temporarily hide this banner from users.</small>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-warning btn-block font-weight-bold py-2 shadow-sm">
                                    <i class="fas fa-save mr-1"></i> Update Slider Banner
                                </button>
                                <a href="{{ route('admin.sliders.index') }}" class="btn btn-light btn-block font-weight-bold py-2 text-muted">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function handleMediaTypeChange(type) {
        const videoThumbnailGroup = document.getElementById('videoThumbnailGroup');
        const videoPlaybackSettings = document.getElementById('videoPlaybackSettings');
        const mediaFileLabel = document.getElementById('mediaFileLabel');
        const mediaHelpText = document.getElementById('mediaHelpText');
        const mediaInput = document.getElementById('media_file');

        if (type === 'video') {
            videoThumbnailGroup.style.display = 'block';
            videoPlaybackSettings.style.display = 'block';
            mediaFileLabel.innerHTML = 'Upload Video File <span class="text-danger">*</span>';
            mediaHelpText.innerText = 'Supported formats: MP4, WEBM, MOV. Max 50MB.';
            mediaInput.setAttribute('accept', 'video/mp4,video/webm,video/quicktime');
        } else {
            videoThumbnailGroup.style.display = 'none';
            videoPlaybackSettings.style.display = 'none';
            mediaFileLabel.innerHTML = 'Upload Image Banner <span class="text-danger">*</span>';
            mediaHelpText.innerText = 'Supported formats: PNG, JPG, WEBP, GIF. Max 50MB.';
            mediaInput.setAttribute('accept', 'image/*');
        }
    }

    function handleActionTypeChange(action) {
        document.getElementById('campaignActionGroup').style.display = (action === 'campaign') ? 'block' : 'none';
        document.getElementById('dealActionGroup').style.display = (action === 'deal') ? 'block' : 'none';
        document.getElementById('appRouteActionGroup').style.display = (action === 'app_route') ? 'block' : 'none';
        document.getElementById('externalUrlActionGroup').style.display = (action === 'external_url') ? 'block' : 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        handleActionTypeChange("{{ old('action_type', $slider->action_type) }}");

        $('#media_file').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $('#media_file_name').text(fileName || 'Choose replacement file...');
        });

        $('#thumbnail_file').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $('#thumbnail_file_name').text(fileName || 'Choose replacement thumbnail...');
        });
    });
</script>
@endpush
@endsection
