Unit Testing Information
====================

This document provides additional information on the folder structure within the unit tests and some basic information about the test suite

Folder Structure
---------------------
* `core` - Registered to Joomla's autoloader by the unit testing bootstrap file, contains all classes named `Test*` and primarily contains TestCase classes extending the PHPUnit Framework and classes to generate mock objects for several classes.
* `schema` - This folder contains all SQL schema for setting up the environment for tests using `TestCaseDatabase`.  The `ddl.sql` file is used by `TestCaseDatabase` to create a SQLite in-memory database which is destroyed at the end of the test cycle.  The other SQL files are specific to each database vendor and require you to manually create the databases.
* `stubs` - Contains miscellaneous test data used throughout the suite.
    * `database` - CSV files loaded by various tests to populate the database with test data
    * `discover*` - Stubs used with `JLoaderTest`
* `suites` - The actual test classes
* `tmp` - A temporary directory used for filesystem operations in the test suite
* `bootstrap.php` - The testing bootstrap called when PHPUnit is run

Running the Tests
---------------------
The suite runs on PHPUnit 9.6, which Composer installs as a development dependency. The database tests use an
SQLite in-memory database, so enable the `pdo_sqlite` extension; without it they are skipped.

```bash
composer install
libraries/vendor/bin/phpunit
```

Run a single suite from `phpunit.xml.dist` with `--testsuite`, for example `--testsuite libraries-cms`.

`composer install` adds the development packages to `libraries/vendor` and changes the autoloader files there. Before
committing anything under `libraries/vendor`, run `composer install --no-dev` again, so that only the packages Joomla
needs at runtime are committed and packaged. The development packages are listed in `.gitignore`.

Compatibility with older tests
---------------------
The tests were written for PHPUnit 4.8. Two files keep them running on PHPUnit 9 without rewriting every test:

* `core/case/phpunit.php` - `TestCasePhpunit`, the base class of every test case. It provides the PHPUnit 4.8 methods
  the tests still call, such as `getMock()`, `setExpectedException()` and `assertAttributeEquals()`, and restores
  `$_SERVER` after each test.
* `core/dbunit.php` - the parts of the abandoned `phpunit/dbunit` package the database tests use: CSV and XML data sets,
  the setup operations and data set comparison.

New tests should use the current PHPUnit API.
