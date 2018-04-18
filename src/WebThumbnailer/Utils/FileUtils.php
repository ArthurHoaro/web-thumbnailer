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
     * Build the real path from all given folders, with a trailing /.
     *
     * @param string[] $args Suite of path/folders.
     *
     * @return string|bool Real path with proper directory separators, false if it doesn't exist.
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
        $out = realpath($out);
        return $out !== false ? $out . DIRECTORY_SEPARATOR : false;
    }

    /**
     * Remove a directory and its content.
     *
     * @param string $path to delete.
     *
     * @return null|bool Nothing or false if an invalid path is provided.
     */
    public static function rmdir($path)
    {
        if (empty($path) || $path == '/' || ! self::getPath($path)) {
            return false;
        }

        foreach (
            new \RecursiveIteratorIterator(
                 new \RecursiveDirectoryIterator(
                     $path,
                     \FilesystemIterator::SKIP_DOTS |
                     \FilesystemIterator::UNIX_PATHS
                 ),
                 \RecursiveIteratorIterator::CHILD_FIRST
            )
            as $value
        ) {
            $value->isFile() ? unlink($value) : rmdir($value);
        }

        rmdir($path);
    }
}
