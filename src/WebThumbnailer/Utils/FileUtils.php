<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

class FileUtils
{
    /** @var string Path to resources folder. */
    public const RESOURCES_PATH =
        __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;

    /**
     * Build the path from all given folders, with a trailing /.
     * It will stay a relative or absolute path depending on what's provided.
     *
    // phpcs:ignore Gskema.Sniffs.CompositeCodeElement.FqcnMethodSniff
     * @param string[] $args Suite of path/folders.
     *
     * @return string|false Path with proper directory separators, false if it doesn't exist.
     */
    // phpcs:ignore Gskema.Sniffs.CompositeCodeElement.FqcnMethodSniff
    public static function getPath(string ...$args)
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
     * @return bool True if the rm was successful, false otherwise or if the path is invalid.
     */
    public static function rmdir(string $path): bool
    {
        if (empty($path) || $path == '/' || !static::getPath($path)) {
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
            ) as $value
        ) {
            $value->isDir() ? rmdir($value->getRealPath()) : unlink($value->getRealPath());
        }

        return rmdir($path);
    }
}
