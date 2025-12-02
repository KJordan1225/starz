<?php

namespace App\Http\Controllers\Tenant;

use App\Models\Carousel;
use App\Models\TenantVideo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\MediaLibrary\Conversions\ImageGenerators\Video;

class TenantCarouselController extends Controller
{
    /**
     * Show the upload/manage page for this tenant's carousel.
     */
    public function edit(Request $request)
    {
        $tenantId = tenant('id'); // from stancl/tenancy helper

        // One carousel row per tenant
        $carousel = Carousel::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'title'       => 'Homepage Carousel',
                'description' => 'Carousel for ' . $tenantId,
            ]
        );

        // All images for this tenant's carousel
        $images = $carousel->getMedia('carousel_images');      

        return view('tenant.carousel.edit', [
            'carousel' => $carousel,
            'images'   => $images,
        ]);
    }

    /**
     * Handle upload of one or more carousel images for this tenant.
     */
    public function store(Request $request)
    {
        $tenantId = tenant('id');

        $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'images'      => ['required', 'array'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'], // 5MB each
        ]);

        $carousel = Carousel::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'title'       => $request->input('title', 'Homepage Carousel'),
                'description' => $request->input('description'),
            ]
        );

        // Optional: update title/description per upload
        $carousel->update([
            'title'       => $request->input('title', $carousel->title),
            'description' => $request->input('description', $carousel->description),
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $img) {
                $carousel->addMedia($img)
                    ->toMediaCollection('carousel_images');
            }
        }

        return back()->with('success', 'Carousel images uploaded for this microsite.');
    }

    /**
     * Clear ONLY this tenant's carousel media.
     */
    public function clear(Request $request)
    {
        $tenantId = tenant('id');

        $carousel = Carousel::where('tenant_id', $tenantId)->first();

        if ($carousel) {
            $carousel->clearMediaCollection('carousel_images');
        }

        return back()->with('success', 'Carousel images cleared for this microsite.');
    }

    /**
     * Show the microsite homepage with a tenant-scoped carousel.
     */
    public function homepage(Request $request)
    {
        $tenantId = tenant('id');

        $carousel = Carousel::where('tenant_id', $tenantId)->first();

        $carouselImages = $carousel
            ? $carousel->getMedia('carousel_images')->take(5) // or ->take(N)
            : collect();

        return view('tenant.home', [
            'carouselImages' => $carouselImages,
            'tenantId'       => $tenantId,
        ]);
    }

    /**
     * Show the microsite homepage with a tenant-scoped carousel.
     */
    public function showSubscribe(Request $request)
    {
        $tenantId = tenant('id');

        $carousel = Carousel::where('tenant_id', $tenantId)->first();

        $carouselImages = $carousel
            ? $carousel->getMedia('carousel_images')->take(5) // or ->take(N)
            : collect();

        return view('tenant.carousel.showSubscribe', [
            'carouselImages' => $carouselImages,
            'tenantId'       => $tenantId,
        ]);
    }


    /**
     * Show the upload/manage page for this tenant's carousel.
     */
    public function videoEdit(Request $request)
    {
        $tenantId = tenant('id'); // from stancl/tenancy helper

        // One carousel row per tenant
        $carousel = TenantVideo::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'title'       => 'Creator Video',
                'description' => 'Video for ' . $tenantId,
            ]
        );

        // All images for this tenant's carousel
        $images = $carousel->getMedia('tenant_videos'); 

        return view('tenant.creator.video.edit', [
            'carousel' => $carousel,
            'videos'   => $images,
        ]);
    }


    /**
     * Handle upload of one or more carousel images for this tenant.
     */
    public function videoStore(Request $request)
    {
        $tenantId = tenant('id');

        $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video'       => ['nullable', 'file', 'mimes:mp4,avi,mkv', 'max:102400000'], // 1000MB max for videos
        ]);


        $carousel = TenantVideo::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'title'       => $request->input('title', 'Homepage Carousel'),
                'description' => $request->input('description'),
            ]
        );
        
        // Optional: update title/description per upload
        $carousel->update([
            'title'       => $request->input('title', $carousel->title),
            'description' => $request->input('description', $carousel->description),
        ]);

        if ($request->hasFile('video') && $request->file('video')->isValid()) {
            $videoMedia = $carousel->addMedia($request->file('video'))
                    ->toMediaCollection('tenant_videos');

            // 2) Use FFmpeg to create a thumbnail image from the video
            $this->createVideoThumbnail($carousel, $videoMedia);
            
        } else {
            return back()->with('error', 'There was an issue with the video upload.');
        }        

        return back()->with('success', 'Carousel video uploaded for this microsite.');
    }


    /**
     * Clear ONLY this tenant's carousel media.
     */
    public function videoClear(Request $request)
    {
        $tenantId = tenant('id');

        $carousel = TenantVideo::where('tenant_id', $tenantId)->first();

        if ($carousel) {
            $carousel->clearMediaCollection('tenant_videos');
        }

        return back()->with('success', 'Carousel videos cleared for this microsite.');
    }


    protected function createVideoThumbnail( TenantVideo $tenantVideo, \Spatie\MediaLibrary\MediaCollections\Models\Media $videoMedia): void
    {
        // Temp file path for the thumbnail
        $thumbnailPath = storage_path('app/tmp/video-thumb-' . $videoMedia->id . '.jpg');

        // Open the video via Laravel-FFMpeg (disk must match your media disk)
        $disk = $videoMedia->disk ?? config('filesystems.default');

        FFMpeg::fromDisk($disk)
            ->open($videoMedia->getPathRelativeToRoot())
            // Pick frame at 1 second (you can adjust)
            ->getFrameFromSeconds(1)
            ->export()
            ->toDisk('local') // we temporarily store thumbnail on 'local'
            ->save('tmp/video-thumb-' . $videoMedia->id . '.jpg');

        // 3) Attach thumbnail as an image media item
        $tenantVideo
            ->addMedia($thumbnailPath)
            ->usingFileName('video-thumb-' . $videoMedia->id . '.jpg')
            ->toMediaCollection('tenant_video_thumbnails');

        // 4) Optional: clean up temp file
        @unlink($thumbnailPath);
    }

    
}
