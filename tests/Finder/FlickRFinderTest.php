<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\TestCase;

class FlickRFinderTest extends TestCase
{
    /**
     * @var mixed[] Finder rules.
     */
    protected static $rules;

    /**
     * @var mixed[] Size parameter.
     */
    protected static $params;

    /**
     * Before every tests, reset rules and params.
     */
    public function setUp(): void
    {
        self::$rules  = [
            'image_regex' => '<meta property=\"og:image\" content=\"(.*?)\"',
            'thumbnail_url' => '${1}',
        ];

        self::$params = [
            'size' => [
                'default' => 'large',
                'large' => [
                    'param' => 'c',
                    'maxwidth' => 800,
                    'maxheight' => 800,
                ]
            ]
        ];
    }

    /**
     * Test find() with an existing FlickR page loaded locally.
     */
    public function testFlickRFinderExistingImage(): void
    {
        $url = __DIR__ . '/../resources/flickr/flickr-image.html';
        $expected = 'https://c1.staticflickr.com/9/8657/29903845474_7d23197890_c.jpg';

        $finder = new FlickRFinder('flickr.com', $url, self::$rules, self::$params);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with an existing FlickR page loaded locally.
     * We use the empty size to make sure it works:
     *   one of thumb size doesn't have a suffix.
     */
    public function testFlickRFinderEmptySuffix(): void
    {
        $url = __DIR__ . '/../resources/flickr/flickr-image.html';
        $expected = 'https://c1.staticflickr.com/9/8657/29903845474_7d23197890.jpg';
        self::$params['size']['large']['param'] = '';

        $finder = new FlickRFinder('flickr.com', $url, self::$rules, self::$params);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with an existing FlickR page loaded locally.
     */
    public function testFlickRFinderProfile(): void
    {
        $url = __DIR__ . '/../resources/flickr/flickr-profile.html';
        $expected = 'https://c7.staticflickr.com/9/8562/29912456894_b3e6ddfe28_c.jpg';

        $finder = new FlickRFinder('flickr.com', $url, self::$rules, self::$params);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with an existing FlickR page loaded locally.
     */
    public function testFlickRFinderHomepage(): void
    {
        $url = __DIR__ . '/../resources/flickr/flickr-homepage.html';
        $expected = 'https://farm4.staticflickr.com/3914/15118079089_489aa62638_c.jpg';

        $finder = new FlickRFinder('flickr.com', $url, self::$rules, self::$params);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Load FlickR homepage, no image found.
     */
    public function testFlickRFinderNoImage(): void
    {
        $url = __DIR__ . '/../resources/flickr/flickr-doc.html';

        $finder = new FlickRFinder('flickr.com', $url, self::$rules, self::$params);
        $this->assertFalse($finder->find());
    }
}
