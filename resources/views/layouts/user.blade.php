<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Affiliate Portal') | {{ config('platform.name') }}</title>

    <!-- Google Font: Inter / Outfit -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .navbar-custom .navbar-brand, .navbar-custom .nav-link {
            color: #fff !important;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(0,0,0,0.08);
        }
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            color: #fff;
        }
        .stat-card-blue { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .stat-card-green { background: linear-gradient(135deg, #10b981 0%, #047857 100%); }
        .stat-card-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); }
        .stat-card-amber { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-3">
    <div class="container">
        <a class="navbar-brand font-weight-bold" href="{{ route('user.dashboard') }}">
            <i class="fas fa-rocket text-primary mr-2"></i>{{ config('platform.name') }}
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#userNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="userNav">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.dashboard') }}"><i class="fas fa-home mr-1"></i> Dashboard</a>
                </li>
                <li class="nav-item {{ request()->routeIs('user.campaigns*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.campaigns.index') }}"><i class="fas fa-bullhorn mr-1"></i> Campaigns</a>
                </li>
                <li class="nav-item {{ request()->routeIs('user.links.generator') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.links.generator') }}"><i class="fas fa-magic mr-1"></i> Generate Link</a>
                </li>
                <li class="nav-item {{ request()->routeIs('user.links.index') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.links.index') }}"><i class="fas fa-link mr-1"></i> My Links</a>
                </li>
                <li class="nav-item {{ request()->routeIs('user.wallet*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('user.wallet.index') }}"><i class="fas fa-wallet mr-1"></i> Wallet</a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white font-weight-bold" href="#" data-toggle="dropdown">
                        <i class="fas fa-user-circle mr-1"></i> {{ auth('web')->user()->name ?? 'Affiliate' }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2">
                        <a class="dropdown-item" href="{{ route('user.profile.index') }}"><i class="fas fa-id-card mr-2 text-primary"></i> Profile & UPI</a>
                        <a class="dropdown-item" href="{{ route('user.reports.clicks') }}"><i class="fas fa-mouse-pointer mr-2 text-info"></i> Click Stats</a>
                        <a class="dropdown-item" href="{{ route('user.reports.conversions') }}"><i class="fas fa-check-double mr-2 text-success"></i> Conversions</a>
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('user.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger font-weight-bold">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-lg shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded-lg shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Please check errors below:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer class="bg-white border-top py-4 mt-5">
    <div class="container text-center text-muted small">
        &copy; {{ date('Y') }} {{ config('platform.name') }}. All rights reserved.
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')
</body>
</html>
