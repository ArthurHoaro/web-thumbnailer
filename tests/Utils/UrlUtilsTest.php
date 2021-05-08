<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

use WebThumbnailer\TestCase;

/**
 * Test utility class for URL.
 */
class UrlUtilsTest extends TestCase
{
    /**
     * Test getDomain() from various URL.
     */
    public function testGetDomain(): void
    {
        $expectedDomain = 'domain.tld';
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('domain.tld'));
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('https://domain.tld/blabla/file.php?foo=bar#foobar'));
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('https://domain.tld:443/file.php?foo=bar#foobar'));
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('ftp://DOMAIN.TLD/blabla/file.php?foo=bar#foobar'));

        $this->assertEquals('sub.' . $expectedDomain, UrlUtils::getDomain('sub.domain.tld'));
        $this->assertEquals('localhost', UrlUtils::getDomain('localhost'));
    }

    /**
     * Test getUrlFileExtension from various URL/file type.
     */
    public function testGetUrlFileExtension(): void
    {
        $url = 'http://hostname.tld/path/index.php?arg=value#anchor';
        $this->assertEquals('php', UrlUtils::getUrlFileExtension($url));

        $url = 'http://hostname.tld/path/INDEX.PHP?arg=value#anchor';
        $this->assertEquals('php', UrlUtils::getUrlFileExtension($url));

        $url = 'http://hostname.tld/path/INDEX.tar.gz?arg=value#anchor';
        $this->assertEquals('gz', UrlUtils::getUrlFileExtension($url));

        $url = 'http://hostname.tld/path/?arg=value#anchor';
        $this->assertEquals('', UrlUtils::getUrlFileExtension($url));

        $url = 'http://hostname.tld/path/file.php/otherpath/?arg=value#anchor';
        $this->assertEquals('', UrlUtils::getUrlFileExtension($url));
    }
}
