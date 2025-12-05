@extends('layouts.landlord')

@section('content')
@php
    $tenantId = request()->segment(1) ? request()->segment(1) : request()->route('tenant');
    $tenant = \App\Models\Tenant::find($tenantId);
@endphp
<div class="container my-4">
    <a href="{{ route('tenant.user.home', ['tenant' => $tenantId]) }}" class="btn btn-outline-primary mb-3 w-100">User Dashboard</a>
    &nbsp;&nbsp;
    <h2 class="mb-4">Creator Exclusive Images for {{ $tenant->id }}</h2>

    {{-- Display images in rows of 4 --}}
    <div class="row">
        @foreach ($images as $index => $image)
            <div class="col-md-3 mb-4">
                <div class="card">
                    <img src="{{ $image->getUrl() }}" class="card-img-top img-thumbnail" alt="Image {{ $index + 1 }}" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="{{ $image->getUrl() }}">
                    <div class="card-body">
                        <h5 class="card-title">Image {{ $index + 1 }}</h5>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal to show full-size image --}}
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Full-size Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="Full-size Image">
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // JavaScript to handle image click and set the full-size image in the modal
    var modal = document.getElementById('imageModal');
    modal.addEventListener('show.bs.modal', function (event) {
        // Get the image element that triggered the modal
        var button = event.relatedTarget; // Button that triggered the modal        
        var imageUrl = button.getAttribute('data-image'); // Get the full-size image URL
       
        // Set the modal's image to the full-size image
        var modalImage = modal.querySelector('#modalImage');
        modalImage.src = imageUrl;
    });
</script>
@endpush
