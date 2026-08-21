# Changelog

All notable changes to this project are documented in this file.

## [1.1.0](https://github.com/sendlayer/sendlayer-php/compare/v1.0.0...v1.1.0) (2026-08-21)

### Bug Fixes

* `Emails::send()` no longer discards the plain-text body when both `text` and
  `html` are supplied. Both `PlainContent` and `HTMLContent` are now sent, and
  `ContentType` is reported as `HTML` whenever an HTML body is present.
* Content bodies consisting of the string `"0"` are no longer rejected as empty.
* `SendLayerAPIException` no longer redeclares `$message` as a public property.
  This was a fatal compile error, so every response that fell through to this
  class — any 4xx outside 400/401/404/422/429, and any 5xx other than 500 —
  previously crashed instead of raising a catchable exception.
* `BaseClient::makeRequest()` now always returns or throws. The 4xx and 5xx
  handlers were declared `void` and could fall through without returning a value.

### Features

* SendLayer's `Errors` response array is now parsed. Every exception carries an
  `$errors` property holding the raw entries (each with the API's numeric `Code`
  and `Message`), and `getMessage()` surfaces the API's own message text instead
  of a generic fallback. Multiple messages are joined with `; `.

### Dependencies

* Widened the Guzzle constraint to `^7.0 || ^8.0`, adding Guzzle 8 support
  without dropping Guzzle 7 consumers.
* Removed the unused `php-coveralls/php-coveralls` development dependency.

### Documentation

* Documented the `$errors` property, the `SendLayerAPIException` message prefix,
  the `$statusCode`/`$response` properties, and `getCode()` behaviour.
* Added an HTML-with-plain-text-fallback email example.

## 1.0.0

* Initial public release.
