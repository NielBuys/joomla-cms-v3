# Raise the minimum PHP version to 8.3

**Status:** in progress on the `3.12-dev` branch, which becomes release 3.12. Do this before the other PHP
library upgrades. The minimum was first planned as 8.1 and was raised to 8.3 before the work started.

## Progress

- **Forks.** Every package that had direct edits, or a `php` constraint that excluded PHP 8, now comes from a fork
  under NielBuys. The Joomla framework packages: `JoomlaApplication` 1.9.5, `JoomlaData` 1.2.1, `JoomlaDI` 1.5.2, `JoomlaEvent`
  1.3.1, `JoomlaFilesystem` 1.6.3, `j3_filter` 1.4.9, `JoomlaImage` 1.5.2, `JoomlaInput` 1.5.1, `JoomlaLdap` 1.5.1,
  `JoomlaRegistry` 1.6.5, `JoomlaSession` 1.6.1 and `JoomlaString` 1.4.7. Each fork branches from the tag
  `composer.lock` named (branch `1.x-j3` for the new forks) and carries only the vendor edits and the `php`
  constraint.
- **PHPMailer** moved to the official `phpmailer/phpmailer` 7.x. `JMail` extends the namespaced class. The 5.2 class
  names `PHPMailer`, `phpmailerException`, `SMTP` and `POP3` remain available as aliases for extensions.
- **lessphp** moved to `splitbrain/lesserphp`, the maintained fork of `leafo/lessphp`. It compiles every core
  template's LESS to the same CSS as before. `wikimedia/less.php` was tried first, but it cannot compile the
  Bootstrap 2 LESS that isis and protostar use. The `lessc` class names remain available as aliases.
- **google/recaptcha and SimplePie** come from the `recaptcha` 1.2.5 and `simplepie` 1.3.4 forks, which carry the
  former direct edits. No official recaptcha release both supports PHP 8.3 and drops the deprecated `curl_close()`
  call, and SimplePie 1.9.0 still calls `curl_close()` and `xml_parser_free()`. Moving to their current releases
  stays step 5 of the workflow below.
- **Development tools.** PHPUnit 9.6, php-cs-fixer 3 and PHP_CodeSniffer 3. The tests run again; see
  `tests/unit/README.md`. `phpunit/dbunit` is replaced by `tests/unit/core/dbunit.php`, because the maintained
  fork of dbunit needs symfony/yaml 5.4 or later. The development packages are no longer committed to
  `libraries/vendor`.
- **Security advisories.** Composer 2.9 refuses packages with known advisories. `composer.json` lists the ones this
  fork has fixed or plans to fix under `audit.ignore`, each with the reason.

## Why

This fork exists to keep Joomla 3 working on current and future PHP versions. Its old minimum,
PHP 7.1, held back every PHP dependency: Composer can only choose library versions that still
support 7.1, and most current releases no longer do.

Raising the minimum to 8.3 allows:

- **symfony/yaml 7.4 LTS.** It contains the 2026 security fixes
  ([symfony-yaml.md](symfony-yaml.md)).
- **sodium_compat 2.x** ([sodium-compat.md](sodium-compat.md)).
- **Removing polyfills** for functions that PHP has included since 5.5 to 7.3.
- **A current PHPUnit**, so the test suite can run on new PHP versions.
- **Current releases of other libraries,** such as SimplePie, which is still on 1.3.3 from 2012.

## Who this affects

Sites running PHP below 8.3 are not offered the release that raises the minimum. They keep the
last release that supports their PHP version, and Joomla Update tells them the new release needs
PHP 8.3.

The minimum only sets the oldest PHP version the fork *allows*. PHP 8.3 receives security fixes until
31 December 2027. Sites should run a PHP version that still receives security fixes.

With 8.3, symfony/yaml 7.4 LTS is available. It receives security fixes until November 2029.

## Where the minimum is set

These places currently disagree:

| Place | Current value |
|---|---|
| `composer.json`, `require.php` and `config.platform.php` | `>=7.1.0` and `7.1.0` |
| `index.php`, `JOOMLA_MINIMUM_PHP` | `7.1.0` |
| `administrator/index.php` and `installation/index.php`, `JOOMLA_MINIMUM_PHP` | `5.3.10` (out of date) |
| `updates.xml`, `<php_minimum>` in each release entry | `7.2.5` |
| `README.md`, "Check the minimum requirements" | "PHP minimum v7.1, recommended v8.2" |

