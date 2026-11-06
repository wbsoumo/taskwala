<!DOCTYPE html>
<html lang="en" data-theme="dark">
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

    <!-- Theme initialization before render -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('taskwala_auth_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg-body: #090e1a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: rgba(20, 27, 45, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(11, 16, 29, 0.7);
            --input-border: rgba(255, 255, 255, 0.09);
            --input-text: #f8fafc;
            --card-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(59, 130, 246, 0.1);
            --theme-btn-bg: rgba(20, 27, 45, 0.8);
            --theme-btn-border: rgba(255, 255, 255, 0.15);
            --theme-btn-text: #e2e8f0;
            --theme-btn-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
            --orb-opacity: 0.5;
            --caps-warn-color: #fbbf24;
        }

        [data-theme="light"] {
            --bg-body: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --card-bg: rgba(255, 255, 255, 0.95);
            --border-color: rgba(226, 232, 240, 0.95);
            --input-bg: #f8fafc;
            --input-border: #cbd5e1;
            --input-text: #0f172a;
            --card-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.1), 0 0 30px rgba(59, 130, 246, 0.08);
            --theme-btn-bg: rgba(255, 255, 255, 0.95);
            --theme-btn-border: rgba(203, 213, 225, 0.9);
            --theme-btn-text: #0f172a;
            --theme-btn-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            --orb-opacity: 0.22;
            --caps-warn-color: #d97706;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: 
                radial-gradient(circle at 10% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
            overflow-x: hidden;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            opacity: var(--orb-opacity);
            animation: floatOrb 14s ease-in-out infinite alternate;
            transition: opacity 0.3s ease;
        }
        .orb-1 { width: 320px; height: 320px; background: rgba(59, 130, 246, 0.22); top: -80px; left: -80px; }
        .orb-2 { width: 300px; height: 300px; background: rgba(139, 92, 246, 0.18); bottom: -60px; right: -60px; animation-duration: 18s; }

        @keyframes floatOrb {
            0% { transform: translateY(0) scale(1); }
            100% { transform: translateY(30px) scale(1.08); }
        }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 100;
            background: var(--theme-btn-bg);
            border: 1.5px solid var(--theme-btn-border);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            color: var(--theme-btn-text);
            border-radius: 9999px;
            padding: 8px 18px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--theme-btn-shadow);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }
        .theme-toggle-btn:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
            color: var(--primary);
        }
        .theme-toggle-btn i {
            font-size: 0.95rem;
            transition: transform 0.3s ease;
        }
        .theme-toggle-btn:hover i {
            transform: rotate(25deg);
        }
        @media (max-width: 576px) {
            .theme-toggle-btn {
                top: 14px;
                right: 14px;
                padding: 6px 14px;
                font-size: 0.8rem;
            }
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
            box-shadow: var(--card-shadow);
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
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
            border: 1.5px solid var(--input-border);
            border-radius: 14px;
            color: var(--input-text) !important;
            padding: 13px 16px;
            font-size: 0.95rem;
            height: 50px;
            transition: all 0.2s ease;
        }
        .form-control-custom:focus {
            background: var(--card-bg);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        .otp-input {
            letter-spacing: 10px;
            font-size: 1.35rem;
            font-weight: 800;
            text-align: center;
            color: #3b82f6 !important;
        }

        .input-group-addon-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            z-index: 5;
            transition: color 0.2s ease;
            padding: 4px;
        }
        .input-group-addon-icon:hover {
            color: #3b82f6;
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
            color: #ef4444;
        }
        .alert-custom-success {
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .caps-warn {
            display: none;
            color: var(--caps-warn-color);
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 6px;
        }
    </style>
</head>
<body>

<!-- Light / Dark Mode Toggle Button -->
<button type="button" class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" aria-label="Toggle theme">
    <i class="fas fa-sun" id="themeToggleIcon"></i>
    <span id="themeToggleText">Light Mode</span>
</button>

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

        <div class="text-center mt-4 pt-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid var(--border-color);">
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
    // Theme Toggle Functionality
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('taskwala_auth_theme', newTheme);
        updateThemeUI(newTheme);
    }

    function updateThemeUI(theme) {
        const icon = document.getElementById('themeToggleIcon');
        const text = document.getElementById('themeToggleText');
        if (theme === 'light') {
            icon.className = 'fas fa-moon';
            text.innerText = 'Dark Mode';
        } else {
            icon.className = 'fas fa-sun';
            text.innerText = 'Light Mode';
        }
    }

    updateThemeUI(document.documentElement.getAttribute('data-theme') || 'dark');

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
