<?php

namespace WebThumbnailer\Utils;

use WebThumbnailer\Application\ConfigManager;
use WebThumbnailer\WebThumbnailer;

/**
 * Class SizeUtils
 *
 * Handles 'meta' size operation.
 *
 * Fixed sizes:
 *   - SMALL=160px
 *   - MEDIUM=320px
 *   - LARGE=640px
 *
 * @package WebThumbnailer\Utils
 */
class SizeUtils
{
    /**
     * Convert a 'meta' size to pixel size.
     *
     * Default value if unknown: 160px.
     *
     * @param string $size Meta size to convert.
     *
     * @return int the size in pixels
     *
     * @throws \WebThumbnailer\Exception\BadRulesException
     * @throws \WebThumbnailer\Exception\IOException
     */
    public static function getMetaSize($size)
    {
        switch ($size) {
            case WebThumbnailer::SIZE_SMALL:
                return (int) ConfigManager::get('settings.size_small', 160);
            case WebThumbnailer::SIZE_MEDIUM:
                return (int) ConfigManager::get('settings.size_medium', 320);
            case WebThumbnailer::SIZE_LARGE:
                return (int) ConfigManager::get('settings.size_large', 640);
            default:
                return (int) ConfigManager::get('settings.size_small', 160);
        }
    }

    /**
     * Check if a string is a meta size.
     *
     * @param string $size the string to test.
     *
     * @return boolean true|false.
     */
    public static function isMetaSize($size)
    {
        $metaSize = array (
            WebThumbnailer::SIZE_SMALL,
            WebThumbnailer::SIZE_MEDIUM,
            WebThumbnailer::SIZE_LARGE
        );
        return in_array($size, $metaSize);
    }
}
