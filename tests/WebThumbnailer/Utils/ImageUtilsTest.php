<?php

namespace WebThumbnailer\Utils;

/**
 * Class ImageUtilsTest
 *
 * Test image utils methods, especially thumbnail rendering.
 *
 * @package WebThumbnailer\utils
 */
class ImageUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Image as string.
     */
    public static $imageSource;

    /**
     * @var string Working directory path.
     */
    public static $WORKDIR = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'workdir'.DIRECTORY_SEPARATOR;

    /**
     * Regenerate the image before every tests.
     */
    public function setUp()
    {
        // From http://php.net/imagecreatefromstring
        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        self::$imageSource = base64_decode($data);
    }

    /**
     * Remove the test image in workdir after every test.
     * Ignore errors.
     */
    public function tearDown()
    {
        @unlink(self::$WORKDIR . 'file1.png');
    }

    /**
     * Test generating valid thumbnails.
     */
    public function testGenerateThumbnail()
    {
        $path = self::$WORKDIR . 'file1.png';
        // Original size.
        ImageUtils::generateThumbnail(self::$imageSource, $path, 28, 18);
        $im = imagecreatefrompng($path);
        $this->assertEquals(28, imagesx($im));
        $this->assertEquals(18, imagesy($im));
        unlink($path);

        // Reduce size.
        ImageUtils::generateThumbnail(self::$imageSource, $path, 14, 9);
        $im = imagecreatefrompng($path);
        $this->assertEquals(14, imagesx($im));
        $this->assertEquals(9, imagesy($im));
        unlink($path);

        // Bigger size: must be changed to original size.
        ImageUtils::generateThumbnail(self::$imageSource, $path, 56, 36);
        $im = imagecreatefrompng($path);
        $this->assertEquals(28, imagesx($im));
        $this->assertEquals(18, imagesy($im));
        unlink($path);

        // Only specify width.
        ImageUtils::generateThumbnail(self::$imageSource, $path, 14, 0);
        $im = imagecreatefrompng($path);
        $this->assertEquals(14, imagesx($im));
        $this->assertEquals(9, imagesy($im));
        unlink($path);

        // Only specify heigth.
        ImageUtils::generateThumbnail(self::$imageSource, $path, 0, 9);
        $im = imagecreatefrompng($path);
        $this->assertEquals(14, imagesx($im));
        $this->assertEquals(9, imagesy($im));
        unlink($path);
    }

    /**
     * Generate a thumbnail into a non existent folder => Exception.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Target file is not writable.
     */
    public function testGenerateThumbnailUnwritable()
    {
        $path = self::$WORKDIR . 'nope' . DIRECTORY_SEPARATOR . 'file';
        @ImageUtils::generateThumbnail(self::$imageSource, $path, 28, 18);
    }

    /**
     * Generate a thumbnail from a string which is not an image => NotAnImageException.
     *
     * @expectedException \WebThumbnailer\Exception\NotAnImageException
     */
    public function testGenerateThumbnailNotAnImage()
    {
        $path = self::$WORKDIR . 'file1.png';
        ImageUtils::generateThumbnail('virus.exe', $path, 28, 18);
    }

    /**
     * Generate a thumbnail with bad sizes => Exception.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Could not generate the thumbnail from source image.
     */
    public function testGenerateThumbnailBadSize()
    {
        $path = self::$WORKDIR . 'file1.png';
        @ImageUtils::generateThumbnail(self::$imageSource, $path, -1, -1);
    }

    /**
     * Generate a thumbnail with bad sizes (Double 0) => Exception.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage At least maxwidth or maxheight needs to be defined.
     */
    public function testGenerateThumbnailBadSizeDoubleZero()
    {
        $path = self::$WORKDIR . 'file1.png';
        @ImageUtils::generateThumbnail(self::$imageSource, $path, 0, 0);
    }

    /**
     * Check that a string is an image.
     */
    public function testIsStringImageTrue()
    {
        $this->assertTrue(ImageUtils::isImageString(self::$imageSource));
    }

    /**
     * Check that a string is not an image.
     */
    public function testIsStringImageFalse()
    {
        $this->assertFalse(ImageUtils::isImageString('string'));
    }
}
