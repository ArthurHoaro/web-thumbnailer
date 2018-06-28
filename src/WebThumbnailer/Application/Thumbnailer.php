<?php

namespace WebThumbnailer\Application;

use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\DownloadException;
use WebThumbnailer\Exception\ImageConvertException;
use WebThumbnailer\Exception\IOException;
use WebThumbnailer\Exception\NotAnImageException;
use WebThumbnailer\Exception\ThumbnailNotFoundException;
use WebThumbnailer\Finder\Finder;
use WebThumbnailer\Finder\FinderFactory;
use WebThumbnailer\Utils\ApplicationUtils;
use WebThumbnailer\Utils\ImageUtils;
use WebThumbnailer\Utils\SizeUtils;
use WebThumbnailer\Utils\UrlUtils;
use WebThumbnailer\WebThumbnailer;

/**
 * Class Thumbnailer
 *
 * Main application class, it will:
 *   - retrieve the thumbnail URL using the approriate finder,
 *   - in download mode, download the thumb and resize it,
 *   - use the cache.
 *
 * @package WebThumbnailer\Application
 */
class Thumbnailer
{
    /**
     * @var string Array key for download type option.
     */
    protected static $DL_OPTION = 'dl';

    /**
     * @var string User given URL, from where to generate a thumbnail.
     */
    protected $url;

    /**
     * @var Finder instance.
     */
    protected $finder;

    /**
     * @var array Thumbnailer user options.
     */
    protected $options;

    /**
     * @var array $_SERVER.
     */
    protected $server;

    /**
     * Thumbnailer constructor.
     *
     * @param string $url User given URL, from where to generate a thumbnail.
     * @param array $options Thumbnailer user options.
     * @param array $server $_SERVER.
     *
     * @throws \WebThumbnailer\Exception\MissingRequirementException
     * @throws \WebThumbnailer\Exception\UnsupportedDomainException
     */
    public function __construct($url, $options, $server)
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
     * Get the thumbnail according to download mode:
     *   - HOTLINK_STRICT: will only try to get hotlink thumb.
     *   - HOTLINK: will retrieve hotlink if available, or download otherwise.
     *   - DOWNLOAD: will download the thumb, resize it, and store it in cache.
     *
     * Default mode: DOWNLOAD.
     *
     * @return string|bool The thumbnail URL (relative if downloaded), or false if no thumb found.
     *
     * @throws DownloadException
     * @throws ImageConvertException
     * @throws NotAnImageException
     * @throws ThumbnailNotFoundException
     * @throws IOException
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
        if (empty($this->options[WebThumbnailer::NOCACHE])
            && CacheManager::isCacheValid($cache, $this->finder->getDomain(), CacheManager::TYPE_FINDER)
        ) {
            $thumburl = file_get_contents($cache);
        } else {
            $thumburl = $this->finder->find();
            $thumburl = html_entity_decode($thumburl);
            file_put_contents($cache, $thumburl);
        }

        if (empty($thumburl)) {
            $error = 'No thumbnail could be found using '. $this->finder->getName() .' finder: '. $this->url;
            throw new ThumbnailNotFoundException($error);
        }

