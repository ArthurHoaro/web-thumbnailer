<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\ConfigManager;
use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessCUrl;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Utils\ImageUtils;
use WebThumbnailer\Utils\UrlUtils;

/**
 * This finder isn't linked to any domain.
 * It will return the resource if it is an image (by extension, or by content).
 * Otherwise, it'll try to retrieve an OpenGraph resource.
 */
class DefaultFinder extends FinderCommon
{
    /** @var WebAccess instance. */
    protected $webAccess;

    /**
     * @inheritdoc
     * @param mixed[]|null $rules   All existing rules loaded from JSON files.
     * @param mixed[]|null $options Options provided by the user to retrieve a thumbnail.
     */
    public function __construct(string $domain, string $url, ?array $rules, ?array $options)
    {
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domain = $domain;
    }

    /**
     * Generic finder.
     *
     * @inheritdoc
     */
    public function find()
    {
        if (ImageUtils::isImageExtension(UrlUtils::getUrlFileExtension($this->url))) {
            return $this->url;
        }

        $content = $thumbnail = null;
        $callback = $this->webAccess instanceof WebAccessCUrl
            ? $this->getCurlCallback($content, $thumbnail)
            : null;
        list($headers, $content) = $this->webAccess->getContent(
            $this->url,
            (int) ConfigManager::get('settings.default.timeout', 30),
            (int) ConfigManager::get('settings.default.max_img_dl', 16777216),
            $callback,
            $content
        );

        if (empty($thumbnail) && !empty($content) && ImageUtils::isImageString($content)) {
            return $this->url;
        }

        if (empty($thumbnail) && ! empty($headers) && strpos($headers[0], '200') === false) {
            return false;
        }

        // With curl, the thumb is extracted during the download
        if ($this->webAccess instanceof WebAccessCUrl && ! empty($thumbnail)) {
            return $thumbnail;
        }

        return ! empty($content) ? static::extractMetaTag($content) : false;
    }

    /**
     * Get a callback for curl write function.
     *
     * @param string|null $content   A variable reference in which the downloaded content should be stored.
     * @param string|null $thumbnail A variable reference in which extracted thumb URL should be stored.
     *
     * @return callable CURLOPT_WRITEFUNCTION callback
     */
    protected function getCurlCallback(?string &$content, ?string &$thumbnail): callable
    {
        $url = $this->url;
        $isRedirected = false;

        /**
         * cURL callback function for CURLOPT_WRITEFUNCTION (called during the download).
         *
         * While downloading the remote page, we check that the HTTP code is 200 and content type is 'html/text'
         * Then we extract the title and the charset and stop the download when it's done.
         *
         * Note that when using CURLOPT_WRITEFUNCTION, we have to manually handle the content retrieved,
         * hence the $content reference variable.
         *
         * @param resource $ch   cURL resource
         * @param string   $data chunk of data being downloaded
         *
         * @return int|false length of $data or false if we need to stop the download
         */
        return function ($ch, $data) use ($url, &$content, &$thumbnail, &$isRedirected) {
            $content .= $data;
            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if (!empty($responseCode) && in_array($responseCode, [301, 302])) {
                $isRedirected = true;
                return strlen($data);
            }
            if (!empty($responseCode) && $responseCode !== 200) {
                return false;
            }
            // After a redirection, the content type will keep the previous request value
            // until it finds the next content-type header.
            if (! $isRedirected || strpos(strtolower($data), 'content-type') !== false) {
                $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            }
            // we look for image, and ignore application/octet-stream,
            // which is a the default content type for any binary
            // @see https://developer.mozilla.org/fr/docs/Web/HTTP/Basics_of_HTTP/MIME_types
            if (
                !empty($contentType)
                && strpos($contentType, 'image/') !== false
                && strpos($contentType, 'application/octet-stream') === false
            ) {
                $thumbnail = $url;
                return false;
            } elseif (
                !empty($contentType)
                && strpos($contentType, 'text/html') === false
                && strpos($contentType, 'application/octet-stream') === false
            ) {
                return false;
            }
            if (empty($thumbnail)) {
                $thumbnail = DefaultFinder::extractMetaTag($data);
            }
            // We got everything we want, stop the download.
            if (!empty($responseCode) && !empty($contentType) && !empty($thumbnail)) {
                return false;
            }

            return strlen($data);
        };
    }

    /**
     * Applies the regexp on the HTML $content to extract the thumb URL.
     *
     * @param string $content Downloaded HTML content
     *
     * @return string|false Extracted thumb URL or false if not found.
     */
    public static function extractMetaTag(string $content)
    {
        $propertiesKey = ['property', 'name', 'itemprop'];
        $properties = implode('|', $propertiesKey);
        // Try to retrieve OpenGraph image.
        $ogRegex = '#<meta[^>]+(?:' . $properties . ')=["\']?og:image["\'\s][^>]*content=["\']?(.*?)["\'\s>]#';
        // If the attributes are not in the order property => content (e.g. Github)
        // New regex to keep this readable... more or less.
        $ogRegexReverse = '#<meta[^>]+content=["\']?([^"\'\s]+)[^>]+(?:' . $properties . ')=["\']?og:image["\'\s/>]#';

        if (
            preg_match($ogRegex, $content, $matches) > 0
            || preg_match($ogRegexReverse, $content, $matches) > 0
        ) {
            return $matches[1];
        }

        return false;
    }

    /** @inheritdoc */
    public function isHotlinkAllowed(): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function checkRules(?array $rules): bool
    {
        return true;
    }

    /** @inheritdoc */
    public function loadRules(?array $rules): void
    {
    }

    /** @inheritdoc */
    public function getName(): string
    {
        return 'default';
    }
}
