<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Partner Registration | {{ config('platform.name', 'Taskwala') }}</title>
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
            --card-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(59, 130, 246, 0.1);
            --theme-btn-bg: rgba(20, 27, 45, 0.8);
            --theme-btn-border: rgba(255, 255, 255, 0.15);
            --theme-btn-text: #e2e8f0;
            --theme-btn-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
            --subcard-bg: rgba(11, 16, 29, 0.5);
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
            --card-shadow: 0 20px 45px -10px rgba(15, 23, 42, 0.1), 0 0 30px rgba(59, 130, 246, 0.08);
            --theme-btn-bg: rgba(255, 255, 255, 0.95);
            --theme-btn-border: rgba(203, 213, 225, 0.9);
            --theme-btn-text: #0f172a;
            --theme-btn-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            --subcard-bg: rgba(241, 245, 249, 0.8);
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
            padding: 35px 15px;
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
            animation: floatOrb 14s ease-in-out infinite alternate;
            transition: opacity 0.3s ease;
        }
        .orb-1 { width: 340px; height: 340px; background: rgba(59, 130, 246, 0.22); top: -80px; left: -80px; }
        .orb-2 { width: 320px; height: 320px; background: rgba(139, 92, 246, 0.18); bottom: -60px; right: -60px; animation-duration: 18s; }

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
            max-width: 540px;
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

        .form-control-custom {
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 14px;
            color: var(--input-text) !important;
            padding: 13px 16px;
            font-size: 0.92rem;
            transition: all 0.2s ease;
            height: 48px;
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
            padding: 0 12px;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .has-prefix .form-control-custom {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .section-tag {
            font-size: 0.75rem;
            font-weight: 800;
            color: #3b82f6;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
        }

        .payout-box {
            background: var(--subcard-bg);
            border: 1px dashed var(--border-color);
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 20px;
            transition: background 0.3s ease, border-color 0.3s ease;
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

        .alert-custom {
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 12px 14px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-custom-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }

        .caps-warn {
            display: none;
            color: var(--caps-warn-color);
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 6px;
        }

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
            <h3 class="font-weight-extrabold mb-1" style="font-weight: 800; letter-spacing: -0.5px;">Join Taskwala</h3>
            <p class="text-muted small mb-0 font-weight-medium">Start promoting offers, track conversions & earn commissions</p>
        </div>

        @if($errors->any())
            <div class="alert-custom alert-custom-danger">
                <i class="fas fa-exclamation-circle mt-1"></i>
                <div class="flex-grow-1">
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('user.register') }}" method="POST" id="registerForm">
            @csrf

            <!-- Full Name -->
            <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">FULL NAME</label>
                <div class="position-relative">
                    <input 
                        type="text" 
                        name="name" 
                        class="form-control form-control-custom w-100 pr-5" 
                        placeholder="e.g. Rahul Sharma" 
                        value="{{ old('name') }}" 
                        required 
                        autocomplete="name"
                        autofocus>
                    <span class="input-group-addon-icon" style="cursor: default;">
                        <i class="fas fa-user"></i>
                    </span>
                </div>
            </div>

            <!-- Email & Mobile Row -->
            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">EMAIL ADDRESS</label>
                    <div class="position-relative">
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control form-control-custom w-100 pr-5" 
                            placeholder="name@email.com" 
                            value="{{ old('email') }}" 
                            required 
                            autocomplete="email">
                        <span class="input-group-addon-icon" style="cursor: default;">
                            <i class="fas fa-envelope"></i>
                        </span>
                    </div>
                </div>

                <div class="col-md-6 form-group mb-3">
                    <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">MOBILE NUMBER</label>
                    <div class="d-flex position-relative has-prefix">
                        <div class="input-prefix-badge">
                            <span class="mr-1">🇮🇳</span> +91
                        </div>
                        <input 
                            type="tel" 
                            name="mobile_number" 
                            maxlength="15" 
                            class="form-control form-control-custom w-100" 
                            placeholder="9876543210" 
                            value="{{ old('mobile_number') }}" 
                            required 
                            autocomplete="tel">
                    </div>
                </div>
            </div>

            <!-- Password & Confirm Password Row -->
            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">PASSWORD (MIN 8)</label>
                    <div class="position-relative">
                        <input 
                            type="password" 
                            name="password" 
                            id="registerPassword"
                            class="form-control form-control-custom w-100 pr-5" 
                            placeholder="••••••••" 
                            minlength="8" 
                            required 
                            autocomplete="new-password">
                        <span class="input-group-addon-icon" id="toggleRegPasswordBtn" title="Toggle password visibility">
                            <i class="fas fa-eye" id="regEyeIcon"></i>
                        </span>
                    </div>
                    <div class="caps-warn" id="regCapsWarning">
                        <i class="fas fa-arrow-up mr-1"></i> Caps Lock is ON
                    </div>
                </div>

                <div class="col-md-6 form-group mb-3">
                    <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">CONFIRM PASSWORD</label>
                    <div class="position-relative">
                        <input 
                            type="password" 
                            name="password_confirmation" 
                            id="registerPasswordConfirm"
                            class="form-control form-control-custom w-100 pr-5" 
                            placeholder="••••••••" 
                            required 
                            autocomplete="new-password">
                        <span class="input-group-addon-icon" id="toggleRegConfirmBtn" title="Toggle password visibility">
                            <i class="fas fa-eye" id="regConfirmEyeIcon"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Optional Payout Details Cardlet -->
            <div class="payout-box">
                <div class="section-tag">
                    <i class="fas fa-wallet"></i> Payout Details (Optional)
                </div>
                <div class="row">
                    <div class="col-md-6 form-group mb-2 mb-md-0">
                        <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.68rem;">UPI ID</label>
                        <input 
                            type="text" 
                            name="upi_id" 
                            class="form-control form-control-custom w-100" 
                            placeholder="username@bank" 
                            value="{{ old('upi_id') }}">
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.68rem;">ACCOUNT HOLDER NAME</label>
                        <input 
                            type="text" 
                            name="upi_holder_name" 
                            class="form-control form-control-custom w-100" 
                            placeholder="e.g. Rahul Sharma" 
                            value="{{ old('upi_holder_name') }}">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-submit" id="submitBtn">
                <span>Create Partner Account</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <!-- Footer / Login Link -->
        <div class="text-center mt-4 pt-3" style="border-top: 1px solid var(--border-color);">
            <span class="text-muted small">Already have a partner account?</span>
            <a href="{{ route('user.login') }}" class="text-primary font-weight-bold ml-1 small" style="text-decoration: none;">
                Sign In to Dashboard <i class="fas fa-angle-right ml-1"></i>
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

    // Toggle Password Visibility
    const toggleRegPasswordBtn = document.getElementById('toggleRegPasswordBtn');
    const registerPassword = document.getElementById('registerPassword');
    const regEyeIcon = document.getElementById('regEyeIcon');

    toggleRegPasswordBtn.addEventListener('click', function() {
        if (registerPassword.type === 'password') {
            registerPassword.type = 'text';
            regEyeIcon.classList.remove('fa-eye');
            regEyeIcon.classList.add('fa-eye-slash');
        } else {
            registerPassword.type = 'password';
            regEyeIcon.classList.remove('fa-eye-slash');
            regEyeIcon.classList.add('fa-eye');
        }
    });

    // Toggle Confirm Password Visibility
    const toggleRegConfirmBtn = document.getElementById('toggleRegConfirmBtn');
    const registerPasswordConfirm = document.getElementById('registerPasswordConfirm');
    const regConfirmEyeIcon = document.getElementById('regConfirmEyeIcon');

    toggleRegConfirmBtn.addEventListener('click', function() {
        if (registerPasswordConfirm.type === 'password') {
            registerPasswordConfirm.type = 'text';
            regConfirmEyeIcon.classList.remove('fa-eye');
            regConfirmEyeIcon.classList.add('fa-eye-slash');
        } else {
            registerPasswordConfirm.type = 'password';
            regConfirmEyeIcon.classList.remove('fa-eye-slash');
            regConfirmEyeIcon.classList.add('fa-eye');
        }
    });

    // Caps Lock Detector
    registerPassword.addEventListener('keyup', function(e) {
        const regCapsWarning = document.getElementById('regCapsWarning');
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            regCapsWarning.style.display = 'block';
        } else {
            regCapsWarning.style.display = 'none';
        }
    });

    // Submit Loading Feedback
    const registerForm = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');

    registerForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Creating account...</span>';
    });
</script>

</body>
</html>
