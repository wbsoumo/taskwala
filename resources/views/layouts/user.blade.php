<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Affiliate Portal') | {{ config('platform.name', 'Taskwala') }}</title>

    <!-- Google Font: Source Sans Pro & Inter -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Theme style: AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    
    <style>
        body {
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .main-sidebar {
            background-color: #1e293b !important;
        }
        .main-sidebar .brand-link {
            border-bottom: 1px solid #334155;
        }
        .user-panel {
            border-bottom: 1px solid #334155;
        }
        .nav-sidebar .nav-link.active {
            background-color: #0284c7 !important;
            color: #fff !important;
        }
        .card-custom {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0 shadow-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('user.dashboard') }}" class="nav-link font-weight-bold"><i class="fas fa-home text-primary mr-1"></i> Dashboard</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('user.campaigns.index') }}" class="nav-link"><i class="fas fa-bullhorn text-info mr-1"></i> Campaigns</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('user.links.generator') }}" class="nav-link"><i class="fas fa-magic text-warning mr-1"></i> Generate Link</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto align-items-center">
            <!-- User Balance Pill -->
            <li class="nav-item mr-3 d-none d-md-inline-block">
                <span class="badge badge-light border px-3 py-2 text-dark font-weight-bold">
                    <i class="fas fa-wallet text-success mr-1"></i> Balance: ₹{{ number_format(auth('web')->user()->wallet->balance ?? 0, 2) }}
                </span>
            </li>

            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link font-weight-bold" data-toggle="dropdown" href="#">
                    <i class="fas fa-user-circle fa-lg mr-1 text-secondary"></i> {{ auth('web')->user()->name ?? 'Affiliate' }}
                    <i class="fas fa-angle-down ml-1 small"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2">
                    <div class="dropdown-header text-uppercase font-weight-bold">Account Options</div>
                    <a class="dropdown-item" href="{{ route('user.profile.index') }}">
                        <i class="fas fa-user-edit mr-2 text-primary"></i> Profile & Settings
                    </a>
                    <a class="dropdown-item" href="{{ route('user.upi.index') }}">
                        <i class="fas fa-university mr-2 text-success"></i> UPI / Payout Details
                    </a>
                    <a class="dropdown-item" href="{{ route('user.wallet.index') }}">
                        <i class="fas fa-wallet mr-2 text-info"></i> My Wallet
                    </a>
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
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('user.dashboard') }}" class="brand-link text-center py-3">
            <span class="brand-text font-weight-bold text-white"><i class="fas fa-rocket text-primary mr-2"></i>{{ config('platform.name', 'Taskwala') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                <div class="image">
                    <i class="fas fa-user-circle fa-2x text-light"></i>
                </div>
                <div class="info">
                    <a href="{{ route('user.profile.index') }}" class="d-block font-weight-bold">{{ auth('web')->user()->name ?? 'Affiliate User' }}</a>
                    <span class="badge badge-success font-weight-normal">Affiliate Partner</span>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">CAMPAIGNS & LINKS</li>

                    <li class="nav-item">
                        <a href="{{ route('user.campaigns.index') }}" class="nav-link {{ request()->routeIs('user.campaigns*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bullhorn text-info"></i>
                            <p>Browse Campaigns</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('user.links.generator') }}" class="nav-link {{ request()->routeIs('user.links.generator') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-magic text-warning"></i>
                            <p>Generate Link</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('user.links.index') }}" class="nav-link {{ request()->routeIs('user.links.index') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-link text-primary"></i>
                            <p>My Links</p>
                        </a>
                    </li>

                    <li class="nav-header">TRACKING & REPORTS</li>

                    <li class="nav-item">
                        <a href="{{ route('user.reports.clicks') }}" class="nav-link {{ request()->routeIs('user.reports.clicks') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-mouse-pointer text-secondary"></i>
                            <p>Clicks Report</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('user.reports.conversions') }}" class="nav-link {{ request()->routeIs('user.reports.conversions*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-check-circle text-success"></i>
                            <p>Conversions Report</p>
                        </a>
                    </li>

                    <li class="nav-header">FINANCIALS & EARNINGS</li>

                    <li class="nav-item">
                        <a href="{{ route('user.wallet.index') }}" class="nav-link {{ request()->routeIs('user.wallet*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-wallet text-success"></i>
                            <p>Wallet & Ledger</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('user.upi.index') }}" class="nav-link {{ request()->routeIs('user.upi*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-university text-warning"></i>
                            <p>UPI / Payout Details</p>
                        </a>
                    </li>

                    <li class="nav-header">SETTINGS</li>

                    <li class="nav-item">
                        <a href="{{ route('user.profile.index') }}" class="nav-link {{ request()->routeIs('user.profile*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>Profile & Security</p>
                        </a>
                    </li>

                    <li class="nav-item mt-3">
                        <form action="{{ route('user.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link text-left text-danger w-100 border-0">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </button>
                        </form>
                    </li>

                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-6">
                        <h1 class="m-0 font-weight-bold text-dark">@yield('title')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Affiliate</a></li>
                            <li class="breadcrumb-item active">@yield('title')</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Please fix the following issues:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @yield('content')

            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            Affiliate Partner Portal
        </div>
        <strong>&copy; {{ date('Y') }} {{ config('platform.name', 'Taskwala') }}.</strong> All rights reserved.
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

@stack('scripts')
</body>
</html>
