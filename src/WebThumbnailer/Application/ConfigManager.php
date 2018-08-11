<?php

namespace WebThumbnailer\Application;

use WebThumbnailer\Utils\DataUtils;
use WebThumbnailer\Utils\FileUtils;

/**
 * Class ConfigManager
 *
 * Load configuration from JSON files.
 *
 * @package WebThumbnailer\Application
 */
class ConfigManager
{
    /**
     * @var string Flag telling a setting is not found.
     */
    public static $NOT_FOUND = 'NOT_FOUND';

    /**
     * @var array List of JSON configuration file path.
     */
    public static $configFiles = [
        FileUtils::RESOURCES_PATH . 'settings.json',
    ];

    /**
     * @var array Loaded config array.
     */
    protected static $loadedConfig;

    /**
     * Rebuild the loaded config array from config files.
     */
    public static function reload()
    {
        self::initialize();
    }

    /**
     * Initialize loaded conf in ConfigManager.
     */
    protected static function initialize()
    {
        self::$loadedConfig = [];
        foreach (self::$configFiles as $configFile) {
            self::$loadedConfig = array_replace_recursive(self::$loadedConfig, DataUtils::loadJson($configFile));
        }
    }

    /**
     * Add a configuration file.
     *
     * @param string $file path.
     */
    public static function addFile($file)
    {
        self::$configFiles[] = $file;
        self::initialize();
    }

    /**
     * Clear the current config
     */
    public static function clear()
    {
        self::$configFiles = [
            FileUtils::RESOURCES_PATH . 'settings.json',
        ];
        self::reload();
    }

    /**
     * Get a setting.
     *
     * Supports nested settings with dot separated keys.
     * Eg. 'config.stuff.option' will find $conf[config][stuff][option],
     * or in JSON:
     *   { "config": { "stuff": {"option": "mysetting" } } } }
     *
     * @param string $setting Asked setting, keys separated with dots.
     * @param mixed  $default Default value if not found.
     *
     * @return mixed Found setting, or the default value.
     */
    public static function get($setting, $default = '')
    {
        if (empty(self::$loadedConfig)) {
            self::initialize();
        }

        $settings = explode('.', $setting);
        $value = self::getConfig($settings, self::$loadedConfig);
        if ($value == self::$NOT_FOUND) {
            return $default;
        }
        return $value;
    }

    /**
     * Recursive function which find asked setting in the loaded config.
     *
     * @param array $settings Ordered array which contains keys to find.
     * @param array $config   Loaded settings, then sub-array.
     *
     * @return mixed Found setting or NOT_FOUND flag.
     */
    protected static function getConfig($settings, $config)
    {
        if (! is_array($settings) || count($settings) == 0) {
            return self::$NOT_FOUND;
        }

        $setting = array_shift($settings);
        if (!isset($config[$setting])) {
            return self::$NOT_FOUND;
        }

        if (count($settings) > 0) {
            return self::getConfig($settings, $config[$setting]);
        }
        return $config[$setting];
    }
}
