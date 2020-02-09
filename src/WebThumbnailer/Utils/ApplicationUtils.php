<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

use WebThumbnailer\Exception\MissingRequirementException;

class ApplicationUtils
{
    /**
     * @param string[] $required list of required extension names
     *
     * @return bool True if the check is successful.
     *
     * @throws MissingRequirementException
     */
    public static function checkExtensionRequirements(array $required): bool
    {
        foreach ($required as $extension) {
            if (! extension_loaded($extension)) {
                throw new MissingRequirementException(sprintf(
                    'PHP extension php-%s is required and must be loaded',
                    $extension
                ));
            }
        }

        return true;
    }

    /**
     * Checks the PHP version to ensure WT can run
     *
     * @param string $minVersion minimum PHP required version
     * @param string $curVersion current PHP version (use PHP_VERSION)
     *
     * @return bool True if the check is successful.
     *
     * @throws MissingRequirementException
     */
    public static function checkPHPVersion(string $minVersion, string $curVersion): bool
    {
        if (version_compare($curVersion, $minVersion) < 0) {
            throw new MissingRequirementException(sprintf(
                'Your PHP version is obsolete! Expected at least %s, found %s.',
                $minVersion,
                $curVersion
            ));
        }

        return true;
    }
}
