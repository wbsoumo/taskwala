<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | {{ config('platform.name', 'Taskwala') }}</title>
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
        }

        .auth-container {
            max-width: 440px;
            width: 100%;
        }

        .card-auth {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(59, 130, 246, 0.1);
        }

        .icon-key-wrap {
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
    </style>
</head>
<body>

<div class="auth-container">
    <div class="card card-auth p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="icon-key-wrap">
                <i class="fas fa-key"></i>
            </div>
            <h3 class="font-weight-extrabold mb-1" style="font-weight: 800;">Reset Password</h3>
            <p class="text-muted small mb-0">Enter your registered email or mobile number to receive a secure code</p>
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

        <form action="{{ route('user.password.email') }}" method="POST" id="resetRequestForm">
            @csrf
            <div class="form-group mb-4">
                <label class="small text-muted font-weight-bold mb-1" style="letter-spacing: 0.5px; font-size: 0.72rem;">EMAIL OR 10-DIGIT MOBILE</label>
                <input 
                    type="text" 
                    name="login" 
                    class="form-control form-control-custom w-100" 
                    placeholder="e.g. 9876543210 or name@email.com" 
                    value="{{ old('login') }}" 
                    required 
                    autofocus>
            </div>

            <button type="submit" class="btn btn-submit" id="submitBtn">
                <span>Send Verification Code</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(255, 255, 255, 0.08);">
            <a href="{{ route('user.login') }}" class="text-muted small font-weight-bold" style="text-decoration: none;">
                <i class="fas fa-arrow-left mr-1"></i> Back to Login
            </a>
        </div>
    </div>
</div>

<script>
    const resetRequestForm = document.getElementById('resetRequestForm');
    const submitBtn = document.getElementById('submitBtn');

    resetRequestForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> <span>Sending code...</span>';
    });
</script>

</body>
</html>
