<?php

namespace WebThumbnailer\Application\WebAccess;

/**
 * Class WebAccessLocal
 *
 * Get a local file content.
 *
 * @package WebThumbnailer\Application
 */
class WebAccessLocal implements WebAccess
{
    /**
     * @inheritdoc
     */
    public function getContent($url, $timeout = null, $maxBytes = null, $downloadCallback = null, &$downloadedContent = null)
    {
        return [['200'], file_get_contents($url)];
    }
}
