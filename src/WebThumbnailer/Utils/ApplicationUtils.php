<?php


namespace WebThumbnailer\Utils;

use WebThumbnailer\Exception\MissingRequirementException;

class ApplicationUtils
{
    /**
     * @param array $required list of required extension names
     *
     * @throws MissingRequirementException
     */
    public static function checkExtensionRequirements($required)
    {
        foreach ($required as $extension) {
            if (! extension_loaded($extension)) {
                throw new MissingRequirementException(sprintf(
                    'PHP extension php-%s is required and must be loaded',
                    $extension
                ));
            }
        }
    }

    /**
     * Checks the PHP version to ensure WT can run
     *
     * @param string $minVersion minimum PHP required version
     * @param string $curVersion current PHP version (use PHP_VERSION)
     *
     * @throws MissingRequirementException
     */
    public static function checkPHPVersion($minVersion, $curVersion)
    {
        if (version_compare($curVersion, $minVersion) < 0) {
            throw new MissingRequirementException(sprintf(
                'Your PHP version is obsolete! Expected at least %s, found %s.',
                $minVersion,
                $curVersion
            ));
        }
    }
}
