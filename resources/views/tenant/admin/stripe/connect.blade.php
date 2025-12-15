@extends('layouts.landlord')

@section('content')
<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Stripe Connect</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-warning">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">

            <p class="text-muted mb-3">
                Connect your Stripe account so subscribers can pay you and payouts can be enabled.
            </p>

            <ul class="small text-muted mb-4">
                <li>Required for subscription checkout (destination charges).</li>
                <li>Your platform fee (e.g. 20%) is taken automatically.</li>
                <li>Creators receive the remaining share (e.g. 80%).</li>
            </ul>

            <div class="mb-3">
                <div class="fw-semibold">Status</div>

                @php
                    $renewal = $tenant->stripe_onboarded_at
                        ? \Carbon\Carbon::parse($tenant->stripe_onboarded_at)
                        : null;
                @endphp

                @if($tenant->stripe_onboarded_at)
                    <span class="badge bg-success">Onboarded</span>
                    <div class="small text-muted mt-1">
                        Completed: {{ $renewal->format('M j, Y g:i A') }}
                    </div>
                @elseif($tenant->stripe_account_id)
                    <span class="badge bg-warning text-dark">In Progress</span>
                    <div class="small text-muted mt-1">
                        Stripe Account: <code>{{ $tenant->stripe_account_id }}</code>
                    </div>
                @else
                    <span class="badge bg-secondary">Not Started</span>
                @endif
            </div>

            <form method="POST" action="{{ route('tenant.stripe.connect.start', ['tenant' => $tenant->id]) }}">
                @csrf

                <button type="submit" class="btn btn-primary w-100">
                    {{ $tenant->stripe_account_id ? 'Continue Stripe Onboarding' : 'Start Stripe Onboarding' }}
                </button>
            </form>

        </div>
    </div>

</div>
@endsection