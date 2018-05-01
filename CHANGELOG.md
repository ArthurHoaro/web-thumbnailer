# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

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