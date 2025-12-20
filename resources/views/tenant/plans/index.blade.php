@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp

<div class="container py-5">

    <!-- Display session errors if there are any -->
    @if(session('errors'))
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach(session('errors')->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <a href="#" class="btn btn-warning">Edit Price</a>

    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="fw-bold">
                Subscribe to {{ $tenant->id }}
            </h2>
            <p class="text-muted">
                Choose a subscription plan to unlock premium content.
            </p>
        </div>
    </div>

    @if($plans->isEmpty())
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="alert alert-info text-center">
                    No subscription plans are currently available.
                </div>
            </div>
        </div>
    @else
        <div class="row justify-content-center g-4">
            @foreach($plans as $plan)
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm {{ $plan->featured ? 'border-primary' : '' }}">

                        @if($plan->featured)
                            <div class="card-header text-center bg-primary text-white fw-semibold">
                                Most Popular
                            </div>
                        @endif

                        <div class="card-body d-flex flex-column text-center">

                            <h5 class="card-title mb-2">
                                {{ $plan->name }}
                            </h5>

                            @if($plan->description)
                                <p class="card-text text-muted small mb-3">
                                    {{ $plan->description }}
                                </p>
                            @endif

                            @if($plan->price)
                                <div class="mb-3">
                                    <span class="fs-3 fw-bold">
                                        ${{ number_format($plan->price / 100, 2) }}
                                    </span>
                                    <span class="text-muted">
                                        / {{ ucfirst($plan->interval) }}
                                    </span>
                                </div>
                            @endif

                            <div class="mt-auto">
                                @auth
                                    <form method="POST"
                                          action="{{ route('tenant.plans.subscribe', ['tenant' => $tenant->id, 'plan' => $plan->id]) }}">
                                        @csrf

                                        <button type="submit"
                                                class="btn {{ $plan->featured ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                            Subscribe
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('tenant.login', ['tenant'=> $tenantId]) }}"
                                       class="btn btn-outline-secondary w-100">
                                        Log in to Subscribe
                                    </a>
                                @endauth
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
