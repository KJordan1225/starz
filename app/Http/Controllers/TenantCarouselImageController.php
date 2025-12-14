<?php

namespace App\Http\Controllers;

use App\Models\Carousel;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\TenantService;

class TenantCarouselImageController extends Controller
{
	public function index(Request $request): View
	{
    
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();

        $tenant = Tenant::findOrFail($tenantId)->first();
        $carousel = Carousel::query()
			->where('tenant_id', $tenantId)
			->latest()
			->first();
        
        // Only images in the 'carousel_images' collection
		$images = $carousel->getMedia('carousel_images');

		return view('tenant.carousel.images.index', [
			'tenant' => $tenant,
			'images' => $images,
			'carousel' => $carousel,
		]);
	}

	public function destroy(Request $request, Tenant $tenant, Media $media): RedirectResponse
	{
		// Safety: ensure the media belongs to THIS tenant + collection
		// abort_unless(
		// 	(int) $media->model_id === (int) $carousel->id
		// 	&& $media->model_type === Carousel::class
		// 	&& $media->collection_name === 'carousel_images',
		// 	403
		// );

		// This deletes the DB record AND the file(s) on disk (including conversions)
		$media->delete();

		return back()->with('success', 'Image deleted successfully.');
	}
}