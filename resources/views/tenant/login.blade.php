<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>StarCity Starz</title>

    {{-- Bootstrap 5 (replace with @vite if you bundle locally) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="min-vh-100 d-flex">

    @php
        $tenantId = request()->segment(1);
    @endphp

    @php
        use App\Services\TenantService;
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();
    @endphp

    <div class="container-fluid g-0 flex-fill">
        <div class="row g-0 min-vh-100">

            {{-- LEFT HALF (hidden on mobile) --}}
            <div class="col-lg-6 d-none d-lg-flex brand-pane align-items-center justify-content-center">
                <div class="text-center px-4">                    
                    @php 
                        $altName = 'StarCity Starz';
                    @endphp
                    <h1 class="display-5 fw-semibold brand-title mb-2">
                        {{ $altName }} 
                    </h1>

                    <div class="text-muted">
                        Tenant: <code></code>
                    </div>
                </div>
            </div>

            {{-- RIGHT HALF (login; full-width on mobile) --}}
            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5">
                <div class="auth-card px-4">
                    <div class="text-center mb-4 d-lg-none">
                        {{-- Optional compact header on mobile --}}                       
                        <h2 class="h4 fw-semibold m-0"></h2>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="h5 fw-semibold mb-3 text-center">Sign in</h3>

                            <form method="POST" action="{{ route('login', ['tenant' => $tenantId]) }}" novalidate>
                                @csrf

                                {{-- Email --}}
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        class="form-control border border-2 @error('email') is-invalid border-danger @else border-secondary @enderror"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="username"
                                    >
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                                {{-- Password --}}
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input
                                        id="password"
                                        type="password"
                                        name="password"
                                        class="form-control border border-2 @error('password') is-invalid border-danger @else border-secondary @enderror"
                                        required
                                        autocomplete="current-password"
                                    >
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                                {{-- Remember + Forgot --}}
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input border border-2 border-secondary"
                                            type="checkbox"
                                            name="remember"
                                            id="remember"
                                        >
                                        <label class="form-check-label" for="remember">Remember me</label>
                                    </div>

                                    @if (Route::has('password.request'))
                                        if (is_null($tenantId))
                                            <a class="small link-brand"
                                            href="{{ route('landlord.password.request') }}">
                                                Forgot password?
                                            </a>
                                        @else
                                            <a class="small link-brand"
                                            href="{{ route('password.request', ['tenant' => $tenantId]) }}">
                                                Forgot password?
                                            </a>
                                        @endif                                        
                                    @endif
                                </div>


                                <button type="submit" class="btn btn-brand w-100">
                                    Log in
                                </button>
                            </form>

                            {{-- Optional: register link --}}                            
                            <div class="text-center mt-3">
                                <span class="small text-muted">New here?</span>
                                if (is_null($tenantId))
                                    <a class="small link-brand"
                                    href="{{ route('register') }}">
                                        Create an account
                                    </a>
                                @else
                                    <a class="small ms-1 link-brand"
                                        href="{{ route('tenant.register', ['tenant' => $tenantId]) }}">
                                        Create an account
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 small text-muted">
                        &copy; {{ date('Y') }} StarCity Starz
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
