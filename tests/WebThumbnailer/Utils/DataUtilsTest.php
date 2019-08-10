<?php

namespace WebThumbnailer\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Class DataUtilsTest
 *
 * Unit test DataUtils.
 *
 * @package WebThumbnailer\Utils
 */
class DataUtilsTest extends TestCase
{
    public function testLoadJsonResource()
    {
        $rules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        // Test a random nested value.
        $this->assertEquals('medium', $rules['imgur_single']['options']['size']['default']);
    }

    public function testLoadJsonResourceNoFile()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JSON resource file not found or not readable.');
        DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'nope.json');
    }

    public function testLoadJsonResourceBadSyntax()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/An error occured while parsing JSON file: error code #/');
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        DataUtils::loadJson($path . 'badsyntax.json');
    }
}
