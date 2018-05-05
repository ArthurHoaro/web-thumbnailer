<?php

namespace WebThumbnailer\Utils;

/**
 * Class UrlUtilsTest
 *
 * Test utility class for URL.
 *
 * @package WebThumbnailer\Utils
 */
class UrlUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test getDomain() from various URL.
     */
    public function testGetDomain()
    {
        $expectedDomain = 'domain.tld';
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('domain.tld'));
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('https://domain.tld/blabla/file.php?foo=bar#foobar'));
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('https://domain.tld:443/file.php?foo=bar#foobar'));
        $this->assertEquals($expectedDomain, UrlUtils::getDomain('ftp://DOMAIN.TLD/blabla/file.php?foo=bar#foobar'));

        $this->assertEquals('sub.'. $expectedDomain, UrlUtils::getDomain('sub.domain.tld'));
        $this->assertEquals('localhost', UrlUtils::getDomain('localhost'));
    }

    /**
     * Test generateRelativeUrlFromPath().
     */
    public function testGenerateRelativeUrlFromPath()
    {
        $server = [
            'DOCUMENT_ROOT' => '/home/website/',
            'SCRIPT_NAME' => '/index.php',
        ];
        $file   = '/home/website/resource/file.txt';
        $file2   = '/home/website/file.txt';

        $this->assertEquals('resource/file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file));
        $this->assertEquals('file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file2));
    }

    /**
     * Test generateRelativeUrlFromPath().
     */
    public function testGenerateRelativeUrlFromPathWithoutTrailingSlash()
    {
        $server = [
            'DOCUMENT_ROOT' => '/home/website/',
            'SCRIPT_NAME' => 'index.php',
        ];
        $file   = '/home/website/resource/file.txt';
        $file2   = '/home/website/file.txt';

        $this->assertEquals('resource/file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file));
        $this->assertEquals('file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file2));
    }

    /**
     * Test generateRelativeUrlFromPath().
     */
    public function testGenerateRelativeUrlFromPathWithSubdir()
    {
        $server = [
            'DOCUMENT_ROOT' => '/home/website/',
            'SCRIPT_NAME' => 'subdir/file.php',
        ];
        $file   = '/home/website/subdir/resource/file.txt';
        $file2   = '/home/website/subdir/file.txt';

        $this->assertEquals('resource/file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file));
        $this->assertEquals('file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file2));
    }

    /**
     * Test generateRelativeUrlFromPath().
     */
    public function testGenerateRelativeUrlFromPathWithSubdirAndTralingSlash()
    {
        $server = [
            'DOCUMENT_ROOT' => '/home/website/',
            'SCRIPT_NAME' => '/subdir/file.php',
        ];
        $file   = '/home/website/subdir/resource/file.txt';
        $file2   = '/home/website/subdir/file.txt';

        $this->assertEquals('resource/file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file));
        $this->assertEquals('file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file2));
    }

    /**
     * Test generateRelativeUrlFromPath().
     */
    public function testGenerateRelativeUrlFromPathWithSubdirInvalidScript()
    {
        $server = [
            'DOCUMENT_ROOT' => '/home/website/',
            'SCRIPT_NAME' => 'subdir/not-a-script',
        ];
        $file   = '/home/website/subdir/resource/file.txt';
        $file2   = '/home/website/subdir/file.txt';

        $this->assertEquals('subdir/resource/file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file));
        $this->assertEquals('subdir/file.txt', UrlUtils::generateRelativeUrlFromPath($server, $file2));
    }

    /**
     * Test getUrlFileExtension from various URL/file type.
     */
    public function testGetUrlFileExtension()
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
