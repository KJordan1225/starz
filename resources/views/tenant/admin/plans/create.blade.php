@extends('layouts.landlord')

@section('content')
<div class="container py-5">
    <h3 class="mb-3">Create Plan</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('tenant.admin.plans.store', ['tenant' => $tenant->id]) }}">
                @csrf

                @include('tenant.admin.plans.partials.form', ['plan' => null])

                <button class="btn btn-primary w-100" type="submit">
                    Create & Sync to Stripe
                </button>
            </form>
        </div>
    </div>
</div>
@endsection