<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\ConfigManager;
use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessCUrl;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Utils\FinderUtils;

/**
 * Generic Finder using regex rules on remote web content.
 * It will use regex rules to resolve a thumbnail in web a page.
 *
 * Mandatory rules:
 *   - image_regex
 *   - thumbnail_url
 *
 * Example:
 *   1. `http://domain.tld/page` content will be downloaded.
 *   2. `image_regex` will be apply on the content
 *   3. Matches will be use to generate `thumbnail_url`.
 */
class QueryRegexFinder extends FinderCommon
{
    /** @var WebAccess instance. */
    protected $webAccess;

    /** @var string thumbnail_url rule. */
    protected $thumbnailUrlFormat;

    /** @var string Regex to apply on provided URL. */
    protected $urlRegex;

    /**
     * @inheritdoc
     * @param mixed[]|null $rules   All existing rules loaded from JSON files.
     * @param mixed[]|null $options Options provided by the user to retrieve a thumbnail.
     *
     * @throws BadRulesException
     */
    public function __construct(string $domain, string $url, ?array $rules, ?array $options)
    {
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domain = $domain;
        $this->loadRules($rules);
        $this->finderOptions = $options;
    }

    /**
     * This finder downloads target URL page, and apply the regex given in rules on its content
     * to extract the thumbnail image.
     * The thumb URL must include ${number} to be replaced from the regex match.
     * Also replace eventual URL options.
     *
     * @inheritdoc
     *
     * @throws BadRulesException
     */
    public function find()
    {
        $thumbnail = $content = null;
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
        if (
            empty($content)
            || empty($headers)
            || (empty($thumbnail) && strpos($headers[0], '200') === false)
        ) {
            return false;
        }

        // With curl, the thumb is extracted during the download
        if ($this->webAccess instanceof WebAccessCUrl && ! empty($thumbnail)) {
            return $thumbnail;
        }

        return $this->extractThumbContent($content);
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
        return function ($ch, $data) use (&$content, &$thumbnail, &$isRedirected) {
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
            if (!empty($contentType) && strpos($contentType, 'text/html') === false) {
                return false;
            }
            if (empty($thumbnail)) {
                $thumbnail = $this->extractThumbContent($data);
            }
            // We got everything we want, stop the download.
            if (!empty($responseCode) && !empty($contentType) && !empty($thumbnail)) {
                return false;
            }

            return strlen($data);
        };
    }

    /**
     * @param string $content to extract thumb from
     *
     * @return string|false Thumbnail URL or false if not found
     *
     * @throws BadRulesException
     */
    public function extractThumbContent(string $content)
    {
        $thumbnailUrl = $this->thumbnailUrlFormat;
        if (preg_match($this->urlRegex, $content, $matches) !== 0) {
            $total = count($matches);
            for ($i = 1; $i < $total; $i++) {
                $thumbnailUrl = str_replace('${' . $i . '}', $matches[$i], $thumbnailUrl);
            }

            // Match only options (not ${number})
            if (preg_match_all('/\${((?!\d)\w+?)}/', $thumbnailUrl, $optionsMatch, PREG_PATTERN_ORDER)) {
                foreach ($optionsMatch[1] as $value) {
                    $thumbnailUrl = $this->replaceOption($thumbnailUrl, $value);
                }
            }
            return $thumbnailUrl;
        }

        return false;
    }

    /** @inheritdoc */
    public function checkRules(?array $rules): bool
    {
        if (count($rules ?? []) > 0 && !FinderUtils::checkMandatoryRules($rules, ['image_regex', 'thumbnail_url'])) {
            throw new BadRulesException();
        }

        return true;
    }

    /**
     * @inheritdoc
     *
     * @throws BadRulesException
     */
    public function loadRules(?array $rules): void
    {
        $this->checkRules($rules);
        $this->urlRegex = FinderUtils::buildRegex($rules['image_regex'], 'im');
        $this->thumbnailUrlFormat = $rules['thumbnail_url'];
    }

    /** @inheritdoc */
    public function getName(): string
    {
        return 'Query Regex';
    }
}
