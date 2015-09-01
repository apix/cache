# APIx Cache changelog

#### Version 1.2.2 (1-Sept-2015)
- Added a `CHANGELOG.md` file.
- Updated PHPUnit to 4.8 version.
- Dropped PHP  5.3 support.
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

#### Version 1.2.0 (19-Sep-2014)
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
- Added loadKey(), loadTag() and removed load().

#### Version 1.0.4 (22-Jan-2013)
- Added dedicated SQL1999 class.
- Fixed PDO and SQL definitions.
- Fixed `.travis.yml`. 

#### Version 1.0.3 (20-Jan-2013)
- Added some aditional tests.
- Refactored Serialisers.

#### Version 1.0.2 (20-Jan-2013)
- Added MongoDB implemnetation. 
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
