# Web Thumbnailer

PHP library which will try to retrieve a thumbnail for any given URL.

## Features

  - Support various specific website features: Imgur, FlickR, Youtube, XKCD, etc.
  - Work with any website supporting [OpenGraph](http://ogp.me/) (tag meta `og:image`)
  - Or use direct link to images
  - Local cache
  - Resizing and/or cropping according to given settings
  
## Installation

Use [Composer](https://getcomposer.org/):

```bash
composer require arthurhoaro/WebThumbnailer
```

## Requirements

Mandatory:

  - PHP 5.6
  - PHP GD extension
  
Recommended:

  - PHP cURL extension
  
## Docs & Examples

See the `[examples]()` folder for more detailed example, or read [the full documentation]().

## Licence

See [LICENSE.md]().