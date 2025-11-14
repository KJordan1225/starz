<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Carousel;
use Illuminate\Http\Request;

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
            ? $carousel->getMedia('carousel_images')->take(3) // or ->take(N)
            : collect();

        return view('tenant.home', [
            'carouselImages' => $carouselImages,
            'tenantId'       => $tenantId,
        ]);
    }
}
