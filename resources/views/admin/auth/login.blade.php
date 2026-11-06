<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Command Console | {{ config('platform.name', 'Taskwala') }}</title>
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
            --admin-primary: #3b82f6;
            --admin-indigo: #6366f1;
            --bg-cyber: #050811;
            --card-cyber: rgba(15, 23, 42, 0.88);
            --border-cyber: rgba(59, 130, 246, 0.2);
            --input-cyber: rgba(7, 11, 20, 0.85);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: 
                radial-gradient(circle at 15% 15%, rgba(59, 130, 246, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.16) 0%, transparent 45%),
                var(--bg-cyber);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle cyber grid background */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 32px 32px;
            pointer-events: none;
            z-index: 0;
        }

        .auth-container {
            max-width: 440px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .card-admin {
            background: var(--card-cyber);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border-cyber);
            border-radius: 28px;
            box-shadow: 0 25px 55px -10px rgba(0, 0, 0, 0.7), 0 0 45px rgba(59, 130, 246, 0.15);
            position: relative;
            overflow: hidden;
        }

        /* Top glowing line */
        .card-admin::before {
            content: '';
            position: absolute;
            top: 0;
            left: 10%;
            right: 10%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #3b82f6, #6366f1, transparent);
        }

        .shield-badge-wrap {
            position: relative;
            display: inline-block;
            margin-bottom: 14px;
        }
        .shield-badge-wrap img {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            box-shadow: 0 10px 25px -4px rgba(59, 130, 246, 0.5);
            transition: transform 0.3s ease;
        }
        .shield-icon-sub {
            position: absolute;
            bottom: -6px;
            right: -6px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #3b82f6;
            color: #60a5fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .security-pill {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #93c5fd;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .form-control-admin {
            background: var(--input-cyber);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            color: #ffffff !important;
            padding: 13px 16px;
            font-size: 0.95rem;
            height: 50px;
            transition: all 0.2s ease;
        }
        .form-control-admin:focus {
            background: rgba(15, 23, 42, 0.95);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
            outline: none;
        }
        .form-control-admin::placeholder {
            color: #475569;
        }

        .addon-icon {
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
        .addon-icon:hover {
            color: #93c5fd;
        }

        .btn-admin-submit {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            border: none;
            border-radius: 14px;
            height: 52px;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.6);
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-admin-submit:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -4px rgba(37, 99, 235, 0.8);
        }
        .btn-admin-submit:active {
            transform: translateY(0);
        }
        .btn-admin-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        .caps-alert {
            display: none;
            color: #f59e0b;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 6px;
        }

        .alert-admin-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .custom-control-label {
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            user-select: none;
        }
        .custom-control-input:checked ~ .custom-control-label::before {
            background-color: #3b82f6;
            border-color: #3b82f6;
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

<div class="auth-container">
    <div class="card card-admin p-4 p-md-5 {{ $errors->any() ? 'shake-box' : '' }}">
        
        <!-- Header -->
        <div class="text-center mb-4">
            <div class="shield-badge-wrap">
                <img src="{{ asset('app-logo.png') }}" alt="Taskwala" onerror="this.onerror=null; this.src='{{ asset('favicon.png') }}';">
                <div class="shield-icon-sub"><i class="fas fa-lock"></i></div>
            </div>
            
            <div class="d-block">
                <span class="security-pill">
                    <i class="fas fa-shield-alt"></i> SYSTEM CONTROL REALM
                </span>
            </div>
            
            <h3 class="font-weight-extrabold mb-1" style="font-weight: 800; letter-spacing: -0.5px;">Admin Console</h3>
            <p class="text-muted small mb-0">Authorized Personnel & Platform Governance</p>
        </div>

        @if($errors->any())
            <div class="alert-admin-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div>{{ $errors->first() }}</div>
            </div>
        @endif

        <form action="{{ route('admin.login') }}" method="POST" id="adminLoginForm">
            @csrf

            <!-- Email -->
            <div class="form-group mb-3">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">ADMINISTRATOR EMAIL</label>
                <div class="position-relative">
                    <input 
                        type="email" 
                        name="email" 
                        class="form-control form-control-admin w-100 pr-5" 
                        placeholder="admin@taskwala.com" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="username"
                        autofocus>
                    <span class="addon-icon" style="cursor: default;">
                        <i class="fas fa-user-shield"></i>
                    </span>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group mb-3 position-relative">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">SECURE MASTER KEY</label>
                <div class="position-relative">
                    <input 
                        type="password" 
                        name="password" 
                        id="adminPasswordInput"
                        class="form-control form-control-admin w-100 pr-5" 
                        placeholder="Enter master password" 
                        required 
                        autocomplete="current-password">
                    <span class="addon-icon" id="toggleAdminPasswordBtn" title="Toggle password visibility">
                        <i class="fas fa-eye" id="adminEyeIcon"></i>
                    </span>
                </div>
                <div class="caps-alert" id="adminCapsWarning">
                    <i class="fas fa-arrow-up mr-1"></i> Caps Lock is active
                </div>
            </div>

            <!-- Remember Option -->
            <div class="form-group d-flex justify-content-between align-items-center mb-4">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="custom-control-label" for="remember">Keep session active</label>
                </div>
                <span class="text-muted small">
                    <i class="fas fa-lock mr-1" style="font-size: 10px;"></i> 256-Bit SSL
                </span>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-admin-submit" id="adminSubmitBtn">
                <span>Access Control Center</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <!-- Security Notice Footer -->
        <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <p class="text-muted mb-0" style="font-size: 0.75rem;">
                <i class="fas fa-shield-alt text-primary mr-1"></i> Unauthorized access attempts are monitored and logged.
            </p>
        </div>
    </div>
</div>

<script>
    // Password Visibility Toggle
    const toggleAdminPasswordBtn = document.getElementById('toggleAdminPasswordBtn');
    const adminPasswordInput = document.getElementById('adminPasswordInput');
    const adminEyeIcon = document.getElementById('adminEyeIcon');

    toggleAdminPasswordBtn.addEventListener('click', function() {
        if (adminPasswordInput.type === 'password') {
            adminPasswordInput.type = 'text';
            adminEyeIcon.classList.remove('fa-eye');
            adminEyeIcon.classList.add('fa-eye-slash');
        } else {
            adminPasswordInput.type = 'password';
            adminEyeIcon.classList.remove('fa-eye-slash');
            adminEyeIcon.classList.add('fa-eye');
        }
    });

    // Caps Lock Detector
    adminPasswordInput.addEventListener('keyup', function(e) {
        const adminCapsWarning = document.getElementById('adminCapsWarning');
        if (e.getModifierState && e.getModifierState('CapsLock')) {
            adminCapsWarning.style.display = 'block';
        } else {
            adminCapsWarning.style.display = 'none';
        }
    });

    // Submit Loading Feedback
    const adminLoginForm = document.getElementById('adminLoginForm');
    const adminSubmitBtn = document.getElementById('adminSubmitBtn');

    adminLoginForm.addEventListener('submit', function() {
        adminSubmitBtn.disabled = true;
        adminSubmitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Authenticating credentials...</span>';
    });
</script>

</body>
</html>
