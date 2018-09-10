# Changelog
The format is based on [Keep a Changelog](http://keepachangelog.com/).

## [Unreleased]
### Added
- Eloquent driver
- Fuzzy searching
- More tests
- Commented the configuration
- Driver can be changed at query level
- Pagination
### Changed
- Major refactoring to internal indexing structure to simplify driver implementation
- Allow custom drivers an easier way to decorate models, removing bloat from model traits
- Index event observers are booted within the provider as opposed to within the trait
- Re-engineered instancing drivers to use object pool in DriverManager
- Service provider binds instances rather than creates singletons when using artisan
- Refactored Field to allow for types
- SearchableFields have a new schema to support index mapping
    - Values can be either null, float or array,
    - When null, the weighting is 1.0 and the type is dynamic
    - When float, the weighting is set and the type is dynamic
    - When array, the type and weight can be set, refer to the Driver documentation for supported types
### Fixed
- Support for all Laravel database drivers

## [0.6.0] - 2017/06/11
### Added
- Console commands to import and flush individual models
### Changed
- Index and flush command signatures to conform to a broader signature style
- Configuration publishing is now only bootstrapped when running in console

## [0.5.0] - 2017/06/06
### Added
- Qualifiers! Specify search query qualifiers to provide your users an advanced search language 
