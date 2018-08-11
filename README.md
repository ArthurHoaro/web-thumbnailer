# Web Thumbnailer

![](https://travis-ci.org/ArthurHoaro/web-thumbnailer.svg?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/ArthurHoaro/web-thumbnailer/badge.svg?branch=master)](https://coveralls.io/github/ArthurHoaro/web-thumbnailer?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ArthurHoaro/web-thumbnailer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ArthurHoaro/web-thumbnailer/?branch=master)

PHP library which will retrieve a thumbnail for any given URL, if available.

## Features

  - Support various specific website features: Imgur, FlickR, Youtube, XKCD, etc.
  - Work with any website supporting [OpenGraph](http://ogp.me/) (tag meta `og:image`)
  - Or use direct link to images
  - Local cache
  - Resizing and/or cropping according to given settings
  
## Requirements

Mandatory:

  - PHP 5.6
  - PHP GD extension
  
(Highly) Recommended:

  - PHP cURL extension: it let you retrieve thumbnails without downloading the whole remote page
  
## Installation

Using [Composer](https://getcomposer.org/):

```bash
composer require arthurhoaro/web-thumbnailer
```
  
## Usage

Using WebThumbnailer is pretty straight forward:

```php
require_once 'vendor/autoload.php';
$wt = new WebThumbnailer();

// Very basic usage
$thumb = $wt->thumbnail('https://github.com/ArthurHoaro');

// Using a bit of configuration
$thumb2 = $wt->maxHeight(180)
            ->maxWidth(320)
            ->crop(true)
            ->thumbnail('https://github.com/ArthurHoaro');
echo '<img src="'. $thumb .'">';
echo '<img src="'. $thumb2 .'">';

// returns false
$wt->thumbnail('bad url');
```

Result:

> ![](https://cloud.githubusercontent.com/assets/1962678/19929568/37f6b796-a104-11e6-85fc-b039eb64bd97.png)
> ![](https://cloud.githubusercontent.com/assets/1962678/19929823/a26fde9e-a105-11e6-915c-ce0db1ffe6b0.png)

## Thumbnail configuration

There are 2 ways to set up thumbnail configuration:

  * using `WebThumbnailer` helper functions as shown in *Usage* section.
  * passing an array of settings to `thumbnail()` while getting a thumbnail.
  This will override any setting setup with the helper functions.
  Example:

```php
$conf = [
    WebThumbnailer::MAX_HEIGHT => 320,
    WebThumbnailer::MAX_WIDTH => 320,
    WebThumbnailer::CROP => true
];
$wt->thumbnail('https://github.com/ArthurHoaro', $conf);
```

### Download mode

There are 3 download modes, only one can be used at once:

  * Download (default): it will download thumbnail, resize it and save it in the cache folder.
  * Hotlink: it will use [image hotlinking](https://en.wikipedia.org/wiki/Inline_linking) if the domain authorize it, download it otherwise.
  * Hotlink strict: it will use image hotlinking if the domain authorize it, fail otherwise.
  
Usage:

```php
// Download (default value)
$wt = $wt->modeDownload();
$conf = [WebThumbnailer::DOWNLOAD];
// Hotlink
$wt = $wt->modeHotlink();
$conf = [WebThumbnailer::HOTLINK];
// Hotlink Strict
$wt = $wt->modeHotlinkStrict();
$conf = [WebThumbnailer::HOTLINK_STRICT];
```
   
> **Warning**: hotlinking means that thumbnails won't be resized, and have to be downloaded as their original size.

### Image Size

In download mode, thumbnails size can be defined using max width/height settings:

  * with max height and max width, the thumbnail will be resized to match the first reached limit.
  * with max height only, the thumbnail will be resized to the given height no matter its width.
  * with max width only, the thumbnail will be resized to the given width no matter its height.
  * if no size is provided, the default settings will apply (see Settings section).
   
Usage:

```php
// Sizes are given in number of pixels as an integer
$wt = $wt->maxHeight(180);
$conf = [WebThumbnailer::MAX_HEIGHT => 180];
$wt = $wt->maxWidth(320);
$conf = [WebThumbnailer::MAX_WIDTH => 180];
```
  
> **Bonus feature**: for websites which support an open API regarding their thumbnail size (e.g. Imgur, FlickR),
  WebThumbnailer makes sure to download the smallest image matching size requirements.
  
### Image Crop

Image resizing might not be enough, and thumbnails might have to have a fixed size. 
This option will crop the image (from its center) to match given dimensions.
 
> Note: max width AND height **must** be provided to use image crop.

Usage:

```php
// Sizes are given in number of pixels as an integer
$wt = $wt->crop(true);
$conf = [WebThumbnailer::CROP => true];
```
  
### Miscellaneous

  * **NOCACHE**: Force the thumbnail to be resolved and downloaded instead of using cache files.
  * **DEBUG**: Will throw an exception if an error occurred or if no thumbnail is found, instead of returning `false`.
  * **DOWNLOAD_TIMEOUT**: Override download timeout setting (in seconds).
  * **DOWNLOAD_MAX_SIZE**: Override download max size setting (in bytes).
  
Usage:

```php
$wt = $wt->noCache(true)
         ->debug(true);
$conf = [
    WebThumbnailer::NOCACHE,
    WebThumbnailer::DEBUG,
    WebThumbnailer::DOWNLOAD_TIMEOUT,
    WebThumbnailer::DOWNLOAD_MAX_SIZE,
];
```

## Settings

Settings are stored in JSON, and can be overrode using a custom JSON file:

```php
use WebThumbnailer\Application\ConfigManager;

ConfigManager::addFile('conf/mysettings.json');
```

Available settings:

  * `default`:
    * `download_mode`: default download mode (`DOWNLOAD`, `HOTLINK` or `HOTLINK_STRICT`).
    * `timeout`: default download timeout, in seconds.
    * `max_img_dl`: default download max size, in bytes.
    * `max_width`: default max width if no size requirement is provided.
    * `max_height`: default max height if no size requirement is provided.
    * `cache_duration`: cache validity duration, in seconds (use a negative value for infinite cache).
  * `path`:
    * `cache`: cache path.
  * `apache_version`: force `.htaccess` syntax depending on Apache's version, otherwise it uses `mod_version`
  (allowed values: `2.2` or `2.4`).
    
## Thumbnails path

In download mode, the path to the thumbnail returned by WebThumbnailer library will depend on what's provided 
to the `path.cache` setting. If an absolute path is set, thumbnails will be attached to an absolute path,
same for relative.

Relative path will depend on the entry point of the execution. For example, if your entry point for all request
is an `index.php` file in your project root directory, the default `cache/` setting will create a `cache/` folder
in the root directory. Another example, for Symfony, the cache folder will be relative to the `web/` directory, 
which is the entry point with `app.php`.

If you don't have a single entry point in your project folder structure, you should provide an absolute path
and process the path yourself.
    
## Contributing

WebThumbnailer can easily support more website by adding new rules in `rules.json` using one of the default Finders,
or by writing a new Finder for specific cases.

Please report any issue you might encounter.

Also, feel free to correct any horrible English mistake I may have made in this README.

## License

MIT license, see LICENSE.md
