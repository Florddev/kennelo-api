<?php

declare(strict_types=1);

namespace App\Services;

use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\HasMedia;

class MediaService
{
    public const COLLECTION_AVATAR = 'avatar';

    public const COLLECTION_IMAGES = 'images';

    public const CONVERSION_AVATAR_WEBP = 'avatar_webp';

    public const CONVERSION_IMAGE_WEBP = 'image_webp';

    public static function registerAvatarConversion(HasMedia $model): void
    {
        /** @var Conversion $conversion */
        $conversion = $model->addMediaConversion(self::CONVERSION_AVATAR_WEBP);
        $conversion->format('webp')->quality(82);
        $conversion->performOnCollections(self::COLLECTION_AVATAR)->queued();
    }

    public static function registerImagesConversion(HasMedia $model): void
    {
        /** @var Conversion $conversion */
        $conversion = $model->addMediaConversion(self::CONVERSION_IMAGE_WEBP);
        $conversion->performOnCollections(self::COLLECTION_IMAGES);
        $conversion->format('webp');
        $conversion->quality(76);
        $conversion->width(1920);
        $conversion->height(1920);
        $conversion->queued();
    }
}
