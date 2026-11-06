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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #fff; }
        .card-auth { background: #1e293b; border: 1px solid #334155; border-radius: 16px; }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-auth p-4 shadow-lg">
                <div class="text-center mb-4">
                    <h3 class="font-weight-bold text-primary"><i class="fas fa-shield-alt mr-2"></i>Verify & Reset</h3>
                    <p class="text-muted small">Enter the 6-digit code sent to your contact and create a new password</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success py-2 small">
                        {{ session('status') }}
                    </div>
                @endif

                @if(isset($debug_otp) && $debug_otp)
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle mr-1"></i> <strong>Dev Code:</strong> {{ $debug_otp }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('user.password.update') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">ACCOUNT EMAIL OR MOBILE</label>
                        <input type="text" name="login" value="{{ old('login', $login) }}" class="form-control form-control-lg bg-dark text-white border-secondary" required>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">6-DIGIT VERIFICATION CODE</label>
                        <input type="text" name="otp" value="{{ old('otp', $debug_otp ?? '') }}" maxlength="6" class="form-control form-control-lg bg-dark text-white border-secondary text-center font-weight-bold" style="letter-spacing: 6px; font-size: 20px;" placeholder="123456" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">NEW PASSWORD (MIN 8 CHARACTERS)</label>
                        <input type="password" name="password" class="form-control form-control-lg bg-dark text-white border-secondary" placeholder="••••••••" required>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">CONFIRM NEW PASSWORD</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-lg bg-dark text-white border-secondary" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold mt-4">Reset Password</button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary">
                    <a href="{{ route('user.password.request') }}" class="text-muted small mr-3">Request New Code</a>
                    <a href="{{ route('user.login') }}" class="text-primary font-weight-bold small">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
