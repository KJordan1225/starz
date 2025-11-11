{{-- resources/views/auth/tenant-login.blade.php --}}
@extends('layouts.landlord')

@section('content')

@php
    $title    = 'Temp Title';
    $tenantId = request()->segment(1);
@endphp

{{-- Top Navbar (mobile-first) --}}
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
      @if(($branding['logo_url'] ?? null))
        <img src="{{ $branding['logo_url'] }}" alt="Logo"
             class="me-2" style="height:28px;width:28px;object-fit:cover;border-radius:.25rem;">
      @endif
      <span class="fw-semibold">{{ $title }}</span>
    </a>

    {{-- Hamburger --}}
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu"
            aria-controls="mobileMenu" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    {{-- Collapsible menu (hidden by default on mobile) --}}
    <div class="collapse navbar-collapse" id="mobileMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/') }}">Home</a>
        </li>
        @if (Route::has('tenant.register'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('tenant.register', ['tenant' => $tenantId]) }}">Register</a>
          </li>
        @endif
        @if (Route::has('password.request'))
          <li class="nav-item">
            <a class="nav-link" href="{{ route('password.request', ['tenant' => $tenantId]) }}">Forgot password</a>
          </li>
        @endif
      </ul>
    </div>
  </div>
</nav>

<div class="container-fluid g-0">
  <div class="row g-0 min-vh-100">
    {{-- LEFT HALF (hidden on mobile) --}}
    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center"
         style="background-color:#000000; color:#ffffff;">
      <div class="text-center px-4">
        <h1 class="display-5 fw-semibold mb-2">{{ $title }}</h1>
      </div>
    </div>

    {{-- RIGHT HALF (login; full-width on mobile) --}}
    <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5">
      <div class="auth-card px-3 px-md-4 w-100" style="max-width: 520px;">
        {{-- Mobile header (since left panel is hidden) --}}
        <div class="text-center mb-4 d-lg-none">
          @if(($branding['logo_url'] ?? null))
            <img src="{{ $branding['logo_url'] }}" alt="Logo"
                 class="mb-3" style="height:48px;width:48px;object-fit:cover;border-radius:.5rem;">
          @endif
          <h2 class="h4 fw-semibold m-0">{{ $title }}</h2>
        </div>

        <div class="card shadow-sm">
          <div class="card-body p-4 p-md-5">
            <h3 class="h5 fw-semibold mb-3 text-center">Sign in</h3>

            {{-- Flash + validation --}}
            @if (session('status'))
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if (session('error'))
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            @if ($errors->any())
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            @endif

            <form method="POST" action="{{ route('tenant.login', ['tenant' => $tenantId]) }}" novalidate>
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
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
                <div class="form-check">
                  <input class="form-check-input border border-2 border-secondary" type="checkbox" name="remember" id="remember">
                  <label class="form-check-label" for="remember">Remember me</label>
                </div>
                @if (Route::has('password.request'))
                  <a class="small" href="{{ route('password.request', ['tenant' => $tenantId]) }}">
                    Forgot password?
                  </a>
                @endif
              </div>

              <button type="submit" class="btn w-100" style="background-color:#000000; color:#ffffff;">
                Log in
              </button>
            </form>

            {{-- Optional: register link --}}
            @if (Route::has('tenant.register'))
              <div class="text-center mt-3">
                <span class="small text-muted">New here?</span>
                <a class="small ms-1" href="{{ route('tenant.register', ['tenant' => $tenantId]) }}">
                  Create an account
                </a>
              </div>
            @endif
          </div>
        </div>

        <div class="text-center mt-4 small text-muted">
          &copy; {{ date('Y') }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
