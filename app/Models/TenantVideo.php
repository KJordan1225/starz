<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TenantVideo extends Model implements HasMedia
{
    use BelongsToTenant, InteractsWithMedia;

    protected $table = 'tenant_videos';

    protected $fillable = ['title', 'description', 'tenant_id'];

    public function registerMediaCollections(): void
    {
        // New media collection for videos
        $this->addMediaCollection('tenant_videos');

        $this->addMediaCollection('tenant_video_thumbnails');

    }
}

