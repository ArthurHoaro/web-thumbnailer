# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## v1.3.1 - 2018-08-11

### Removed

  * Remove PHP extension dependecies in `composer.json` to prevent docker multi stage build failure 

## v1.3.0 - 2018-08-11

### Added

  * Add a setting to force Apache version for htaccess syntax

### Fixed

  * PHPDocs improvements

### Removed

  * Parameter `PATH_TYPE` has been removed
  * WebThumbnailer no longer try to resolve relative path to thumbnails, it now relies on provided `path.cache` setting

## v1.2.1 - 2018-07-17

### Fixed

  * Fix a issue where download_mode from JSON config has no effect

## v1.2.0 - 2018-06-30

### Added

  * Path type parameter, to retrieve either a relative or an absolute path to the thumbnail cache file
  * `.htaccess` files are now created in cache folders (denied for `finder` and granted for `thumb`)
  
### Changed

  * The relative path to the thumbnail cache file is now retrieved using `SCRIPT_FILENAME`.

## v1.1.3 - 2018-06-13

### Fixed

  * Fix an issue with thumbs path with Apache alias where DOCUMENT_ROOT is not set properly

## v1.1.2 - 2018-05-05

### Added

  * Support redirection in cURL download callback

### Fixed 

  * Fix an issue preventing the relative path to work properly in a subfolder
  * Decode HTML entities on thumb urls (e.g. &amp;)
  * Fixed an issue where an empty cache folder where created

## v1.1.1 - 2018-05-01

### Fixed

  * Fixed dev dependency

## v1.1.0 - 2018-05-01

> **Warning**: this release will invalidates existing cache.

### Added

  * An exception is now thrown if PHP extension requirements are not satisfied. 
  * CI:
    - Coverall PR check added
    - Scrutinizer PR check added
    - PHP CodeSniffer PSR-2 syntax is now run along unit tests

### Changed

  * Image cache files are now stored as JPEG instead of PNG to save disk space.
  * Image cache domain folders are now stored using a hash instead of the raw domain name. 
  
  
### Fixed

  * Make Github ignore HTML test files for language detection.

## v1.0.1 - 2017-11-27

First public release.

## v1.0.0 - 2017-11-27

First release.