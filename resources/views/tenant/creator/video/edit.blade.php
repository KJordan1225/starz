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

    <h2 class="mb-3">Manage Carousel Videos for {{ $tenantId ?? tenant('id') }}</h2>

    {{-- Upload form --}}
    <form action="{{ route('tenant.creator.video.store', ['tenant' => tenant('id')]) }}"
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
            <label for="video" class="form-label">Upload Creator Video</label>
            <input type="file" name="video" class="form-control" id="video">
            @error('video') 
                <div class="text-danger small">{{ $message }}</div> 
            @enderror
        </div>       
        <button type="submit" class="btn btn-primary">Upload Video</button>
    </form>

    {{-- Clear collection button --}}
    <form action="{{ route('tenant.creator.video.clear', ['tenant' => tenant('id')]) }}"
          method="POST"
          onsubmit="return confirm('Clear all carousel videos for this microsite?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Clear Carousel Videos</button>
    </form>

    <hr class="my-4">

    {{-- Preview current images --}}
    <h4>Current Creator Videos</h4>

    @if($videos->isEmpty())
        <p class="text-muted">No videos uploaded yet for this microsite.</p>
    @else
        <div class="row g-3">
            @foreach($videos as $video)
                <div class="col-6 col-md-3">
                    <div class="card">
                        <img src="{{ $video->getUrl() }}" class="card-img-top" alt="Carousel image">
                        <div class="card-body p-2">
                            <small class="text-muted">{{ $video->file_name }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection