<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Affiliate Login | {{ config('platform.name') }}</title>
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
                    <h3 class="font-weight-bold text-primary"><i class="fas fa-rocket mr-2"></i>{{ config('platform.name') }}</h3>
                    <p class="text-muted small">Sign in to your affiliate dashboard</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('user.login') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">EMAIL ADDRESS</label>
                        <input type="email" name="email" class="form-control form-control-lg bg-dark text-white border-secondary" required autofocus>
                    </div>

                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">PASSWORD</label>
                        <input type="password" name="password" class="form-control form-control-lg bg-dark text-white border-secondary" required>
                    </div>

                    <div class="form-group d-flex justify-content-between align-items-center">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                            <label class="custom-control-label small text-muted" for="remember">Remember me</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold mt-4">Sign In</button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary">
                    <span class="text-muted small">Don't have an account?</span>
                    <a href="{{ route('user.register') }}" class="text-primary font-weight-bold ml-1 small">Register Now</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
