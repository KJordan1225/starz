<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>

    {{-- Bootstrap 5 (CDN). Swap with @vite if you bundle locally. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://vjs.zencdn.net/8.23.4/video-js.css" rel="stylesheet" />

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

    {{-- TOP NAVBAR --}}
    <nav class="navbar navbar-expand-lg topbar navbar-dark">
        <div class="container-fluid">
            {{-- Left: Hamburger toggles the offcanvas sidebar on mobile --}}
            <button class="btn btn-outline-light d-lg-none me-2"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#tenantSidebar"
                    aria-controls="tenantSidebar"
                    aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Brand --}}
            <a class="navbar-brand d-flex align-items-center gap-2"
            href="#">
                @if(!empty($branding['logo_url']))
                    <img src="#" class="tenant-logo" alt="Logo">
                @endif
                <span class="fw-semibold">StarCity Starz</span>
            </a>

            {{-- Mobile toggler for the horizontal nav links --}}
            <button class="navbar-toggler ms-2" type="button"
                    data-bs-toggle="collapse" data-bs-target="#topnavLinks"
                    aria-controls="topnavLinks" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Center: Horizontal links --}}
            <div class="collapse navbar-collapse" id="topnavLinks">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a
                            class="nav-link text-white {{ request()->routeIs('guest.home') ? 'active' : '' }}"
                            href="#"
                            style="font-size:20px;"
                        >
                            Home
                        </a>

                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white{{ request()->routeIs('guestr.about') ? 'active' : '' }}"
                            href="#"
                            style="font-size:20px;">
                            Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white{{ request()->routeIs('guest.contact') ? 'active' : '' }}"
                            href="#"
                            style="font-size:20px;">
                            Contact
                        </a>
                    </li>
                </ul>

                {{-- Right-side top nav (tenant badge + auth) --}}
                <div class="ms-lg-auto d-flex align-items-center gap-3">
                    @auth
                    {{-- Tenant badge --}}
                    <span class="text-white-50 small d-none d-sm-inline">
                        Logged in: <strong>{{ auth()->user()->name }}</strong>
                    </span>
                    <form method="POST" action="{{ route('tenant.logout', ['tenant' => request()->segment(1)]) }}" class="m-0">
                        @csrf
                        <button class="btn btn-sm btn-light" type="submit">Log out</button>
                    </form>
                    @else
                    <span class="text-white-50 small d-none d-sm-inline">
                        Not Logged In
                    </span> 
                    <a href="{{ route('tenant.login', ['tenant'=> tenant('id')]) }}" class="btn btn-primary btn-sm">
                        Login
                    </a>                   
                    @endauth
                </div>
            </div>
        </div>
    </nav>


    {{-- MAIN WRAPPER: sidebar (left) + content (right) --}}
    <div class="container-fluid py-3 flex-grow-1">
        <div class="row">
            {{-- LEFT SIDEBAR: visible as column on lg+, offcanvas on < lg --}}
            <div class="col-lg-3 col-xl-2 d-none d-lg-block">
                <aside class="sidebar sticky-top" style="top: 1rem;">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <nav class="nav flex-column">
                                <a class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                                   href="#">
                                    <span class="me-2">🏠</span> Dashboard
                                </a>

                                {{-- Extra sidebar links from children --}}
                                @yield('sidebar')
                            </nav>
                        </div>
                    </div>

                    <div class="card shadow-sm mt-2">
                        <div class="card-header">
                            <h6 class="mb-0">Manage Tenants</h6>
                        </div>

                        <div class="card-body">
                            <nav class="nav flex-column">
                                <a class="nav-link d-flex align-items-center {{ request()->routeIs('tenants.index') ? 'active' : '' }}"
                                href="#">
                                    <span class="me-2">📋</span> List Tenants
                                </a>

                                <a class="nav-link d-flex align-items-center {{ request()->routeIs('tenants.create') ? 'active' : '' }}"
                                href="#">
                                    <span class="me-2">➕</span> Add Tenant
                                </a>

                                {{-- Extra sidebar links from children --}}
                                @yield('sidebar')
                            </nav>
                        </div>
                    </div>

                    {{-- Optional: quick tenant info --}}
                    <div class="card shadow-sm mt-3">
                        <div class="card-body small text-muted">
                            <div class="mb-1">Brand Primary: <code></code></div>
                            <div class="mb-1">Accent: <code></code></div>
                        </div>
                    </div>
                </aside>
            </div>

            {{-- CONTENT AREA --}}
            <div class="col-12 col-lg-9 col-xl-10">
                {{-- Mobile offcanvas (same sidebar) --}}
                <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="tenantSidebar"
                     aria-labelledby="tenantSidebarLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="tenantSidebarLabel">Menu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <nav class="nav flex-column">
                            <a class="nav-link {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                               href="#">
                                <span class="me-2">🏠</span> Dashboard
                            </a>
                            @yield('sidebar')
                        </nav>
                    </div>
                </div>

                {{-- Page header (optional) --}}
                @hasSection('title')
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h1 class="h4 m-0">@yield('title')</h1>
                        {{-- Optional additional actions --}}
                        @yield('actions')
                    </div>
                @endif

                {{-- MAIN CONTENT --}}
                <div class="content">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    {{-- Footer (optional) --}}
    <footer class="mt-auto py-3">
        <div class="container-fluid small text-center text-muted">
            &copy; {{ date('Y') }}
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
