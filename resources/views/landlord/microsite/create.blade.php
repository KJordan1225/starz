{{-- resources/views/landlord/tenants/create.blade.php --}}
@extends('layouts.landlord') {{-- change to your layout --}}

@section('title', 'Create Microsite')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 m-0">Create Microsite</h1>
        <a href="#" class="btn btn-outline-secondary">Cancel</a>
    </div>

    {{-- Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('landlord.microsite.store') }}" class="row g-4">
        @csrf

        {{-- Tenant ID / Slug (primary key used by stancl/tenancy) --}}
        <div class="col-12 col-md-6">
            <label for="id" class="form-label">MicroSite ID / Slug</label>
            <input type="text" id="id" name="id"
                   class="form-control @error('id') is-invalid @enderror"
                   value="{{ old('id') }}" required
                   data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Type microsite ID/slug [all lowercase-dashes instead of spaces]">
            <div class="form-text">e.g. <code>alpha</code>, <code>bravo-studios</code>. Used in the URL path.</div>
            @error('id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Display name --}}
        <div class="col-12 col-md-6">
            <label for="display_name" class="form-label">Display Name</label>
            <input type="text" id="display_name" name="display_name"
                   class="form-control @error('display_name') is-invalid @enderror"
                   value="{{ old('display_name') }}" required
                   data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Human readable microsite ID">
            @error('display_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Name --}}
        <div class="col-12">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" id="name" name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}" placeholder="Enter your full name" required>
            @error('name') 
                <div class="invalid-feedback">{{ $message }}</div> 
            @enderror
        </div>


        {{-- Email --}}
        <div class="col-12">
            <label for="email" class="form-label">Email (optional)</label>
            <input type="email" id="email" name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}" placeholder="example@domain.com"
               data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Admin email address"
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Password --}}
        <div class="col-12">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password"
                class="form-control @error('password') is-invalid @enderror"
                value="{{ old('password') }}" placeholder="Enter your password" required
                data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Enter password here>"
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        {{-- Confirm password  --}}
        <div class="col-12">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror"
                value="{{ old('password_confirmation') }}" placeholder="Confirm your password" required>
            @error('password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        
        {{-- Submit --}}
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Create Microsite</button>
            <a href="#" class="btn btn-outline-secondary">Back</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
