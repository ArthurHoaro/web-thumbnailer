<?php

namespace WebThumbnailer\Application;

use WebThumbnailer\TestCase;
use WebThumbnailer\Utils\FileUtils;

/**
 * Class CacheManagerTest
 *
 * Test the cache manager.
 *
 * @package WebThumbnailer\Application
 */
class CacheManagerTest extends TestCase
{
    /**
     * @var string $cache relative path.
     */
    protected static $cache = 'tests/WebThumbnailer/workdir/cache/';

    /**
     * Load test config before running tests.
     */
    public function setUp(): void
    {
        $resource = 'tests/WebThumbnailer/resources/';
        ConfigManager::$configFiles = [$resource . 'settings-useful.json'];
        ConfigManager::reload();
    }

    /**
     * Remove cache folder after every tests.
     */
    public function tearDown(): void
    {
        FileUtils::rmdir(ConfigManager::get('settings.path.cache'));
    }

    /**
     * Test getCachePath().
     */
    public function testGetCachePathValid()
    {
        $path = CacheManager::getCachePath(CacheManager::TYPE_THUMB);
        $this->assertTrue(is_dir($path));
        $this->assertStringContainsString(self::$cache . CacheManager::TYPE_THUMB . '/', $path);
        $path = CacheManager::getCachePath(CacheManager::TYPE_FINDER);
        $this->assertTrue(is_dir($path));
        $this->assertStringContainsString(self::$cache . CacheManager::TYPE_FINDER . '/', $path);
    }

