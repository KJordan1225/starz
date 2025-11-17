@extends('layouts.landlord')

@section('content')
<div class="container my-4">
    <h2 class="mb-4">Tenant Videos</h2>

    {{-- Display videos in rows of 4 --}}
    <div class="row">
        @foreach ($videos as $index => $video)
            <div class="col-md-3 mb-4">
                <div class="card">
                    <video class="card-img-top" controls width="100%">
                        <source src="{{ $video->getUrl() }}" type="{{ $video->mime_type }}">
                        Your browser does not support the video tag.
                    </video>
                    <div class="card-body">
                        <h5 class="card-title">Video {{ $index + 1 }}</h5>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- If no videos are found --}}
    @if ($videos->isEmpty())
        <p class="text-muted">No videos found for this tenant.</p>
    @endif
</div>
@endsection
