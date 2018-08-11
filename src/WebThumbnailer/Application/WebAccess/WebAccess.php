<?php

namespace WebThumbnailer\Application\WebAccess;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\IOException;

/**
 * Interface WebAccess
 *
 * @package WebThumbnailer\Application
 */
interface WebAccess
{
    /**
     * GET an HTTP URL to retrieve its content
     * Uses the cURL library or a fallback method
     *
     * @param string   $url        URL to get (http://...)
     * @param int      $timeout    network timeout (in seconds)
     * @param int      $maxBytes   maximum downloaded bytes (default: 4 MiB)
     * @param callable $dlCallback Optional callback called during the download (cURL CURLOPT_WRITEFUNCTION).
     *                             Can be used to add download conditions on the headers and content
     *                             (response code, content type, apply a regexp, etc.).
     * @param string   $dlContent  A variable use to handle the downloaded content (as a reference).
     *                             Used with $downloadCallback, it allows to store the actual response content.
     *
     * @return array HTTP response headers, downloaded content
     *
     * @throws BadRulesException
     * @throws IOException
     *
     * Output format:
     *  [0] = associative array containing HTTP response headers
     *  [1] = URL content (downloaded data)
     */
    public function getContent($url, $timeout = null, $maxBytes = null, $dlCallback = null, &$dlContent = null);
}
