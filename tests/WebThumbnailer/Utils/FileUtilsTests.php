<?php

namespace WebThumbnailer\Utils;

use PHPUnit\Framework\TestCase;

/**
 * Class FileUtilsTests
 *
 * @package WebThumbnailer\Utils
 */
class FileUtilsTests extends TestCase
{
    /**
     * Test getPath() with a valid path.
     */
    public function testGetPathValid()
    {
        $path = FileUtils::getPath(__DIR__, '..', 'resources');
        $this->assertRegExp('#^/.*?/tests/WebThumbnailer/resources/$#', $path);
    }

    /**
     * Test getPath() with a non existent path.
     */
    public function testGetPathNonExistent()
    {
        $this->assertFalse(FileUtils::getPath(__DIR__, 'nope'));
    }

    /**
     * Test getPath() with a non existent path.
     */
    public function testGetPathEmpty()
    {
        $this->assertFalse(FileUtils::getPath());
    }

    /**
     * Test rmdir with a valid path.
     */
    public function testRmdirValid()
    {
        mkdir('tmp');
        mkdir('tmp/tmp');
        touch('tmp/file');
        touch('tmp/tmp/file');
        $this->assertTrue(is_dir('tmp'));
        $this->assertTrue(is_dir('tmp/tmp'));
        FileUtils::rmdir('tmp');
        $this->assertFalse(is_dir('tmp'));
    }

    /**
     * Test rmdir with a invalid paths.
     */
    public function testRmdirInvalid()
    {
        $this->assertFalse(FileUtils::rmdir('nope/'));
        $this->assertFalse(FileUtils::rmdir('/'));
        $this->assertFalse(FileUtils::rmdir(''));
    }
}
