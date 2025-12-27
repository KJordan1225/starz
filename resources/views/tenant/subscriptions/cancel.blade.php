@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp

<div class="container py-4 py-md-5">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
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
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Centered responsive card --}}
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white text-center">
                    <h5 class="mb-0">
                        Manage Subscription – {{ $tenant->id }}
                    </h5>
                </div>

                <div class="card-body">
                    <p class="text-muted small text-center mb-4">
                        You are managing your subscription for
                        <span class="fw-semibold">{{ $tenant->id }}</span>.
                    </p>

                    <div class="mb-4">
                        <h6 class="fw-bold">Option 1: Cancel at period end</h6>
                        <p class="small text-muted mb-3">
                            Your access remains active until the end of the current billing period.
                            You will not be billed again, and your subscription will automatically
                            end at that time.
                        </p>

                        <form method="POST"
                              action="{{ route('tenant.subscriptions.cancel.period_end', ['tenant' => $tenantId]) }}"
                              onsubmit="return confirm('Are you sure you want to cancel at the end of the current period?');">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100 mb-3">
                                Cancel at Period End
                            </button>
                        </form>
                    </div>

                    <hr>

                    <div class="mt-4">
                        <h6 class="fw-bold text-danger">Option 2: Cancel immediately</h6>
                        <p class="small text-muted mb-3">
                            Your subscription will be terminated right away and access will be removed.
                            Depending on the creator’s policy, charges may be prorated.
                        </p>

                        <form method="POST"
                              action="{{ route('tenant.subscriptions.cancel.now', ['tenant' => $tenantId]) }}"
                              onsubmit="return confirm('This will cancel your subscription immediately. Continue?');">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">
                                Cancel Immediately
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-footer text-center">
                    <a href="{{ route('tenant.user.home', ['tenant' => $tenantId]) }}" class="btn btn-link btn-sm">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection