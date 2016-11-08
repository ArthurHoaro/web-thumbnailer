<?php

namespace WebThumbnailer\Application;

use WebThumbnailer\Utils\FileUtils;

/**
 * Class CacheManagerTest
 *
 * Test the cache manager.
 *
 * @package WebThumbnailer\Application
 */
class CacheManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string $cache relative path.
     */
    protected static $cache = 'tests/WebThumbnailer/workdir/cache/';

    /**
     * Load test config before running tests.
     */
    public static function setUpBeforeClass()
    {
        $resource = 'tests/WebThumbnailer/resources/';
        ConfigManager::$configFiles = [$resource . 'settings-useful.json'];
        ConfigManager::reload();
    }

    /**
     * Remove cache folder after every tests.
     */
    public function tearDown()
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
        $this->assertContains(self::$cache . CacheManager::TYPE_THUMB .'/', $path);
        $path = CacheManager::getCachePath(CacheManager::TYPE_FINDER);
        $this->assertTrue(is_dir($path));
        $this->assertContains(self::$cache . CacheManager::TYPE_FINDER .'/', $path);
    }

    /**
     * Test getCachePath() with an invalid type.
     *
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Unknown cache type/
     */
    public function testGetCachePathInvalidType()
    {
        CacheManager::getCachePath('nope');
    }

    /**
     * Test getCachePath() without cache folder.
     *
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Cache folders are not writable/
     *
     */
    public function testGetCachePathNoFolder()
    {
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
        $whateverDir = self::$cache . 'thumb/whatever.io/';
        $this->assertTrue(is_dir($whateverDir));
        $this->assertContains($whateverDir, $cacheFile);
        // sha1 sum + dimensions
        $this->assertContains('0a35602901944a0c6d853da2a5364665c2bda069' . '51200' . '.png', $cacheFile);
    }

    /**
     * Test isCacheValid() with an existing file.
     */
    public function testIsCacheValidExisting()
    {
        $domain = 'whatever.io';
        $filename = '0a35602901944a0c6d853da2a5364665c2bda06951200.png';
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
        $filename = '0a35602901944a0c6d853da2a5364665c2bda0695120.png';
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
        $this->assertTrue(is_dir(self::$cache . '/thumb/' . $domain));
    }
}