    /**
     * Test getCachePath() with an invalid type.
     */
    public function testGetCachePathInvalidType()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/Unknown cache type/');
        CacheManager::getCachePath('nope');
    }

    /**
     * Test getCachePath() without cache folder.
     */
    public function testGetCachePathNoFolder()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/Cache folders are not writable/');
        CacheManager::getCachePath(CacheManager::TYPE_THUMB, true);
    }

    /**
     * Test getCacheFilePath
     */
    public function testGetCacheFilePathValid()
    {
        $url = 'http://whatever.io';
        $domain = 'whatever.io';
        $type = CacheManager::TYPE_THUMB;
        $width = 512;
        $height = 0;
        $cacheFile = CacheManager::getCacheFilePath($url, $domain, $type, $width, $height, false);
        $whateverDir = self::$cache . 'thumb/' . md5($domain) . '/';
        $this->assertTrue(is_dir($whateverDir));
        $this->assertStringContainsString($whateverDir, $cacheFile);
        // sha1 sum + dimensions
        $this->assertStringContainsString('0a35602901944a0c6d853da2a5364665c2bda069' . '51200' . '.jpg', $cacheFile);
    }

    /**
     * Test isCacheValid() with an existing file.
     */
    public function testIsCacheValidExisting()
    {
        $domain = 'whatever.io';
        $filename = '0a35602901944a0c6d853da2a5364665c2bda06951200.jpg';
        mkdir(self::$cache . '/thumb/' . $domain, 0755, true);
        $cacheFile = self::$cache . '/thumb/' . $domain . '/' . $filename;
        touch($cacheFile);

        $this->assertTrue(CacheManager::isCacheValid($cacheFile, $domain, CacheManager::TYPE_THUMB));
    }

    /**
     * Test isCacheValid() with an outdated file.
     */
    public function testIsCacheValidExpired()
    {
        $domain = 'whatever.io';
        $filename = '0a35602901944a0c6d853da2a5364665c2bda0695120.jpg';
        mkdir(self::$cache . '/thumb/' . $domain, 0755, true);
        $cacheFile = self::$cache . '/thumb/' . $domain . '/' . $filename;
        touch($cacheFile, time() - ConfigManager::get('settings.cache_duration') - 1);

        $this->assertFalse(CacheManager::isCacheValid($cacheFile, $domain, CacheManager::TYPE_THUMB));
    }
    
    /**
     * Test isCacheValid() without any file.
     */
    public function testIsCacheValidNotExistent()
    {
        $domain = 'whatever.io';
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertTrue(is_dir(self::$cache . '/thumb/' . md5($domain)));
    }

    /**
     * Test isCacheValid() without any file and infinite cache setting.
     */
    public function testIsCacheValidInfiniteNotExistent()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile('tests/WebThumbnailer/resources/settings-infinite-cache.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertTrue(is_dir(self::$cache . '/thumb/' . md5($domain)));
    }

    /**
     * Test isCacheValid() with an existing file and infinite cache setting.
     */
    public function testIsCacheValidInfiniteExisting()
    {
        $domain = 'whatever.io';
        $filename = '0a35602901944a0c6d853da2a5364665c2bda06951200.jpg';
        mkdir(self::$cache . '/thumb/' . $domain, 0755, true);
        $cacheFile = self::$cache . '/thumb/' . $domain . '/' . $filename;
        touch($cacheFile);

        ConfigManager::addFile('tests/WebThumbnailer/resources/settings-infinite-cache.json');
        $this->assertTrue(CacheManager::isCacheValid($cacheFile, $domain, CacheManager::TYPE_THUMB));
    }

    /**
     * Test isCacheValid() with an existing file and infinite cache setting.
     */
    public function testIsCacheValidInfiniteExistingOneYear()
    {
        $domain = 'whatever.io';
        $filename = '0a35602901944a0c6d853da2a5364665c2bda06951200.jpg';
        mkdir(self::$cache . '/thumb/' . $domain, 0755, true);
        $cacheFile = self::$cache . '/thumb/' . $domain . '/' . $filename;
        touch($cacheFile, time() - 3600 * 24 * 31 * 12);

        ConfigManager::addFile('tests/WebThumbnailer/resources/settings-infinite-cache.json');
        $this->assertTrue(CacheManager::isCacheValid($cacheFile, $domain, CacheManager::TYPE_THUMB));
    }

    /**
     * Check that htaccess file is properly created (finder -> denied).
     */
    public function testHtaccessCreationDenied()
    {
        $domain = 'whatever.io';
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_FINDER));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess_denied', self::$cache . '/finder/.htaccess');
    }

    /**
     * Check that htaccess file is properly created (thumb -> granted).
     */
    public function testHtaccessCreationGranted()
    {
        $domain = 'whatever.io';
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess_granted', self::$cache . '/thumb/.htaccess');
    }

    /**
     * Check that htaccess file is properly created with Apache 2.2 forced (finder -> granted).
     */
    public function testHtaccess22CreationDenied()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile(__DIR__ . '/../resources/settings-apache22.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_FINDER));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess22_denied', self::$cache . '/finder/.htaccess');
    }

    /**
     * Check that htaccess file is properly created with Apache 2.2 forced (finder -> denied).
     */
    public function testHtaccess22CreationGranted()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile(__DIR__ . '/../resources/settings-apache22.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess22_granted', self::$cache . '/thumb/.htaccess');
    }

    /**
     * Check that htaccess file is properly created with Apache 2.4 forced (finder -> granted).
     */
    public function testHtaccess24CreationDenied()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile(__DIR__ . '/../resources/settings-apache24.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_FINDER));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess24_denied', self::$cache . '/finder/.htaccess');
    }

    /**
     * Check that htaccess file is properly created with Apache 2.4 forced (finder -> denied).
     */
    public function testHtaccess24CreationGranted()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile(__DIR__ . '/../resources/settings-apache24.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess24_granted', self::$cache . '/thumb/.htaccess');
    }

    /**
     * Check that htaccess file is properly created with Apache invalid version forced (finder -> granted).
     */
    public function testHtaccessInvalidCreationDenied()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile(__DIR__ . '/../resources/settings-apache-ko.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_FINDER));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess_denied', self::$cache . '/finder/.htaccess');
    }

    /**
     * Check that htaccess file is properly created with Apache invalid version forced (finder -> denied).
     */
    public function testHtaccessInvalidCreationGranted()
    {
        $domain = 'whatever.io';
        ConfigManager::addFile(__DIR__ . '/../resources/settings-apache-ko.json');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertFileEquals(__DIR__ . '/../resources/htaccess_granted', self::$cache . '/thumb/.htaccess');
    }

    /**
     * Check that htaccess file is not overridden if it already exists
     */
    public function testHtaccessDontOverride()
    {
        $domain = 'whatever.io';
        $htaccessFile =  self::$cache . '/thumb/.htaccess';
        mkdir(self::$cache . '/thumb/', 0755, true);
        file_put_contents($htaccessFile, $content = 'kek');
        $this->assertFalse(CacheManager::isCacheValid('nope', $domain, CacheManager::TYPE_THUMB));
        $this->assertEquals($content, file_get_contents($htaccessFile));
    }
}
