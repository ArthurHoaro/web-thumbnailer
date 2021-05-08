<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

/**
 * Create WebAccess instances depending on PHP extensions and given URL/path.
 */
class WebAccessFactory
{
    /**
     * Return a new WebAccess instance, can be used for local files using a path as $url.
     *
     * @param string|null $url URL on which the WebAccess will be used (optional)
     *
     * @return WebAccess instance.
     */
    public static function getWebAccess(?string $url = null): WebAccess
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
