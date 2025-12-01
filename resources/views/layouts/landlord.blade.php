 <!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title', 'StarCity Starz')</title>

    {{-- Bootstrap 5 (CDN). Swap with @vite if you bundle locally. --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://vjs.zencdn.net/8.23.4/video-js.css" rel="stylesheet" />

    <style>
        /* Optional tweaks for mobile spacing */
        body {
            background-color: #f8f9fa;
        }

        .topbar {
            background: linear-gradient(90deg, #111827, #1f2937);
        }

        .tenant-logo {
            height: 32px;
            width: auto;
            object-fit: contain;
        }

        .sidebar .nav-link.active {
            font-weight: 600;
            background-color: #e9ecef;
            border-radius: 0.375rem;
        }

        @media (max-width: 991.98px) {
            /* tighten container padding on mobile */
            .content-wrapper {
                padding-top: 0.75rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @php
use App\Models\Tenant;

// Get Tenancy manager
$tenancy = tenancy();

// Default
$tenant_id = null;

// Already initialized
if ($tenancy->initialized) {
    $tenant_id = $tenancy->tenant?->id;
} else {
    // Prefer route param `{tenant}`, fall back to first segment
    $segment = request()->route('tenant') ?? request()->segment(1);

    if ($segment) {
        $tenant = Tenant::query()
            ->where('id', $segment)
            // ->orWhere('slug', $segment)
            ->first();

        if ($tenant) {
            $tenancy->initialize($tenant);
            $tenant_id = $tenant->id;
        }
        // else: silently ignore non-tenant segment
    }
}
@endphp


    {{-- TOP NAVBAR --}}
    <nav class="navbar navbar-expand-lg topbar navbar-dark sticky-top">
        <div class="container-fluid px-2 px-sm-3">
            {{-- Left: Hamburger toggles the LEFT offcanvas sidebar on mobile --}}
            <button class="btn btn-outline-light d-lg-none me-2"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#tenantSidebar"
                    aria-controls="tenantSidebar"
                    aria-label="Toggle sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Brand --}}
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                @if(!empty($branding['logo_url'] ?? null))
                    <img src="{{ $branding['logo_url'] }}" class="tenant-logo" alt="Logo">
                @endif
                <span class="fw-semibold">StarCity Starz</span>
            </a>

            {{-- Mobile toggler for the TOP nav links + auth --}}
            <button class="navbar-toggler ms-2" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#topnavLinks"
                    aria-controls="topnavLinks"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- TOP NAV CONTENT (hidden on small, shown when hamburger is clicked) --}}
            <div class="collapse navbar-collapse mt-2 mt-lg-0" id="topnavLinks">
                {{-- Center: Horizontal links --}}
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 text-center text-lg-start">
                    <li class="nav-item">
                        <a
                            class="nav-link text-white {{ request()->routeIs('guest.home') ? 'active fw-semibold' : '' }}"
                            href="#"
                            style="font-size: 18px;"
                        >
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link text-white {{ request()->routeIs('guest.plans') ? 'active fw-semibold' : '' }}"
                            href="#"
                            style="font-size: 18px;"
                        >
                            Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a
                            class="nav-link text-white {{ request()->routeIs('guest.contact') ? 'active fw-semibold' : '' }}"
                            href="#"
                            style="font-size: 18px;"
                        >
                            Contact
                        </a>
                    </li>
                </ul>

                {{-- Right-side top nav (tenant badge + auth) --}}
                <div class="ms-lg-auto d-flex flex-column flex-sm-row align-items-sm-center gap-2 gap-sm-3 text-center text-sm-start">
                    @auth
                        {{-- Tenant badge --}}
                        <span class="text-white-50 small">
                            Logged in:
                            <strong class="text-white">{{ auth()->user()->name }}</strong>
                        </span>
                        <form method="POST" action="{{ route('tenant.logout', ['tenant' => $tenant_id]) }}" class="m-0">
                            @csrf
                            <button class="btn btn-sm btn-light w-100 w-sm-auto" type="submit">Log out</button>
                        </form>
                    @else
                        <span class="text-white-50 small">
                            Not Logged In
                        </span>
                        @if (is_null($tenant_id))
                            <a href="{{ route('login') }}"
                               class="btn btn-primary btn-sm w-100 w-sm-auto">
                                Login
                            </a>
                        @else
                            <a href="{{ route('tenant.login', ['tenant'=> $tenant_id]) }}"
                               class="btn btn-primary btn-sm w-100 w-sm-auto">
                                Login
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>


    {{-- MAIN WRAPPER: sidebar (left) + content (right) --}}
    <div class="container-fluid py-3 flex-grow-1 content-wrapper">
        <div class="row">
            {{-- LEFT SIDEBAR: visible as column on lg+, offcanvas on < lg --}}
            <div class="col-lg-3 col-xl-2 d-none d-lg-block">
                <aside class="sidebar sticky-top" style="top: 1rem;">
                    <div class="card shadow-sm mb-2">
                        <div class="card-body">
                            <nav class="nav flex-column">
                                <a class="nav-link d-flex align-items-center {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                                   href="#">
                                    <span class="me-2">🏠</span> Dashboard
                                </a>

                                {{-- Extra sidebar links from children --}}
                                @yield('sidebar')
                            </nav>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-2">
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

                                {{-- Optional: additional landlord sidebar links --}}
                                @yield('landlord_sidebar')
                            </nav>
                        </div>
                    </div>

                    {{-- Optional: quick tenant info --}}
                    <div class="card shadow-sm mt-2">
                        <div class="card-body small text-muted">
                            <div class="mb-1">
                                Brand Primary:
                                <code>{{ $branding['primary_color'] ?? '#000000' }}</code>
                            </div>
                            <div class="mb-1">
                                Accent:
                                <code>{{ $branding['accent_color'] ?? '#ffffff' }}</code>
                            </div>
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
                        <nav class="nav flex-column mb-3">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"
                               href="#">
                                <span class="me-2">🏠</span> Dashboard
                            </a>
                            @yield('sidebar')
                        </nav>

                        <hr>

                        <h6 class="text-muted">Manage Tenants</h6>
                        <nav class="nav flex-column">
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('tenants.index') ? 'active' : '' }}"
                               href="#">
                                <span class="me-2">📋</span> List Tenants
                            </a>
                            <a class="nav-link d-flex align-items-center {{ request()->routeIs('tenants.create') ? 'active' : '' }}"
                               href="#">
                                <span class="me-2">➕</span> Add Tenant
                            </a>
                            @yield('landlord_sidebar')
                        </nav>
                    </div>
                </div>

                {{-- Page header (optional) --}}
                @hasSection('title')
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-3 gap-2">
                        <h1 class="h4 m-0 text-truncate">@yield('title')</h1>
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
    <footer class="mt-auto py-3 bg-white border-top">
        <div class="container-fluid small text-center text-muted">
            &copy; {{ date('Y') }} StarCity Starz
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
