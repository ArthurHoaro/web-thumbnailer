<?php

namespace WebThumbnailer\Application\WebAccess;

/**
 * Class WebAccessFactory
 *
 * Create WebAccess instances depending on PHP extensions and given URL/path.
 *
 * @package WebThumbnailer\Application
 */
class WebAccessFactory
{
    /**
     * Return a new WebAccess instance, can be used for local files using a path as $url.
     *
     * @param string $url URL on which the WebAccess will be used (optional)
     *
     * @return WebAccess instance.
     */
    public static function getWebAccess($url = null)
    {
        // Local file
        if (! empty($url) && $url[0] === '/') {
            return new WebAccessLocal();
        }

        // Default for remote: cURL
        if (function_exists('curl_init')) {
            return new WebAccessCUrl();
        }

        // Fallback
        return new WebAccessPHP();
    }
}
