<?php

namespace WebThumbnailer\Application;

/**
 * Class WebAccess
 * 
 * @package WebThumbnailer\Application
 */
class WebAccess
{
    /**
     * Download web content.
     *
     * @param string $url      URL to download.
     * @param int    $maxBytes Maximum bytes to download (optional - default 4MB).
     *
     * @return mixed Downloaded content, or false if it failed.
     */
    public function getWebContent($url, $maxBytes = null) {
        if ($maxBytes === null) {
            $maxBytes = ConfigManager::get('settings.default.max_img_dl', 4194304);
        }

        $data = @file_get_contents($url, false, stream_context_create(self::getContext()), 0, $maxBytes);
        // Some hosts don't like fulluri request, some requires it...
        if ($data === false) {
            $data = @file_get_contents($url, false, stream_context_create(self::getContext(false)), 0, $maxBytes);
        }
        return $data;
    }

    /**
     * Download URL HTTP headers and follow redirections (30x) if necessary.
     *
     * @param string $url              URL to download.
     * @param int    $redirectionLimit Stop trying to follow redrection if this number is reached.
     *
     * @return array containing HTTP headers.
     */
    public function getRedirectedHeaders($url, $redirectionLimit = 3) {
        stream_context_set_default(self::getContext());

        $headers = @get_headers($url, 1);
        // Some hosts don't like fulluri request, some requires it...
        if ($headers === false) {
            stream_context_set_default(self::getContext(false));
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
                return self::getRedirectedHeaders($redirection, $redirectionLimit);
            }
        }

        return [$headers, $url];
    }

    /**
     * Create a valid context for PHP HTTP functions.
     *
     * @param bool $fulluri this is required by some hosts, rejected by others, so option.
     *
     * @return array context.
     */
    private function getContext($fulluri = true) {
        return $contextArray = [
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:44.0; WebThumbnailer) Gecko/20100101 Firefox/44.0',
                'request_fulluri' => $fulluri,
            ]
        ];
    }
}