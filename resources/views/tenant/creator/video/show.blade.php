@extends('layouts.landlord')

@section('content')
@php
    $tenantId = request()->segment(1) ? request()->segment(1) : request()->route('tenant');
    $tenant = \App\Models\Tenant::find($tenantId);
@endphp
<div class="container my-4">
    <h2 class="mb-4">Creator Exclusive Videos for {{ $tenant->id }}</h2>

    {{-- Display videos in rows of 4 (Media-based) --}}
    <div class="row">
        @foreach ($videos as $index => $video)
            @php
                /** @var \Spatie\MediaLibrary\MediaCollections\Models\Media $video */

                // If thumbnails are generated as a conversion on the VIDEO
                // (recommended approach)
                $thumbUrl = $video->hasGeneratedConversion('thumb')
                    ? $video->getUrl('thumb')
                    : null;
            @endphp

            <div class="col-md-3 mb-4">
                <div class="card h-100">

                    {{-- Clickable thumbnail --}}
                    <button
                        type="button"
                        class="p-0 border-0 bg-transparent w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#videoModal"
                        data-video-url="{{ $video->getUrl() }}"
                        data-video-type="{{ $video->mime_type }}"
                    >
                        @if ($thumbUrl)
                            {{-- Generated thumbnail --}}
                            <img
                                src="{{ $thumbUrl }}"
                                alt="Video thumbnail"
                                class="card-img-top img-fluid"
                            >
                        @else
                            {{-- Fallback if no conversion exists --}}
                            <video
                                class="card-img-top"
                                muted
                                playsinline
                                preload="metadata"
                                width="100%"
                            >
                                <source src="{{ $video->getUrl() }}" type="{{ $video->mime_type }}">
                                Your browser does not support the video tag.
                            </video>
                        @endif
                    </button>

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

  