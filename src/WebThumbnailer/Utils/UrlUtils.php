<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

/**
 * Util class for operations on URL strings.
 */
class UrlUtils
{
    /**
     * Extract the domains from an URL.
     *
     * @param string $url Given URL.
     *
     * @return string Extracted domains, lowercase.
     */
    public static function getDomain(string $url): string
    {
        if (!parse_url($url, PHP_URL_SCHEME)) {
            $url = 'http://' . $url;
        }
        return strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    }

    /**
     * Retrieve the file extension from a URL.
     *
     * @param string $url given URL.
     *
     * @return string File extension or false if not found.
     */
    public static function getUrlFileExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (preg_match('/\.(\w+)$/i', $path, $match) > 0) {
            return strtolower($match[1]);
        }
        return '';
    }
}
