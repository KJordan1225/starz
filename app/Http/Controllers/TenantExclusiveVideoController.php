<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantVideo;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TenantExclusiveVideoController extends Controller
{
    public function index(Request $request): View
    {
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();

        // Fix: findOrFail already returns a model (no ->first())
        $tenant = Tenant::findOrFail($tenantId);

        // If you’re storing multiple videos on a single "playlist" record:
        $videoOwner = TenantVideo::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->first();

        // Safeguard if nothing exists yet
        $videos = $videoOwner
            ? $videoOwner->getMedia('tenant_videos')    // collection name; adjust if needed
            : collect();

        return view('tenant.exclusive.videos.index', [
            'tenant'     => $tenant,
            'videos'     => $videos,
            'videoOwner' => $videoOwner,
        ]);
    }

    /**
     * Delete a single video media item (DB row + file + conversions)
     */
    public function destroy(Request $request, Tenant $tenant, Media $media): RedirectResponse
    {
        // OPTIONAL: extra safety – ensure media belongs to this tenant
        if ($media->model && method_exists($media->model, 'tenant_id')) {
            if ($media->model->tenant_id !== $tenant->id) {
                abort(403, 'You are not allowed to delete this video.');
            }
        }

        // This deletes the DB record AND the file(s) on disk (including conversions)
        $media->delete();

        return back()->with('success', 'Video deleted successfully.');
    }
}
