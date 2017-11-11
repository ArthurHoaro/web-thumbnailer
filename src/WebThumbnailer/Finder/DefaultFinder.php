<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\Application\WebAccess\WebAccess;
use WebThumbnailer\Application\WebAccess\WebAccessFactory;
use WebThumbnailer\Utils\ImageUtils;
use WebThumbnailer\Utils\UrlUtils;
use WebThumbnailer\WebThumbnailer;

/**
 * Class DefaultFinder
 *
 * This finder isn't linked to any domain.
 * It will return the resource if it is an image (by extension, or by content).
 * Otherwise, it'll try to retrieve an OpenGraph resource.
 *
 * @package WebThumbnailer\Finder
 */
class DefaultFinder extends FinderCommon
{
    /**
     * @var WebAccess instance.
     */
    protected $webAccess;

    /**
     * @inheritdoc
     */
    public function __construct($domain, $url, $rules, $options)
    {
        $this->webAccess = WebAccessFactory::getWebAccess($url);
        $this->url = $url;
        $this->domains = $domain;
    }

    /**
     * Generic finder.
     *
     * @inheritdoc
     */
    function find()
    {
        if (ImageUtils::isImageExtension(UrlUtils::getUrlFileExtension($this->url))) {
            return $this->url;
        }

        list($headers, $content) = $this->webAccess->getContent($this->url);

        if (strpos($headers[0], '200') === false) {
            return false;
        }

        if (ImageUtils::isImageString($content)) {
            return $this->url;
        }

        $propertiesKey = ['property', 'name', 'itemprop'];
        // Try to retrieve OpenGraph image.
        $ogRegex = '#<meta[^>]+(?:'. implode('|', $propertiesKey) .')=["\']?og:image["\'\s][^>]*content=["\']?(.*?)["\'\s>]#';
        // If the attributes are not in the order property => content (e.g. Github)
        // New regex to keep this readable... more or less.
        $ogRegexReverse = '#<meta[^>]+content=["\']?([^"\'\s]+)[^>]+(?:'. implode('|', $propertiesKey) .')=["\']?og:image["\'\s/>]#';

        if (preg_match($ogRegex, $content, $matches) > 0
            || preg_match($ogRegexReverse, $content, $matches) > 0
        ) {
            return $matches[1];
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function isHotlinkAllowed()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    function checkRules($rules)
    {
    }

    /**
     * @inheritdoc
     */
    function loadRules($rules)
    {
    }

    /**
     * @inheritdoc
     */
    function getName()
    {
        return 'default';
    }
}
