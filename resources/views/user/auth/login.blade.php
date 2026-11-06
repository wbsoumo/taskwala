<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partner Login | {{ config('platform.name', 'Taskwala') }}</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <!-- Theme initialization before render to avoid flash of wrong mode -->
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
            --accent: #10b981;
            --bg-body: #090e1a;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: rgba(20, 27, 45, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(11, 16, 29, 0.7);
            --input-border: rgba(255, 255, 255, 0.09);
            --input-text: #f8fafc;
            --input-prefix-bg: rgba(255, 255, 255, 0.05);
            --tab-switcher-bg: rgba(11, 16, 29, 0.7);
            --tab-btn-text: #94a3b8;
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
            --input-prefix-bg: #e2e8f0;
            --tab-switcher-bg: #f1f5f9;
            --tab-btn-text: #64748b;
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
                radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.05) 0%, transparent 50%),
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

        /* Ambient floating orbs */
        .ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
            opacity: var(--orb-opacity);
            animation: floatOrb 12s ease-in-out infinite alternate;
            transition: opacity 0.3s ease;
        }
        .orb-1 { width: 320px; height: 320px; background: rgba(59, 130, 246, 0.25); top: -80px; left: -80px; }
        .orb-2 { width: 300px; height: 300px; background: rgba(139, 92, 246, 0.2); bottom: -60px; right: -60px; animation-duration: 16s; }

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
            max-width: 440px;
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
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
        }

        .brand-logo-wrap {
            position: relative;
            display: inline-block;
            margin-bottom: 12px;
        }
        .brand-logo-wrap img {
            width: 76px;
            height: 76px;
            border-radius: 22px;
            box-shadow: 0 12px 28px -6px rgba(59, 130, 246, 0.5);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .brand-logo-wrap:hover img {
            transform: scale(1.06) rotate(2deg);
        }

        /* Segmented Login Switcher */
        .tab-switcher {
            background: var(--tab-switcher-bg);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 4px;
            display: flex;
            margin-bottom: 22px;
            position: relative;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        .tab-btn {
            flex: 1;
            padding: 9px 12px;
            border: none;
            background: transparent;
            color: var(--tab-btn-text);
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 11px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .tab-btn.active {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .form-control-custom {
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 14px;
            color: var(--input-text) !important;
            padding: 13px 16px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            height: 50px;
        }
        .form-control-custom:focus {
            background: var(--card-bg);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            outline: none;
        }
        .form-control-custom::placeholder {
            color: #94a3b8;
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

        .input-prefix-badge {
            background: var(--input-prefix-bg);
            border: 1.5px solid var(--input-border);
            border-right: none;
            border-radius: 14px 0 0 14px;
            color: var(--text-muted);
            font-weight: 700;
            padding: 0 14px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .has-prefix .form-control-custom {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
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
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
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
        .btn-submit:active {
            transform: translateY(0);
        }
        .btn-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        .custom-control-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            cursor: pointer;
            user-select: none;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #3b82f6;
            border-color: #3b82f6;
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

        /* Micro shake animation on error */
        .shake-box {
            animation: shake 0.4s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
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
    <div class="card card-auth p-4 p-md-5 {{ $errors->any() ? 'shake-box' : '' }}">
        
        <!-- Logo & Header -->
        <div class="text-center mb-4">
            <div class="brand-logo-wrap">
                <img src="{{ asset('app-logo.png') }}" alt="Taskwala" onerror="this.onerror=null; this.src='{{ asset('favicon.png') }}';">
            </div>
            <h3 class="font-weight-extrabold mb-1" style="font-weight: 800; letter-spacing: -0.5px;">Taskwala Partner</h3>
            <p class="text-muted small mb-0 font-weight-medium">Promote • Earn Commission • Grow</p>
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

        <!-- Interactive Login Mode Tabs -->
        <div class="tab-switcher" id="loginTypeTabs">
            <button type="button" class="tab-btn active" id="tabEmail" onclick="setLoginType('email')">
                <i class="fas fa-envelope"></i> Email Address
            </button>
            <button type="button" class="tab-btn" id="tabMobile" onclick="setLoginType('mobile')">
                <i class="fas fa-mobile-alt"></i> Mobile Number
            </button>
        </div>

        <form action="{{ route('user.login') }}" method="POST" id="loginForm">
            @csrf

            <!-- Login Identifier Input -->
            <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold mb-1" id="loginLabel" style="letter-spacing: 0.5px; font-size: 0.72rem;">EMAIL ADDRESS</label>
                
                <div class="d-flex position-relative" id="inputWrapper">
                    <div class="input-prefix-badge d-none" id="mobilePrefix">
                        <span class="mr-1">🇮🇳</span> +91
                    </div>
                    <input 
                        type="email" 
                        name="email" 
                        id="loginInput"
                        class="form-control form-control-custom w-100" 
                        placeholder="e.g. partner@example.com" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="username"
                        autofocus>
                </div>
            </div>

            <!-- Password Input -->
            <div class="form-group mb-3 position-relative">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">PASSWORD</label>
                <div class="position-relative">
                    <input 
                        type="password" 
                        name="password" 
                        id="passwordInput"
                        class="form-control form-control-custom w-100 pr-5" 
                        placeholder="Enter your password" 
                        required 
                        autocomplete="current-password">
                    <span class="input-group-addon-icon" id="togglePasswordBtn" title="Toggle password visibility">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
                <div class="caps-warn" id="capsWarning">
                    <i class="fas fa-arrow-up mr-1"></i> Caps Lock is ON
                </div>
            </div>

            <!-- Options Row -->
            <div class="form-group d-flex justify-content-between align-items-center mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="remember">Remember me</label>
                </div>
                <a href="{{ route('user.password.request') }}" class="small font-weight-bold text-primary" style="text-decoration: none;">
                    Forgot Password?
                </a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-submit" id="submitBtn">
                <span>Sign In to Dashboard</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <!-- Footer / Register Link -->
        <div class="text-center mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
            <span class="text-muted small">Don't have a partner account?</span>
            <a href="{{ route('user.register') }}" class="text-primary font-weight-bold ml-1 small" style="text-decoration: none;">
                Register Now <i class="fas fa-angle-right ml-1"></i>
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

    // Initialize Theme UI based on current active theme
    updateThemeUI(document.documentElement.getAttribute('data-theme') || 'dark');

    // Tab Switching Logic (Email vs Mobile)
    function setLoginType(type) {
        const tabEmail = document.getElementById('tabEmail');
        const tabMobile = document.getElementById('tabMobile');
        const loginLabel = document.getElementById('loginLabel');
        const loginInput = document.getElementById('loginInput');
        const mobilePrefix = document.getElementById('mobilePrefix');
        const inputWrapper = document.getElementById('inputWrapper');

        if (type === 'mobile') {
            tabMobile.classList.add('active');
            tabEmail.classList.remove('active');
            loginLabel.innerText = '10-DIGIT MOBILE NUMBER';
            loginInput.type = 'tel';
            loginInput.name = 'email'; // Submitted to same auth field
            loginInput.placeholder = '9876543210';
            loginInput.maxLength = 10;
            mobilePrefix.classList.remove('d-none');
            inputWrapper.classList.add('has-prefix');
        } else {
            tabEmail.classList.add('active');
            tabMobile.classList.remove('active');
            loginLabel.innerText = 'EMAIL ADDRESS';
            loginInput.type = 'email';
            loginInput.name = 'email';
            loginInput.placeholder = 'e.g. partner@example.com';
            loginInput.removeAttribute('maxLength');
            mobilePrefix.classList.add('d-none');
            inputWrapper.classList.remove('has-prefix');
        }
        loginInput.focus();
    }

    // Password Visibility Toggle
    const togglePasswordBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePasswordBtn.addEventListener('click', function() {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    });

    // Caps Lock Detector
    passwordInput.addEventListener('keyup', function(e) {
        const capsWarning = document.getElementById('capsWarning');
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            capsWarning.style.display = 'block';
        } else {
            capsWarning.style.display = 'none';
        }
    });

    // Submit Loading Feedback
    const loginForm = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    loginForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Verifying credentials...</span>';
    });
</script>

</body>
</html>
