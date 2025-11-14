@extends('layouts.landlord')

@section('content')
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
</div>
@endsection
