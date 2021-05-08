<?php

/**
 * @copyright Shaarli Community under zlib license https://github.com/shaarli/Shaarli
 *
 * ZLIB/LIBPNG LICENSE
 *
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from
 * the use of this software.
 *
 * Permission is granted to anyone to use this software for any purpose,
 * including commercial applications, and to alter it and redistribute it
 * freely, subject to the following restrictions:
 *
 * 1. The origin of this software must not be misrepresented; you must not
 * claim that you wrote the original software. If you use this software
 * in a product, an acknowledgment in the product documentation would
 * be appreciated but is not required.
 *
 * 2. Altered source versions must be plainly marked as such, and must
 * not be misrepresented as being the original software.
 *
 * 3. This notice may not be removed or altered from any source distribution.
 */

declare(strict_types=1);

namespace WebThumbnailer\Application\WebAccess;

use WebThumbnailer\Application\ConfigManager;

/**
 * Require php-curl
 */
class WebAccessCUrl implements WebAccess
{
    /**
     * Download content using cURL.
     *
     * @see https://secure.php.net/manual/en/ref.curl.php
     * @see https://secure.php.net/manual/en/functions.anonymous.php
     * @see https://secure.php.net/manual/en/function.preg-split.php
     * @see https://secure.php.net/manual/en/function.explode.php
     * @see http://stackoverflow.com/q/17641073
     * @see http://stackoverflow.com/q/9183178
     * @see http://stackoverflow.com/q/1462720
     *
     * @inheritdoc
     */
    public function getContent(
        string $url,
        ?int $timeout = null,
        ?int $maxBytes = null,
        ?callable $dlCallback = null,
        ?string &$dlContent = null
    ): array {
        if (empty($timeout)) {
            $timeout = ConfigManager::get('settings.default.timeout', 30);
        }

        if (empty($maxBytes)) {
            $maxBytes = ConfigManager::get('settings.default.max_img_dl', 16777216);
        }

        $locale = setlocale(LC_COLLATE, '0');
        $cookie = ConfigManager::get('settings.path.cache') . '/cookie.txt';
        $userAgent =
            'Mozilla/5.0 (X11; Linux x86_64; rv:45.0; WebThumbnailer)'
            . ' Gecko/20100101 Firefox/45.0';
        $acceptLanguage =
            substr($locale ?: 'en', 0, 2) . ',en-US;q=0.7,en;q=0.3';
        $maxRedirs = 6;

        $ch = curl_init($url);
        if ($ch === false) {
            return [[0 => 'curl_init() error'], false];
        }

        // General cURL settings
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            ['Accept-Language: ' . $acceptLanguage]
        );
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirs);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);

        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);

        // Max download size management
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 1024 * 16);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt(
            $ch,
            CURLOPT_PROGRESSFUNCTION,
            function ($arg0, $arg1, $arg2) use ($maxBytes) {
                $downloaded = $arg2;
                // Non-zero return stops downloading
                return ($downloaded > $maxBytes) ? 1 : 0;
            }
        );

        if (is_callable($dlCallback)) {
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $dlCallback);
            curl_exec($ch);
            $response = $dlContent;
        } else {
            $response = curl_exec($ch);
        }

        $errorNo = curl_errno($ch);
        $errorStr = curl_error($ch);
        $headSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if (!is_string($response)) {
            return [[0 => 'curl_exec() error #' . $errorNo . ': ' . $errorStr], false];
        }

        // Formatting output like the fallback method
        $rawHeaders = substr($response, 0, $headSize);

        // Keep only headers from latest redirection
        $rawHeadersArrayRedirs = explode("\r\n\r\n", trim($rawHeaders));
        $rawHeadersLastRedir = end($rawHeadersArrayRedirs);

        $content = substr($response, $headSize);
        $headers = [];
        foreach (preg_split('~[\r\n]+~', $rawHeadersLastRedir ?: '') ?: [] as $line) {
            if (empty($line) or ctype_space($line)) {
                continue;
            }
            $splitLine = explode(': ', $line, 2);
            if (count($splitLine) > 1) {
                $key = $splitLine[0];
                $value = $splitLine[1];
                if (array_key_exists($key, $headers)) {
                    if (!is_array($headers[$key])) {
                        $headers[$key] = array(0 => $headers[$key]);
                    }
                    $headers[$key][] = $value;
                } else {
                    $headers[$key] = $value;
                }
            } else {
                $headers[] = $splitLine[0];
            }
        }

        return [$headers, $content];
    }
}
