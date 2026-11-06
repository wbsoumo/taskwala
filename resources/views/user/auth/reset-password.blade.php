<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password | {{ config('platform.name', 'Taskwala') }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-dark: #090e1a;
            --card-bg: rgba(20, 27, 45, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(11, 16, 29, 0.7);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: 
                radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                var(--bg-dark);
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
            overflow-x: hidden;
        }

        .ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.5;
            animation: floatOrb 14s ease-in-out infinite alternate;
        }
        .orb-1 { width: 320px; height: 320px; background: rgba(59, 130, 246, 0.22); top: -80px; left: -80px; }
        .orb-2 { width: 300px; height: 300px; background: rgba(139, 92, 246, 0.18); bottom: -60px; right: -60px; animation-duration: 18s; }

        @keyframes floatOrb {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(30px) scale(1.08); }
        }

        .auth-container {
            max-width: 460px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .card-auth {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(59, 130, 246, 0.1);
        }

        .icon-shield-wrap {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: rgba(59, 130, 246, 0.12);
            border: 1.5px solid rgba(59, 130, 246, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 28px;
            box-shadow: 0 10px 25px -4px rgba(59, 130, 246, 0.4);
            margin-bottom: 12px;
        }

        .form-control-custom {
            background: var(--input-bg);
            border: 1.5px solid rgba(255, 255, 255, 0.09);
            border-radius: 14px;
            color: #f8fafc !important;
            padding: 13px 16px;
            font-size: 0.95rem;
            height: 50px;
            transition: all 0.2s ease;
        }
        .form-control-custom:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .otp-input {
            letter-spacing: 10px;
            font-size: 1.35rem;
            font-weight: 800;
            text-align: center;
            color: #93c5fd !important;
        }

        .input-group-addon-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            z-index: 5;
            transition: color 0.2s ease;
            padding: 4px;
        }
        .input-group-addon-icon:hover {
            color: #93c5fd;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            border: none;
            border-radius: 14px;
            height: 52px;
            width: 100%;
            box-shadow: 0 10px 22px -4px rgba(37, 99, 235, 0.5);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -4px rgba(37, 99, 235, 0.7);
        }
        .btn-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        .alert-custom {
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-custom-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-custom-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .caps-warn {
            display: none;
            color: #fbbf24;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 6px;
        }
    </style>
</head>
<body>

<div class="ambient-orb orb-1"></div>
<div class="ambient-orb orb-2"></div>

<div class="auth-container">
    <div class="card card-auth p-4 p-md-5">
        
        <div class="text-center mb-4">
            <div class="icon-shield-wrap">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3 class="font-weight-extrabold mb-1" style="font-weight: 800; letter-spacing: -0.5px;">Set New Password</h3>
            <p class="text-muted small mb-0 font-weight-medium">Enter the 6-digit verification code and your new credentials</p>
        </div>

        @if(session('status'))
            <div class="alert-custom alert-custom-success">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if($errors->any())
            <div class="alert-custom alert-custom-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form action="{{ route('user.password.update') }}" method="POST" id="resetPasswordForm">
            @csrf

            <!-- Account Email / Mobile -->
            <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">ACCOUNT EMAIL OR MOBILE</label>
                <input 
                    type="text" 
                    name="login" 
                    value="{{ old('login', $login) }}" 
                    class="form-control form-control-custom w-100" 
                    required>
            </div>

            <!-- Verification Code -->
            <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">6-DIGIT VERIFICATION CODE</label>
                <input 
                    type="text" 
                    name="otp" 
                    value="{{ old('otp', '') }}" 
                    maxlength="6" 
                    class="form-control form-control-custom otp-input w-100" 
                    placeholder="••••••" 
                    inputmode="numeric"
                    pattern="[0-9]*"
                    required 
                    autofocus>
            </div>

            <!-- New Password -->
            <div class="form-group mb-3 position-relative">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">NEW PASSWORD (MIN 8 CHARS)</label>
                <div class="position-relative">
                    <input 
                        type="password" 
                        name="password" 
                        id="newPasswordInput"
                        class="form-control form-control-custom w-100 pr-5" 
                        placeholder="••••••••" 
                        required 
                        autocomplete="new-password">
                    <span class="input-group-addon-icon" id="toggleNewPasswordBtn" title="Toggle password visibility">
                        <i class="fas fa-eye" id="newEyeIcon"></i>
                    </span>
                </div>
                <div class="caps-warn" id="capsWarning">
                    <i class="fas fa-arrow-up mr-1"></i> Caps Lock is ON
                </div>
            </div>

            <!-- Confirm New Password -->
            <div class="form-group mb-4 position-relative">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">CONFIRM NEW PASSWORD</label>
                <div class="position-relative">
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        id="confirmPasswordInput"
                        class="form-control form-control-custom w-100 pr-5" 
                        placeholder="••••••••" 
                        required 
                        autocomplete="new-password">
                    <span class="input-group-addon-icon" id="toggleConfirmPasswordBtn" title="Toggle password visibility">
                        <i class="fas fa-eye" id="confirmEyeIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-submit" id="submitBtn">
                <span>Update Password</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <a href="{{ route('user.password.request') }}" class="text-muted small font-weight-bold" style="text-decoration: none;">
                <i class="fas fa-redo-alt mr-1"></i> Request New Code
            </a>
            <a href="{{ route('user.login') }}" class="text-primary font-weight-bold small" style="text-decoration: none;">
                Back to Login <i class="fas fa-angle-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<script>
    // Toggle New Password
    const toggleNewPasswordBtn = document.getElementById('toggleNewPasswordBtn');
    const newPasswordInput = document.getElementById('newPasswordInput');
    const newEyeIcon = document.getElementById('newEyeIcon');

    toggleNewPasswordBtn.addEventListener('click', function() {
        if (newPasswordInput.type === 'password') {
            newPasswordInput.type = 'text';
            newEyeIcon.classList.remove('fa-eye');
            newEyeIcon.classList.add('fa-eye-slash');
        } else {
            newPasswordInput.type = 'password';
            newEyeIcon.classList.remove('fa-eye-slash');
            newEyeIcon.classList.add('fa-eye');
        }
    });

    // Toggle Confirm Password
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPasswordBtn');
    const confirmPasswordInput = document.getElementById('confirmPasswordInput');
    const confirmEyeIcon = document.getElementById('confirmEyeIcon');

    toggleConfirmPasswordBtn.addEventListener('click', function() {
        if (confirmPasswordInput.type === 'password') {
            confirmPasswordInput.type = 'text';
            confirmEyeIcon.classList.remove('fa-eye');
            confirmEyeIcon.classList.add('fa-eye-slash');
        } else {
            confirmPasswordInput.type = 'password';
            confirmEyeIcon.classList.remove('fa-eye-slash');
            confirmEyeIcon.classList.add('fa-eye');
        }
    });

    // Caps Lock Detector
    newPasswordInput.addEventListener('keyup', function(e) {
        const capsWarning = document.getElementById('capsWarning');
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            capsWarning.style.display = 'block';
        } else {
            capsWarning.style.display = 'none';
        }
    });

    // Submit Loading Feedback
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const submitBtn = document.getElementById('submitBtn');

    resetPasswordForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Updating password...</span>';
    });
</script>

</body>
</html>
