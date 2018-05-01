<?php

namespace WebThumbnailer\Application;

use WebThumbnailer\Utils\FileUtils;
use WebThumbnailer\Utils\SizeUtils;
use WebThumbnailer\WebThumbnailer;

/**
 * Class ThumbnailerTest
 *
 * Rely on UrlRegexFinder.
 *
 * @package WebThumbnailer\Application
 */
class ThumbnailerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Gravatar image link.
     */
    public static $gravatarLink = 'https://gravatar.com/avatar/69ae657aa40c6c777aa2f391a63f327f';

    /**
     * @var string Associated Gravatar thumbnail.
     */
    public static $gravatarThumb = 'https://gravatar.com/avatar/69ae657aa40c6c777aa2f391a63f327f?s=320';

    public function setUp()
    {
        FileUtils::rmdir(__DIR__ .'/../../../cache/finder');
        FileUtils::rmdir(__DIR__ .'/../../../cache/thumb');
    }

    /**
     * Test strictHotlinkThumbnail().
     */
    public function testStrictHotlinkThumbnail()
    {
        $options = [WebThumbnailer::HOTLINK_STRICT];
        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $this->assertEquals(self::$gravatarThumb, $thumburl);
    }

    /**
     * Test strictHotlinkThumbnail() with a domains which doesn't allow hotlink.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Hotlink is not supported for this URL.
     */
    public function testStrictHotlinkThumbnailInvalid()
    {
        // I don't know any website where hotlink is disabled.
        // FIXME! Use test rule.
        $this->markTestSkipped();
        $options = [WebThumbnailer::HOTLINK_STRICT];
        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        //$this->assertEquals(self::$gravatarThumb, $thumburl);
    }

    /**
     * Test hotlinkThumbnail().
     */
    public function testHotlinkThumbnail()
    {
        $options = array(WebThumbnailer::HOTLINK);
        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $this->assertEquals(self::$gravatarThumb, $thumburl);
    }

    /**
     * Test hotlinkThumbnail() with a domains which doesn't allow hotlink => download mode.
     */
    public function testHotlinkThumbnailDownload()
    {
        // I don't know any website where hotlink is disabled.
        // FIXME! Use test rule.
        $this->markTestSkipped();
        $options = [WebThumbnailer::HOTLINK_STRICT];
        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        //$this->assertEquals(self::$gravatarThumb, $thumburl);
    }

    /**
     *Test downloadThumbnail().
     */
    public function testDownloadThumbnailValid()
    {
        $options = array(WebThumbnailer::DOWNLOAD);
        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $fileHash = hash('sha1', self::$gravatarThumb);
        $this->assertContains('/cache/thumb/'. md5('gravatar.com') .'/'. $fileHash .'1601600.jpg', $thumburl);
        unlink($thumburl);
    }

    /**
     *Test downloadThumbnail() with both width and height defined.
     */
    public function testDownloadSizedThumbnailBoth()
    {
        $options = array(
            WebThumbnailer::DOWNLOAD,
            WebThumbnailer::MAX_WIDTH => 205,
            WebThumbnailer::MAX_HEIGHT => 205,
        );
        $fileHash = hash('sha1', self::$gravatarThumb);

        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $this->assertContains('/cache/thumb/'. md5('gravatar.com') .'/' . $fileHash . '2052050.jpg', $thumburl);
        $img = imagecreatefromjpeg($thumburl);
        $this->assertEquals(205, imagesx($img));
        $this->assertEquals(205, imagesy($img));
        imagedestroy($img);
        unlink($thumburl);
    }

    /**
     *Test downloadThumbnail() with both width and height defined with preset values.
     */
    public function testDownloadSizedThumbnailBothPreset()
    {
        $options = array(
            WebThumbnailer::DOWNLOAD,
            WebThumbnailer::MAX_HEIGHT => WebThumbnailer::SIZE_SMALL,
            WebThumbnailer::MAX_WIDTH => WebThumbnailer::SIZE_SMALL,
        );
        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $fileHash = hash('sha1', 'https://gravatar.com/avatar/69ae657aa40c6c777aa2f391a63f327f?s=160');
        $this->assertContains('/cache/thumb/'. md5('gravatar.com') .'/'. $fileHash .'1601600.jpg', $thumburl);
        $img = imagecreatefromjpeg($thumburl);
        $this->assertEquals(SizeUtils::getMetaSize(WebThumbnailer::SIZE_SMALL), imagesx($img));
        $this->assertEquals(SizeUtils::getMetaSize(WebThumbnailer::SIZE_SMALL), imagesy($img));
        imagedestroy($img);
        unlink($thumburl);
    }

    /**
     *Test downloadThumbnail() with height defined.
     */
    public function testDownloadSizedThumbnailHeight()
    {
        $options = array(
            WebThumbnailer::DOWNLOAD,
            WebThumbnailer::MAX_HEIGHT => 205,
        );
        $fileHash = hash('sha1', self::$gravatarThumb);

        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $this->assertContains('/cache/thumb/'. md5('gravatar.com') .'/' . $fileHash . '02050.jpg', $thumburl);
        $img = imagecreatefromjpeg($thumburl);
        $this->assertEquals(205, imagesx($img));
        $this->assertEquals(205, imagesy($img));
        imagedestroy($img);
        unlink($thumburl);
    }

    /**
     *Test downloadThumbnail() with width defined.
     */
    public function testDownloadSizedThumbnailWidth()
    {
        $options = array(
            WebThumbnailer::DOWNLOAD,
            WebThumbnailer::MAX_WIDTH => 205,
        );
        $fileHash = hash('sha1', self::$gravatarThumb);

        $thumbnailer = new Thumbnailer(self::$gravatarLink, $options, null);
        $thumburl = $thumbnailer->getThumbnail();
        $this->assertContains('/cache/thumb/'. md5('gravatar.com') .'/' . $fileHash . '20500.jpg', $thumburl);
        $img = imagecreatefromjpeg($thumburl);
        $this->assertEquals(205, imagesx($img));
        $this->assertEquals(205, imagesy($img));
        imagedestroy($img);
        unlink($thumburl);
    }

    /**
     * Try to create an instance of Thumbnailer with incompatible settings.
     *
     * @expectedException \Exception
     * @expectedExceptionMessageRegExp /Only one of these flags can be set between: DOWNLOAD HOTLINK HOTLINK_STRICT/
     */
    public function testDownloadBadConfiguration()
    {
        $options = array(
            WebThumbnailer::DOWNLOAD,
            WebThumbnailer::HOTLINK_STRICT,
        );
        new Thumbnailer(self::$gravatarLink, $options, null);
    }
}
