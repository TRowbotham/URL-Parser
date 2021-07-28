# Changelog

## [Unreleased]

### Added

- Support for PHP 8.1

### Changed

- Make the `URL::$hostname` setter do nothing if the host name contains a ":" per [whatwg/url#601](https://github.com/whatwg/url/issues/601) [whatwg/url#604](https://github.com/whatwg/url/pull/604)

### Fixed

- Prevent the pathname setter from erasing the path of path-only URLs [whatwg/url#581](https://github.com/whatwg/url/issues/581) [whatwg/url#582](https://github.com/whatwg/url/pull/582)

## [3.1.3] - 2021-05-11

### Fixed

- File URL reparse bug with percent encoded windows drive letter [whatwg/url#589](https://github.com/whatwg/url/pull/589)

### Changed

- Switched to GitHub Actions for CI
- Minimum PHPUnit version is now 7.5

## [3.1.2] - 2021-02-11

### Added

- Performance improvements
- Test on PHP 8 release in CI

## [3.1.1] - 2020-10-17

### Added

- Support for `brick/math` ^0.9

### Changed

- Serializing non-special URLs is now idempotent per [whatwg/url#505](https://github.com/whatwg/url/pull/505)
- Changes to file URL path normalization per [whatwg/url#544](https://github.com/whatwg/url/pull/544).

## [3.1.0] - 2020-07-15

### Added

- Added test coverage on Windows 32-bit.
- Dependency on `rowbot/idna` to support international domain names without `ext-intl`.

### Changed

- `URLSearchParams::sort()` now correctly sorts by code units instead of trying to fake it using code points.
- Null bytes are now percent encoded in fragments instead of being ignored per [whatwg/url#440](https://github.com/whatwg/url/issues/440).
- Domain names of URLs with special schemes can no longer be an empty string per [whatwg/url#492](https://github.com/whatwg/url/pull/492) and [whatwg/url#497](https://github.com/whatwg/url/pull/497).
- Host names now also forbid the use of the `<`, `>`, and `^` code points per [whatwg/url#458](https://github.com/whatwg/url/issues/458).

### Fixed

- Incorrect output encoding used when encoding was overriden to be "replacement", "utf-16", "utf-16le", or "utf-16be".
- `ext-json` is now correctly listed as a dependency.

### Removed

- Dependency on `ext-intl` and `lib-ICU`.

## [3.0.1] - 2020-02-29

### Added

- Improved portability and 32-bit PHP support.
- Dependency on `brick/math` to support 32-bit versions of PHP.

### Removed

- Removed `ext-gmp` as a dependency.

## [3.0.0] - 2020-02-11

The majority of this library was rewritten in this update, but the public API has remain unchanged. This should make for a relatively painless upgrade.

### Added

- Installation requirement of ICU >= 4.6 to assist with [#6](https://github.com/TRowbotham/URL-Parser/issues/6)
- Support for PHPUnit ^8.0 and ^9.0.
- More test coverage.
- Support for symfony/cache ^5.0.

### Changed

- "gopher" was removed from the list of special schemes per [whatwg/url#453](https://github.com/whatwg/url/pull/453) and [whatwg/url#454](https://github.com/whatwg/url/pull/454).
- Coding style has been updated for PSR-12.
- Non-iterable objects passed to the `URLSearchParams` constructor now have their properties iterated over using `\ReflectionObject::getProperties()` rather than directly. There should not be any change in behavior.
- Removed artificial limitation when passing a sequence of sequences to the `URLSearchParams` constructor that required the non-array sub-sequences to implement the `\ArrayAccess` interface.
    - Note that sub-sequences must still be countable and only contain exactly 2 items.
    - This broadens the type of sequences that can be supplied from `iterable<mixed, array{0: string, 1: string}>` to `iterable<mixed, iterable<mixed, stringable>>`, where `stringable` is a scalar value or an object with the `__toString()` method.
- `URLSearchParams` now throws a `\Rowbot\URL\Exception\TypeError` instead of a `\InvalidArgument` exception to match browsers when passed an iterable that does not solely contain other iterables that are countable.
- JSON encoding the `URL` object will now throw a `\Rowbot\URL\Exception\JsonException` when JSON encoding fails.

### Fixed

- Documentaion errors.
- Restores expected string conversion behavior on systems using an ICU version >= 60.1 ([#7](https://github.com/TRowbotham/URL-Parser/issues/6)).
- `Origin::getEffectiveDomain()` was incorrectly returning the string `"null"` instead of the actual value `null` when the origin was an opaque origin.

### Removed

- `phpstan/phpstan` and associated packages are no longer a dev dependency and is now only run in CI.
- No longer depends on `ext-ctype`, which was an unlisted dependency.
- `php-coveralls/php-coveralls` is no longer a dev dependency. The project has moved to Codecov instead.
- `squizlabs/php_codesniffer` is no longer a dev dependency and is only run in CI now.

## [2.0.3] - 2019-08-13

### Fixed

- "%2e%2E" and "%2E%2e" were not properly detected as percent encoded double dot path segments.

## [2.0.2] - 2019-04-28

### Added

- PHP 7.4 compatibility
- Sped up IPv6 address serialization by removing some unncessary type casting.

## [2.0.1] - 2019-04-15

### Fixed

- Loading cached test data failed when using newer versions of the symfony/cache component.

## [2.0.0] - 2018-12-08

### Added

- Tests now automatically pull the latest data directly from the Web Platform Tests repository - thanks [@nyamsprod](https://github.com/nyamsprod)

### Changed

- The minimum required PHP version is now 7.1.
- Testing environment updated for PHP 7.1 - thanks [@nyamsprod](https://github.com/nyamsprod)
- Native typehints are now used. This which means that an `\TypeError` is now thrown instead of an `\InvalidArgumentException` when a value with an incorrect type is passed.
- `\Rowbot\URL\Exception\TypeError` and `\Rowbot\URL\Exception\InvalidParserState` now inherit from `\Rowbot\URL\Exception\URLException`

## [1.1.1] - 2018-08-15

### Added

- Sped up `URLSearchParams::has()` when the string does not exist in the list.
- TravisCI automation for tests and code coverage.

### Fixed

- The query string sorting algorithm now correctly sorts by code units instead of code points.

## [1.1.0] - 2018-06-21

### Added

- Updated documentation
- Updated tests

### Changed

- Only scalar values (bool, float, int, string) and objects with a `__toString()` method are considered as valid input now for methods and properties that only accept strings. This matches what PHP's string type hint would allow, allowing for an easier upgrade path when adding native type hints in the future.
    - A `null` value is no longer considered valid input and will cause an `\InvalidArgumentException` to be thrown. Previously, this was converted to the string `"null"`.
    - A `resource` value such as that returned by a call to `fopen()` is no longer considered valid input and will cause an `\InvalidArgumentException` to be thrown. This was previously casted to a string resulting in something like `"Resource id #1"`.
    - Previously, the values `true` and `false` were converted to the strings `"true"` and `"false"`. This is no longer the case. They now are now simply cast to a string resulting in the values `"1"` and `""` respectively.
- Passing an `iterable` that does not solely contain other `iterables` to the `URLSearchParams` constructor now causes it to throw an `\InvalidArgumentException`.
- Trying to access an invalid property on the `URL` object will now throw an `\InvalidArgumentException` to help catch typos.

## [1.0.3] - 2018-06-19

### Added

- Sped up serialization of IPv6 addresses
- Slightly better handling of non-UTF-8 encoded text when parsing query strings.
- A bunch of missing `use` import statements
- Updated tests and test data.

### Changed

- URLs with [special schemes](https://url.spec.whatwg.org/#special-scheme) now percent encode `'` characters in the query string.
- The application/x-www-form-urlencoded parser now only handles UTF-8 encoded text.

## [1.0.2] - 2018-02-28

### Changed

- Trying to set `URL::searchParams` directly will now throw a `\Rowbot\URL\Exception\TypeError`
- Passing invalid input will throw an `\InvalidArgumentException`
- Malformed byte sequences will now get fixed up with `\u{FFFD}` replacement characters.
- `URLSearchParams` now implements `\Iterator` instead of `\IteratorAggregate` to match test expectations.

### Fixed

- The last few failing tests are now passing with the exception of 3 errors as a result of PHP bug [72506](https://bugs.php.net/bug.php?id=72506)

## [1.0.1] - 2018-02-22

### Added

- MIT License

## [1.0.0] - 2018-02-22

- Initial Release!
