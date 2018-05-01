<?php

namespace WebThumbnailer\Application\WebAccess;

use WebThumbnailer\Application\ConfigManager;

/**
 * Class WebAccessPHP
 *
 * @package WebThumbnailer\Application
 */
class WebAccessPHP implements WebAccess
{
    /**
     * Download content using PHP built-in functions.
     * Note that this method may fail more often than the cURL one.
     *
     * @inheritdoc
     */
    public function getContent($url, $timeout = null, $maxBytes = null, $dlCallback = null, &$dlContent = null)
    {
        if (empty($timeout)) {
            $timeout = ConfigManager::get('settings.default.timeout', 30);
        }

        if (empty($maxBytes)) {
            $maxBytes = ConfigManager::get('settings.default.max_img_dl', 4194304);
        }

        $maxRedr = 3;
        $context = $this->getContext($timeout, false);
        stream_context_set_default($context);
        list($headers, $finalUrl) = $this->getRedirectedHeaders($url, $timeout, $maxRedr);
        if (! $headers || strpos($headers[0], '200 OK') === false) {
            $context = $this->getContext($timeout, true);
            stream_context_set_default($context);
            list($headers, $finalUrl) = $this->getRedirectedHeaders($url, $timeout, $maxRedr);
        }

        if (! $headers) {
            return array($headers, false);
        }

        $context = stream_context_create($context);
        $content = file_get_contents($finalUrl, false, $context, -1, $maxBytes);

        return array($headers, $content);
    }

    /**
     * Download URL HTTP headers and follow redirections (HTTP 30x) if necessary.
     *
     * @param string $url              URL to download.
     * @param int  $timeout network timeout (in seconds)
     * @param int    $redirectionLimit Stop trying to follow redrection if this number is reached.
     *
     * @return array containing HTTP headers.
     */
    protected function getRedirectedHeaders($url, $timeout, $redirectionLimit = 3)
    {
        stream_context_set_default($this->getContext($timeout));

        $headers = @get_headers($url, 1);
        // Some hosts don't like fulluri request, some requires it...
        if ($headers === false) {
            stream_context_set_default($this->getContext($timeout, false));
            $headers = @get_headers($url, 1);
        }

        // Headers found, redirection found, and limit not reached.
        if ($redirectionLimit-- > 0
            && !empty($headers)
            && (strpos($headers[0], '301') !== false || strpos($headers[0], '302') !== false)
            && !empty($headers['Location'])
        ) {
            $redirection = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
            if ($redirection != $url) {
                return $this->getRedirectedHeaders($redirection, $timeout, $redirectionLimit);
            }
        }

        return [$headers, $url];
    }

    /**
     * Create a valid context for PHP HTTP functions.
     *
     * @param int  $timeout network timeout (in seconds)
     * @param bool $fulluri this is required by some hosts, rejected by others, so option.
     *
     * @return array context.
     */
    protected function getContext($timeout, $fulluri = true)
    {
        return [
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:45.0; WebThumbnailer) Gecko/20100101 Firefox/45.0',
                'request_fulluri' => $fulluri,
            ]
        ];
    }
}
