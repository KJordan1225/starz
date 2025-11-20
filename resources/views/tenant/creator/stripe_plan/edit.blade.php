@extends('layouts.landlord')

@section('content')
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header text-center">
                    <h5>Stripe Plan – Tenant: {{ tenant('id') }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tenant.stripe.plan.update', ['tenant' => tenant('id')]) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="monthly_price" class="form-label">Monthly Price</label>
                            <input type="number" step="0.01" min="1"
                                   class="form-control"
                                   id="monthly_price"
                                   name="monthly_price"
                                   value="{{ old('monthly_price', $plan->monthly_price ?? '') }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text"
                                   class="form-control"
                                   id="currency"
                                   name="currency"
                                   value="{{ old('currency', $plan->currency ?? 'USD') }}"
                                   maxlength="3">
                        </div>

                        @php
                            $tenant = tenant();
                        @endphp
                        @if(! $tenant->stripe_account_id)
                            <div class="alert alert-warning">
                                You must connect a Stripe account to receive payouts.
                            </div>
                            <div class="mb-3 text-center">
                                <a href="{{ route('tenant.stripe.onboard.start', ['tenant' => tenant('id')]) }}"
                                class="btn btn-outline-primary">
                                    Connect with Stripe
                                </a>
                            </div>
                        @endif


                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                Save Stripe Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
