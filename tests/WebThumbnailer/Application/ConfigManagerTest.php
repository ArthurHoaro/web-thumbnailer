<?php

namespace WebThumbnailer\Application;

use WebThumbnailer\Utils\DataUtils;
use WebThumbnailer\Utils\FileUtils;

/**
 * Class ConfigManagerTest
 *
 * Test the configuration manager.
 *
 * @package WebThumbnailer\Application
 */
class ConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Before each test method.
     */
    public function setUp()
    {
        ConfigManager::$configFiles = [];
        ConfigManager::reload();
    }

    /**
     * Load simple config file and get nested values.
     */
    public function testLoadConfig()
    {
        ConfigManager::$configFiles = [FileUtils::getPath(__DIR__, '..', 'resources') . 'settingsok.json'];

        $value = ConfigManager::get('nested.setting.1.top');
        $this->assertEquals('value', $value);

        $value = ConfigManager::get('nested.setting');
        $this->assertEquals(2, count($value));
    }

    /**
     * Load config file and read non existing keys.
     */
    public function testLoadConfigNotFound()
    {
        ConfigManager::$configFiles = [FileUtils::getPath(__DIR__, '..', 'resources') . 'empty.json'];

        $value = ConfigManager::get(null);
        $this->assertEmpty($value);

        $value = ConfigManager::get('nope');
        $this->assertEmpty($value);

        $value = ConfigManager::get('nope.nope');
        $this->assertEmpty($value);

        $value = ConfigManager::get('nope', false);
        $this->assertEquals(false, $value);
    }

    /**
     * Load multiple config files with overriding value.
     */
    public function testLoadConfigMultiFiles()
    {
        ConfigManager::$configFiles = [
            FileUtils::getPath(__DIR__, '..', 'resources') . 'settingsok.json',
            FileUtils::getPath(__DIR__, '..', 'resources') . 'settings-multiple.json',
        ];

        $value = ConfigManager::get('nested.setting.1.top');
        $this->assertEquals('value', $value);

        $value = ConfigManager::get('key');
        $this->assertEquals('value2', $value);
    }

    /**
     * Add a second config file, with overriding setting.
     */
    public function testLoadConfigMultiFilesReloaded()
    {
        ConfigManager::$configFiles = [FileUtils::getPath(__DIR__, '..', 'resources') . 'settingsok.json'];

        $value = ConfigManager::get('nested.setting.1.top');
        $this->assertEquals('value', $value);
        $this->assertEquals('foo', ConfigManager::get('key'));

        ConfigManager::$configFiles[] = FileUtils::getPath(__DIR__, '..', 'resources') . 'settings-multiple.json';
        ConfigManager::reload();

        $value = ConfigManager::get('key');
        $this->assertEquals('value2', $value);
    }

    /**
     * Add a second config file, with overriding setting (using addFile()).
     */
    public function testLoadConfigAddFile()
    {
        ConfigManager::$configFiles = [FileUtils::getPath(__DIR__, '..', 'resources') . 'settingsok.json'];

        $value = ConfigManager::get('nested.setting.1.top');
        $this->assertEquals('value', $value);
        $this->assertEquals('foo', ConfigManager::get('key'));

        ConfigManager::addFile(FileUtils::getPath(__DIR__, '..', 'resources') . 'settings-multiple.json');

        $value = ConfigManager::get('key');
        $this->assertEquals('value2', $value);
    }
}
