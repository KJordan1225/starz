@extends('layouts.landlord')

@section('content')

@php
    use App\Services\TenantService;
    $tenantService = new TenantService();
    $tenantId = $tenantService->getTenantId();
@endphp

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="mb-0">
            @if($videoOwner)
                Videos for {{ $videoOwner->title ?? $tenant->id }}
            @else
                Videos for {{ $tenant->id }}
            @endif
        </h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <a href="{{ route('tenant.admin.home', ['tenant' => $tenantId]) }}"
       class="btn btn-outline-primary mb-3 w-100">
        Admin Dashboard
    </a>

    @if($videos->isEmpty())
        <div class="alert alert-info">
            No videos have been uploaded yet.
        </div>
    @else
        <div class="row g-3">
            @foreach($videos as $media)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">

                        {{-- CLICKABLE VIDEO PREVIEW --}}
                        <video
                            class="card-img-top"
                            style="max-height: 220px; object-fit: cover; cursor: pointer;"
                            controls
                            data-bs-toggle="modal"
                            data-bs-target="#videoPreviewModal"
                            data-video-url="{{ $media->getUrl() }}"
                            data-video-name="{{ $media->file_name }}"
                        >
                            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
                            Your browser does not support the video tag.
                        </video>

                        <div class="card-body p-2">
                            <div class="small text-muted mb-2">
                                {{ $media->file_name }}
                            </div>

                            <div class="d-grid">
                                <form method="POST"
                                      action="{{ route('tenant.exclusive.videos.destroy', ['tenant' => $tenantId, 'media' => $media->id]) }}"
                                      onsubmit="return confirm('Delete this video? This cannot be undone.')">
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

{{-- FULL VIDEO MODAL --}}
<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="videoPreviewTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center p-0">
                <video
                    id="videoPreview"
                    class="w-100"
                    style="max-height: 85vh; background: #000;"
                    controls
                >
                    <source id="videoPreviewSource" src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal       = document.getElementById('videoPreviewModal');
    const modalVideo  = document.getElementById('videoPreview');
    const modalSource = document.getElementById('videoPreviewSource');
    const modalTitle  = document.getElementById('videoPreviewTitle');

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget;

        const url  = trigger.getAttribute('data-video-url');
        const name = trigger.getAttribute('data-video-name');

        modalSource.src = url;
        modalTitle.textContent = name;

        // Reload video with new source
        modalVideo.load();
        modalVideo.play();
    });

    modal.addEventListener('hidden.bs.modal', function () {
        modalVideo.pause();
        modalVideo.currentTime = 0;
        modalSource.src = '';
        modalVideo.load();
        modalTitle.textContent = '';
    });
});
</script>
@endpush