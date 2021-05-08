<?php

declare(strict_types=1);

namespace WebThumbnailer\Utils;

use WebThumbnailer\TestCase;

class FileUtilsTests extends TestCase
{
    /**
     * Test getPath() with a valid path.
     */
    public function testGetPathValid(): void
    {
        $path = FileUtils::getPath(__DIR__, '..', 'resources');
        $this->assertRegExp('#^/.*?/tests/resources/$#', $path);
    }

    /**
     * Test getPath() with a non existent path.
     */
    public function testGetPathNonExistent(): void
    {
        $this->assertFalse(FileUtils::getPath(__DIR__, 'nope'));
    }

    /**
     * Test getPath() with a non existent path.
     */
    public function testGetPathEmpty(): void
    {
        $this->assertFalse(FileUtils::getPath());
    }

    /**
     * Test rmdir with a valid path.
     */
    public function testRmdirValid(): void
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
    public function testRmdirInvalid(): void
    {
        $this->assertFalse(FileUtils::rmdir('nope/'));
        $this->assertFalse(FileUtils::rmdir('/'));
        $this->assertFalse(FileUtils::rmdir(''));
    }
}
