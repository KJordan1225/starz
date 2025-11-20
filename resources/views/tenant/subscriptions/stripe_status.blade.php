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
                            {{-- No Stripe subscription yet --}}
                            <div class="text-center">
                                <form method="POST"
                                      action="{{ route('tenant.stripe.subscriptions.start', ['tenant' => tenant('id')]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Subscribe with Stripe
                                    </button>
                                </form>
                            </div>
                        @else
                            {{-- Stripe subscription details --}}
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
                                <p class="mb-1">
                                    Cancel at period end:
                                    @if($subscription->cancel_at_period_end)
                                        <span class="badge bg-warning text-dark">Yes</span>
                                    @else
                                        <span class="badge bg-light text-muted">No</span>
                                    @endif
                                </p>
                            </div>
                            
                            @if($subscription->provider === 'stripe' && $subscription->isActive())
                                <div class="d-flex justify-content-center gap-3 flex-wrap mt-3">
                                    {{-- Cancel at period end --}}
                                    <form method="POST"
                                          action="{{ route('tenant.stripe.subscriptions.cancel.period_end', [
                                              'tenant'       => tenant('id'),
                                              'subscription' => $subscription->id,
                                          ]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-outline-warning"
                                                onclick="return confirm('Keep access until the end of this billing period, then cancel?');">
                                            Cancel at Period End
                                        </button>
                                    </form>

                                    {{-- Cancel immediately --}}
                                    <form method="POST"
                                          action="{{ route('tenant.stripe.subscriptions.cancel.now', [
                                              'tenant'       => tenant('id'),
                                              'subscription' => $subscription->id,
                                          ]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-outline-danger"
                                                onclick="return confirm('Cancel now and lose access immediately?');">
                                            Cancel Now
                                        </button>
                                    </form>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
