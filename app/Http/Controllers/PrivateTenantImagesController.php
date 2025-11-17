<?php

namespace App\Http\Controllers;

use App\Models\TenantImage;
use Illuminate\Http\Request;

class PrivateTenantImagesController extends Controller
{
    /**
     * Show the upload/manage page for this tenant's carousel.
     */
    public function creatorImageEdit(Request $request)
    {
        $tenantId = tenant('id'); // from stancl/tenancy helper

        // One carousel row per tenant
        $carousel = TenantImage::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'title'       => 'Homepage Carousel',
                'description' => 'Carousel for ' . $tenantId,
            ]
        );

        // All images for this tenant's carousel
        $images = $carousel->getMedia('tenant_images');      

        return view('tenant.creator.images.edit', [
            'carousel' => $carousel,
            'images'   => $images,
        ]);
    }

    /**
     * Clear ONLY this tenant's carousel media.
     */
    public function creatorImageClear(Request $request)
    {
        $tenantId = tenant('id');

        $carousel = TenantImage::where('tenant_id', $tenantId)->first();

        if ($carousel) {
            $carousel->clearMediaCollection('tenant_images');
        }

        return back()->with('success', 'Creator images cleared for this microsite.');
    }

    /**
     * Handle upload of one or more carousel images for this tenant.
     */
    public function creatorImageStore(Request $request)
    {
        $tenantId = tenant('id');

        $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'images'      => ['required', 'array'],
            'images.*'    => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:102400'], // 10MB each
        ]);

        $carousel = TenantImage::firstOrCreate(
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
                    ->toMediaCollection('tenant_images');
            }
        }

        return back()->with('success', 'Carousel images uploaded for this microsite.');
    }

    /**
     * Show the media (images) for the current tenant
     */
    public function creatorImagePageTwo(Request $request)
    {
        // Get the tenant's ID from the tenancy helper
        $tenantId = tenant('id');

        // Retrieve the tenant's media (images) from the 'tenant_images' collection
        $tenant = TenantImage::where('tenant_id', $tenantId)->first();
       

        // Get all images in the 'tenant_images' media collection
        $images = $tenant
            ? $tenant->getMedia('tenant_images') // Fetch media from the tenant's collection
            : collect(); // If no media, return an empty collection
        
        // Pass the images to the view
        return view('tenant.creator.images.pagetwo', compact('images'));
    }


}



