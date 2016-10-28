<?php

namespace WebThumbnailer\Utils;

/**
 * Class FinderUtils
 *
 * Utility class used by Finders.
 *
 * @package WebThumbnailer\Utils
 */
class FinderUtils
{
    /**
     * Format a regex for PHP, with delimiters and flags.
     *
     * Using brackets delimiter, can't search bracket parameter.
     * Fine for WebThumnailer.
     *
     * @param string $regex regex to format
     * @param string $flags regex flags
     *
     * @return string Formatted regex.
     */
    public static function buildRegex($regex, $flags)
    {
        return '{' . $regex . '}' . $flags;
    }

    /**
     * Make sure that given rules contain all mandatory fields.
     * Support nested arrays.
     *
     * @param array $rules         List of loaded rules.
     * @param array $mandatoryKeys List of mandatory rules expected.
     *
     * @return bool if all mandatory rules are provided, false otherwise.
     */
    public static function checkMandatoryRules($rules, $mandatoryKeys)
    {
        foreach ($mandatoryKeys as $key => $value) {
            if (is_array($value)) {
                if (isset($rules[$key])) {
                    return self::checkMandatoryRules($rules[$key], $value);
                } else {
                    return false;
                }
            } else {
                if (! isset($rules[$value])) {
                    return false;
                }
            }
        }

        return true;
    }
}
