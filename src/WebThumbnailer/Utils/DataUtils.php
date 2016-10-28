<?php

namespace WebThumbnailer\Utils;

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
     * @throws \Exception JSON file is not readable or badly formatted.
     */
    public static function loadJson($jsonFile)
    {
        if (! file_exists($jsonFile) || ! is_readable($jsonFile)) {
            throw new \Exception('JSON resource file not found or not readable.');
        }
        $data = json_decode(file_get_contents($jsonFile), true);
        if ($data === null) {
            $error = json_last_error();
            throw new \Exception('An error occured while parsing JSON file: error code #'. $error);
        }
        return $data;
    }
}
