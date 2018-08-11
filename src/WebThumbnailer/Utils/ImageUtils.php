<?php

namespace WebThumbnailer\Utils;

use WebThumbnailer\Exception\ImageConvertException;
use WebThumbnailer\Exception\NotAnImageException;

/**
 * Class ImageUtils
 *
 * Util class to manipulate GD images.
 *
 * @package WebThumbnailer\Utils
 */
class ImageUtils
{
    /**
     * Generate a clean PNG thumbnail from given image resource.
     *
     * It makes sure the downloaded image is really an image,
     * doesn't contain funny stuff, and it resize it to a standard size.
     * Resizing conserves proportions.
     *
     * @param string   $imageStr  Source image.
     * @param string   $target    Path where the generated thumb will be saved.
     * @param int      $maxWidth  Max width for the generated thumb.
     * @param int      $maxHeight Max height for the generated thumb.
     * @param bool     $crop      Will crop the image to a fixed size if true. Height AND width must be provided.
     *
     * @throws NotAnImageException   The given resource isn't an image.
     * @throws ImageConvertException Another error occured.
     */
    public static function generateThumbnail($imageStr, $target, $maxWidth, $maxHeight, $crop = false)
    {
        if (! touch($target)) {
            throw new ImageConvertException('Target file is not writable.');
        }

        if ($crop && ($maxWidth == 0  || $maxHeight == 0)) {
            throw new ImageConvertException('Both width and height must be provided for cropping');
        }

        $sourceImg = @imagecreatefromstring($imageStr);
        if ($sourceImg === false) {
            throw new NotAnImageException();
        }

        $originalWidth = imagesx($sourceImg);
        $originalHeight = imagesy($sourceImg);
        if ($maxWidth > $originalWidth) {
            $maxWidth = $originalWidth;
        }
        if ($maxHeight > $originalHeight) {
            $maxHeight = $originalHeight;
        }

        list($finalWidth, $finalHeight) = self::calcNewSize(
            $originalWidth,
            $originalHeight,
            $maxWidth,
            $maxHeight,
            $crop
        );

        $targetImg = imagecreatetruecolor($finalWidth, $finalHeight);
        if ($targetImg === false) {
            throw new ImageConvertException('Could not generate the thumbnail from source image.');
        }

        if (! imagecopyresized(
            $targetImg,
            $sourceImg,
            0,
            0,
            0,
            0,
            $finalWidth,
            $finalHeight,
            $originalWidth,
            $originalHeight
        )
        ) {
            @imagedestroy($sourceImg);
            @imagedestroy($targetImg);
            throw new ImageConvertException('Could not generate the thumbnail from source image.');
        }

        if ($crop) {
            $targetImg = imagecrop($targetImg, [
                'x' => $finalWidth >= $finalHeight ? ($finalWidth - $maxWidth) / 2 : 0,
                'y' => $finalHeight <= $finalWidth ? ($finalHeight - $maxHeight) / 2 : 0,
                'width' => $maxWidth,
                'height' => $maxHeight
            ]);
        }

        imagedestroy($sourceImg);
        imagejpeg($targetImg, $target);
        imagedestroy($targetImg);
    }

    /**
     * Calculate image new size to keep proportions depending on actual image size
     * and max width/height settings.
     *
     * @param int  $originalWidth  Image original width
     * @param int  $originalHeight Image original height
     * @param int  $maxWidth       Target image maximum width
     * @param int  $maxHeight      Target image maximum height
     * @param bool $crop           Is cropping enabled
     *
     * @return array [final width, final height]
     *
     * @throws ImageConvertException At least maxwidth or maxheight needs to be defined
     */
    public static function calcNewSize($originalWidth, $originalHeight, $maxWidth, $maxHeight, $crop)
    {
        if (empty($maxHeight) && empty($maxWidth)) {
            throw new ImageConvertException('At least maxwidth or maxheight needs to be defined.');
        }
        $diffWidth = !empty($maxWidth) ? $originalWidth - $maxWidth : false;
        $diffHeight = !empty($maxHeight) ? $originalHeight - $maxHeight : false;

        if (($diffHeight === false && $diffWidth !== false)
            || ($diffWidth > $diffHeight && ! $crop)
            || ($diffWidth < $diffHeight && $crop)
        ) {
            $finalWidth = $maxWidth;
            $finalHeight = $originalHeight * ($finalWidth / $originalWidth);
        } else {
            $finalHeight = $maxHeight;
            $finalWidth = $originalWidth * ($finalHeight / $originalHeight);
        }

        return [$finalWidth, $finalHeight];
    }

    /**
     * Check if a file extension is an image.
     *
     * @param string $ext file extension.
     *
     * @return bool true if it's an image extension, false otherwise.
     */
    public static function isImageExtension($ext)
    {
        $supportedImageFormats = ['png', 'jpg', 'jpeg', 'svg'];
        return in_array($ext, $supportedImageFormats);
    }

    /**
     * Check if a string is an image.
     *
     * @param string $content String to check.
     *
     * @return bool True if the content is image, false otherwise.
     */
    public static function isImageString($content)
    {
        return @imagecreatefromstring($content) !== false;
    }
}
