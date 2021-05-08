<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

use WebThumbnailer\TestCase;

/**
 * Unit test DataUtils.
 */
class DataUtilsTest extends TestCase
{
    public function testLoadJsonResource(): void
    {
        $rules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        // Test a random nested value.
        $this->assertEquals('medium', $rules['imgur_single']['options']['size']['default']);
    }

    public function testLoadJsonResourceNoFile(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('JSON resource file not found or not readable.');
        DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'nope.json');
    }

    public function testLoadJsonResourceBadSyntax(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/An error occured while parsing JSON file: error code #/');
        $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
        DataUtils::loadJson($path . 'badsyntax.json');
    }
}
