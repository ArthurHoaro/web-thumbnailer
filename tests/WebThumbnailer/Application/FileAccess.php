<?php

namespace WebThumbnailer\Application;

/**
 * Class FileWebAccess
 *
 * Utility class to load a local file instead of a web resource.
 *
 * @package WebThumbnailer\Application
 */
class FileAccess extends WebAccess
{
    /**
     * Return local file content.
     *
     * @inheritdoc
     */
    public function getWebContent($url, $maxBytes = null)
    {
        return file_get_contents($url);
    }
}