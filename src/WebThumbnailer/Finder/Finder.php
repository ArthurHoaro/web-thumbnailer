<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\IOException;

/**
 * Interface Finder
 *
 * A Finder is an object which will resolve a thumbnail for a giver URL.
 * It must match rules defined in `rules.json`.
 * It can either use a specific API to resolve the thumbnail URL from the provided URL
 * or download the provided URL to find an image to use as a thumbnail.
 * A Finder is usually linked to a specific domains, or a list of domains.
 *
 * @package WebThumbnailer\Finder
 */
interface Finder
{
    /**
     * Finder constructor.
     *
     * @param string $domain  Standardized domains name: `imgur.com`, `youtu.be`, etc.
     * @param string $url     URL provided.
     * @param array  $rules   All existing rules loaded from JSON files.
     * @param array  $options Options provided by the user to retrieve a thumbnail.
     */
    public function __construct($domain, $url, $rules, $options);

    /**
     * Retrieve the thumbnail URL or the image from which a thumbnail will be created,
     * using provided rules and options.
     *
     * @return string|bool Thumbnail URL or false if it couldn't be resolved.
     *
     * @throws IOException
     * @throws BadRulesException
     */
    public function find();

    /**
     * Load provided rules, usually in specific class attributes.
     *
     * @param array $rules Finder rules loaded from JSON.
     */
    public function loadRules($rules);

    /**
     * Validate provided rule for this Finder.
     *
     * @param array $rules Finder rules loaded from JSON.
     *
     * @throws BadRulesException Rules aren't valid.
     */
    public function checkRules($rules);

    /**
     * Indicate if hotlink is allowed or not.
     * If the rule has not been set, hotlink is allowed by default.
     *
     * @return bool true if it's allowed, false otherwise.
     */
    public function isHotlinkAllowed();

    /**
     * Load options provided by the user into the object.
     * Example: size settings.
     *
     * @param array $userOptions List of user options.
     */
    public function setUserOptions($userOptions);

    /**
     * @return string Domain associated with the Finder and the current URL.
     */
    public function getDomain();

    /**
     * @return string Return the Finder name.
     */
    public function getName();
}
