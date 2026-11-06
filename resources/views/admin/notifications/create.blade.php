@extends('layouts.admin')

@section('title', 'Send Push Notification')

@section('content')
<div class="row">
    <!-- Form Column -->
    <div class="col-lg-7 col-12">
        <div class="card card-outline card-primary elevation-2">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-paper-plane text-primary mr-2"></i>Compose Notification
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Sent List
                    </a>
                </div>
            </div>

            <form action="{{ route('admin.notifications.store') }}" method="POST" id="notificationForm">
                @csrf
                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 pl-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Audience Target -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark mb-2">TARGET AUDIENCE</label>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="custom-control custom-radio p-3 border rounded mb-2 bg-light" style="cursor: pointer;" onclick="document.getElementById('targetAll').click();">
                                    <input type="radio" id="targetAll" name="target_type" value="all" class="custom-control-input" {{ old('target_type', $selectedUserId ? 'selected_users' : 'all') === 'all' ? 'checked' : '' }} onchange="toggleUserSelect()">
                                    <label class="custom-control-label font-weight-bold text-primary" for="targetAll" style="cursor: pointer;">
                                        <i class="fas fa-globe mr-1"></i> All Registered Users
                                    </label>
                                    <p class="small text-muted mb-0 pl-4">Broadcast push alert to every registered affiliate partner.</p>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="custom-control custom-radio p-3 border rounded mb-2 bg-light" style="cursor: pointer;" onclick="document.getElementById('targetSelected').click();">
                                    <input type="radio" id="targetSelected" name="target_type" value="selected_users" class="custom-control-input" {{ old('target_type', $selectedUserId ? 'selected_users' : 'all') === 'selected_users' ? 'checked' : '' }} onchange="toggleUserSelect()">
                                    <label class="custom-control-label font-weight-bold text-secondary" for="targetSelected" style="cursor: pointer;">
                                        <i class="fas fa-user-check mr-1"></i> Target Specific Users
                                    </label>
                                    <p class="small text-muted mb-0 pl-4">Choose one or multiple specific partner accounts.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Selector (conditional) -->
                    <div class="form-group mb-4 {{ old('target_type', $selectedUserId ? 'selected_users' : 'all') === 'selected_users' ? '' : 'd-none' }}" id="userSelectWrapper">
                        <label class="font-weight-bold text-dark mb-1">SELECT USERS</label>
                        <select name="user_ids[]" id="userSelect" class="form-control select2" multiple="multiple" style="width: 100%; min-height: 100px;">
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ (is_array(old('user_ids')) && in_array($user->id, old('user_ids'))) || $selectedUserId == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }} - {{ $user->mobile_number }}) {{ $user->fcm_token ? '✓ Connected App' : '⚠️ No App Token' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Command/Ctrl to select multiple partners.</small>
                    </div>

                    <!-- Title -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">NOTIFICATION TITLE <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            name="title" 
                            id="inputTitle"
                            class="form-control form-control-lg font-weight-bold" 
                            placeholder="e.g. Special Commission Boost on HDFC Bank!" 
                            value="{{ old('title') }}" 
                            maxlength="150" 
                            required 
                            oninput="updatePreview()">
                        <small class="form-text text-muted d-flex justify-content-between">
                            <span>Keep it concise and attention-grabbing.</span>
                            <span id="titleCount">0 / 150</span>
                        </small>
                    </div>

                    <!-- Message Body -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">NOTIFICATION MESSAGE <span class="text-danger">*</span></label>
                        <textarea 
                            name="body" 
                            id="inputBody"
                            rows="3" 
                            class="form-control" 
                            placeholder="e.g. Earn flat ₹850 payout on every approved savings account conversion today. Check offers now!" 
                            maxlength="1000" 
                            required 
                            oninput="updatePreview()">{{ old('body') }}</textarea>
                        <small class="form-text text-muted d-flex justify-content-between">
                            <span>Clear value proposition encouraging user action.</span>
                            <span id="bodyCount">0 / 1000</span>
                        </small>
                    </div>

                    <!-- Deeplink URL / Route -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark mb-1">DEEPLINK / SCREEN DESTINATION (OPTIONAL)</label>
                        <input 
                            type="text" 
                            name="deeplink" 
                            id="inputDeeplink"
                            class="form-control font-monospace" 
                            placeholder="e.g. taskwala://campaigns or /wallet" 
                            value="{{ old('deeplink') }}" 
                            oninput="updatePreview()">
                        
                        <!-- Quick Pickers -->
                        <div class="mt-2">
                            <span class="small font-weight-bold text-muted mr-1">Quick Presets:</span>
                            <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setDeeplink('taskwala://campaigns')">Campaigns</button>
                            <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setDeeplink('taskwala://wallet')">Wallet & Payout</button>
                            <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setDeeplink('taskwala://referral')">Referral Team</button>
                            <button type="button" class="btn btn-xs btn-outline-primary mr-1 mb-1" onclick="setDeeplink('taskwala://links')">My Links</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary mr-1 mb-1" onclick="setDeeplink('')">Clear</button>
                        </div>
                    </div>

                    <!-- Image URL -->
                    <div class="form-group mb-4">
                        <label class="font-weight-bold text-dark mb-1">BANNER IMAGE URL (OPTIONAL)</label>
                        <input 
                            type="url" 
                            name="image_url" 
                            id="inputImageUrl"
                            class="form-control" 
                            placeholder="https://example.com/banner.png" 
                            value="{{ old('image_url') }}" 
                            oninput="updatePreview()">
                        <small class="form-text text-muted">Supports wide 2:1 PNG or JPEG image.</small>
                    </div>

                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <span class="small text-muted">
                        <i class="fas fa-shield-alt text-primary mr-1"></i> Dispatches via official Firebase HTTP v1 protocol.
                    </span>
                    <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" id="btnSubmit">
                        <i class="fas fa-paper-plane mr-2"></i> Send Notification Now
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Live Preview Column -->
    <div class="col-lg-5 col-12">
        <div class="card card-outline card-secondary elevation-2 sticky-top" style="top: 20px;">
            <div class="card-header">
                <h3 class="card-title font-weight-bold text-secondary">
                    <i class="fas fa-mobile-alt mr-2"></i>Android Push Notification Preview
                </h3>
            </div>
            <div class="card-body bg-dark p-4 rounded-bottom">
                
                <!-- Android Status Bar Mockup -->
                <div class="d-flex justify-content-between align-items-center text-muted small mb-3 px-2" style="font-size: 11px;">
                    <span><i class="fas fa-bell mr-1"></i> Taskwala Notification</span>
                    <span>Just now • 9:41 AM</span>
                </div>

                <!-- Notification Box -->
                <div class="bg-white text-dark rounded-lg p-3 shadow-lg" style="border-radius: 18px !important;">
                    <div class="d-flex align-items-center mb-2">
                        <!-- Notification App Icon -->
                        <div class="mr-2 d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 28px; height: 28px;">
                            <img src="{{ asset('android_notification_icon.png') }}" alt="icon" style="width: 18px; height: 18px; filter: brightness(0) invert(1);" onerror="this.onerror=null; this.src='{{ asset('favicon-32x32.png') }}';">
                        </div>
                        <span class="font-weight-bold small text-dark mr-2">Taskwala</span>
                        <span class="text-muted" style="font-size: 10px;">• now</span>
                        <i class="fas fa-chevron-down ml-auto text-muted small"></i>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1 pr-2">
                            <h6 class="font-weight-bold mb-1 text-dark" id="previewTitle" style="font-size: 13px;">Special Commission Boost on HDFC Bank!</h6>
                            <p class="small text-muted mb-0" id="previewBody" style="font-size: 12px; line-height: 1.35;">Earn flat ₹850 payout on every approved savings account conversion today. Check offers now!</p>
                        </div>
                        <div id="previewImageWrap" class="d-none">
                            <img src="" id="previewImage" alt="banner" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                        </div>
                    </div>

                    <div id="previewDeeplinkBadge" class="mt-2 pt-2 border-top d-flex align-items-center text-primary small" style="font-size: 11px;">
                        <i class="fas fa-arrow-circle-right mr-1"></i>
                        <span id="previewDeeplinkText">Opens in App</span>
                    </div>
                </div>

                <div class="text-center mt-3 text-muted small">
                    <i class="fas fa-info-circle mr-1"></i> Notification will display this icon and trigger the configured in-app deeplink upon click.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleUserSelect() {
        const targetSelected = document.getElementById('targetSelected');
        const userSelectWrapper = document.getElementById('userSelectWrapper');
        if (targetSelected.checked) {
            userSelectWrapper.classList.remove('d-none');
        } else {
            userSelectWrapper.classList.add('d-none');
        }
    }

    function setDeeplink(val) {
        const inputDeeplink = document.getElementById('inputDeeplink');
        inputDeeplink.value = val;
        updatePreview();
    }

    function updatePreview() {
        const inputTitle = document.getElementById('inputTitle');
        const inputBody = document.getElementById('inputBody');
        const inputDeeplink = document.getElementById('inputDeeplink');
        const inputImageUrl = document.getElementById('inputImageUrl');

        const previewTitle = document.getElementById('previewTitle');
        const previewBody = document.getElementById('previewBody');
        const previewDeeplinkBadge = document.getElementById('previewDeeplinkBadge');
        const previewDeeplinkText = document.getElementById('previewDeeplinkText');
        const previewImageWrap = document.getElementById('previewImageWrap');
        const previewImage = document.getElementById('previewImage');

        previewTitle.innerText = inputTitle.value.trim() || 'Notification Title';
        previewBody.innerText = inputBody.value.trim() || 'Notification message will appear here...';

        document.getElementById('titleCount').innerText = inputTitle.value.length + ' / 150';
        document.getElementById('bodyCount').innerText = inputBody.value.length + ' / 1000';

        if (inputDeeplink.value.trim()) {
            previewDeeplinkBadge.classList.remove('d-none');
            previewDeeplinkText.innerText = 'Tap to open: ' + inputDeeplink.value.trim();
        } else {
            previewDeeplinkBadge.classList.add('d-none');
        }

        if (inputImageUrl.value.trim()) {
            previewImage.src = inputImageUrl.value.trim();
            previewImageWrap.classList.remove('d-none');
        } else {
            previewImageWrap.classList.add('d-none');
        }
    }

    const notificationForm = document.getElementById('notificationForm');
    const btnSubmit = document.getElementById('btnSubmit');

    notificationForm.addEventListener('submit', function() {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Sending Notifications...';
    });

    updatePreview();
</script>
@endsection
