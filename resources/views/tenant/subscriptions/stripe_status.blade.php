@extends('layouts.landlord')

@section('content')
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card">
                <div class="card-header text-center">
                    <h5>Stripe Subscription – {{ tenant('id') }}</h5>
                </div>
                <div class="card-body">
                    @if(! $plan)
                        <p class="text-muted">This creator has not set up a subscription plan yet.</p>
                    @else
                        <p class="lead text-center">
                            Monthly Price:
                            <strong>{{ $plan->currency }} {{ number_format($plan->monthly_price, 2) }}</strong>
                        </p>

                        @if(! $subscription)
                            <div class="text-center">
                                <form method="POST" action="{{ route('tenant.stripe.subscriptions.start', ['tenant' => tenant('id')]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Subscribe with Stripe
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="mb-3 text-center">
                                <p class="mb-1">
                                    Status:
                                    <span class="badge bg-{{ $subscription->isActive() ? 'success' : 'secondary' }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </p>
                                <p class="mb-1">
                                    Starts at: {{ optional($subscription->starts_at)->format('Y-m-d') ?? '—' }}
                                </p>
                                <p class="mb-1">
                                    Ends at: {{ optional($subscription->ends_at)->format('Y-m-d') ?? '—' }}
                                </p>
                            </div>

                            <div class="text-center mt-3">
                                {{-- Optional: cancel buttons (Stripe) --}}
                                {{-- You can add cancel-now/cancel-at-end-of-term here later. --}}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
