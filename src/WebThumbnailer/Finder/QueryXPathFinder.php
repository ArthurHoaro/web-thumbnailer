<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\NotImplementedException;
use WebThumbnailer\Utils\FinderUtils;

/**
 * Class QueryXPathFinder
 *
 * DO NOT USE: SimpleXMLElement needs a strict XML validity, which doesn't work in HTML5.
 * For now, I don't want this lib to rely on libxml extension, neither rely on a 3rd party
 * library to load the DOM. Use QueryRegexFinder instead.
 *
 * Generic Finder using XQuery on remote web content.
 * Very similar to QueryRegexFinder except we use a XQuery instead of a regex.
 *
 * Note that using this instead of QueryRegexFinder, only one placeholder called `${1}`
 * can be replaced in `thumbnail_url` (except for options).
 *
 * Mandatory rules:
 *   - image_xpath
 *   - thumbnail_url
 *
 * Example:
 *   1. `http://domain.tld/page` content will be downloaded.
 *   2. `image_xpath` will be apply on the content
 *   3. XQuery result will be use to generate `thumbnail_url`.
 *
 * @package WebThumbnailer\Finder
 */
class QueryXPathFinder extends FinderCommon
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
    protected $xPath;

    /**
     * @var string Remote page content.
     */
    protected $content;

    /**
     * @inheritdoc
     *
     * @throws NotImplementedException
     */
    public function __construct($domain, $url, $rules, $options)
    {
        throw new NotImplementedException();
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domains = $domain;
        $this->loadRules($rules);
        $this->finderOptions = $options;
    }

    /**
     * This finder downloads target URL page, and apply the XQuery given in rules on its content
     * to extract the thumbnail image.
     * Also replace eventual URL options.
     *
     * @inheritdoc1
     */
    public function find()
    {
        $this->content = $this->webAccess->getContent($this->url);
        if (empty($this->content)) {
            return false;
        }

        $sxml = new \SimpleXMLElement($this->content);
        $thumbnailUrl = $sxml->xpath($this->xPath);
        if (empty($thumbnailUrl)) {
            return false;
        }

        // Replace the ${1} placeholder.
        $thumbnailUrl = str_replace('${1}', $thumbnailUrl, $this->thumbnailUrlFormat);

        // Replace option placeholders.
        if (preg_match_all('/\${(\w+)}/', $thumbnailUrl, $optionsMatch, PREG_PATTERN_ORDER)) {
            foreach ($optionsMatch[1] as $value) {
                $thumbnailUrl = $this->replaceOption($thumbnailUrl, $value);
            }
        }

        return $thumbnailUrl;
    }

    /**
     * @inheritdoc
     */
    public function checkRules($rules)
    {
        if (! FinderUtils::checkMandatoryRules($rules, [
            'image_xpath',
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
        $this->xPath = FinderUtils::buildRegex($rules['image_xpath'], 'im');
        $this->thumbnailUrlFormat = $rules['thumbnail_url'];
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Query Regex';
    }
}
