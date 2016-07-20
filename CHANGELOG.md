# APIx Cache changelog

#### Version 1.2.7 (20-July-2016)
- Fix the HHVM issues.
- Fix APC/APCu for both PHP7 and HHVM.
- Updated `.travis` (optimisations).
- Added `msgpack` to Redis, Memcached and to all the PDO backends.  
- Added 'auto' and 'json_array' to Memcached.
- Changed Memcached default serializer to `auto`.
- Updated `README.md`.
- Added some additional unit-tests.
- Fix issue #15 "Files cache not correctly handling EOL on Windows" (thanks goes to @davybatsalle). 

#### Version 1.2.6 (4-July-2016)
- Fix issue #13 "TaggablePool and Pool overrides prefix_key and prefix_tag options with hardcoded value" (thanks goes to @alexpica). 
- Fix PHP 5.3, using `array()` instead of the short array syntax `[]`.
- Marcked as depreciated `isSerialized()` and `testIsSerialized()`.
- Added `msgpack` serializer.
- Set Travis to skip Memcached on PHP 7.0 (not yet officially supported).
- Added additional unit-tests, aiming for 100% code coverage.

#### Version 1.2.5 (20-Jun-2016)
- Fix issue #12 by adding `Files` and `Directory` backends to the Factory class (thanks goes to @alexpica). 
- Added some additional Factory tests.

#### Version 1.2.4 (6-Jan-2016)
- Updated PSR-Cache (Draft) to PSR-6 (Accepted).
- Marked as deprecated: `PsrCache::setExpiration`, `PsrCache::isRegenerating`, `PsrCache::exists`.
- Added additional unit tests to cover PSR-6.
- Updated `composer.json`.
- Updated `README.md`.
- Updated `.gitignore`.
- Added file locking option to the filesystem backends (contrib by @MacFJA).

#### Version 1.2.3 (5-Jan-2016)
- Fix APCu versions (contrib by @mimmi20).
- Added `Files` and `Directory` backends (contrib by @MacFJA).
- Updated `README.md`.

#### Version 1.2.2 (1-Sept-2015)
- Added a `CHANGELOG.md` file.
- Updated PHPUnit to 4.8 version.
- Dropped (partially) PHP 5.3 support - Memcached seems to be broken.
- Dropped PEAR support.
- Refactored `.travis.yml` tests.
- Made Travis faster (using Docker containers and skipping allowable failures).
- Added support to PHP 7.0.

#### Version 1.2.1 (4-Oct-2014)
- Added setOption().
- Updated `composer.json`.
- Updated `README.md`.
- Added Scrutinizer checks.
- Merged Scrutinizer Auto-Fixes.
- Various minor changes.

#### Version 1.2.0 (19-Sept-2014)
- Added preflight option to PDO backends.
- Added PSR-6 Cache support as a factory class.
- Added [Coverall](https://coveralls.io/github/frqnck/apix-cache) support.
- Updated `README.md`.
- Added APCu support.
- Added PHP 5.6 and HHVM support.
- Updated the `README.md`.

#### Version 1.1.0 (30-Jan-2013)
- Added `--prefer-source` (recommended by @seldaek).
- Updated the `README.md`.
- Added some unit tests.
- Added JSON support to Redis.

#### Version 1.0.5 (23-Jan-2013)
- Added `loadKey()`, `loadTag()` and removed `load()`.

#### Version 1.0.4 (22-Jan-2013)
- Added dedicated SQL1999 class.
- Fixed PDO and SQL definitions.
- Fixed `.travis.yml`. 

#### Version 1.0.3 (20-Jan-2013)
- Added some aditional tests.
- Refactored Serialisers.

#### Version 1.0.2 (20-Jan-2013)
- Added MongoDB implementation. 
- Various fixes. 

#### Version 1.0.1 (15-Jan-2013)
- Fixed test for Redis with igBinary.
- Added APC and PhpRedis environments.
- Added PHP 5.5 support.
- Fixed `.travis.yml`. 
- Added additional tests and minor changes.
- Updated `README.md`.

#### Version 1.0.0 (11-Jan-2013)
- Initial release.

<pre>
  _|_|    _|_|    _|     _|      _|
_|    _| _|    _|         _|    _|
_|    _| _|    _| _|        _|_|
_|_|_|_| _|_|_|   _| _|_|   _|_|
_|    _| _|       _|      _|    _|
_|    _| _|       _|     _|      _|
</pre>