`JOOMLA_MINIMUM_PHP` controls three things:

- the version check at the top of each `index.php`
- the installer's pre-installation check (`installation/model/setup.php`)
- the fallback Joomla Update uses when an update entry has no `<php_minimum>`
  (`administrator/components/com_joomlaupdate/models/default.php`)

## Three things to sort out first

### 1. Edits made directly in `libraries/vendor`

Some earlier fixes for PHP 8.x deprecations and security issues were made directly in vendor
files. Composer does not know about these edits. Any `composer update` that reinstalls one of
these packages removes them without warning.

As of 2026-10-29, these files have direct edits:

| Package | Edited files | Fork under NielBuys |
|---|---|---|
| joomla/application | `src/AbstractApplication.php`, `src/Cli/CliOutput.php` | Yes, `JoomlaApplication` |
| joomla/filter | `src/InputFilter.php` | Yes, `j3_filter` |
| joomla/input | `src/Input.php`, `src/Cli.php` | Yes, `JoomlaInput` |
| joomla/data | `src/DataSet.php` | No |
| joomla/filesystem | `src/Clients/FtpClient.php` | No |
| joomla/image | `src/Image.php`, `src/Filter/Backgroundfill.php` | No |
| joomla/registry | `src/Registry.php` | No |
| joomla/session | `Joomla/Session/Storage/Database.php` | No |
| joomla/string | `src/StringHelper.php` | No |
| google/recaptcha | `src/ReCaptcha/RequestMethod/Curl.php` | No |
| simplepie/simplepie | `library/SimplePie.php`, `library/SimplePie/File.php`, `library/SimplePie/Locator.php`, `library/SimplePie/Parser.php` | No |
| phpmailer/phpmailer | `class.phpmailer.php`, `class.phpmaileroauth.php`, `class.pop3.php`, `class.smtp.php` | No; it uses the `joomla-backports` fork |
| leafo/lessphp | `lessc.inc.php` | No; it uses the `joomla-backports` fork |

The table may be incomplete. Before starting, build the full list:

1. Copy `composer.json` and `composer.lock` into an empty folder outside the repository and run
   `composer install --no-dev` there.
2. Compare the `libraries/vendor` folder it creates with the repository's `libraries/vendor`. Every difference that is not caused by
   `.gitignore` is a direct edit.

For packages that already have a NielBuys fork, also check that each edit is in the fork.

### 2. Packages that refuse PHP 8

Seven Joomla framework 1.x packages declare `"php": "^5.3.10|~7.0"`, which excludes PHP 8:

- `joomla/di`
- `joomla/event`
- `joomla/image`
- `joomla/input`
- `joomla/ldap`
- `joomla/registry`
- `joomla/session`

The code works on PHP 8; only the constraint is wrong. Composer accepts these packages today only
because `config.platform.php` is `7.1.0`. With `8.3.0`, Composer refuses to install them.

Moving them to 2.x is not an option, because the 2.x packages change namespaces and APIs that
extensions use.

### 3. Development tools

`composer update` resolves `require-dev` even when installing with `--no-dev`. Several of the
current development tools cannot be installed on PHP 8.3:

- `phpunit/phpunit` 4.8
- `phpunit/dbunit` 1.3
- `friendsofphp/php-cs-fixer` 1.x

`squizlabs/php_codesniffer` 1.5 and `pear/cache_lite` 1.7.16 are also too old to rely on.

## Workflow

1. **Fork each package that needs it.** That is every package from the first two lists without a
   NielBuys fork:
   - `joomla/data`, `di`, `event`, `filesystem`, `image`, `ldap`, `registry`, `session` and
     `string`
   - `google/recaptcha` and `simplepie/simplepie`
   - `phpmailer` and `lessphp`, which also move under NielBuys, so every fix lives in a repository
     this project controls

   For recaptcha and simplepie, first consider moving to their current releases instead (step 5).

   In each fork:
   1. Branch from the tag that `composer.lock` names.
   2. Commit the direct edits from this repository.
   3. Widen the `php` constraint to `"^7.1|^8.0"`.
   4. Tag a version that sorts after the original, for example `1.6.5-joomla.1`.
