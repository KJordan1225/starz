@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp

    <div class="container">
        <h1>Create New Subscription Plan</h1>

        <a href="{{ route('tenant.admin.home', ['tenant' => $tenantId]) }}"
            class="btn btn-outline-primary mb-3 w-100">
            Admin Dashboard
        </a>

        <form method="POST" action="{{ route('tenant.plans.store', ['tenant' => $tenant->id]) }}">
            @csrf

            {{-- Plan Name --}}
            <div class="form-group mb-3">
                <label for="name">Plan Name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    required
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Price --}}
            <div class="form-group mb-3">
                <label for="price">Price</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    id="price"
                    class="form-control @error('price') is-invalid @enderror"
                    value="{{ old('price') }}"
                    required
                >
                @error('price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Description --}}
            <div class="form-group mb-3">
                <label for="description">Description</label>
                <textarea
                    name="description"
                    id="description"
                    class="form-control @error('description') is-invalid @enderror"
                    maxlength="500"
                >{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Featured (BOOLEAN SAFE) --}}
            <div class="form-group form-check mb-3">
                {{-- hidden field ensures boolean is always submitted --}}
                <input type="hidden" name="featured" value="0">
                <input
                    type="checkbox"
                    name="featured"
                    id="featured"
                    value="1"
                    class="form-check-input @error('featured') is-invalid @enderror"
                    {{ old('featured') ? 'checked' : '' }}
                >
                <label class="form-check-label" for="featured">Featured</label>
                @error('featured')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            {{-- Active (BOOLEAN SAFE) --}}
            <div class="form-group form-check mb-4">
                {{-- hidden field ensures boolean is always submitted --}}
                <input type="hidden" name="active" value="0">
                <input
                    type="checkbox"
                    name="active"
                    id="active"
                    value="1"
                    class="form-check-input @error('active') is-invalid @enderror"
                    {{ old('active', true) ? 'checked' : '' }}
                >
                <label class="form-check-label" for="active">Active</label>
                @error('active')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                Create Plan
            </button>
        </form>

    </div>
@endsection