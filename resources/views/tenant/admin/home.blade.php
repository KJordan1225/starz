@extends('layouts.landlord')

@section('content')
@php
    $tenantId = request()->segment(1) ? request()->segment(1) : request()->route('tenant');
@endphp
<div class="container my-4">    
    <div class="row justify-content-center">
        <!-- Admin Navigation Card -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header text-center">
                    <h5>Admin Navigation</h5>
                </div>
                <div class="card-body d-flex flex-column align-items-center">
                    <!-- Vertical Navigation Buttons -->
                    <a href="{{ route('tenant.creator.images.edit', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary mb-3 w-100">Upload Exclusive Images</a>
                    <a href="{{ route('tenant.creator.video.edit', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary mb-3 w-100">Upload Exclusive Videos</a>
                    <a href="{{ route('tenant.carousel.edit', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary mb-3 w-100">Upload Homepage Preview Images</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
