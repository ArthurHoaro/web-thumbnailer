<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\TestCase;

class FinderFactoryTest extends TestCase
{
    /**
     * Test getFinder() with a supported domain.
     */
    public function testGetFinderExistent(): void
    {
        $finder = FinderFactory::getFinder('youtube.com');
        $this->assertEquals(UrlRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('http://youtube.com');
        $this->assertEquals(UrlRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('https://youtube.com/stuff/bla.aspx?foo=bar#foobar');
        $this->assertEquals(UrlRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('imgur.com/fds');
        $this->assertEquals(UrlRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('i.imgur.com/fds');
        $this->assertEquals(UrlRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('i.imgur.com/gallery/fds');
        $this->assertEquals(QueryRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('gravatar.com/avatar/');
        $this->assertEquals(UrlRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('twitter.com/status/');
        $this->assertEquals(QueryRegexFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('instagram.com/p/stuff');
        $this->assertEquals(QueryRegexFinder::class, get_class($finder));
    }

    /**
     * Test getFinder() with an unsupported domain: it should return DefaultFinder.
     */
    public function testGetFinderNotSupportedDomain(): void
    {
        $finder = FinderFactory::getFinder('somewhere.io');
        $this->assertEquals(DefaultFinder::class, get_class($finder));

        $finder = FinderFactory::getFinder('https://somewhere.io/stuff/index.php?foo=bar#foobar');
        $this->assertEquals(DefaultFinder::class, get_class($finder));
    }

    /**
     * Test getFinder() with support domains, but not valid URL: fallback to DefaultFinder.
     */
    public function testGetFinderUrlRequirementInvalid(): void
    {
        $finder = FinderFactory::getFinder('gravatar.com');
        $this->assertEquals(DefaultFinder::class, get_class($finder));
    }

    /**
     * Test getThumbnailMeta() for Youtube.
     */
    public function testGetThumbnailMetaYoutube(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('youtube.com', 'http://youtube.com/bla/bla');
        $this->assertEquals('youtube.com', $data[0]);
        $this->assertEquals('UrlRegex', $data[1]);
        $this->assertEquals('v=([^&]+)', $data[2]['url_regex']);
        $this->assertEquals('https://img.youtube.com/vi/${1}/${size}.jpg', $data[2]['thumbnail_url']);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals('medium', $data[3]['size']['default']);
        // random size value
        $this->assertEquals('mqdefault', $data[3]['size']['medium']['param']);
    }

    /**
     * Test getThumbnailMeta() for Youtube short URL.
     */
    public function testGetThumbnailMetaYoutubeShort(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('youtu.be', 'http://youtube.com/bla/bla');
        $this->assertEquals('youtu.be', $data[0]);
        $this->assertEquals('UrlRegex', $data[1]);
        $this->assertEquals('youtu.be/([^&]+)', $data[2]['url_regex']);
        $this->assertEquals('https://img.youtube.com/vi/${1}/${size}.jpg', $data[2]['thumbnail_url']);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals('medium', $data[3]['size']['default']);
        // random size value
        $this->assertEquals('mqdefault', $data[3]['size']['medium']['param']);
    }


    /**
     * Test getThumbnailMeta() for Imgur single image.
     */
    public function testGetThumbnailMetaImgur(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('i.imgur.com', 'http://imgur.com/bla/bla');
        $this->assertEquals('imgur.com', $data[0]);
        $this->assertEquals('UrlRegex', $data[1]);
        $this->assertEquals('\.com/([\w\d]+)', $data[2]['url_regex']);
        $this->assertEquals('https://i.imgur.com/${1}${size}.jpg', $data[2]['thumbnail_url']);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals('medium', $data[3]['size']['default']);
        // random size value
        $this->assertEquals('m', $data[3]['size']['medium']['param']);
    }

    /**
     * Test getThumbnailMeta() for Imgur albums.
     */
    public function testGetThumbnailMetaImgurAlbum(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('i.imgur.com', 'http://imgur.com/gallery/bla/bla');
        $this->assertEquals('imgur.com', $data[0]);
        $this->assertEquals('QueryRegex', $data[1]);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals('medium', $data[3]['size']['default']);
        // random size value
        $this->assertEquals('m', $data[3]['size']['medium']['param']);
    }

    /**
     * Test getThumbnailMeta() for Imgur albums.
     */
    public function testGetThumbnailMetaInstagram(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('instagram.com', 'http://instagram.com/p/bla/bla');
        $this->assertEquals('instagram.com', $data[0]);
        $this->assertEquals('QueryRegex', $data[1]);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals(1, count($data[3]));
    }

    /**
     * Test getThumbnailMeta() for Twitter.
     */
    public function testGetThumbnailMetaTwitter(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('twitter.com', 'http://twitter.com/status/bla/bla');
        $this->assertEquals('twitter.com', $data[0]);
        $this->assertEquals('QueryRegex', $data[1]);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals(1, count($data[3]));
    }

    /**
     * Test getThumbnailMeta() for Twitter.
     */
    public function testGetThumbnailMetaGravatar(): void
    {
        // imgur single
        $data = FinderFactory::getThumbnailMeta('gravatar.com', 'http://gravatar.com/avatar/bla/bla');
        $this->assertEquals('gravatar.com', $data[0]);
        $this->assertEquals('UrlRegex', $data[1]);
        $this->assertEquals('(https?)://gravatar\\.com/avatar/(\\w+)', $data[2]['url_regex']);
        $this->assertEquals('${1}://gravatar.com/avatar/${2}?s=${size}', $data[2]['thumbnail_url']);
        $this->assertTrue($data[3]['hotlink_allowed']);
        $this->assertEquals('medium', $data[3]['size']['default']);
        // random size value
        $this->assertEquals('320', $data[3]['size']['medium']['param']);
    }

    /**
     * Test checkMetaFormat() with valid info.
     */
    public function testCheckMetaFormatValid(): void
    {
        $this->addToAssertionCount(1);
        $meta = [
            'finder' => 'foo',
            'domains' => ['bar']
        ];
        FinderFactory::checkMetaFormat($meta);
        $meta['foo'] = ['bar'];
        FinderFactory::checkMetaFormat($meta);
    }

    /**
     * Test checkMetaFormat() with invalid info.
     */
    public function testCheckMetaFormatBadRules(): void
    {
        $this->expectException(BadRulesException::class);
        $meta = array('finder' => 'test');
        FinderFactory::checkMetaFormat($meta);
    }
}
