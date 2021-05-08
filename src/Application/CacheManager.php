<?php

declare(strict_types=1);

namespace WebThumbnailer\Application;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\CacheException;
use WebThumbnailer\Exception\IOException;
use WebThumbnailer\Utils\FileUtils;
use WebThumbnailer\Utils\TemplatePolyfill;

/**
 * Handles file caching using static methods.
 * There are 2 types of cache:
 *  - thumb: thumbnail images after being resized.
 *  - finder: url->thumbnail url resolution is also cached.
 * Cache files are organized by domains name, and have a unique name
 * based on their URL, max-width and max-height.
 *
 * Cache duration is defined in JSON settings.
 */
class CacheManager
{
    /** Thumbnails image cache. */
    public const TYPE_THUMB  = 'thumb';

    /** Finder cache. */
    public const TYPE_FINDER = 'finder';

    /** @var string Clean filename, used to clean directories periodically. */
    protected static $CLEAN_FILE = '.clean';

    /**
     * Returns the cache path according to the given type.
     *
     * @param string $type    Type of cache.
     * @param bool   $rebuilt Flag to tell if a rebuild tentative has been done.
     *
     * @return string Cache path.
     *
     * @throws IOException Type not found.
     * @throws CacheException
     * @throws BadRulesException
     */
    public static function getCachePath(string $type, bool $rebuilt = false): string
    {
        static::checkCacheType($type);
        $cache = ConfigManager::get('settings.path.cache', 'cache/');
        $path = FileUtils::getPath($cache, $type);
        if (!$path && !$rebuilt) {
            static::rebuildCacheFolders();
            return static::getCachePath($type, true);
        } elseif (!$path) {
            throw new IOException('Cache folders are not writable: ' . $cache);
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
     * @param bool|null  $crop   Crop enabled or not.
     *
     * @return string Absolute file path.
     *
     * @throws IOException
     * @throws CacheException
     * @throws BadRulesException
     */
    public static function getCacheFilePath(
        string $url,
        string $domain,
        string $type,
        $width = 0,
        $height = 0,
        ?bool $crop = false
    ): string {
        $domainHash = static::getDomainHash($domain);

        static::createDomainThumbCacheFolder($domainHash, $type);

        $domainFolder = FileUtils::getPath(static::getCachePath($type), $domainHash);
        if ($domainFolder === false) {
            throw new CacheException(sprintf(
                'Could not find cache path for type %s and domain hash %s',
                $type,
                $domainHash
            ));
        }

        if ($type === static::TYPE_THUMB) {
            $suffix = $width . $height . ($crop ? '1' : '0') . '.jpg';
        } else {
            $suffix = $width . $height;
        }

        return $domainFolder . static::getThumbFilename($url) . $suffix;
    }

    /**
     * Check whether a valid cache file exists or not.
     * Also check that that file is still valid.
     *
     * Support endless cache using a negative value.
     *
     * @param string $cacheFile Cache file path.
     * @param string $domain    Domain concerned.
     * @param string $type      Type of cache.
     *
     * @return bool true if valid cache exists, false otherwise.
     *
     * @throws CacheException
     * @throws IOException
     * @throws BadRulesException
     */
    public static function isCacheValid(string $cacheFile, string $domain, string $type): bool
    {
        $out = false;
        $cacheDuration = ConfigManager::get('settings.cache_duration', 3600 * 24 * 31);

        if (
            is_readable($cacheFile)
            && ($cacheDuration < 0 || (time() - filemtime($cacheFile)) < $cacheDuration)
        ) {
            $out = true;
        } else {
            static::createDomainThumbCacheFolder(static::getDomainHash($domain), $type);
        }

        return $out;
    }

    /**
     * Create the domains folder for thumb cache if it doesn't exists.
     *
     * @param string $domain Domain used.
     * @param string $type   Type of cache.
     *
     * @throws CacheException
     * @throws IOException
     * @throws BadRulesException
     */
    protected static function createDomainThumbCacheFolder(string $domain, string $type): void
    {
        $cachePath = static::getCachePath($type);
        $domainFolder = $cachePath . $domain;
        if (!file_exists($domainFolder)) {
            mkdir($domainFolder, 0775, false);
            touch($domainFolder . '/' . static::$CLEAN_FILE);
        }
        static::createHtaccessFile($cachePath, $type === static::TYPE_THUMB);
    }

    /**
     * Create a .htaccess file for Apache webserver if it doesn't exists.
     * The folder should be allowed for thumbs, and denied for finder's cache.
     *
     * @param string $path    Cache directory path
     * @param bool   $allowed Weather the access is allowed or not
     *
     * @throws BadRulesException
     * @throws IOException
     */
    protected static function createHtaccessFile(string $path, bool $allowed = false): void
    {
        $apacheVersion = ConfigManager::get('settings.apache_version', '');
        $htaccessFile = $path . '.htaccess';
        if (file_exists($htaccessFile)) {
            return;
        }
        $templateFile = file_exists(FileUtils::RESOURCES_PATH . 'htaccess' . $apacheVersion . '_template')
            ? FileUtils::RESOURCES_PATH . 'htaccess' . $apacheVersion . '_template'
            : FileUtils::RESOURCES_PATH . 'htaccess_template';
        $template = TemplatePolyfill::get($templateFile);
        $template->setVar([
            'new_all' => $allowed ? 'granted' : 'denied',
            'old_allow' => $allowed ? 'all' : 'none',
            'old_deny' => $allowed ? 'none' : 'all',
        ]);
        file_put_contents($htaccessFile, $template->render());
    }

    /**
     * Get the cache filename according to the given URL.
     * Using a sha1 hash to get unique valid filenames.
     *
     * @param string $url Thumbnail URL.
     *
     * @return string Thumb filename.
     */
    protected static function getThumbFilename(string $url): string
    {
        return hash('sha1', $url);
    }

    /**
     * Make sure that the cache type exists.
     *
     * @param string $type Cache type.
     *
     * @return bool True if the check was successful.
     *
     * @throws CacheException Cache type doesn't exists.
     */
    protected static function checkCacheType(string $type): bool
    {
        if ($type != static::TYPE_THUMB && $type != static::TYPE_FINDER) {
            throw new CacheException('Unknown cache type ' . $type);
        }

        return true;
    }

    /**
     * Recreates cache folders just in case the user delete them.
     *
     * @throws BadRulesException
     * @throws IOException
     */
    protected static function rebuildCacheFolders(): void
    {
        $mainFolder = ConfigManager::get('settings.path.cache', 'cache/');
        if (! is_dir($mainFolder)) {
            mkdir($mainFolder, 0755);
        }
        if (! is_dir($mainFolder . static::TYPE_THUMB)) {
            mkdir($mainFolder . static::TYPE_THUMB, 0755);
        }
        if (! is_readable($mainFolder . static::TYPE_THUMB . DIRECTORY_SEPARATOR . '.gitkeep')) {
            touch($mainFolder . static::TYPE_THUMB . DIRECTORY_SEPARATOR . '.gitkeep');
        }
        if (! is_dir($mainFolder . static::TYPE_FINDER)) {
            mkdir($mainFolder . static::TYPE_FINDER, 0755);
        }
        if (! is_readable($mainFolder . static::TYPE_THUMB . DIRECTORY_SEPARATOR . '.gitkeep')) {
            touch($mainFolder . static::TYPE_FINDER . DIRECTORY_SEPARATOR . '.gitkeep');
        }
    }

    /**
     * Return the hashed folder name for a given domain.
     *
     * @param string $domain name
     *
     * @return string hash
     */
    protected static function getDomainHash(string $domain): string
    {
        return md5($domain);
    }
}
