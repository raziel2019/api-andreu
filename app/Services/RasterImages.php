<?php

namespace App\Services;

use Imagick;
use RuntimeException;

class RasterImages
{
    public function encode(string $bytes, string $mime): string
    {
        if ($mime === 'image/webp' && ! function_exists('imagewebp')) {
            return $this->webp($bytes);
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw new RuntimeException('Invalid raster image.');
        }
        try {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            ob_start();
            $encoded = match ($mime) {
                'image/png' => imagepng($image),
                'image/jpeg' => imagejpeg($image, null, 90),
                'image/webp' => imagewebp($image, null, 90),
                default => false,
            };
            $cleanBytes = ob_get_clean();
            if (! $encoded || ! $cleanBytes) {
                throw new RuntimeException('Raster encoding failed.');
            }

            return $cleanBytes;
        } finally {
            imagedestroy($image);
        }
    }

    private function webp(string $bytes): string
    {
        if (! class_exists(Imagick::class) || Imagick::queryFormats('WEBP') === [] || substr($bytes, 0, 4) !== 'RIFF' || substr($bytes, 8, 4) !== 'WEBP') {
            throw new RuntimeException('WebP decoder unavailable or invalid WebP.');
        }
        $limits = [Imagick::RESOURCETYPE_MEMORY => 128 * 1024 * 1024, Imagick::RESOURCETYPE_MAP => 128 * 1024 * 1024, Imagick::RESOURCETYPE_DISK => 0];
        $previous = [];
        foreach ($limits as $resource => $limit) {
            $previous[$resource] = Imagick::getResourceLimit($resource);
            Imagick::setResourceLimit($resource, min($previous[$resource], $limit));
        }
        $image = new Imagick;
        try {
            $image->readImageBlob($bytes, 'image.webp');
            if ($image->getNumberImages() !== 1 || $image->getImageFormat() !== 'WEBP') {
                throw new RuntimeException('Only still WebP images are supported.');
            }
            $image->stripImage();
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality(90);

            return $image->getImageBlob();
        } finally {
            $image->clear();
            foreach ($previous as $resource => $limit) {
                Imagick::setResourceLimit($resource, $limit);
            }
        }
    }
}
