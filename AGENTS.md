# AGENTS.md — PHP File Download (`karelwintersky/arris.php-file-download`)

## Overview

A small library for streaming file downloads from PHP: works from a file path, an opened
resource, or an in-memory string. Namespace `Arris\Toolkit`, PSR-4 autoload `src/`.

Repo: `/var/www.arris/Arris.Toolkit.FileDownload/` (GitHub: ArrisFramework).

## composer.json facts

- `require`: `php: ^8.0`, `karelwintersky/arris.toolkit.mimetypes: *`
- `require-dev`: `phpunit/phpunit: ^9.6 || ^10.5 || ^11.0`
- `autoload` psr-4: `Arris\Toolkit\` → `src/`; `autoload-dev`: `Arris\Toolkit\Tests\` → `tests/`
- `scripts.test`: `phpunit`
- `composer.lock` is NOT committed (in `.gitignore`). Local install pulls mimetypes from GitHub.

## Structure

```
src/
  FileDownload.php          - implementation
  FileDownloadInterface.php - interface (constructor + sendDownload + getFileSize + 3 static factories)
tests/
  FileDownloadTest.php      - PHPUnit suite
phpunit.xml
composer.json
README.md
```

## Key class: `Arris\Toolkit\FileDownload`

Implements `FileDownloadInterface`.

Public API (see also `FileDownloadInterface.php`):

- `__construct(resource $filePointer, string $filePath)` — filename is derived from `$filePath`
  via `pathinfo(PATHINFO_BASENAME)`; throws `InvalidArgumentException` on non-resource.
  BC note: since 2.0 the constructor requires the second `$filePath` argument.
- `sendDownload(string $filename = '', bool $forceDownload = true, bool $exit = true)` —
  sends Pragma/Expires/Cache-Control/Content-Type/Content-Disposition/Content-Length headers,
  flushes output buffers (`ob_clean()` if any), rewinds the pointer and `fpassthru`.
  Throws `RuntimeException` if `headers_sent()`. When `$exit = true` (default) the script
  terminates after the file is flushed. `$forceDownload = false` → `inline` disposition.
  Content-Disposition uses an ASCII-sanitized `filename=` fallback plus RFC 5987
  `filename*=UTF-8''<rawurlencode(name)>`.
- `getFileSize(): int` — `fstat()['size']`, throws `RuntimeException` on failure.
- `createFromFilePath(string $filePath): FileDownloadInterface` — validates file exists and is
  readable, opens `rb`.
- `createFromString(string $content): FileDownloadInterface` — writes content to `tmpfile()`.
- `createFromResource($fileResource): FileDownloadInterface` — derives filename from
  `stream_get_meta_data()['uri']`.
- `__destruct()` — closes the underlying file pointer (releases tmpfile from `createFromString`).

MIME resolution goes through `MimeTypes::fromExtension()` (companion package
`karelwintersky/arris.toolkit.mimetypes`); unknown extensions fall back to
`application/octet-stream`.

## Tests

```
composer test        # = vendor/bin/phpunit
```

Current state: **9 tests / 13 assertions**, 1 test skipped when running as root
(the "unreadable file" case cannot be reproduced as root). Test suite verified on
PHP 8.2 + PHPUnit 11.

## Wiring

Local package: `composer require karelwintersky/arris.php-file-download` — or point a
`path`/`vcs` repository at `/var/www.arris/Arris.Toolkit.FileDownload/`.

Companion packages and the path map live in the global AGENTS.md
(`~/.config/opencode/AGENTS.md`).

## Versioning / history

Tags match composer versions: 0.9, 0.9.1, 1.0.0, 1.1.0, 1.99.0, 2.0.0 (PHP 8 release),
2.1.0. 2.1.x adds interface completeness, header hardening, and test coverage.
