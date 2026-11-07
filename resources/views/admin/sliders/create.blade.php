@extends('layouts.admin')

@section('title', 'Add App Slider')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">
                    <i class="fas fa-plus-circle text-primary mr-2"></i> Create App Slider / Banner
                </h1>
                <small class="text-muted">Configure image or video banners and configure click destination actions.</small>
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
        <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

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
                            <!-- MEDIA TYPE SELECTION -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-dark d-block mb-2">MEDIA TYPE <span class="text-danger">*</span></label>
                                <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                                    <label class="btn btn-outline-info active font-weight-bold py-2">
                                        <input type="radio" name="media_type" id="type_image" value="image" checked onchange="handleMediaTypeChange('image')">
                                        <i class="fas fa-image mr-1"></i> Image Banner (PNG, JPG, WEBP)
                                    </label>
                                    <label class="btn btn-outline-danger font-weight-bold py-2">
                                        <input type="radio" name="media_type" id="type_video" value="video" onchange="handleMediaTypeChange('video')">
                                        <i class="fas fa-video mr-1"></i> Video Banner (MP4, WEBM)
                                    </label>
                                </div>
                            </div>

                            <!-- FILE UPLOAD -->
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark" id="mediaFileLabel">
                                    Upload Image Banner <span class="text-danger">*</span>
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="media_file" class="custom-file-input @error('media_file') is-invalid @enderror" id="media_file" accept="image/*,video/mp4,video/webm">
                                    <label class="custom-file-label" for="media_file" id="media_file_name">Choose file (Recommended: 16:9 or 2:1 ratio)...</label>
                                </div>
                                <small class="form-text text-muted" id="mediaHelpText">
                                    Supported formats: PNG, JPG, WEBP, GIF. Max 50MB.
                                </small>
                                @error('media_file')
                                    <span class="text-danger small font-weight-bold">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="text-center my-2 text-muted small font-weight-bold">
                                — OR PROVIDE DIRECT MEDIA URL —
                            </div>

                            <div class="form-group mb-4">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                                    </div>
                                    <input type="url" name="media_url_input" class="form-control" placeholder="https://example.com/banner.jpg or https://example.com/video.mp4" value="{{ old('media_url_input') }}">
                                </div>
                            </div>

                            <!-- VIDEO THUMBNAIL (POSTER) -->
                            <div class="form-group mb-4" id="videoThumbnailGroup" style="display: none;">
                                <label class="font-weight-bold text-dark">
                                    Video Thumbnail / Poster Image (Optional)
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="thumbnail_file" class="custom-file-input" id="thumbnail_file" accept="image/*">
                                    <label class="custom-file-label" for="thumbnail_file" id="thumbnail_file_name">Choose thumbnail image...</label>
                                </div>
                                <small class="form-text text-muted">Cover image displayed before/while the video is buffering.</small>
                            </div>

                            <hr>

                            <!-- TEXT OVERLAYS -->
                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">BANNER TITLE (OPTIONAL)</label>
                                <input type="text" name="title" class="form-control font-weight-bold" placeholder="e.g. Kotak811 Zero Balance Account" value="{{ old('title') }}">
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold text-dark">SUBTITLE / HIGHLIGHT TEXT (OPTIONAL)</label>
                                <textarea name="subtitle" rows="2" class="form-control" placeholder="e.g. Earn flat ₹300 on every successful account opening">{{ old('subtitle') }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold text-dark">BADGE TAG (OPTIONAL)</label>
                                        <input type="text" name="badge_text" class="form-control" placeholder="e.g. HOT DEAL, 50% OFF, WATCH" value="{{ old('badge_text') }}">
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold text-dark">DISPLAY ORDER</label>
                                        <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}" min="0">
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
                                    <option value="none" {{ old('action_type') == 'none' ? 'selected' : '' }}>None (Informational banner only)</option>
                                    <option value="campaign" {{ old('action_type') == 'campaign' ? 'selected' : '' }}>🎯 Open Specific Offer / Campaign</option>
                                    <option value="deal" {{ old('action_type') == 'deal' ? 'selected' : '' }}>⚡ Open Specific Deal</option>
                                    <option value="app_route" {{ old('action_type') == 'app_route' ? 'selected' : '' }}>📱 Open App Page / Screen</option>
                                    <option value="external_url" {{ old('action_type') == 'external_url' ? 'selected' : '' }}>🌐 Open External Website URL</option>
                                </select>
                            </div>

                            <!-- CAMPAIGN SELECTOR -->
                            <div class="form-group mb-3" id="campaignActionGroup" style="display: none;">
                                <label class="font-weight-bold text-dark">SELECT CAMPAIGN / OFFER <span class="text-danger">*</span></label>
                                <select name="campaign_id" class="custom-select select2">
                                    <option value="">-- Choose Campaign --</option>
                                    @foreach($campaigns as $camp)
                                        <option value="{{ $camp->id }}" {{ old('campaign_id') == $camp->id ? 'selected' : '' }}>
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
                                        <option value="{{ $dl->id }}" {{ old('deal_id') == $dl->id ? 'selected' : '' }}>
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
                                        <option value="{{ $cr['route'] }}" {{ old('app_route') == $cr['route'] ? 'selected' : '' }}>
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
                                    <input type="url" name="external_url" class="form-control" placeholder="https://example.com/promo" value="{{ old('external_url') }}">
                                </div>
                            </div>

                            <hr>

                            <!-- VIDEO SETTINGS (Only relevant for video) -->
                            <div id="videoSettingsBox" style="display: none;">
                                <h6 class="font-weight-bold text-dark mb-2"><i class="fas fa-cog mr-1"></i> Video Playback Options</h6>
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="auto_play_video" name="auto_play_video" value="1" {{ old('auto_play_video', '1') ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="auto_play_video">
                                        Auto-play Video in Slider
                                    </label>
                                </div>
                                <div class="custom-control custom-checkbox mb-3">
                                    <input type="checkbox" class="custom-control-input" id="loop_video" name="loop_video" value="1" {{ old('loop_video', '1') ? 'checked' : '' }}>
                                    <label class="custom-control-label font-weight-bold text-dark" for="loop_video">
                                        Loop Video Continuously
                                    </label>
                                </div>
                                <hr>
                            </div>

                            <!-- STATUS TOGGLE -->
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="custom-control-label font-weight-bold text-dark" for="is_active">
                                    Active (Visible in App Slider)
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block font-weight-bold py-2 shadow-sm">
                                <i class="fas fa-save mr-1"></i> Save & Publish Slider
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function handleMediaTypeChange(type) {
        const fileLabel = document.getElementById('mediaFileLabel');
        const helpText = document.getElementById('mediaHelpText');
        const thumbGroup = document.getElementById('videoThumbnailGroup');
        const videoSettings = document.getElementById('videoSettingsBox');

        if (type === 'video') {
            fileLabel.innerHTML = 'Upload Video Banner <span class="text-danger">*</span>';
            helpText.textContent = 'Supported formats: MP4, WEBM, MOV. Max 50MB.';
            thumbGroup.style.display = 'block';
            videoSettings.style.display = 'block';
        } else {
            fileLabel.innerHTML = 'Upload Image Banner <span class="text-danger">*</span>';
            helpText.textContent = 'Supported formats: PNG, JPG, WEBP, GIF. Max 50MB.';
            thumbGroup.style.display = 'none';
            videoSettings.style.display = 'none';
        }
    }

    function handleActionTypeChange(action) {
        document.getElementById('campaignActionGroup').style.display = action === 'campaign' ? 'block' : 'none';
        document.getElementById('dealActionGroup').style.display = action === 'deal' ? 'block' : 'none';
        document.getElementById('appRouteActionGroup').style.display = action === 'app_route' ? 'block' : 'none';
        document.getElementById('externalUrlActionGroup').style.display = action === 'external_url' ? 'block' : 'none';
    }

    // Update filename display on file selection
    document.getElementById('media_file').addEventListener('change', function(e) {
        const name = e.target.files[0] ? e.target.files[0].name : 'Choose file...';
        document.getElementById('media_file_name').textContent = name;
    });

    const thumbInput = document.getElementById('thumbnail_file');
    if (thumbInput) {
        thumbInput.addEventListener('change', function(e) {
            const name = e.target.files[0] ? e.target.files[0].name : 'Choose thumbnail image...';
            document.getElementById('thumbnail_file_name').textContent = name;
        });
    }

    // Trigger on load for old input state
    document.addEventListener('DOMContentLoaded', function() {
        const selectedType = document.querySelector('input[name="media_type"]:checked')?.value || 'image';
        handleMediaTypeChange(selectedType);

        const selectedAction = document.getElementById('action_type')?.value || 'none';
        handleActionTypeChange(selectedAction);
    });
</script>
@endpush
