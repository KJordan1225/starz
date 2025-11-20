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
                    <h5>Subscribe to {{ tenant('id') }}</h5>
                </div>
                <div class="card-body text-center">
                    @if (! $plan)
                        <p class="text-muted">This creator has not set up a subscription plan yet.</p>
                    @else
                        <p class="lead">
                            Monthly Price:
                            <strong>{{ $plan->currency }} {{ number_format($plan->monthly_price, 2) }}</strong>
                        </p>

                        <p class="mb-4">Choose your payment method:</p>

                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            {{-- Stripe --}}
                            <form method="POST" action="{{ route('tenant.stripe.subscriptions.start', ['tenant' => tenant('id')]) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-lg">
                                    Pay with Stripe
                                </button>
                            </form>

                            {{-- PayPal (existing flow, you already have this wired separately) --}}
                            <form method="POST" action="{{ route('tenant.subscriptions.start', ['tenant' => tenant('id')]) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-lg">
                                    Pay with PayPal
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
