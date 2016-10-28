<?php

namespace WebThumbnailer\Utils;

/**
 * Class DataUtilsTest
 *
 * Unit test DataUtils.
 *
 * @package WebThumbnailer\Utils
 */
class DataUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadJsonResource()
    {
        $rules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        // Test a random nested value.
        $this->assertEquals('medium', $rules['imgur_single']['options']['size']['default']);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage JSON resource file not found or not readable.
     */
    public function testLoadJsonResourceNoFile()
    {
        DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'nope.json');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /An error occured while parsing JSON file: error code #/
     */
    public function testLoadJsonResourceBadSyntax()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        DataUtils::loadJson($path . 'badsyntax.json');
    }
}
