<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantVideo;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;


class TenantVideoController extends Controller
{
    public function index(Request $request)
    {
        // Get the tenant's carousel (or any other model associated with media)
        $tenantId = tenant('id'); // Stancl Tenancy helper

        $carousel = TenantVideo::where('tenant_id', $tenantId)->first();

        // Retrieve all videos in the 'tenant_videos' collection
        $videos = $carousel
            ? $carousel->getMedia('tenant_videos')
            : collect();

        return view('tenant.carousel.video.index', compact('videos')); 
    }

    /**
     * Show the microsite homepage with a tenant-scoped carousel.
     */
    public function creatorVideoPage(Request $request)
    {
        $tenantId = tenant('id');

        $carousel = TenantVideo::where('tenant_id', $tenantId)
            ->with('media')
            ->first();

        $carouselImages = $carousel
            ? $carousel->getMedia('tenant_videos') // or ->take(N)
            : collect();

        return view('tenant.carousel.video.display', [
            'videos'   => $carouselImages,
            'tenantId' => $tenantId,
        ]);
    }

    /**
     * Stream a Spatie Media video with HTTP Range support (seekable).
     * Route-model binds the Media item.
     */
    public function stream(Request $request, int|string $mediaId)
    {
        $mediaId = request()->segment(4);
        // Load the Media record manually
        $media = Media::findOrFail($mediaId);

        // Optional: tenant safety check
        $currentTenantId = tenant('id');
        if (
            ! $media->model instanceof \App\Models\TenantVideo ||
            $media->model->tenant_id !== $currentTenantId
        ) {
            abort(403, 'You are not allowed to access this video.');
        }

        $path = $media->getPath();

        if (! file_exists($path)) {
            abort(404);
        }

        $fileSize = filesize($path);
        $start    = 0;
        $end      = $fileSize - 1;
        $length   = $fileSize;
        $status   = 200;

        $mimeType = $media->mime_type ?: 'video/mp4';

        $headers = [
            'Content-Type'  => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ];

        if ($request->headers->has('Range')) {
            $range = $request->header('Range');

            if (preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
                $start = (int) $matches[1];

                if ($matches[2] !== '') {
                    $end = (int) $matches[2];
                }

                if ($end > $fileSize - 1) {
                    $end = $fileSize - 1;
                }

                if ($start > $end) {
                    $start = 0;
                }

                $length = $end - $start + 1;
                $status = 206;

                $headers['Content-Range']  = "bytes {$start}-{$end}/{$fileSize}";
                $headers['Content-Length'] = $length;
            }
        } else {
            $headers['Content-Length'] = $length;
        }

        return new StreamedResponse(function () use ($path, $start, $end) {
            $chunkSize = 1024 * 1024;
            $handle    = fopen($path, 'rb');

            fseek($handle, $start);

            $position = $start;

            while (! feof($handle) && $position <= $end) {
                $bytesToRead = min($chunkSize, $end - $position + 1);
                echo fread($handle, $bytesToRead);
                flush();
                $position += $bytesToRead;
            }

            fclose($handle);
        }, $status, $headers);
    }
}