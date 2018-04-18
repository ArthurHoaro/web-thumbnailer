<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\Exception;
use WebThumbnailer\Utils\FinderUtils;

/**
 * Class UrlRegexFinder
 *
 * Generic Finder using regex rules. It will use regex rules to resolve a thumbnail from the provided URL.
 * Example:
 *   - url_regex: \.com/image/([\w\d]+),
 *   - thumbnail_url: "https://domain.com/thumb/${1}"
 *   URL: https://domain.com/image/abcdef
 *   Will be resolved in  https://domain.com/thumb/abcdef
 *
 * @package WebThumbnailer\Finder
 */
class UrlRegexFinder extends FinderCommon
{
    /**
     * @var string thumbnail_url rule.
     */
    protected $thumbnailUrlFormat;

    /**
     * @var string Final thumbnail.
     */
    protected $thumbnailUrl;

    /**
     * @var string Regex to apply on provided URL.
     */
    protected $urlRegex;

    /**
     * {@inheritdoc}
     */
    public function __construct($domain, $url, $rules, $options)
    {
        $this->url = $url;
        $this->domain = $domain;

        $this->loadRules($rules);
        $this->finderOptions = $options;
    }

    /**
     * Will replace ${number} in URL format to regex match.
     * Also replace eventual URL options.
     *
     * {@inheritdoc}
     *
     * @throws Exception\BadRulesException
     */
    public function find()
    {
        $this->thumbnailUrl = $this->thumbnailUrlFormat;
        if (preg_match($this->urlRegex, $this->url, $matches) !== 0) {
            $total = count($matches);
            for ($i = 1; $i < $total; $i++) {
                $this->thumbnailUrl = str_replace('${'. $i . '}', $matches[$i], $this->thumbnailUrl);
            }

            // Match only options (not ${number})
            if (preg_match_all('/\${((?!\d)\w+?)}/', $this->thumbnailUrl, $optionsMatch, PREG_PATTERN_ORDER)) {
                foreach ($optionsMatch[1] as $value) {
                    $this->thumbnailUrl = $this->replaceOption($this->thumbnailUrl, $value);
                }
            }

            return $this->thumbnailUrl;
        }
        return false;
    }

    /**
     * Mandatory rules:
     *   - url_regex
     *   - thumbnail_url
     *
     * {@inheritdoc}
     */
    public function checkRules($rules)
    {
        $mandatoryRules = [
            'url_regex',
            'thumbnail_url',
        ];
        foreach ($mandatoryRules as $mandatoryRule) {
            if (empty($rules[$mandatoryRule])) {
                throw new Exception\BadRulesException();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadRules($rules)
    {
        $this->checkRules($rules);
        $this->urlRegex = FinderUtils::buildRegex($rules['url_regex'], 'i');
        $this->thumbnailUrlFormat = $rules['thumbnail_url'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'URL regex';
    }
}
