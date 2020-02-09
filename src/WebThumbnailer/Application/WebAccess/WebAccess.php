<?php

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\IOException;

interface WebAccess
{
    /**
     * GET an HTTP URL to retrieve its content
     * Uses the cURL library or a fallback method
     *
     * @param string        $url        URL to get (http://...)
     * @param int|null      $timeout    network timeout (in seconds)
     * @param int|null      $maxBytes   maximum downloaded bytes (default: 4 MiB)
     * @param callable|null $dlCallback Optional callback called during the download (cURL CURLOPT_WRITEFUNCTION).
     *                                  Can be used to add download conditions on the headers and content
     *                                  (response code, content type, apply a regexp, etc.).
     * @param string|null   $dlContent  A variable use to handle the downloaded content (as a reference).
     *                                  Used with $downloadCallback, it allows to store the actual response content.
     *
     * @return mixed[] HTTP response headers, downloaded content
     *
     * @throws BadRulesException
     * @throws IOException
     *
     * Output format:
     *  [0] = associative array containing HTTP response headers
     *  [1] = URL content (downloaded data)
     */
    public function getContent(
        string $url,
        ?int $timeout = null,
        ?int $maxBytes = null,
        ?callable $dlCallback = null,
        ?string &$dlContent = null
    ): array;
}
