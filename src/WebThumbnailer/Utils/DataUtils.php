<?php

namespace WebThumbnailer\Utils;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\Exception\IOException;

/**
 * Class DataUtils
 *
 * Util class for operation regarding data.
 *
 * @package WebThumbnailer\Utils
 */
class DataUtils
{
    /**
     * Read a JSON file, and convert it to an array.
     *
     * @param string $jsonFile JSON file.
     *
     * @return array JSON loaded in an array.
     *
     * @throws IOException       JSON file is not readable
     * @throws BadRulesException JSON file badly formatted.
     */
    public static function loadJson($jsonFile)
    {
        if (! file_exists($jsonFile) || ! is_readable($jsonFile)) {
            throw new IOException('JSON resource file not found or not readable.');
        }
        $data = json_decode(file_get_contents($jsonFile), true);
        if ($data === null) {
            $error = json_last_error();
            $msg = json_last_error_msg();
            throw new BadRulesException('An error occured while parsing JSON file: error code #'. $error .': '. $msg);
        }
        return $data;
    }
}
