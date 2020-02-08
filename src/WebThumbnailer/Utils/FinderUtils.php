<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

/**
 * Utility class used by Finders.
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
    public static function buildRegex(string $regex, string $flags): string
    {
        return '{' . $regex . '}' . $flags;
    }

    /**
     * Make sure that given rules contain all mandatory fields.
     * Support nested arrays.
     *
     * @param mixed[] $rules         List of loaded rules.
     * @param mixed[] $mandatoryKeys List of mandatory rules expected.
     *
     * @return bool if all mandatory rules are provided, false otherwise.
     */
    public static function checkMandatoryRules(array $rules, array $mandatoryKeys): bool
    {
        foreach ($mandatoryKeys as $key => $value) {
            if (is_array($value)) {
                if (isset($rules[$key])) {
                    return static::checkMandatoryRules($rules[$key], $value);
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
