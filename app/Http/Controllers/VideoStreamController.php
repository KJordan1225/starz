<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VideoStreamController extends Controller
{
    public function stream(Request $request, Media $media)
    {
        // Get the absolute path to the file
        // (works for local disks; for S3 you'd do a different approach)
        $path = $media->getPath();

        if (! file_exists($path)) {
            abort(404);
        }

        $fileSize = filesize($path);
        $start    = 0;
        $end      = $fileSize - 1;
        $length   = $fileSize;
        $status   = 200;

        // Determine mime type from media
        $mimeType = $media->mime_type ?: 'video/mp4';

        $headers = [
            'Content-Type'  => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ];

        // Handle HTTP Range header for seeking
        if ($request->headers->has('Range')) {
            $range = $request->header('Range'); // e.g. "bytes=12345-" or "bytes=12345-67890"

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
                $status = 206; // Partial Content

                $headers['Content-Range']  = "bytes {$start}-{$end}/{$fileSize}";
                $headers['Content-Length'] = $length;
            }
        } else {
            // Full content
            $headers['Content-Length'] = $length;
        }

        $response = new StreamedResponse(function () use ($path, $start, $end) {
            $chunkSize = 1024 * 1024; // 1 MB per chunk
            $handle    = fopen($path, 'rb');

            // Jump to start byte
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

        return $response;
    }
}
