@extends('layouts.landlord')

@section('content')
@php
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    // Defensive fallback
    $videos = $videos ?? collect();

    // Optional: only keep video media items
    $videos = $videos->filter(fn (Media $m) =>
        str_starts_with($m->mime_type ?? '', 'video/')
    );
@endphp

<div class="container py-5">

    <h1 class="mb-4">Exclusive Videos</h1>

    @if ($videos->isEmpty())
        <div class="alert alert-info">
            No videos have been uploaded yet.
        </div>
    @else
        <div class="row g-4">
            @foreach ($videos as $media)
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card h-100 shadow-sm">

                        <div class="ratio ratio-16x9">
                            <video
                                class="w-100 h-100"
                                controls
                                preload="metadata"
                            >
                                <source
                                    src="{{ route('tenant.videos.stream', [
                                        'tenant' => $tenantId,
                                        'media'  => $media->id,
                                    ]) }}"
                                    type="{{ $media->mime_type ?? 'video/mp4' }}"
                                >
                                Your browser does not support the video tag.
                            </video>
                        </div>

                        <div class="card-body">
                            <h6 class="card-title mb-1">
                                {{ $media->name ?? 'Exclusive Video' }}
                            </h6>

                            <small class="text-muted">
                                ID #{{ $media->id }}
                            </small>
                        </div>

                        <div class="card-footer bg-white">
                            <a
                                href="{{ route('tenant.videos.stream', [
                                    'tenant' => $tenantId,
                                    'media'  => $media->id,
                                ]) }}"
                                class="btn btn-sm btn-outline-primary w-100"
                            >
                                ▶ Watch
                            </a>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
