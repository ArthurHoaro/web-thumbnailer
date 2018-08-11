<?php

namespace WebThumbnailer\Utils;

/**
 * Class FileUtils
 *
 * @package WebThumbnailer\Utils
 */
class FileUtils
{
    /**
     * @var string Path to resources folder.
     */
    const RESOURCES_PATH = __DIR__ . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'resources'. DIRECTORY_SEPARATOR;

    /**
     * Build the path from all given folders, with a trailing /.
     * It will stay a relative or absolute path depending on what's provided.
     *
     * @param string[] $args Suite of path/folders.
     *
     * @return string|bool Path with proper directory separators, false if it doesn't exist.
     */
    public static function getPath(...$args)
    {
        $out = '';
        if (empty($args)) {
            return false;
        }
        foreach ($args as $arg) {
            $out .= rtrim(rtrim($arg, '/'), '\\') . DIRECTORY_SEPARATOR;
        }
        return is_dir($out) ? $out : false;
    }

    /**
     * Remove a directory and its content.
     *
     * @param string $path to delete.
     *
     * @return bool Nothing or false if an invalid path is provided.
     */
    public static function rmdir($path)
    {
        if (empty($path) || $path == '/' || ! self::getPath($path)) {
            return false;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS |
                     \FilesystemIterator::UNIX_PATHS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $value) {
            $value->isFile() ? unlink($value) : rmdir($value);
        }

        return rmdir($path);
    }
}
