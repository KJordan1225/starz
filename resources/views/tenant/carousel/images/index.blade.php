@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">Images for {{ $carousel->title }}</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($images->isEmpty())
        <div class="alert alert-info">
            <a href="{{route('tenant.admin.home', ['tenant' => $tenantId] ) }}" class="btn btn-outline-primary mb-3 w-100">Admin Dashboard</a>
            No images have been uploaded yet.
        </div>
    @else
        <div class="row g-3">
            <a href="{{route('tenant.admin.home', ['tenant' => $tenantId] ) }}" class="btn btn-outline-primary mb-3 w-100">Admin Dashboard</a>
            @foreach($images as $media)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card h-100 shadow-sm">
                        <img
                            src="{{ $media->getUrl() }}"
                            class="card-img-top"
                            alt="Tenant image {{ $media->id }}"
                            style="object-fit: cover; height: 100px;"
                        >

                        <div class="card-body p-2">
                            <div class="small text-muted mb-2">
                                {{ $media->file_name }}
                            </div>

                            <div class="d-grid">
                                <form method="POST"
                                      action="{{ route('tenant.carousel.images.destroy', ['tenant' => $tenantId, 'media' => $media->id]) }}"
                                      onsubmit="return confirm('Delete this image? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
