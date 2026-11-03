<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | {{ config('platform.name', 'Taskwala') }}</title>
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
                    <h3 class="font-weight-bold text-primary"><i class="fas fa-key mr-2"></i>Reset Password</h3>
                    <p class="text-muted small">Enter your email or mobile number to receive a verification code</p>
                </div>

                @if(session('status'))
                    <div class="alert alert-success py-2 small">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('user.password.email') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">EMAIL OR 10-DIGIT MOBILE NUMBER</label>
                        <input type="text" name="login" value="{{ old('login') }}" class="form-control form-control-lg bg-dark text-white border-secondary" placeholder="e.g. 9876543210 or name@email.com" required autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold mt-4">Send Verification Code</button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary">
                    <a href="{{ route('user.login') }}" class="text-muted small"><i class="fas fa-arrow-left mr-1"></i> Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
