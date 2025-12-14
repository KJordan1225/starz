<?php

namespace App\Http\Controllers;

use App\Models\TenantImage;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Services\TenantService;

class TenantExclusiveImageController extends Controller
{
	public function index(Request $request): View
	{
		$tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();

        $tenant = Tenant::findOrFail($tenantId)->first();
        $carousel = TenantImage::query()
			->where('tenant_id', $tenantId)
			->latest()
			->first();
        
        // Only images in the 'carousel_images' collection
		$images = $carousel->getMedia('tenant_images');

		return view('tenant.exclusive.images.index', [
			'tenant' => $tenant,
			'images' => $images,
			'carousel' => $carousel,
            ]
        );
    }

	
	public function destroy(Request $request, Tenant $tenant, Media $media): RedirectResponse
	{
		
		// This deletes the DB record AND the file(s) on disk (including conversions)
		$media->delete();

		return back()->with('success', 'Image deleted successfully.');
	}
}
