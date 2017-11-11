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
    public function getContent($url,  $debug = false, $timeout = null, $maxBytes = null)
    {
        return [['200'], file_get_contents($url)];
    }
}
