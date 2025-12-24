@extends('layouts.landlord')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{ $plan->exists ? 'Edit Subscription Plan' : 'Create Subscription Plan' }}
                    </h5>
                </div>

                <div class="card-body">

                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST"
                          action="{{ route('save.subscription-plan', ['tenant' => $tenant->id]) }}">
                        @csrf

                        {{-- PLAN NAME --}}
                        <div class="mb-3">
                            <label class="form-label">Plan Name</label>
                            <input type="text"
                                   name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $plan->name ?? 'Monthly Subscription') }}"
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- DESCRIPTION --}}
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="3">{{ old('description', $plan->description) }}</textarea>
                        </div>

                        <div class="row">
                            {{-- AMOUNT --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number"
                                           step="0.01"
                                           min="1"
                                           name="amount"
                                           class="form-control @error('amount') is-invalid @enderror"
                                           value="{{ old('amount', $plan->amount ? number_format($plan->price, 2, '.', '') : '') }}"
                                           required>
                                </div>
                                <div class="form-text">
                                    Amount charged per billing period.
                                </div>
                                @error('amount')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- INTERVAL --}}
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Billing Interval</label>
                                <select name="interval"
                                        class="form-select @error('interval') is-invalid @enderror">
                                    <option value="month" @selected(old('interval', $plan->interval) === 'month')>
                                        Monthly
                                    </option>
                                    <option value="year" @selected(old('interval', $plan->interval) === 'year')>
                                        Yearly
                                    </option>
                                </select>
                                @error('interval')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- STATUS --}}
                        <div class="form-check mb-3">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="active"
                                   value="1"
                                   id="active"
                                   @checked(old('active', $plan->active))>
                            <label class="form-check-label" for="active">
                                Plan is active and visible to users
                            </label>
                        </div>

                        {{-- STRIPE INFO (READ-ONLY) --}}
                        @if($plan->stripe_price_id)
                            <div class="alert alert-info">
                                <strong>Stripe Price ID:</strong>
                                <code>{{ $plan->stripe_price_id }}</code>
                                <div class="small text-muted mt-1">
                                    Changing price will create a new Stripe Price automatically.
                                </div>
                            </div>
                        @endif

                        {{-- ACTIONS --}}
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('tenant.admin.home', ['tenant' => $tenant->id]) }}"
                               class="btn btn-outline-secondary">
                                Cancel
                            </a>

                            <button type="submit" class="btn btn-primary">
                                {{ $plan->exists ? 'Save Changes' : 'Create Plan' }}
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
