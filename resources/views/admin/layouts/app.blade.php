<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · CurbCream</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="brand">
                <div class="brand-title">CurbCream</div>
                <div class="brand-sub">Admin Panel</div>
            </div>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="{{ route('admin.customers.index') }}" class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Customers
                </a>
                <a href="{{ route('admin.providers.index') }}" class="{{ request()->routeIs('admin.providers.*') ? 'active' : '' }}">
                    <i class="bi bi-truck"></i> Providers
                </a>
                <a href="{{ route('admin.bookings.index') }}" class="{{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <i class="bi bi-calendar-check"></i> Bookings
                </a>
                <a href="{{ route('admin.disputes.index') }}" class="{{ request()->routeIs('admin.disputes.*') ? 'active' : '' }}">
                    <i class="bi bi-exclamation-triangle"></i> Disputes
                </a>
                <!-- <a href="{{ route('admin.listings.index') }}" class="{{ request()->routeIs('admin.listings.*') ? 'active' : '' }}">
                    <i class="bi bi-star"></i> Featured Listings
                </a> -->
                <!-- <a href="{{ route('admin.plans.index') }}" class="{{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card"></i> Subscription Plans
                </a> -->
            </nav>
            <div class="sidebar-footer">
                <div class="small mb-2">{{ auth()->user()->name ?? 'Admin' }}</div>
                <div class="text-white-50 small mb-3">{{ auth()->user()->email ?? '' }}</div>
                <form action="{{ route('admin.logout') }}" method="POST" class="js-action-form">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100 btn-action">
                        <span class="btn-label"><i class="bi bi-box-arrow-right"></i> Logout</span>
                        <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Logging out...</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="admin-main">
            <div class="d-flex justify-content-between align-items-center mb-3 d-lg-none">
                <button class="btn btn-dark" type="button" id="sidebarToggle"><i class="bi bi-list"></i> Menu</button>
            </div>
            @yield('content')
        </main>
    </div>

    <div id="toast-stack" class="toast-stack"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if(session('success'))
                AdminUI.toast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
                AdminUI.toast(@json(session('error')), 'error');
            @endif
            @if($errors->any())
                AdminUI.toast(@json($errors->first()), 'error');
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
