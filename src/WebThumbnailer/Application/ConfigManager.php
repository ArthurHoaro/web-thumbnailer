<?php

declare(strict_types=1);

namespace WebThumbnailer\Application;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\IOException;
use WebThumbnailer\Utils\DataUtils;
use WebThumbnailer\Utils\FileUtils;

/**
 * Load configuration from JSON files.
 */
class ConfigManager
{
    /** @var string Flag telling a setting is not found. */
    public const NOT_FOUND = 'NOT_FOUND';

    /** @var string[] List of JSON configuration file path. */
    public static $configFiles = [
        FileUtils::RESOURCES_PATH . 'settings.json',
    ];

    /** @var mixed[] Loaded config array. */
    protected static $loadedConfig;

    /**
     * Rebuild the loaded config array from config files.
     *
     * @throws IOException
     * @throws BadRulesException
     */
    public static function reload(): void
    {
        static::initialize();
    }

    /**
     * Initialize loaded conf in ConfigManager.
     *
     * @throws IOException
     * @throws BadRulesException
     */
    protected static function initialize(): void
    {
        static::$loadedConfig = [];
        foreach (static::$configFiles as $configFile) {
            static::$loadedConfig = array_replace_recursive(static::$loadedConfig, DataUtils::loadJson($configFile));
        }
    }

    /**
     * Add a configuration file.
     *
     * @param string $file path.
     *
     * @throws BadRulesException
     * @throws IOException
     */
    public static function addFile(string $file): void
    {
        static::$configFiles[] = $file;
        static::initialize();
    }

    /**
     * Clear the current config
     *
     * @throws BadRulesException
     * @throws IOException
     */
    public static function clear(): void
    {
        static::$configFiles = [
            FileUtils::RESOURCES_PATH . 'settings.json',
        ];
        static::reload();
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
     *
     * @throws BadRulesException
     * @throws IOException
     */
    public static function get(string $setting, $default = '')
    {
        if (empty(static::$loadedConfig)) {
            static::initialize();
        }

        $settings = explode('.', $setting);
        $value = static::getConfig($settings, static::$loadedConfig);
        if ($value == static::NOT_FOUND) {
            return $default;
        }
        return $value;
    }

    /**
     * Recursive function which find asked setting in the loaded config.
     *
     * @param string[] $settings Ordered array which contains keys to find.
     * @param mixed[]  $config   Loaded settings, then sub-array.
     *
     * @return mixed Found setting or NOT_FOUND flag.
     */
    protected static function getConfig(array $settings, array $config)
    {
        if (count($settings) === 0) {
            return static::NOT_FOUND;
        }

        $setting = array_shift($settings);
        if (!isset($config[$setting])) {
            return static::NOT_FOUND;
        }

        if (count($settings) > 0) {
            return static::getConfig($settings, $config[$setting]);
        }

        return $config[$setting];
    }
}
