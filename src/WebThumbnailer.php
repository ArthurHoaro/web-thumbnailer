<?php

declare(strict_types=1);

namespace WebThumbnailer;

use WebThumbnailer\Application\Thumbnailer;
use WebThumbnailer\Exception\MissingRequirementException;
use WebThumbnailer\Exception\WebThumbnailerException;

/**
 * WebThumbnailer.php
 */
class WebThumbnailer
{
    /*
     * SIZES
     */
    public const MAX_WIDTH = 'MAX_WIDTH';
    public const MAX_HEIGHT = 'MAX_HEIGHT';
    public const SIZE_SMALL = 'small';
    public const SIZE_MEDIUM = 'medium';
    public const SIZE_LARGE = 'large';

    /*
     * DOWNLOAD & CACHE
     */
    /** Flag to download and serve locally all image. */
    public const DOWNLOAD = 'DOWNLOAD';

    /** Flag to use hotlink if available. */
    public const HOTLINK = 'HOTLINK';

    /** Use only hotlink, no thumbnail if not available. */
    public const HOTLINK_STRICT = 'HOTLINK_STRICT';

    /** Network timeout, in seconds. */
    public const DOWNLOAD_TIMEOUT = 'DOWNLOAD_TIMEOUT';

    /** Number of bytes to download for a thumbnail. Default 4194304 (4MB). */
    public const DOWNLOAD_MAX_SIZE = 'DOWNLOAD_MAX_SIZE';

    /** Enable verbose mode: log errors with error_log */
    public const VERBOSE = 'VERBOSE';

    /** Disable the cache system. */
    public const NOCACHE = 'NOCACHE';

    /** Crop image to fixed size. */
    public const CROP = 'CROP';

    /** Debug mode. Throw exceptions. */
    public const DEBUG = 'DEBUG';

    /** @var int|null */
    protected $maxWidth = null;

    /** @var int|null */
    protected $maxHeight = null;

    /** @var int|null */
    protected $downloadTimeout = null;

    /** @var int|null */
    protected $downloadMaxSize = null;

    /** @var bool|null */
    protected $debug = null;

    /** @var bool|null */
    protected $verbose = null;

    /** @var bool|null */
    protected $nocache = null;

    /** @var bool|null */
    protected $crop = null;

    /** @var string|null */
    protected $downloadMode = null;

    /**
     * Get the thumbnail for the given URL>
     *
     * @param string  $url     User URL.
     * @param mixed[] $options Options array. See the documentation for more infos.
     *
     * @return string|false Thumbnail URL, false if not found.
     *
     * @throws WebThumbnailerException Only throw exception in debug mode.
     */
    public function thumbnail(string $url, array $options = [])
    {
        $url = trim($url);
        if (empty($url)) {
            return false;
        }

        $options = array_merge(
            [
                static::DEBUG => $this->debug,
                static::VERBOSE => $this->verbose,
                static::NOCACHE => $this->nocache,
                static::MAX_WIDTH => $this->maxWidth,
                static::MAX_HEIGHT => $this->maxHeight,
                static::DOWNLOAD_TIMEOUT => $this->downloadTimeout,
                static::DOWNLOAD_MAX_SIZE => $this->downloadMaxSize,
                static::CROP => $this->crop,
                $this->downloadMode
            ],
            $options
        );

        try {
            $downloader = new Thumbnailer($url, $options, $_SERVER);
            return $downloader->getThumbnail();
        } catch (MissingRequirementException $e) {
            throw $e;
        } catch (WebThumbnailerException $e) {
            if (isset($options[static::VERBOSE]) && $options[static::VERBOSE] === true) {
                error_log($e->getMessage());
            }

            if (isset($options[static::DEBUG]) && $options[static::DEBUG] === true) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * @param int|string $maxWidth Either number of pixels or SIZE_SMALL|SIZE_MEDIUM|SIZE_LARGE.
     *
     * @return WebThumbnailer self instance.
     */
    public function maxWidth($maxWidth): self
    {
        $this->maxWidth = (int) $maxWidth;

        return $this;
    }

    /**
     * @param int|string $maxHeight Either number of pixels or SIZE_SMALL|SIZE_MEDIUM|SIZE_LARGE.
     *
     * @return WebThumbnailer self instance.
     */
    public function maxHeight($maxHeight): self
    {
        $this->maxHeight = (int) $maxHeight;

        return $this;
    }

    /**
     * @param bool $debug
     *
     * @return WebThumbnailer self instance.
     */
    public function debug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @param bool $verbose
     *
     * @return WebThumbnailer self instance.
     */
    public function verbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * @param bool $nocache
     *
     * @return WebThumbnailer self instance.
     */
    public function noCache(bool $nocache): self
    {
        $this->nocache = $nocache;

        return $this;
    }

    /**
     * @param bool $crop
     *
     * @return WebThumbnailer $this
     */
    public function crop(bool $crop): self
    {
        $this->crop = $crop;

        return $this;
    }

    /**
     * @param int $downloadTimeout in seconds
     *
     * @return WebThumbnailer $this
     */
    public function downloadTimeout(int $downloadTimeout): self
    {
        $this->downloadTimeout = $downloadTimeout;

        return $this;
    }

    /**
     * @param int $downloadMaxSize in bytes
     *
     * @return WebThumbnailer $this
     */
    public function downloadMaxSize(int $downloadMaxSize): self
    {
        $this->downloadMaxSize = $downloadMaxSize;

        return $this;
    }

    /**
     * Enable download mode
     * It will download thumbnail, resize it and save it in the cache folder.
     *
     * @return WebThumbnailer $this
     */
    public function modeDownload(): self
    {
        $this->downloadMode = static::DOWNLOAD;

        return $this;
    }

    /**
     * Enable hotlink mode
     * It will use image hotlinking if the domain authorize it, download it otherwise.
     *
     * @return WebThumbnailer $this
     */
    public function modeHotlink(): self
    {
        $this->downloadMode = static::HOTLINK;

        return $this;
    }

    /**
     * Enable strict hotlink mode
     * It will use image hotlinking if the domain authorize it, fail otherwise.
     *
     * @return WebThumbnailer $this
     */
    public function modeHotlinkStrict(): self
    {
        $this->downloadMode = static::HOTLINK_STRICT;

        return $this;
    }
}
