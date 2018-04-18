<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\WebThumbnailer;

/**
 * Class DefaultFinderTest
 *
 * @package WebThumbnailer\Finder
 */
class DefaultFinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * PHP builtin local server URL.
     */
    const LOCAL_SERVER = 'http://localhost:8081/';

    /**
     * Test the default finder with URL which match an image (.png).
     */
    public function testDefaultFinderImage()
    {
        $url = 'http://domains.tld/image.png';
        $finder = new DefaultFinder(null, $url, [], []);
        $this->assertEquals($url, $finder->find());

        $url = 'http://domains.tld/image.JPG';
        $finder = new DefaultFinder(null, $url, [], []);
        $this->assertEquals($url, $finder->find());

        $url = 'http://domains.tld/image.svg';
        $finder = new DefaultFinder(null, $url, [], []);
        $this->assertEquals($url, $finder->find());
    }

    /**
     * Test the default finder with URL which does NOT match an image.
     */
    public function testDefaultFinderNotImage()
    {
        $file = __DIR__ . '/../workdir/nope';
        touch($file);
        $finder = new DefaultFinder(null, $file, [], []);
        $this->assertFalse($finder->find());
        @unlink($file);
    }

    /**
     * Test the default finder downloading an image without extension.
     */
    public function testDefautFinderRemoteImage()
    {
        $file = __DIR__ . '/../workdir/image';
        // From http://php.net/imagecreatefromstring
        $data = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
            . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
            . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
            . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
        file_put_contents($file, base64_decode($data));
        $finder = new DefaultFinder(null, $file, null, null);
        $this->assertEquals($file, $finder->find());
        @unlink($file);
    }

    /**
     * Test the default finder trying to find an open graph link.
     */
    public function testDefaultFinderOpenGraph()
    {
        $url = __DIR__ . '/../resources/default/le-monde.html';
        $expected = 'http://s1.lemde.fr/image/2016/10/24/644x322/5019472_3_91ef_cette-image-prise-par-la-sonde-americaine-mro_c27bb4fec19310d709347424f93addec.jpg';
        $finder = new DefaultFinder(null, $url, null, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test the default finder trying to find an open graph link.
     */
    public function testDefaultFinderOpenGraphRemote()
    {
        $url = self::LOCAL_SERVER . 'default/le-monde.html';
        $expected = 'http://s1.lemde.fr/image/2016/10/24/644x322/5019472_3_91ef_cette-image-prise-par-la-sonde-americaine-mro_c27bb4fec19310d709347424f93addec.jpg';
        $finder = new DefaultFinder(null, $url, null, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test the default finder trying to find an image mime-type.
     */
    public function testDefaultFinderImageMimetype()
    {
        $url = self::LOCAL_SERVER . 'default/image-mimetype.php';
        $expected = $url;
        $finder = new DefaultFinder(null, $url, null, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test the default finder finding a non 200 status code.
     */
    public function testDefaultFinderStatusError()
    {
        $url = self::LOCAL_SERVER . 'default/status-ko.php';
        $finder = new DefaultFinder(null, $url, null, null);
        $this->assertFalse($finder->find());
    }

    /**
     * Test getName().
     */
    public function testGetName()
    {
        $finder = new DefaultFinder(null, null, [], []);
        $this->assertEquals('default', $finder->getName());
    }
}
