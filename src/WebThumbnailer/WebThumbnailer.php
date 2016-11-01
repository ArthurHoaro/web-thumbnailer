<?php

namespace WebThumbnailer;

use WebThumbnailer\Application\Thumbnailer;

/**
 * WebThumbnailer.php
 */
class WebThumbnailer
{
    /*
     * SIZE
     */
    const MAX_WIDTH = 'MAX_WIDTH';
    const MAX_HEIGHT = 'MAX_HEIGHT';
    const SIZE_SMALL = 'small';
    const SIZE_MEDIUM = 'medium';
    const SIZE_LARGE = 'large';

    /*
     * DOWNLOAD & CACHE
     */
    /**
     * Flag to download and serve locally all image.
     */
    const DOWNLOAD = 'DOWNLOAD';
    /**
     * Flag to use hotlink if available.
     */
    const HOTLINK = 'HOTLINK';
    /**
     * Use only hotlink, no thumbnail if not available.
     */
    const HOTLINK_STRICT = 'HOTLINK_STRICT';
    /**
     * Network timeout, in seconds.
     */
    const DOWNLOAD_TIMEOUT = 'DOWNLOAD_TIMEOUT';
    /**
     * Number of bytes to download for a thumbnail. Default 4194304 (4MB).
     */
    const DOWNLOAD_MAX_SIZE = 'DOWNLOAD_MAX_SIZE';
    /**
     * Disable the cache system.
     */
    const NOCACHE = 'NOCACHE';
    /**
     * Crop image to fixed size.
     */
    const CROP = 'CROP';
    
    /*
     * OTHER
     */
    /**
     * Debug mode. Throw exceptions.
     */
    const DEBUG = 'DEBUG';

    protected $maxWidth;

    protected $maxHeight;

    protected $debug;

    protected $nocache;
    
    protected $crop;

    protected $downloadMode = self::DOWNLOAD;

    /**
     * Get the thumbnail for the given URL>
     *
     * @param string $url     User URL.
     * @param array  $options Options array. See the documentation for more infos.
     *
     * @return bool|string Thumbnail URL, false if not found.
     *
     * @throws \Exception Only throw exception in debug mode.
     */
    public function thumbnail($url, $options = [])
    {
        $url = trim($url);
        if (empty($url)) {
            return false;
        }

        $options = array_merge(
            [
                self::DEBUG => $this->debug,
                self::NOCACHE => $this->nocache,
                self::MAX_WIDTH => $this->maxWidth,
                self::MAX_HEIGHT => $this->maxHeight,
                self::CROP => $this->crop,
                $this->downloadMode
            ],
            $options
        );

        try {
            $downloader = new Thumbnailer($url, $options, $_SERVER);
            return $downloader->getThumbnail();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            if (isset($options[self::DEBUG])) {
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
    public function maxWidth($maxWidth)
    {
        $this->maxWidth = $maxWidth;
        return $this;
    }

    /**
     * @param int|string $maxHeight Either number of pixels or SIZE_SMALL|SIZE_MEDIUM|SIZE_LARGE.
     *
     * @return WebThumbnailer self instance.
     */
    public function maxHeight($maxHeight)
    {
        $this->maxHeight = $maxHeight;
        return $this;
    }

    /**
     * @param bool $debug
     *
     * @return WebThumbnailer self instance.
     */
    public function debug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @param mixed $nocache
     *
     * @return WebThumbnailer self instance.
     */
    public function noCache($nocache)
    {
        $this->nocache = $nocache;
        return $this;
    }

    /**
     * @param bool $crop
     *
     * @return WebThumbnailer $this
     */
    public function crop($crop)
    {
        $this->crop = $crop;
        return $this;
    }

    /**
     * Enable download mode
     * FIXME! details
     */
    public function modeDownload()
    {
        $this->downloadMode = self::DOWNLOAD;
        return $this;
    }

    /**
     * Enable hotlink mode
     * FIXME! details
     */
    public function modeHotlink()
    {
        $this->downloadMode = self::HOTLINK;
        return $this;
    }

    /**
     * Enable strict hotlink mode
     * FIXME! details
     */
    public function modeHotlinkStrict()
    {
        $this->downloadMode = self::HOTLINK_STRICT;
        return $this;
    }
}
