<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tenant_Image extends Model implements HasMedia
{
    use BelongsToTenant, InteractsWithMedia;

    protected $table = 'tenant_images';

    protected $fillable = ['title', 'description', 'tenant_id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('tenant_images')
            ->useDisk('public') // Optional, specify disk if needed
            ->acceptsFile(function (File $file) {
                return in_array($file->mimeType(), ['image/jpeg', 'image/png', 'image/gif']);
            });

    }
}

