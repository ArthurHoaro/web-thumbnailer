<?php

namespace WebThumbnailer\Application\WebAccess;

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
     * @param string $url      URL to get (http://...)
     * @param bool   $debug    Enable debug mode (e.g. verbose for cURL).
     * @param int    $timeout  network timeout (in seconds)
     * @param int    $maxBytes maximum downloaded bytes (default: 4 MiB)
     *
     * @return array HTTP response headers, downloaded content
     *
     * Output format:
     *  [0] = associative array containing HTTP response headers
     *  [1] = URL content (downloaded data)
     *
     * Example:
     *  list($headers, $data) = get_http_response('http://sebauvage.net/');
     *  if (strpos($headers[0], '200 OK') !== false) {
     *      echo 'Data type: '.htmlspecialchars($headers['Content-Type']);
     *  } else {
     *      echo 'There was an error: '.htmlspecialchars($headers[0]);
     *  }
     *
     * @throws \Exception An error occurred with cURL download.
     */
    public function getContent($url, $debug = false, $timeout = null, $maxBytes = null);
}
