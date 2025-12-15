@extends('layouts.landlord')

@section('content')
<div class="container py-5">
    <h3 class="mb-3">Edit Plan</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.admin.plans.update', ['tenant' => $tenant->id, 'plan' => $plan->id]) }}">
                @csrf
                @method('PUT')

                @include('tenant.admin.plans.partials.form', ['plan' => $plan])

                <button class="btn btn-primary w-100" type="submit">
                    Save & Sync to Stripe
                </button>
            </form>

            <hr class="my-4">

            <div class="small text-muted">
                Stripe Product: <code>{{ $plan->stripe_product_id ?: '—' }}</code><br>
                Stripe Price: <code>{{ $plan->stripe_price_id ?: '—' }}</code><br>
                <span class="d-block mt-2">
                    If you change amount/currency/interval, a <strong>new Stripe Price</strong> will be created automatically.
                </span>
            </div>
        </div>
    </div>
</div>
@endsection