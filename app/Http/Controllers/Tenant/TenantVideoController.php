<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantVideo;
use Illuminate\Http\Request;

class TenantVideoController extends Controller
{
    public function index(Request $request)
    {
        // Get the tenant's carousel (or any other model associated with media)
        $tenantId = tenant('id'); // Assuming you're using Stancl Tenancy
        $carousel = TenantVideo::where('tenant_id', $tenantId)->first();

        // Retrieve all videos in the 'tenant_videos' collection
        $videos = $carousel
            ? $carousel->getMedia('tenant_videos') // Get all videos
            : collect(); // Return empty collection if no media found

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

        return view('tenant.creator.video.show', [
            'videos' => $carouselImages,
            'tenantId'       => $tenantId,
        ]);
    }

}
