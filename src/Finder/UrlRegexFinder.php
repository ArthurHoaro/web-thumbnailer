<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Utils\FinderUtils;

/**
 * Generic Finder using regex rules. It will use regex rules to resolve a thumbnail from the provided URL.
 * Example:
 *   - url_regex: \.com/image/([\w\d]+),
 *   - thumbnail_url: "https://domain.com/thumb/${1}"
 *   URL: https://domain.com/image/abcdef
 *   Will be resolved in  https://domain.com/thumb/abcdef
 */
class UrlRegexFinder extends FinderCommon
{
    /** @var string thumbnail_url rule. */
    protected $thumbnailUrlFormat;

    /** @var string Final thumbnail. */
    protected $thumbnailUrl;

    /** @var string Regex to apply on provided URL. */
    protected $urlRegex;

    /**
     * @inheritdoc
     * @param mixed[]|null $rules   All existing rules loaded from JSON files.
     * @param mixed[]|null $options Options provided by the user to retrieve a thumbnail.
     */
    public function __construct(string $domain, string $url, ?array $rules, ?array $options)
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
     * @throws BadRulesException
     */
    public function find()
    {
        $this->thumbnailUrl = $this->thumbnailUrlFormat;
        if (preg_match($this->urlRegex, $this->url, $matches) !== 0) {
            $total = count($matches);
            for ($i = 1; $i < $total; $i++) {
                $this->thumbnailUrl = str_replace('${' . $i . '}', $matches[$i], $this->thumbnailUrl);
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
    public function checkRules(?array $rules): bool
    {
        $mandatoryRules = [
            'url_regex',
            'thumbnail_url',
        ];
        foreach ($mandatoryRules as $mandatoryRule) {
            if (empty($rules[$mandatoryRule])) {
                throw new BadRulesException();
            }
        }

        return true;
    }

    /** @inheritdoc */
    public function loadRules(?array $rules): void
    {
        $this->checkRules($rules);
        $this->urlRegex = FinderUtils::buildRegex($rules['url_regex'], 'i');
        $this->thumbnailUrlFormat = $rules['thumbnail_url'];
    }

    /** @inheritdoc */
    public function getName(): string
    {
        return 'URL regex';
    }
}
