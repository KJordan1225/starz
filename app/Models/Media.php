<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant; // your existing trait
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    use BelongsToTenant;

    // If your BelongsToTenant already sets tenant_id on creating,
    // you may not need extra logic. If not, you can do:

    protected static function booted()
    {
        static::creating(function ($media) {
            if (tenancy()->initialized) {
                $media->tenant_id = tenant('id');
            }
        });
    }
}

