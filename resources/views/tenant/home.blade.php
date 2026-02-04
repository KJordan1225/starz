@extends('layouts.landlord')

@section('content')
 @php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp
<div class="container my-4">
    {{-- Tenant-aware carousel --}}
    @if ($carouselImages->isNotEmpty())
        <div id="tenantCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                @foreach ($carouselImages as $index => $image)
                    <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                        <img
                            src="{{ $image->getUrl() }}"
                            alt="Carousel Image"
                            class="d-block mx-auto"
                            style="width: 300px; height: 400px; object-fit: cover;"
                        >
                    </div>

                @endforeach
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#tenantCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>

            <button class="carousel-control-next" type="button" data-bs-target="#tenantCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    @else
        <p class="text-muted">
            No carousel images uploaded yet for this microsite.
        </p>
    @endif
    @php
        if ($tenantId===null) {
            $tenantId = request()->segment(1);
        }
    @endphp
    <div class="text-center">
        <h3>Login (or Register) to view more of this model</h3>
        <a href="{{ route('tenant.login', ['tenant'=> $tenantId]) }}" class="btn btn-primary me-2">Login</a>
        <a href="{{ route('tenant.register', ['tenant'=> $tenantId]) }}" class="btn btn-secondary">Register</a>
    </div>


</div>
@endsection
