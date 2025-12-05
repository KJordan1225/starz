@extends('layouts.landlord')

@section('content')
@php
    $tenantId = request()->segment(1) ? request()->segment(1) : request()->route('tenant');
    $tenant = \App\Models\Tenant::find($tenantId);
@endphp

<div class="container py-4">
    <h1 class="mb-4">
        {{ $tenant->name ?? 'Creator' }} – Videos
    </h1>

    @if ($videos->isEmpty())
        <p>No videos have been uploaded yet.</p>
    @else
        <div class="row g-4">            
            @foreach ($videos as $media)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <video
                                class="w-100"
                                controls
                                preload="metadata"
                            >
                                <source
                                    src="{{ route('tenant.videos.stream', ['tenant' => $tenantId, 'media' => $media]) }}"
                                    type="{{ $media->mime_type ?? 'video/mp4' }}"
                                >
                                Your browser does not support HTML5 video.
                            </video>
                        </div>

                        @if (! empty($media->name))
                            <div class="card-footer text-muted small">
                                {{ $media->name }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection