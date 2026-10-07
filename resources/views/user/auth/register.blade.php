<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Affiliate Register | {{ config('platform.name') }}</title>
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
        <div class="col-md-6">
            <div class="card card-auth p-4 shadow-lg">
                <div class="text-center mb-4">
                    <h3 class="font-weight-bold text-primary"><i class="fas fa-rocket mr-2"></i>{{ config('platform.name') }}</h3>
                    <p class="text-muted small">Create your affiliate publisher account</p>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('user.register') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">FULL NAME</label>
                        <input type="text" name="name" class="form-control bg-dark text-white border-secondary" value="{{ old('name') }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small text-muted font-weight-bold">EMAIL ADDRESS</label>
                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small text-muted font-weight-bold">MOBILE NUMBER</label>
                            <input type="text" name="mobile_number" class="form-control bg-dark text-white border-secondary" value="{{ old('mobile_number') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small text-muted font-weight-bold">PASSWORD</label>
                            <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required minlength="8">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small text-muted font-weight-bold">CONFIRM PASSWORD</label>
                            <input type="password" name="password_confirmation" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small text-muted font-weight-bold">UPI ID (OPTIONAL)</label>
                            <input type="text" name="upi_id" class="form-control bg-dark text-white border-secondary" value="{{ old('upi_id') }}" placeholder="user@upi">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small text-muted font-weight-bold">UPI HOLDER NAME</label>
                            <input type="text" name="upi_holder_name" class="form-control bg-dark text-white border-secondary" value="{{ old('upi_holder_name') }}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg font-weight-bold mt-4">Create Account</button>
                </form>

                <div class="text-center mt-4 pt-3 border-top border-secondary">
                    <span class="text-muted small">Already registered?</span>
                    <a href="{{ route('user.login') }}" class="text-primary font-weight-bold ml-1 small">Sign In</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
