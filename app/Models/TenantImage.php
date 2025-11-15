<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TenantImage extends Model implements HasMedia
{
    use BelongsToTenant, InteractsWithMedia;

    protected $table = 'tenant_images';

    protected $fillable = ['title', 'description', 'tenant_id'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('tenant_images');
    }


}

