<?php

declare(strict_types=1);

namespace WebThumbnailer\Application;

use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\CacheException;
use WebThumbnailer\Exception\DownloadException;
use WebThumbnailer\Exception\ImageConvertException;
use WebThumbnailer\Exception\IOException;
use WebThumbnailer\Exception\MissingRequirementException;
use WebThumbnailer\Exception\NotAnImageException;
use WebThumbnailer\Exception\ThumbnailNotFoundException;
use WebThumbnailer\Finder\Finder;
use WebThumbnailer\Finder\FinderFactory;
use WebThumbnailer\Utils\ApplicationUtils;
use WebThumbnailer\Utils\ImageUtils;
use WebThumbnailer\Utils\SizeUtils;
use WebThumbnailer\WebThumbnailer;

/**
 * Main application class, it will:
 *   - retrieve the thumbnail URL using the approriate finder,
 *   - in download mode, download the thumb and resize it,
 *   - use the cache.
 */
class Thumbnailer
{
    /** @var string Array key for download type option. */
    protected const DL_OPTION = 'dl';

    /** @var string User given URL, from where to generate a thumbnail. */
    protected $url;

    /** @var Finder instance. */
    protected $finder;

    /** @var mixed[] Thumbnailer user options. */
    protected $options;

    /** @var mixed[] .$_SERVER */
    protected $server;

    /**
     * @param string       $url     User given URL, from where to generate a thumbnail.
     * @param mixed[]      $options Thumbnailer user options.
     * @param mixed[]|null $server  $_SERVER.
     *
     * @throws MissingRequirementException
     * @throws BadRulesException
     * @throws IOException
     */
    public function __construct(string $url, array $options, ?array $server)
    {
        ApplicationUtils::checkExtensionRequirements(['gd']);
        ApplicationUtils::checkPHPVersion('5.6', PHP_VERSION);

        $this->url = $url;
        $this->server = $server;
        $this->finder = FinderFactory::getFinder($url);
        $this->finder->setUserOptions($options);
        $this->setOptions($options);
    }

    /**
     * Set Thumbnailer options from user input.
     *
     * @param mixed[] $options User options array.
     *
     * @throws BadRulesException
     * @throws IOException
     */
    protected function setOptions(array $options): void
    {
        static::checkOptions($options);

        $this->options[static::DL_OPTION] = ConfigManager::get('settings.default.download_mode', 'DOWNLOAD');

        foreach ($options as $key => $value) {
            // Download option.
            if (
                $value === WebThumbnailer::DOWNLOAD
                || $value === WebThumbnailer::HOTLINK
                || $value === WebThumbnailer::HOTLINK_STRICT
            ) {
                $this->options[static::DL_OPTION] = $value;
                break;
            }
        }

        // DL size option
        if (
            isset($options[WebThumbnailer::DOWNLOAD_MAX_SIZE])
            && is_int($options[WebThumbnailer::DOWNLOAD_MAX_SIZE])
        ) {
            $this->options[WebThumbnailer::DOWNLOAD_MAX_SIZE] = $options[WebThumbnailer::DOWNLOAD_MAX_SIZE];
        } else {
            $maxdl = ConfigManager::get('settings.default.max_img_dl', 4194304);
            $this->options[WebThumbnailer::DOWNLOAD_MAX_SIZE] = $maxdl;
        }

        // DL timeout option
        if (
            isset($options[WebThumbnailer::DOWNLOAD_TIMEOUT])
            && is_int($options[WebThumbnailer::DOWNLOAD_TIMEOUT])
        ) {
            $this->options[WebThumbnailer::DOWNLOAD_TIMEOUT] = $options[WebThumbnailer::DOWNLOAD_TIMEOUT];
        } else {
            $timeout = ConfigManager::get('settings.default.timeout', 30);
            $this->options[WebThumbnailer::DOWNLOAD_TIMEOUT] = $timeout;
        }

        if (isset($options[WebThumbnailer::NOCACHE])) {
            $this->options[WebThumbnailer::NOCACHE] = $options[WebThumbnailer::NOCACHE];
        }

        if (isset($options[WebThumbnailer::CROP])) {
            $this->options[WebThumbnailer::CROP] = $options[WebThumbnailer::CROP];
        } else {
            $this->options[WebThumbnailer::CROP] = false;
        }

        if (isset($options[WebThumbnailer::DEBUG])) {
            $this->options[WebThumbnailer::DEBUG] = $options[WebThumbnailer::DEBUG];
        } else {
            $this->options[WebThumbnailer::DEBUG] = false;
        }

        // Image size
        $this->setSizeOptions($options);
    }

