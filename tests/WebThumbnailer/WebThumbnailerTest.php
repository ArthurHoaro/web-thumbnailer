<?php

namespace WebThumbnailer;

use WebThumbnailer\Application\ConfigManager;
use WebThumbnailer\Utils\FileUtils;

/**
 * Class WebThumbnailerTest
 *
 * Test library front end using a local server launched by PHPUnit.
 *
 * @package WebThumbnailer
 */
class WebThumbnailerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * PHP builtin local server URL.
     */
    const LOCAL_SERVER = 'http://localhost:8081/';

    /**
     * @var string $cache relative path.
     */
    protected static $cache = 'tests/WebThumbnailer/workdir/cache/';

    /**
     * @var string $cache relative path.
     */
    protected static $expected = 'tests/WebThumbnailer/resources/expected-thumbs/';

    /**
     * Load test config before running tests.
     */
    public function setUp()
    {
        $resource = 'tests/WebThumbnailer/resources/';
        ConfigManager::addFile($resource . 'settings-useful.json');
    }

    /**
     * Remove cache folder after every tests.
     */
    public function tearDown()
    {
        FileUtils::rmdir(self::$cache);
    }

    /**
     * Simple image URL.
     */
    public function testDirectImage()
    {
        $image = 'default/image.png';
        $expected = self::$expected . $image;
        $url = self::LOCAL_SERVER . $image;
        $wt = new WebThumbnailer();
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL without extension.
     */
    public function testDirectImageWithoutExtension()
    {
        $image = 'default/image';
        $expected = self::$expected . $image;
        $url = self::LOCAL_SERVER . $image;
        $wt = new WebThumbnailer();
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * URL which contains an opengraph image.
     */
    public function testOpenGraphImage()
    {
        $expected = self::$expected . 'default/le-monde.png';
        $url = self::LOCAL_SERVER . 'default/le-monde.html';
        $wt = new WebThumbnailer();
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Get a file URL which isn't an image.
     */
    public function testNotAnImage()
    {
        $oldlog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $image = 'default/not-image.txt';
        $url = self::LOCAL_SERVER . $image;
        $wt = new WebThumbnailer();
        $this->assertFalse($wt->thumbnail($url));

        ini_set('error_log', $oldlog);
    }

    /**
     * Simple image URL in download mode, resizing with max width.
     */
    public function testDownloadDirectImageResizeWidth()
    {
        $expected = self::$expected . 'default/image-width-341.png';
        $url = self::LOCAL_SERVER . 'default/image.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxWidth(341);
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL in download mode, resizing with max height.
     */
    public function testDownloadDirectImageResizeHeight()
    {
        $expected = self::$expected . 'default/image-height-341.png';
        $url = self::LOCAL_SERVER . 'default/image.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxHeight(341);
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL in download mode, resizing with max width and max height.
     */
    public function testDownloadDirectImageResizeBothWidth()
    {
        $expected = self::$expected . 'default/image-width-341.png';
        $url = self::LOCAL_SERVER . 'default/image.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxWidth(341)->maxHeight(341);
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL in download mode, resizing with max height and max height, with vertical image.
     */
    public function testDownloadDirectImageResizeBothHeight()
    {
        $expected = self::$expected . 'default/image-vertical-height-341.png';
        $url = self::LOCAL_SERVER . 'default/image-vertical.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxHeight(341)->maxWidth(341);
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL in download mode, crop enabled without both dimensions.
     */
    public function testDownloadDirectImageResizeWidthCrop()
    {
        $oldlog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $url = self::LOCAL_SERVER . 'default/image.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxWidth(341)->crop(true);
        $this->assertFalse(@$wt->thumbnail($url));

        ini_set('error_log', $oldlog);
    }

    /**
     * Simple image URL in download mode, crop enabled without both dimensions.
     */
    public function testDownloadDirectImageResizeHeightCrop()
    {
        $oldlog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        $url = self::LOCAL_SERVER . 'default/image.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxHeight(341)->crop(true);
        $this->assertFalse($wt->thumbnail($url));

        ini_set('error_log', $oldlog);
    }

    /**
     * Simple image URL in download mode, resizing with max height/width + crop.
     */
    public function testDownloadDirectImageResizeWidthHeightCrop()
    {
        $expected = self::$expected . 'default/image-crop-341-341.png';
        $url = self::LOCAL_SERVER . 'default/image-crop.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxHeight(341)->maxWidth(341)->crop(true);
        $thumb = $wt->thumbnail($url);
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL in download mode, resizing with max height/width + crop.
     * Override max heigth/width using array settings.
     */
    public function testDownloadDirectImageResizeWidthHeightCropOverride()
    {
        $expected = self::$expected . 'default/image-crop-120-160.png';
        $url = self::LOCAL_SERVER . 'default/image-crop.png';
        $wt = new WebThumbnailer();
        $wt = $wt->maxHeight(341)->maxWidth(341)->crop(true);
        $thumb = $wt->thumbnail(
            $url,
            [
                WebThumbnailer::MAX_WIDTH => 120,
                WebThumbnailer::MAX_HEIGHT => 160,
            ]
        );
        $this->assertFileEquals($expected, $thumb);
    }

    /**
     * Simple image URL, in hotlink mode.
     */
    public function testHotlinkSimpleImage()
    {
        $url = self::LOCAL_SERVER . 'default/image.png';
        $wt = new WebThumbnailer();
        $thumb = $wt->modeHotlink()->thumbnail($url);
        $this->assertEquals($url, $thumb);
    }

    /**
     * Simple image URL without extension, in hotlink mode.
     */
    public function testHotlinkSimpleImageWithoutExtension()
    {
        $url = self::LOCAL_SERVER . 'default/image';
        $wt = new WebThumbnailer();
        $thumb = $wt->modeHotlink()->thumbnail($url);
        $this->assertEquals($url, $thumb);
    }

    /**
     * Simple opengraph URL, in hotlink mode.
     */
    public function testHotlinkOpenGraph()
    {
        $expected = 'http://s1.lemde.fr/image/2016/10/24/644x322/5019472_3_91ef_cette-image-prise-par-la-sonde-americaine-mro_c27bb4fec19310d709347424f93addec.jpg';
        $url = self::LOCAL_SERVER . 'default/le-monde.html';
        $wt = new WebThumbnailer();
        $thumb = $wt->modeHotlink()->thumbnail($url);
        $this->assertEquals($expected, $thumb);
    }
}
