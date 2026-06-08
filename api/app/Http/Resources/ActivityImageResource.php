<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin Media */
class ActivityImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = $this->hasGeneratedConversion(MediaService::CONVERSION_IMAGE_WEBP)
            ? $this->getUrl(MediaService::CONVERSION_IMAGE_WEBP)
            : $this->getUrl();

        return [
            'id' => $this->uuid,
            'url' => $url,
            'order' => $this->order_column,
            'created_at' => human_date($this->created_at),
        ];
    }
}