2. **Switch `composer.json` to the forks.**
   1. Add a `vcs` entry to `repositories` for each fork.
   2. Update the `require` lines.
   3. Run `composer update <those packages> --no-dev`.
   4. Compare `libraries/vendor` with the previous version. The only differences must be in
      `composer.json` files and Composer metadata. Any other difference is an edit missing from a
      fork.
3. **Raise the minimum in `composer.json`.**
   - Set `"php": ">=8.3.0"`, and `"config": { "platform": { "php": "8.3.0" } }`.
   - Remove `symfony/polyfill-php55`, `-php56`, `-php71` and `-php73`, and
     `ircmaxell/password-compat`. PHP 8.3 includes everything they provide.
   - Before removing each package, check with `composer why <package>` that nothing else requires
     it.
   - Remove `paragonie/random_compat` once sodium_compat is on 2.x. The 1.x line still needs it.
   - Keep `php81_bc/strftime`. `strftime()` is deprecated from PHP 8.1 and the fork still uses
     this replacement.
4. **Replace the development tools.**
   - Update PHPUnit to `^9.6` or `^10`.
   - Remove `phpunit/dbunit`; it is abandoned and has no PHP 8 release.
   - Update `friendsofphp/php-cs-fixer` and `squizlabs/php_codesniffer` to `^3`.

   The tests themselves will need porting to the new PHPUnit. That is a separate piece of work. In
   the meantime the test suite may not run, but `composer update` must succeed. Run
   `composer update --no-dev` and compare `libraries/vendor` again.
5. **Upgrade the libraries the old minimum held back.** Each one gets its own branch and pull
   request, after this one is merged:
   - symfony/yaml ([symfony-yaml.md](symfony-yaml.md))
   - sodium_compat ([sodium-compat.md](sodium-compat.md))
   - SimplePie, from 1.3.3 to the current release, if the API used by the feed module still exists
   - google/recaptcha, to the current 1.x release
6. **Update the constants and the installer.**
   - Set `JOOMLA_MINIMUM_PHP` to `8.3.0` in `index.php`, `administrator/index.php` and
     `installation/index.php`.
   - Search for other hard-coded version checks with `grep -rn "version_compare(PHP_VERSION"`,
     leaving out `libraries/vendor`.
   - Check the installer message `INSTL_PHP_VERSION_NEWER` still reads correctly.
7. **Update the update feed.** Do this at release time, as the last release step, as README.md
   describes.
   - Give the new release's entry in `updates.xml` `<php_minimum>8.3.0</php_minimum>`.
   - Leave older entries unchanged, so sites on older PHP versions can still see the release that
     supports them.
8. **Update the requirements in `README.md`** to "PHP minimum v8.3". Recommend the newest PHP
   version the fork is tested on.
9. **Optional cleanup, in a separate pull request.** Remove code paths for PHP versions below 8.3,
   and `function_exists()` checks for functions PHP 8.3 always has.

## Testing

- **Fresh installation** on PHP 8.3 and on the newest supported PHP. On PHP 8.2, the installer
  must stop at the pre-installation check with a clear message.
- **Update from the latest release** through Joomla Update, on PHP 8.3 and on the newest
  supported PHP.
- **Earlier fixes still in place.** Repeat the checks for the fixes that were made as direct
  vendor edits:
  - the PHP 8.4, 8.5 and 8.6 deprecation fixes: no deprecation notices on normal page requests, CLI
    scripts or the Smart Search indexer
  - the CVE-2025-54476 fix
  - the input filter fixes ported from the Joomla 3 EOL security plugin
- **No new warnings** with `error_reporting(E_ALL)` and debug mode on, across the administrator
  and the site.

## Done when

- `composer update` succeeds with `config.platform.php` set to `8.3.0`.
- It rebuilds `libraries/vendor` exactly, including every earlier fix, from forks.
- Every place listed under "Where the minimum is set" says 8.3, and older `updates.xml` entries are
  unchanged.