        // Only hotlink, find() is enough.
        if ($this->options[self::$DL_OPTION] === WebThumbnailer::HOTLINK_STRICT) {
            return $this->thumbnailStrictHotlink($thumburl);
        }
        // Hotlink if available, download otherwise.
        if ($this->options[self::$DL_OPTION] === WebThumbnailer::HOTLINK) {
            return $this->thumbnailHotlink($thumburl);
        } // Download
        else {
            return $this->thumbnailDownload($thumburl);
        }
    }

    /**
     * Get thumbnails in HOTLINK_STRICT mode.
     * Won't work for domains which doesn't allow hotlinking.
     *
     * @param string $thumburl Thumbnail URL, generated by the Finder.
     *
     * @return string The thumbnail URL, or false if hotlinking is disabled.
     *
     * @throws ThumbnailNotFoundException Hotlink is disabled for this domains.
     */
    protected function thumbnailStrictHotlink($thumburl)
    {
        if (! $this->finder->isHotlinkAllowed()) {
            throw new ThumbnailNotFoundException('Hotlink is not supported for this URL.');
        }
        return $thumburl;
    }

    /**
     * Get thumbnails in HOTLINK mode.
     *
     * @param string $thumburl Thumbnail URL, generated by the Finder.
     *
     * @return string The thumbnail URL, or false if no thumb found.
     *
     * @throws DownloadException
     * @throws ImageConvertException
     * @throws NotAnImageException
     * @throws IOException
     */
    protected function thumbnailHotlink($thumburl)
    {
        if (! $this->finder->isHotlinkAllowed()) {
            return $this->thumbnailDownload($thumburl);
        }
        return $thumburl;
    }

    /**
     * Get thumbnails in HOTLINK mode.
     *
     * @param string $thumburl Thumbnail URL, generated by the Finder.
     *
     * @return string|bool The thumbnail URL, or false if no thumb found.
     *
     * @throws DownloadException     Couldn't download the image
     * @throws ImageConvertException Thumbnail not generated
     * @throws NotAnImageException
     * @throws IOException
     */
    protected function thumbnailDownload($thumburl)
    {
        // Cache file path.
        $thumbPath = CacheManager::getCacheFilePath(
            $thumburl,
            $this->finder->getDomain(),
            CacheManager::TYPE_THUMB,
            $this->options[WebThumbnailer::MAX_WIDTH],
            $this->options[WebThumbnailer::MAX_HEIGHT],
            $this->options[WebThumbnailer::CROP],
            $this->options[WebThumbnailer::PATH_TYPE]
        );

        // If the cache is valid, serve it.
        if (empty($this->options[WebThumbnailer::NOCACHE])
            && CacheManager::isCacheValid(
                $thumbPath,
                $this->finder->getDomain(),
                CacheManager::TYPE_THUMB
            )
        ) {
            return UrlUtils::generateRelativeUrlFromPath($this->server, $thumbPath);
        }

        $webaccess = WebAccessFactory::getWebAccess($thumburl);

        // Download the thumb.
        list($headers, $data) = $webaccess->getContent(
            $thumburl,
            $this->options[WebThumbnailer::DOWNLOAD_TIMEOUT],
            $this->options[WebThumbnailer::DOWNLOAD_MAX_SIZE]
        );

        if (strpos($headers[0], '200') === false) {
            throw new DownloadException(
                'Unreachable thumbnail URL. HTTP '. $headers[0] .'.'. PHP_EOL .
                ' - thumbnail URL: '. $thumburl
            );
        }

        if (empty($data)) {
            throw new DownloadException('Couldn\'t download the thumbnail at '. $thumburl);
        }

        // Resize and save it locally.
        ImageUtils::generateThumbnail(
            $data,
            $thumbPath,
            $this->options[WebThumbnailer::MAX_WIDTH],
            $this->options[WebThumbnailer::MAX_HEIGHT],
            $this->options[WebThumbnailer::CROP]
        );

        if (! is_file($thumbPath)) {
            throw new ImageConvertException('Thumbnail was not generated.');
        }

        if ($this->options[WebThumbnailer::PATH_TYPE] === WebThumbnailer::PATH_RELATIVE) {
            return UrlUtils::generateRelativeUrlFromPath($this->server, $thumbPath);
        }
        return $thumbPath;
    }

    /**
     * Set Thumbnailer options from user input.
     *
     * @param array $options User options array.
     *
     * @throws BadRulesException
     */
    protected function setOptions($options)
    {
        self::checkOptions($options);

        $this->options[self::$DL_OPTION] = ConfigManager::get('settings.default.download_mode', 'DOWNLOAD');

        foreach ($options as $key => $value) {
            // Download option.
            if ($value === WebThumbnailer::DOWNLOAD
                || $value === WebThumbnailer::HOTLINK
                || $value === WebThumbnailer::HOTLINK_STRICT
            ) {
                $this->options[self::$DL_OPTION] = $value;
                break;
            }
        }

        // DL size option
        if (isset($options[WebThumbnailer::DOWNLOAD_MAX_SIZE])
            && is_int($options[WebThumbnailer::DOWNLOAD_MAX_SIZE])
        ) {
            $this->options[WebThumbnailer::DOWNLOAD_MAX_SIZE] = $options[WebThumbnailer::DOWNLOAD_MAX_SIZE];
        } else {
            $maxdl = ConfigManager::get('settings.default.max_img_dl', 4194304);
            $this->options[WebThumbnailer::DOWNLOAD_MAX_SIZE] = $maxdl;
        }

        // DL timeout option
        if (isset($options[WebThumbnailer::DOWNLOAD_TIMEOUT])
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

        if (isset($options[WebThumbnailer::PATH_TYPE])) {
            $this->options[WebThumbnailer::PATH_TYPE] = $options[WebThumbnailer::PATH_TYPE];
        } else {
            $this->options[WebThumbnailer::PATH_TYPE] = WebThumbnailer::PATH_RELATIVE;
        }

        // Image size
        $this->setSizeOptions($options);
    }

    /**
     * Set specific size option, allowing 'meta' size SMALL, MEDIUM, etc.
     *
     * @param array $options User options array.
     */
    protected function setSizeOptions($options)
    {
        // Width
        $width = 0;
        if (! empty($options[WebThumbnailer::MAX_WIDTH])) {
            if (SizeUtils::isMetaSize($options[WebThumbnailer::MAX_WIDTH])) {
                $width = SizeUtils::getMetaSize($options[WebThumbnailer::MAX_WIDTH]);
            } elseif (is_int($options[WebThumbnailer::MAX_WIDTH])
                || ctype_digit($options[WebThumbnailer::MAX_WIDTH])
            ) {
                $width = $options[WebThumbnailer::MAX_WIDTH];
            }
        }
        $this->options[WebThumbnailer::MAX_WIDTH] = $width;

        // Height
        $height = 0;
        if (!empty($options[WebThumbnailer::MAX_HEIGHT])) {
            if (SizeUtils::isMetaSize($options[WebThumbnailer::MAX_HEIGHT])) {
                $height = SizeUtils::getMetaSize($options[WebThumbnailer::MAX_HEIGHT]);
            } elseif (is_int($options[WebThumbnailer::MAX_HEIGHT])
                || ctype_digit($options[WebThumbnailer::MAX_WIDTH])
            ) {
                $height = $options[WebThumbnailer::MAX_HEIGHT];
            }
        }
        $this->options[WebThumbnailer::MAX_HEIGHT] = $height;

        if ($this->options[WebThumbnailer::MAX_WIDTH] == 0 && $this->options[WebThumbnailer::MAX_HEIGHT] == 0) {
            $maxwidth = ConfigManager::get('settings.default.max_width', 160);
            $this->options[WebThumbnailer::MAX_WIDTH] = $maxwidth;
            $maxheight = ConfigManager::get('settings.default.max_height', 160);
            $this->options[WebThumbnailer::MAX_HEIGHT] = $maxheight;
        }
    }

    /**
     * Make sure user options are coherent.
     *   - Only one thumb mode can be defined.
     *   - PATH_TYPE is properly defined
     *
     * @param array $options User options array.
     *
     * @throws BadRulesException Invalid options.
     */
    protected static function checkOptions($options)
    {
        $incompatibleFlagsList = [
            [WebThumbnailer::DOWNLOAD, WebThumbnailer::HOTLINK, WebThumbnailer::HOTLINK_STRICT]
        ];

        foreach ($incompatibleFlagsList as $incompatibleFlags) {
            if (count(array_intersect($incompatibleFlags, $options)) > 1) {
                $error = 'Only one of these flags can be set between: ';
                foreach ($incompatibleFlags as $flag) {
                    $error .= $flag .' ';
                }
                throw new BadRulesException($error);
            }
        }

        if (isset($options[WebThumbnailer::PATH_TYPE]) && ! in_array(
            $options[WebThumbnailer::PATH_TYPE],
            [WebThumbnailer::PATH_RELATIVE, WebThumbnailer::PATH_ABSOLUTE]
        )) {
            $error = 'The PATH_TYPE must be either relative or absolute';
            throw new BadRulesException($error);
        }
    }
}
