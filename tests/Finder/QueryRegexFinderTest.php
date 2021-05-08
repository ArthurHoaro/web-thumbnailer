<?php

declare(strict_types=1);

namespace WebThumbnailer\Finder;

use WebThumbnailer\Exception\BadRulesException;
use WebThumbnailer\TestCase;
use WebThumbnailer\Utils\DataUtils;
use WebThumbnailer\Utils\FileUtils;

class QueryRegexFinderTest extends TestCase
{
    /**
     * @var mixed[] Finder rules.
     */
    protected static $rules;

    /**
     * PHP builtin local server URL.
     */
    protected const LOCAL_SERVER = 'http://localhost:8081/';

    /**
     * Before every tests, reset rules and params.
     */
    public function setUp(): void
    {
        self::$rules  = [
            'image_regex' => '<img class="thumb" src="(.*?)" alt="(.*?)">',
            'thumbnail_url' => 'https://domain.tld/pics/${1}?name=${2}',
        ];
    }

    /**
     * Test find() with a valid thumb found.
     */
    public function testQueryRegexFinderValid(): void
    {
        $url = __DIR__ . '/../resources/queryregex/one-thumb.html';
        $expected = 'https://domain.tld/pics/thumb.png?name=text';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with 2 valid thumbs matching the regex, we use the first one.
     */
    public function testQueryRegexFinderTwoThumbs(): void
    {
        $url = __DIR__ . '/../resources/queryregex/two-thumb.html';
        $expected = 'https://domain.tld/pics/thumb.png?name=text';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with parameter.
     */
    public function testQueryRegexFinderWithParameter(): void
    {
        $url = __DIR__ . '/../resources/queryregex/one-thumb.html';
        $expected = 'https://domain.tld/pics/thumb.png?param=foobar-other';
        self::$rules['thumbnail_url'] = 'https://domain.tld/pics/${1}?param=${option1}-${option2}';
        $params = [
            'option1' => [
                'default' => 'name',
                'name' => [
                    'param' => 'foobar',
                ]
            ],
            'option2' => [
                'default' => 'name',
                'name' => [
                    'param' => 'other',
                ]
            ]
        ];
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, $params);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with a valid thumb found.
     */
    public function testQueryRegexFinderCurlValid(): void
    {
        $url = self::LOCAL_SERVER . 'queryregex/one-thumb.html';
        $expected = 'https://domain.tld/pics/thumb.png?name=text';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with 2 valid thumbs matching the regex, we use the first one.
     */
    public function testQueryRegexFinderCurlTwoThumbs(): void
    {
        $url = self::LOCAL_SERVER . 'queryregex/two-thumb.html';
        $expected = 'https://domain.tld/pics/thumb.png?name=text';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test find() with parameter.
     */
    public function testQueryRegexFinderCurlWithParameter(): void
    {
        $url = self::LOCAL_SERVER . 'queryregex/one-thumb.html';
        $expected = 'https://domain.tld/pics/thumb.png?param=foobar-other';
        self::$rules['thumbnail_url'] = 'https://domain.tld/pics/${1}?param=${option1}-${option2}';
        $params = [
            'option1' => [
                'default' => 'name',
                'name' => [
                    'param' => 'foobar',
                ]
            ],
            'option2' => [
                'default' => 'name',
                'name' => [
                    'param' => 'other',
                ]
            ]
        ];
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, $params);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test the default finder trying to find an image mime-type.
     */
    public function testQueryRegexFinderImageMimetype(): void
    {
        $url = self::LOCAL_SERVER . 'default/image-mimetype.php';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertFalse($finder->find());
    }

    /**
     * Test the default finder finding a non 200 status code.
     */
    public function testQueryRegexFinderStatusError(): void
    {
        $url = self::LOCAL_SERVER . 'default/status-ko.php';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertFalse($finder->find());
    }

    /**
     * Test getName().
     */
    public function testGetName(): void
    {
        $rules = [
            'image_regex' => 'foo',
            'thumbnail_url' => 'bar',
        ];
        $finder = new QueryRegexFinder('', '', $rules, []);
        $this->assertEquals('Query Regex', $finder->getName());
    }

    /**
     * Test loading the finder with bad rules (`thumbnail_url`).
     */
    public function testQueryRegexFinderBadRulesThumbUrl(): void
    {
        $this->expectException(BadRulesException::class);

        unset(self::$rules['thumbnail_url']);
        new QueryRegexFinder('domain.tld', '', self::$rules, null);
    }

    /**
     * Test loading the finder with bad rules (`image_regex`).
     */
    public function testQueryRegexFinderBadRulesImageRegex(): void
    {
        $this->expectException(BadRulesException::class);

        unset(self::$rules['image_regex']);
        new QueryRegexFinder('domain.tld', '', self::$rules, null);
    }

    /**
     * Test downloading an inaccessible remote content (empty content).
     */
    public function testQueryRegexFinderResourceNotReachable(): void
    {
        $finder = new QueryRegexFinder('domain.tld', '', self::$rules, null);
        $this->assertFalse($finder->find());
    }

    /**
     * A page without thumbnails, return false.
     */
    public function testQueryRegexFinderNoMatch(): void
    {
        $url = __DIR__ . '/../resources/queryregex/no-thumb.html';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertFalse($finder->find());
    }

    /**
     * Not matching placeholder are ignored.
     */
    public function testQueryRegexNoEnoughMatch(): void
    {
        $url = __DIR__ . '/../resources/queryregex/one-thumb.html';
        $expected = 'thumb.png text ${3}';
        self::$rules['thumbnail_url'] = '${1} ${2} ${3}';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Use an unknown option in the URL.
     */
    public function testQueryRegexUnknownOption(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown option "option" for the finder "Query Regex"');

        $url = __DIR__ . '/../resources/queryregex/one-thumb.html';
        self::$rules['thumbnail_url'] = '${option}';
        $finder = new QueryRegexFinder('domain.tld', $url, self::$rules, null);
        $finder->find();
    }

    /**
     * Test Giphy.
     */
    public function testQueryRegexGiphy(): void
    {
        $expected = 'https://media.giphy.com/media/8JQqAqsxNDUXu/giphy-facebook_s.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['giphy']['rules'];
        $options = $allRules['giphy']['options'];
        $url = __DIR__ . '/../resources/giphy/giphy-gif.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Imgur Album: multiple images on a single page, we take the first (OpenGraph choice).
     */
    public function testQueryRegexImgurAlbum(): void
    {
        $expected = 'https://i.imgur.com/iQxE4BHm.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['imgur_album']['rules'];
        $options = $allRules['imgur_album']['options'];
        $url = __DIR__ . '/../resources/imgur/imgur-album.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Imgur Gallery: multiple images on a single page, we take the first (OpenGraph choice).
     * The difference between albums (/a/) and galleries (/gallery/), is that
     * a gallery has been published to the community and includes votes and comments.
     */
    public function testQueryRegexImgurGallery(): void
    {
        $expected = 'https://i.imgur.com/iQxE4BHm.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['imgur_album']['rules'];
        $options = $allRules['imgur_album']['options'];
        $url = __DIR__ . '/../resources/imgur/imgur-gallery.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Instagram thumb: one picture
     */
    public function testQueryRegexInstagramPicture(): void
    {
        $expected = 'https://scontent-cdg2-1.cdninstagram.com/v/t51.2885-15/e35/'
            . '14719286_1129421600429160_916728922148700160_n.jpg'
            . '?_nc_ht=scontent-cdg2-1.cdninstagram.com'
            . '&_nc_cat=100&_nc_ohc=xWaFFBqAj6wAX_gqYWt&tp=1&oh=dd77c7c72429d2db9ca3666f01c60e60&oe=605B2EDA';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['instagram']['rules'];
        $options = $allRules['instagram']['options'];
        $url = __DIR__ . '/../resources/instagram/instagram-picture.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Instagram thumb: profile, get the avatar
     */
    public function testQueryRegexInstagramProfile(): void
    {
        $expected = 'https://scontent-cdg2-1.cdninstagram.com/v/t51.2885-19/s150x150/'
            . '11351823_506089142881765_717664936_a.jpg'
            . '?_nc_ht=scontent-cdg2-1.cdninstagram.com'
            . '&_nc_ohc=US3NCxc_VOcAX-WwNIl&tp=1&oh=2bb367a88e579c411c7c484fcc6b1e3e&oe=605C8165';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['instagram']['rules'];
        $options = $allRules['instagram']['options'];
        $url = __DIR__ . '/../resources/instagram/instagram-profile.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Pinterest thumb: single picture
     */
    public function testQueryRegexPinterestPicture(): void
    {
        $expected = 'https://s-media-cache-ak0.pinimg.com/600x315/e0/7d/c0/e07dc09f93e12170fae7caa09329d815.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['pinterest']['rules'];
        $options = $allRules['pinterest']['options'];
        $url = __DIR__ . '/../resources/pinterest/pinterest-picture.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Pinterest thumb: profile picture
     */
    public function testQueryRegexPinterestProfile(): void
    {
        $expected = 'https://s-media-cache-ak0.pinimg.com/avatars/sjoshua1_1367516806_140.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['pinterest']['rules'];
        $options = $allRules['pinterest']['options'];
        $url = __DIR__ . '/../resources/pinterest/pinterest-profile.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test The Oatmeal comic.
     */
    public function testQueryRegexTheOatmealComic(): void
    {
        $expected = 'http://s3.amazonaws.com/theoatmeal-img/thumbnails/unhappy_big.png';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['theoatmeal']['rules'];
        $options = $allRules['theoatmeal']['options'];
        $url = __DIR__ . '/../resources/theoatmeal/theoatmeal-comic.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Twitter rules: no media, should use the avatar.
     */
    public function testQueryRegexTwitterNoMedia(): void
    {
        $expected = 'https://pbs.twimg.com/profile_images/737009192758870016/I_p72JBK_400x400.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['twitter']['rules'];
        $options = $allRules['twitter']['options'];
        $url = __DIR__ . '/../resources/twitter/twitter-no-media.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Twitter rules: one media, should use it.
     */
    public function testQueryRegexTwitterOneMedia(): void
    {
        $expected = 'https://pbs.twimg.com/media/CvilUtwWgAAQ46n.jpg:large';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['twitter']['rules'];
        $options = $allRules['twitter']['options'];
        $url = __DIR__ . '/../resources/twitter/twitter-single-media.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Twitter rules: multiple medias, should use the first one.
     */
    public function testQueryRegexTwitterMultipleMedia(): void
    {
        $expected = 'https://pbs.twimg.com/media/CuKCNVBVUAU332-.jpg:large';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['twitter']['rules'];
        $options = $allRules['twitter']['options'];
        $url = __DIR__ . '/../resources/twitter/twitter-multiple-media.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test Youtube profile page: use the avatar.
     */
    public function testQueryRegexYoutubeProfile(): void
    {
        $expected = 'https://yt3.ggpht.com/-KLL2Lp8Zqso/AAAAAAAAAAI/AAAAAAAAAAA/Y0qd6h5C_jQ/'
            . 's900-c-k-no-mo-rj-c0xffffff/photo.jpg';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['youtube_profile']['rules'];
        $options = $allRules['youtube_profile']['options'];
        $url = __DIR__ . '/../resources/youtube/youtube-profile.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }

    /**
     * Test XKCD comic.
     */
    public function testQueryRegexXkcdComic(): void
    {
        $expected = '//imgs.xkcd.com/comics/movie_folder.png';
        $allRules = DataUtils::loadJson(FileUtils::RESOURCES_PATH . 'rules.json');
        $rules = $allRules['xkcd']['rules'];
        $options = $allRules['xkcd']['options'];
        $url = __DIR__ . '/../resources/xkcd/xkcd-comic.html';
        $finder = new QueryRegexFinder('domain.tld', $url, $rules, $options);
        $this->assertEquals($expected, $finder->find());
    }
}
