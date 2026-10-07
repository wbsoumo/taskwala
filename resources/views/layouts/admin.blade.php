<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') | {{ config('platform.name', 'Admin Panel') }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Theme style: AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">Home</a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-user-circle mr-1"></i> {{ auth('admin')->user()->name ?? 'Admin' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
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
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <span class="brand-text font-weight-bold px-3"><i class="fas fa-chart-line text-primary mr-2"></i>{{ config('platform.name', 'BankSathi Platform') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">CAMPAIGNS & AFFILIATES</li>

                    <li class="nav-item {{ request()->is('admin/campaigns*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/campaigns*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <p>Campaigns <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.campaigns.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Campaigns</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.campaigns.create') }}" class="nav-link {{ request()->routeIs('admin.campaigns.create') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add Campaign</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item {{ request()->is('admin/users*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Affiliates / Users <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>All Users</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.users.create') }}" class="nav-link {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Add User</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-header">TRACKING & ATTRIBUTION</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.tracking.links') }}" class="nav-link {{ request()->routeIs('admin.tracking.links') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-link"></i>
                            <p>Affiliate Links</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.tracking.clicks') }}" class="nav-link {{ request()->routeIs('admin.tracking.clicks') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-mouse-pointer"></i>
                            <p>Click Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.tracking.conversions') }}" class="nav-link {{ request()->routeIs('admin.tracking.conversions*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-check-circle"></i>
                            <p>Conversions</p>
                        </a>
                    </li>

                    <li class="nav-header">POSTBACKS & INTEGRATION</li>

                    <li class="nav-item {{ request()->is('admin/postbacks*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('admin/postbacks*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-network-wired text-warning"></i>
                            <p>Postbacks <i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('admin.postbacks.global') }}" class="nav-link {{ request()->routeIs('admin.postbacks.global') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon text-primary"></i>
                                    <p>Global Postback</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.postbacks.offer_wise') }}" class="nav-link {{ request()->routeIs('admin.postbacks.offer_wise') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon text-success"></i>
                                    <p>Offer Wise Postback</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.postbacks.test') }}" class="nav-link {{ request()->routeIs('admin.postbacks.test*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon text-warning"></i>
                                    <p>Test Postback</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.postbacks.providers') }}" class="nav-link {{ request()->routeIs('admin.postbacks.providers') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Integration Networks</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.postbacks.ip_whitelists') }}" class="nav-link {{ request()->routeIs('admin.postbacks.ip_whitelists') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>IP Whitelists</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('admin.postbacks.logs') }}" class="nav-link {{ request()->routeIs('admin.postbacks.logs') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Postback Logs</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-header">FINANCIAL LEDGER</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.finance.ledger') }}" class="nav-link {{ request()->routeIs('admin.finance.ledger') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-wallet"></i>
                            <p>Wallet Ledger</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.finance.customer_payouts') }}" class="nav-link {{ request()->routeIs('admin.finance.customer_payouts') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hand-holding-usd"></i>
                            <p>Customer Payouts</p>
                        </a>
                    </li>

                    <li class="nav-header">REPORTS & SECURITY</li>

                    <li class="nav-item">
                        <a href="{{ route('admin.reports.performance') }}" class="nav-link {{ request()->routeIs('admin.reports.performance') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line text-success"></i>
                            <p>Performance Report</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.campaigns') }}" class="nav-link {{ request()->routeIs('admin.reports.campaigns') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-pie"></i>
                            <p>Campaign Reports</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.affiliates') }}" class="nav-link {{ request()->routeIs('admin.reports.affiliates') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-chart"></i>
                            <p>Affiliate Reports</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.reports.financial') }}" class="nav-link {{ request()->routeIs('admin.reports.financial') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Financial Reports</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.security.audit_logs') }}" class="nav-link {{ request()->routeIs('admin.security.audit_logs') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-shield"></i>
                            <p>Audit Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.security.login_logs') }}" class="nav-link {{ request()->routeIs('admin.security.login_logs') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Login Logs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.index') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i>
                            <p>Settings</p>
                        </a>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('title')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            <li class="breadcrumb-item active">@yield('title')</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Please correct the following errors:</strong>
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
    </div>

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            Production Financial Platform
        </div>
        <strong>&copy; {{ date('Y') }} {{ config('platform.name') }}.</strong> All rights reserved.
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

@stack('scripts')
</body>
</html>
