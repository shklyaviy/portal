<?php

namespace App\Support;

use RuntimeException;

class ImageResizer
{
    /**
     * Resize image in-place to max width and write a thumb sibling.
     *
     * @return array{main: string, thumb: string|null}
     */
    public static function process(string $absolutePath, int $maxWidth = 1600, int $thumbWidth = 400): array
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('GD extension is required for ImageResizer.');
        }

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Image not found: {$absolutePath}");
        }

        $info = @getimagesize($absolutePath);
        if ($info === false) {
            throw new RuntimeException("Unsupported image: {$absolutePath}");
        }

        [$width, $height, $type] = $info;
        $src = self::createFrom($absolutePath, $type);
        if ($src === false) {
            throw new RuntimeException("Unable to open image: {$absolutePath}");
        }

        if ($width > $maxWidth) {
            $newH = (int) round($height * ($maxWidth / $width));
            $resized = self::resample($src, $width, $height, $maxWidth, $newH);
            self::save($resized, $absolutePath, $type);
            imagedestroy($resized);
            $width = $maxWidth;
            $height = $newH;
            imagedestroy($src);
            $src = self::createFrom($absolutePath, $type);
        }

        $thumbPath = self::thumbPath($absolutePath);
        $thumbH = (int) max(1, round($height * ($thumbWidth / max(1, $width))));
        $thumb = self::resample($src, $width, $height, min($thumbWidth, $width), min($thumbH, $height));
        self::save($thumb, $thumbPath, $type);
        imagedestroy($thumb);
        imagedestroy($src);

        return [
            'main' => $absolutePath,
            'thumb' => $thumbPath,
        ];
    }

    public static function thumbPath(string $absolutePath): string
    {
        $dir = dirname($absolutePath);
        $name = pathinfo($absolutePath, PATHINFO_FILENAME);
        $ext = pathinfo($absolutePath, PATHINFO_EXTENSION);

        return $dir.DIRECTORY_SEPARATOR.$name.'_thumb'.($ext ? '.'.$ext : '');
    }

    /** @return \GdImage|resource|false */
    private static function createFrom(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => false,
        };
    }

    /**
     * @param  \GdImage|resource  $src
     * @return \GdImage|resource
     */
    private static function resample($src, int $srcW, int $srcH, int $dstW, int $dstH)
    {
        $dst = imagecreatetruecolor($dstW, $dstH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        return $dst;
    }

    /** @param  \GdImage|resource  $image */
    private static function save($image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, 85),
            IMAGETYPE_PNG => imagepng($image, $path, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($image, $path, 85) : imagejpeg($image, $path, 85),
            IMAGETYPE_GIF => imagegif($image, $path),
            default => throw new RuntimeException('Unsupported image type for save.'),
        };
    }
}
