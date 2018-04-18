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
     * (PHP 5.3 compliant)
     *
     * @param ...strings $_ Suite of path/folders.
     *
     * @return string|bool Real path with proper directory separators, false if it doesn't exist.
     */
    public static function getPath()
    {
        $out = '';
        $nbArgs = func_num_args();
        if ($nbArgs == 0) {
            return false;
        }
        for($i = 0; $i < $nbArgs; $i++) {
            $out .= rtrim(rtrim(func_get_arg($i), '/'), '\\') . DIRECTORY_SEPARATOR;
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
