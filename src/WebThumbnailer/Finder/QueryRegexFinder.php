<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Utils\FinderUtils;

/**
 * Class QueryRegexFinder
 *
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
 *
 * @package WebThumbnailer\Finder
 */
class QueryRegexFinder extends FinderCommon
{
    /**
     * @var WebAccess instance.
     */
    protected $webAccess;

    /**
    * @var string thumbnail_url rule.
    */
    protected $thumbnailUrlFormat;

    /**
     * @var string Regex to apply on provided URL.
     */
    protected $urlRegex;

    /**
     * @var string Remote page content.
     */
    protected $content;

    /**
     * @inheritdoc
     */
    public function __construct($domain, $url, $rules, $options)
    {
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domains = $domain;
        $this->loadRules($rules);
        $this->finderOptions = $options;
    }

    /**
     * This finder downloads target URL page, and apply the regex given in rules on its content
     * to extract the thumbnail image.
     * The thumb URL must include ${number} to be replaced from the regex match.
     * Also replace eventual URL options.
     *
     *
     * @inheritdoc
     */
    public function find()
    {
        list($headers, $this->content) = $this->webAccess->getContent($this->url);
        if (empty($this->content) || strpos($headers[0], '200') === false) {
            return false;
        }

        $thumbnailUrl = $this->thumbnailUrlFormat;
        if (preg_match($this->urlRegex, $this->content, $matches) != false) {
            for ($i = 1; $i < count($matches); $i++) {
                $thumbnailUrl = str_replace('${'. $i . '}', $matches[$i], $thumbnailUrl);
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

    /**
     * @inheritdoc
     */
    public function checkRules($rules)
    {
        if (! FinderUtils::checkMandatoryRules($rules, [
            'image_regex',
            'thumbnail_url'
        ])) {
            throw new BadRulesException();
        }
    }

    /**
     * @inheritdoc
     */
    public function loadRules($rules)
    {
        $this->checkRules($rules);
        $this->urlRegex = FinderUtils::buildRegex($rules['image_regex'], 'im');
        $this->thumbnailUrlFormat = $rules['thumbnail_url'];
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Query Regex';
    }

    /**
     * Set the web access.
     *
     * @param WebAccess $webAccess instance.
     */
    public function setWebAccess($webAccess)
    {
        $this->webAccess = $webAccess;
    }
}
