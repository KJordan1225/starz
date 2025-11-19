@extends('layouts.landlord')

@section('content')
<div class="container my-4">
    <a href="{{ route('tenant.admin.home', ['tenant'=>tenant('id')]) }}">Admin Dashboard</a>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
	
	@if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h2 class="mb-3">Manage Carousel Imagess for {{ $tenantId ?? tenant('id') }}</h2>

    {{-- Upload form --}}
    <form action="{{ route('tenant.creator.images.store', ['tenant' => tenant('id')]) }}"
          method="POST"
          enctype="multipart/form-data"
          class="mb-4">
        @csrf

        <div class="mb-3">
            <label for="title" class="form-label">Title (optional)</label>
            <input type="text" name="title" id="title"
                   class="form-control"
                   value="{{ old('title', $carousel->title ?? '') }}">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Description (optional)</label>
            <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $carousel->description ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="images" class="form-label">Upload Creator Images</label>
            <input type="file" name="images[]" class="form-control" id="images" accept="image/*" multiple required>
            @error('images') 
                <div class="text-danger small">{{ $message }}</div> 
            @enderror
        </div>       
        <button type="submit" class="btn btn-primary">Upload Images</button>
    </form>

    {{-- Clear collection button --}}
    <form action="{{ route('tenant.creator.image.clear', ['tenant' => tenant('id')]) }}"
          method="POST"
          onsubmit="return confirm('Clear all carousel imagess for this microsite?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Clear Carousel Videos</button>
    </form>

    <hr class="my-4">

    {{-- Preview current images --}}
    <h4>Current Carousel Images</h4>

    @if($images->isEmpty())
        <p class="text-muted">No images uploaded yet for this microsite.</p>
    @else
        <div class="row g-3">
            @foreach($images as $image)
                <div class="col-6 col-md-3">
                    <div class="card">
                        <img src="{{ $image->getUrl() }}" class="card-img-top" alt="Carousel image">
                        <div class="card-body p-2">
                            <small class="text-muted">{{ $image->file_name }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection