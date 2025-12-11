@extends('layouts.landlord')

@section('content')
@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Support {{ $tenant->name }}</h5>
                </div>
                <div class="card-body">
                    
                    <form method="POST" action="{{ route('tenant.orders.checkout', ['tenant' => $tenantId]) }}">
                        @csrf

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (USD)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="1"
                                class="form-control @error('amount') is-invalid @enderror"
                                id="amount"
                                name="amount"
                                value="{{ old('amount', 5.00) }}"
                                required
                            >
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            Proceed to Checkout
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection