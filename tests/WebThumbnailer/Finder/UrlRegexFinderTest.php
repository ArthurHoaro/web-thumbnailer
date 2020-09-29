<?php

namespace WebThumbnailer\Finder;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\TestCase;
use WebThumbnailer\Utils\DataUtils;
use WebThumbnailer\Utils\FileUtils;
use WebThumbnailer\WebThumbnailer;

/**
 * Class UrlRegexFinderTest
 *
 * @package WebThumbnailer\Finder
 */
class UrlRegexFinderTest extends TestCase
{
    /**
     * Test checkRules() with valid data.
     */
    public function testCheckRulesValid()
    {
        $this->addToAssertionCount(1);

        $rules = array(
            'url_regex' => 'str',
            'thumbnail_url' => 'str',
        );
        $finder = new UrlRegexFinder('', '', $rules, null);
        $finder->checkRules($rules);
    }

    /**
     * Test checkRules() with invalid data.
     */
    public function testCheckRulesMissingThumbUrl()
    {
        $this->expectException(BadRulesException::class);

        $rules = [
            'url_regex' => 'str',
        ];
        $finder = new UrlRegexFinder('', '', $rules, null);
        $finder->checkRules($rules);
    }

    /**
     * Test checkRules() with invalid data.
     */
    public function testCheckRulesMissingUrlRegex()
    {
        $this->expectException(BadRulesException::class);

        $rules = [
            'thumbnail_url' => 'str',
        ];
        $finder = new UrlRegexFinder('', '', $rules, null);
        $finder->checkRules($rules);
    }

    /**
     * Test find() with basic replacements.
     */
    public function testFind()
    {
        $url = 'http://test.io/q=id1&id2&notimportant';
        $thumburl = 'http://test.io/img/id1/id2.png';
        $rules = array(
            'url_regex' => 'q=([^&]+)&([^&]+)',
            'thumbnail_url' => 'http://test.io/img/${1}/${2}.png',
        );
        $finder = new UrlRegexFinder('', $url, $rules, null);
        $this->assertEquals($thumburl, $finder->find());
    }

    /**
     * Test find() with basic replacements plus size replacement.
     */
    public function testFindWithSizeOptions()
    {
        $url = 'http://test.io/?123';
        $rules = array(
            'url_regex' => '/\\?([^&]+)',
            'thumbnail_url' => 'http://test.io/${1}/${size}.png',
        );
        $options = array(
            'size' => array(
                'default' => 'small',
                'small' => array(
                    'param' => 'a',
                    'maxwidth' => 200,
                    'maxheight' => 200,
                ),
                'medium' => array(
                    'param' => 'b',
                    'maxwidth' => 320,
                    'maxheight' => 320,
                ),
                'large' => array(
                    'param' => 'c',
                    'maxwidth' => 640,
                    'maxheight' => 640,
                )
            ),
        );

        $userOptions = array(
            WebThumbnailer::MAX_HEIGHT => 200,
            WebThumbnailer::MAX_WIDTH => 200,
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/a.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            WebThumbnailer::MAX_HEIGHT => 200,
            WebThumbnailer::MAX_WIDTH => 201,
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/b.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            WebThumbnailer::MAX_HEIGHT => WebThumbnailer::SIZE_MEDIUM,
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/b.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            WebThumbnailer::MAX_HEIGHT => 199,
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/a.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            WebThumbnailer::MAX_HEIGHT => WebThumbnailer::SIZE_MEDIUM,
            WebThumbnailer::MAX_WIDTH => WebThumbnailer::SIZE_MEDIUM,
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/b.png';
        $this->assertEquals($thumburl, $finder->find());
    }

    /**
     *  Test find() with basic replacements, and default options.
     */
    public function testFindWithDefaultOptions()
    {
        $url = 'http://test.io/?123';
        $rules = array(
            'url_regex' => '/\\?([^&]+)',
            'thumbnail_url' => 'http://test.io/${1}/${setting}.png',
        );
        $options = array(
            'setting' => array(
                'default' => 'first',
                'first' => array(
                    'param' => 'a',
                ),
                'second' => array(
                    'param' => 'b',
                ),
                'third' => array(
                    'param' => 'c',
                ),
            )
        );

        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions(array());
        $thumburl = 'http://test.io/123/a.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            'setting' => 'second'
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/b.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            'setting' => 'third'
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/c.png';
        $this->assertEquals($thumburl, $finder->find());
    }

    /**
     * Test find() with basic replacements, and default options using bad values.
     */
    public function testFindWithDefaultOptionsBadValues()
    {
        $url = 'http://test.io/?123';
        $rules = array(
            'url_regex' => '/\\?([^&]+)',
            'thumbnail_url' => 'http://test.io/${1}/${setting}.png',
        );
        $options = array(
            'setting' => array(
                'default' => 'first',
                'first' => array(
                    'param' => 'a',
                ),
                'second' => array(
                    'param' => 'b',
                ),
            )
        );

        $userOptions = array(
            'setting' => 'nope'
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/a.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            'setting' => ''
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/a.png';
        $this->assertEquals($thumburl, $finder->find());

        $userOptions = array(
            'setting' => array('other' => 'stuff')
        );
        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions($userOptions);
        $thumburl = 'http://test.io/123/a.png';
        $this->assertEquals($thumburl, $finder->find());
    }

    /**
     * Test find() with basic replacements, and default options
     * but without defining default values.
     */
    public function testFindWithDefaultOptionsNoDefault()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/No default set for option/');

        $url = 'http://test.io/?123';
        $rules = array(
            'url_regex' => '/\\?([^&]+)',
            'thumbnail_url' => 'http://test.io/${1}/${setting}.png',
        );
        $options = array(
            'setting' => array()
        );

        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions(array());
        $finder->find();
    }

    /**
     * Test find() with basic replacements, and default options
     * with an invalid default option.
     */
    public function testFindWithDefaultOptionsNoDefaultParam()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/No default parameter set for option/');

        $url = 'http://test.io/?123';
        $rules = array(
            'url_regex' => '/\\?([^&]+)',
            'thumbnail_url' => 'http://test.io/${1}/${setting}.png',
        );
        $options = array(
            'setting' => array(
                'default' => 'unknown'
            )
        );

        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions(array());
        $finder->find();
    }