    /**
     * Make sure user options are coherent.
     *   - Only one thumb mode can be defined.
     *
     * @param mixed[] $options User options array.
     *
     * @return bool True if the check is successful.
     *
     * @throws BadRulesException Invalid options.
     */
    protected static function checkOptions(array $options): bool
    {
        $incompatibleFlagsList = [
            [WebThumbnailer::DOWNLOAD, WebThumbnailer::HOTLINK, WebThumbnailer::HOTLINK_STRICT],
        ];

        foreach ($incompatibleFlagsList as $incompatibleFlags) {
            if (count(array_intersect($incompatibleFlags, $options)) > 1) {
                $error = 'Only one of these flags can be set between: ';
                foreach ($incompatibleFlags as $flag) {
                    $error .= $flag . ' ';
                }
                throw new BadRulesException($error);
            }
        }

        return true;
    }

    /**
     * Set specific size option, allowing 'meta' size SMALL, MEDIUM, etc.
     *
     * @param mixed[] $options User options array.
     *
     * @throws BadRulesException
     * @throws IOException
     */
    protected function setSizeOptions(array $options): void
    {
        foreach ([WebThumbnailer::MAX_WIDTH, WebThumbnailer::MAX_HEIGHT] as $parameter) {
            $value = 0;
            if (!empty($options[$parameter])) {
                if (SizeUtils::isMetaSize((string) $options[$parameter])) {
                    $value = SizeUtils::getMetaSize((string) $options[$parameter]);
                } elseif (is_int($options[$parameter]) || ctype_digit($options[$parameter])) {
                    $value = $options[$parameter];
                }
            }

            $this->options[$parameter] = $value;
        }

        if ($this->options[WebThumbnailer::MAX_WIDTH] == 0 && $this->options[WebThumbnailer::MAX_HEIGHT] == 0) {
            $maxwidth = ConfigManager::get('settings.default.max_width', 160);
            $this->options[WebThumbnailer::MAX_WIDTH] = $maxwidth;
            $maxheight = ConfigManager::get('settings.default.max_height', 160);
            $this->options[WebThumbnailer::MAX_HEIGHT] = $maxheight;
        }
    }

    /**
     * Get the thumbnail according to download mode:
     *   - HOTLINK_STRICT: will only try to get hotlink thumb.
     *   - HOTLINK: will retrieve hotlink if available, or download otherwise.
     *   - DOWNLOAD: will download the thumb, resize it, and store it in cache.
     *
     * Default mode: DOWNLOAD.
     *
     * @return string|false The thumbnail URL (relative if downloaded), or false if no thumb found.
     *
     * @throws DownloadException
     * @throws ImageConvertException
     * @throws NotAnImageException
     * @throws ThumbnailNotFoundException
     * @throws IOException
     * @throws CacheException
     * @throws BadRulesException
     */
    public function getThumbnail()
    {
        $cache = CacheManager::getCacheFilePath(
            $this->url,
            $this->finder->getDomain(),
            CacheManager::TYPE_FINDER,
            $this->options[WebThumbnailer::MAX_WIDTH],
            $this->options[WebThumbnailer::MAX_HEIGHT]
        );
        // Loading Finder result from cache if enabled and valid to prevent useless requests.
        if (
            empty($this->options[WebThumbnailer::NOCACHE])
            && CacheManager::isCacheValid($cache, $this->finder->getDomain(), CacheManager::TYPE_FINDER)
        ) {
            $thumbUrl = file_get_contents($cache);
        } else {
            $thumbUrl = $this->finder->find();
            $thumbUrl = $thumbUrl !== false ? html_entity_decode($thumbUrl) : $thumbUrl;
            file_put_contents($cache, $thumbUrl);
        }

        if (empty($thumbUrl)) {
            $error = 'No thumbnail could be found using ' . $this->finder->getName() . ' finder: ' . $this->url;
            throw new ThumbnailNotFoundException($error);
        }

        // Only hotlink, find() is enough.
        if ($this->options[static::DL_OPTION] === WebThumbnailer::HOTLINK_STRICT) {
            return $this->thumbnailStrictHotlink($thumbUrl);
        }
        // Hotlink if available, download otherwise.
        if ($this->options[static::DL_OPTION] === WebThumbnailer::HOTLINK) {
            return $this->thumbnailHotlink($thumbUrl);
        } else { // Download
            return $this->thumbnailDownload($thumbUrl);
        }
    }

