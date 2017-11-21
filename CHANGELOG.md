# Changelog
The format is based on [Keep a Changelog](http://keepachangelog.com/).

## [Unreleased]
### Added
- Eloquent driver
- Testing suites
- Friendly config comments
### Changed
- Major refactoring to internal indexing structure to simplify driver implementation
- Allow custom drivers an easier way to decorate models, removing bloat from model traits
- Index event observers are booted within the provider as opposed to within the trait
### Fixed
- Support for all Laravel defined database drivers

## [0.6.0] - 2017/06/11
### Added
- Console commands to import and flush individual models
### Changed
- Index and flush command signatures to conform to a broader signature style
- Configuration publishing is now only bootstrapped when running in console

## [0.5.0] - 2017/06/06
### Added
- Qualifiers! Specify search query qualifiers to provide your users an advanced search language 
