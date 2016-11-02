<?php

namespace WebThumbnailer\Application;


use WebThumbnailer\Utils\FileUtils;

/**
 * Class CacheManager
 *
 * Handles file caching using static methods.
 * There are 2 types of cache:
 *  - thumb: thumbnail images after being resized.
 *  - finder: url->thumbnail url resolution is also cached.
 * Cache files are organized by domains name, and have a unique name
 * based on their URL, max-width and max-height.
 *
 * Cache duration is defined in JSON settings.
 *
 * @package WebThumbnailer\Application
 */
class CacheManager
{
    /**
     * Thumbnails image cache.
     */
    const TYPE_THUMB  = 'thumb';
    /**
     * Finder cache.
     */
    const TYPE_FINDER = 'finder';

    /**
     * @var string Clean filename, used to clean directories periodically.
     */
    protected static $CLEAN_FILE = '.clean';

    /**
     * Returns the cache path according to the given type.
     *
     * @param string $type    Type of cache.
     * @param bool   $rebuilt Flag to tell if a rebuild tentative has been done.
     *
     * @return string Cache absolute path.
     *
     * @throws \Exception Type not found.
     */
    public static function getCachePath($type, $rebuilt = false)
    {
        self::checkCacheType($type);
        $cache = ConfigManager::get('settings.path.cache', 'cache/');
        $path = FileUtils::getPath($cache, $type);
        if (!$path && !$rebuilt) {
            self::rebuildCacheFolders();
            return self::getCachePath($type, true);
        } else if (!$path) {
            throw new \Exception('Cache folders are not writable.');
        }
        return $path;
    }

    /**
     * Get a thumb cache file absolute path.
     *
     * @param string     $url    URL of the thumbnail (unique file per URL).
     * @param string     $domain Domain concerned.
     * @param string     $type   Type of cache.
     * @param int|string $width  User setting for image width.
     * @param int|string $height User setting for image height.
     * @param bool       $crop   Crop enabled or not.
     *
     * @return string Absolute file path.
     */
    public static function getCacheFilePath($url, $domain, $type, $width = 0, $height = 0, $crop = false)
    {
        self::createDomainThumbCacheFolder($domain, $type);
        $domainFolder = FileUtils::getPath(self::getCachePath($type), $domain);
        if ($type === self::TYPE_THUMB) {
            $suffix = $width . $height . ($crop ? '1' : '0') .'.png';
        } else {
            $suffix = '';
        }
        return $domainFolder . self::getThumbFilename($url) . $suffix;
    }

    /**
     * Check whether a valid cache file exists or not.
     * Also check that that file is still valid.
     *
     * @param string $cacheFile Cache file path.
     * @param string $domain Domain concerned.
     * @param string $type   Type of cache.
     *
     * @return bool true if valid cache exists, false otherwise.
     */
    public static function isCacheValid($cacheFile, $domain, $type) {
        $out = false;
        $cacheDuration = ConfigManager::get('settings.cache_duration', 3600*24*31);
        if (is_readable($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
            $out = true;
        } else {
            self::createDomainThumbCacheFolder($domain, $type);
        }

        return $out;
    }

    /**
     * Create the domains folder for thumb cache if it doesn't exists.
     *
     * @param string $domain Domain used.
     * @param string $type   Type of cache.
     */
    protected static function createDomainThumbCacheFolder($domain, $type)
    {
        $domainFolder = self::getCachePath($type) . $domain;
        if (!file_exists($domainFolder)) {
            mkdir($domainFolder, 0775, false);
            touch($domainFolder . '/' . self::$CLEAN_FILE);
        }
    }

    /**
     * Get the cache filename according to the given URL.
     * Using a sha1 hash to get unique valid filenames.
     *
     * @param string $url Thumbnail URL.
     *
     * @return string Thumb filename.
     */
    protected static function getThumbFilename($url)
    {
        return hash('sha1', $url);
    }

    /**
     * Make sure that the cache type exists.
     *
     * @param string $type Cache type.
     *
     * @throws \Exception Cache type doesn't exists.
     */
    protected static function checkCacheType($type)
    {
        if ($type != self::TYPE_THUMB && $type != self::TYPE_FINDER) {
            throw new \Exception('Unknown cache type '. $type);
        }
    }

    /**
     * Recreates cache folders just in case the user delete them.
     */
    protected static function rebuildCacheFolders()
    {
        $mainFolder = ConfigManager::get('settings.path.cache', 'cache/');
        @mkdir($mainFolder, 0755);
        @mkdir($mainFolder.self::TYPE_THUMB, 0755);
        @touch($mainFolder.self::TYPE_THUMB.DIRECTORY_SEPARATOR.'.gitkeep');
        @mkdir($mainFolder.self::TYPE_FINDER, 0755);
        @touch($mainFolder.self::TYPE_FINDER.DIRECTORY_SEPARATOR.'.gitkeep');
    }
}