    /**
     * Get thumbnails in HOTLINK_STRICT mode.
     * Won't work for domains which doesn't allow hotlinking.
     *
     * @param string $thumbUrl Thumbnail URL, generated by the Finder.
     *
     * @return string The thumbnail URL.
     *
     * @throws ThumbnailNotFoundException Hotlink is disabled for this domains.
     */
    protected function thumbnailStrictHotlink(string $thumbUrl): string
    {
        if (!$this->finder->isHotlinkAllowed()) {
            throw new ThumbnailNotFoundException('Hotlink is not supported for this URL.');
        }

        return $thumbUrl;
    }

    /**
     * Get thumbnails in HOTLINK mode.
     *
     * @param string $thumbUrl Thumbnail URL, generated by the Finder.
     *
     * @return string|false The thumbnail URL, or false if no thumb found.
     *
     * @throws DownloadException
     * @throws ImageConvertException
     * @throws NotAnImageException
     * @throws IOException
     * @throws CacheException
     * @throws BadRulesException
     */
    protected function thumbnailHotlink(string $thumbUrl)
    {
        if (!$this->finder->isHotlinkAllowed()) {
            return $this->thumbnailDownload($thumbUrl);
        }

        return $thumbUrl;
    }

    /**
     * Get thumbnails in HOTLINK mode.
     *
     * @param string $thumbUrl Thumbnail URL, generated by the Finder.
     *
     * @return string|false The thumbnail URL, or false if no thumb found.
     *
     * @throws DownloadException     Couldn't download the image
     * @throws ImageConvertException Thumbnail not generated
     * @throws NotAnImageException
     * @throws IOException
     * @throws CacheException
     * @throws BadRulesException
     */
    protected function thumbnailDownload(string $thumbUrl)
    {
        // Cache file path.
        $thumbPath = CacheManager::getCacheFilePath(
            $thumbUrl,
            $this->finder->getDomain(),
            CacheManager::TYPE_THUMB,
            $this->options[WebThumbnailer::MAX_WIDTH],
            $this->options[WebThumbnailer::MAX_HEIGHT],
            $this->options[WebThumbnailer::CROP]
        );

        // If the cache is valid, serve it.
        if (
            empty($this->options[WebThumbnailer::NOCACHE])
            && CacheManager::isCacheValid(
                $thumbPath,
                $this->finder->getDomain(),
                CacheManager::TYPE_THUMB
            )
        ) {
            return $thumbPath;
        }

        $webaccess = WebAccessFactory::getWebAccess($thumbUrl);

        // Download the thumb.
        list($headers, $data) = $webaccess->getContent(
            $thumbUrl,
            $this->options[WebThumbnailer::DOWNLOAD_TIMEOUT],
            $this->options[WebThumbnailer::DOWNLOAD_MAX_SIZE]
        );

        if (strpos($headers[0], '200') === false) {
            throw new DownloadException(
                'Unreachable thumbnail URL. HTTP ' . $headers[0] . '.' . PHP_EOL .
                ' - thumbnail URL: ' . $thumbUrl
            );
        }

        if (empty($data)) {
            throw new DownloadException('Couldn\'t download the thumbnail at ' . $thumbUrl);
        }

        // Resize and save it locally.
        ImageUtils::generateThumbnail(
            $data,
            $thumbPath,
            $this->options[WebThumbnailer::MAX_WIDTH],
            $this->options[WebThumbnailer::MAX_HEIGHT],
            $this->options[WebThumbnailer::CROP]
        );

        if (!is_file($thumbPath)) {
            throw new ImageConvertException('Thumbnail was not generated.');
        }

        return $thumbPath;
    }
}
