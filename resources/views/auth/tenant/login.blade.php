@extends('layouts.landlord')

@section('content')
@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();

    if ($tenantId === null) {
        $tenantId = request()->segment(1);
    }
@endphp
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header text-center">
                    <h1 class="h5 mb-0">Log In</h1>
                </div>

                <div class="card-body">
                    {{-- Session Status --}}
                    @if (session('status'))
                        <div class="alert alert-info small">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tenant.login.store', ['tenant' => $tenantId]) }}">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email Address
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control form-control-lg @error('email') is-invalid @enderror"
                                required
                                autofocus
                                autocomplete="username"
                            >

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control form-control-lg @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                            >

                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
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

                                @if (is_null($tenantId))
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
                            
                        </div>

                        {{-- Submit --}}
                        <button type="submit" class="btn btn-primary w-100">
                            Log in
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