    /**
     * Test find() with basic replacements, and options not matching anything.
     */
    public function testFindWithDefaultOptionsUnknownOption()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/Unknown option/');

        $url = 'http://test.io/?123';
        $rules = array(
            'url_regex' => '/\\?([^&]+)',
            'thumbnail_url' => 'http://test.io/${1}/${unkown}.png',
        );
        $options = array(
            'setting' => array()
        );

        $finder = new UrlRegexFinder('', $url, $rules, $options);
        $finder->setUserOptions(array());
        $finder->find();
    }

    /**
     * Test getName().
     */
    public function testGetName()
    {
        $rules = [
            'url_regex' => 'foo',
            'thumbnail_url' => 'bar',
        ];
        $finder = new UrlRegexFinder('', '', $rules, []);
        $this->assertEquals('URL regex', $finder->getName());
    }

    /**
     * Test Gfycat permalink
     */
    public function testQueryRegexGfycatPermalink()
    {
        $url = 'https://gfycat.com/RigidJadedBirdofparadise';
        $expected = 'https://thumbs.gfycat.com/RigidJadedBirdofparadise-mobile.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['gfycat']['rules'];
        $options = $allRules['gfycat']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Gfycat in navigation mode (/detail)
     */
    public function testQueryRegexGfycatNavigation()
    {
        $url = 'https://gfycat.com/detail/HoarseJubilantHadrosaurus?tagname=battlefield_one&tvmode=1';
        $expected = 'https://thumbs.gfycat.com/HoarseJubilantHadrosaurus-mobile.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['gfycat']['rules'];
        $options = $allRules['gfycat']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Imgur single image
     */
    public function testQueryRegexImgurSingle()
    {
        $url = 'http://i.imgur.com/iQxE4BH';
        $expected = 'https://i.imgur.com/iQxE4BHm.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['imgur_single']['rules'];
        $options = $allRules['imgur_single']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }


    /**
     * Test Imgur Homepage (no thumb)
     */
    public function testQueryRegexImgurHomepage()
    {
        $url = 'https://imgur.com/';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['imgur_single']['rules'];
        $options = $allRules['imgur_single']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertFalse($finder->find());
    }

    /**
     * Test Youtube video link
     */
    public function testQueryRegexYoutubeVideo()
    {
        $url = 'https://youtube.com/watch?v=videoid';
        $expected = 'https://img.youtube.com/vi/videoid/mqdefault.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['youtube']['rules'];
        $options = $allRules['youtube']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Youtube: not a video link
     */
    public function testQueryRegexYoutubeNotVideo()
    {
        $url = 'https://youtube.com/about';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['youtube']['rules'];
        $options = $allRules['youtube']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertFalse($finder->find());
    }

    /**
     * Test Youtube video link
     */
    public function testQueryRegexYoutubeShort()
    {
        $url = 'https://youtu.be/videoid&stuff';
        $expected = 'https://img.youtube.com/vi/videoid/mqdefault.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['youtube_short']['rules'];
        $options = $allRules['youtube_short']['options'];
        $finder = new UrlRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }
}
