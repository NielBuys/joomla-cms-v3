# Planned library upgrades

This folder holds the plans for bringing the third-party libraries bundled with this fork up to
date. The goal is to do it without breaking existing sites, templates or extensions.

**Status:** planned. None of these upgrades has started yet. Last reviewed 2026-10-29.

Library versions and advisories change quickly. Before starting a workflow, check every version
number and advisory in it again.

## Background

This fork keeps Joomla 3 working on current and future PHP versions. Most of its bundled libraries
are still the versions official Joomla 3.10.12 shipped in July 2023. Several of them have known
security advisories.

An audit by mySites.guru, published 12 October 2026 in
[What is Joomla 3.x UTD](https://mysites.guru/blog/what-is-joomla-3x-utd/), lists the affected
libraries. It was written about a different Joomla 3 fork, but this fork ships the same library
versions, so the findings apply here too.

## The plan

The first step raises the fork's minimum PHP version to **8.1**. The two PHP library upgrades
depend on it. The JavaScript library upgrades do not, so they can be done in parallel.

| Library | Current | Problem | Plan | Target |
|---|---|---|---|---|
| PHP minimum | 7.1 | Holds back every PHP dependency. Some fixes live only as edits in `libraries/vendor`. | [php-minimum.md](php-minimum.md) | PHP 8.1, with every vendor fix kept in a fork |
| paragonie/sodium_compat | 1.17.1 | CVE-2025-69277 (Moderate) | [sodium-compat.md](sodium-compat.md) | Latest 2.x (2.5.0 or later) |
| symfony/yaml | 2.8.52 | CVE-2026-45304, CVE-2026-45305 and CVE-2026-45133: parser denial of service (Low) | [symfony-yaml.md](symfony-yaml.md) | 6.4 LTS (6.4.40 or later) |
| CodeMirror | 5.60.0 | Old 5.x release; CodeMirror 6 is the current major version | [codemirror.md](codemirror.md) | Latest 5.65.x, then CodeMirror 6 as an optional editor |
| TinyMCE | 4.5.12 | 17 advisories, including three High-severity XSS issues from June 2026 | [tinymce.md](tinymce.md) | TinyMCE 8.x, replacing 4.5.12 in the existing editor plugin |
| Bootstrap (JavaScript) | 2.3.2 | Six XSS advisories with no 2.x fix upstream | [bootstrap.md](bootstrap.md) | Fixes patched into 2.3.2 |
| jQuery and jQuery Migrate | 1.12.4 (Joomla-patched) and 1.4.1 | A hand-patched 2016 build that scanners flag as vulnerable | [jquery.md](jquery.md) | jQuery 3.x with Migrate 3.x, and jQuery 4 as an option |
| MooTools Core and More | 1.6.0, the last release | CVE-2021-23432 (prototype pollution) and CVE-2021-32821 (ReDoS); no fix upstream, project abandoned | [mootools.md](mootools.md) | Fixes patched into 1.6.0, and less MooTools loading in core |

**Suggested order:**

1. PHP minimum, then sodium_compat and symfony/yaml.
2. In parallel: CodeMirror 5.65 first (a drop-in upgrade), then TinyMCE, Bootstrap, jQuery and
   MooTools. TinyMCE and jQuery need the most testing.

The audit lists MooTools 1.4.5, but this fork already ships 1.6.0, the last release. 1.4.5 broke
Google's scripts. [mootools.md](mootools.md) covers the advisories that still affect 1.6.0.

## What "backward compatible" means here

Every plan in this folder follows these rules:

1. **Compatibility is about code, not server versions.** Sites running a PHP version below the
   fork's minimum (8.1 once [php-minimum.md](php-minimum.md) is done) are not offered new
   releases. They keep the last release that supports their PHP. Any library release that
   supports the minimum may be used.
2. **Public names stay the same.** Extensions call these directly, so they keep working:
   - the editor plugin names `tinymce` and `codemirror`
   - the `JHtml` helpers (`jquery.framework`, `bootstrap.*`)
   - the `Joomla.editors.instances[id]` JavaScript API
   - asset paths under `media/`
   - PHP class names
3. **Saved settings keep working.** Plugin settings saved by the old version (toolbar sets, skins,
   themes, key maps) are read and translated. Nobody has to save their settings again.
4. **A new major version that cannot be made compatible is optional.** It ships as a separate
   plugin or a setting, and is never switched on for existing sites by an update.
5. **The Joomla framework packages stay on 1.x.** Their 2.x releases change the APIs extensions
   use. Where a package needs a change, it is forked under
   [github.com/NielBuys](https://github.com/NielBuys) and pulled in as a Composer `vcs`
   repository, like `JoomlaInput` and `j3_filter` already are.
6. **No more direct edits in `libraries/vendor`.** A fix goes into the package's fork and arrives
   through Composer. Otherwise the next `composer update` silently removes it.

## Steps every plan shares

**Branches and pull requests.** Use one branch per library, named `tasks/upgrade-<library>`, and
one pull request into `3.10-dev`. See [CONTRIBUTING.md](../../.github/CONTRIBUTING.md).

**Composer packages.** `libraries/vendor` is committed to the repository. Update one package at a
time:

```bash
composer update <vendor/package> --with-dependencies --no-dev
```

Then check that:

- `composer.lock` and `libraries/vendor/composer/installed.json` changed only for the packages you
  meant to update.
- `.gitignore` still leaves out each package's tests, documentation and dot-files. Files that a
  new version adds may need new ignore lines.
- `libraries/vendor/composer/platform_check.php`, if Composer created one, does not require a
  higher PHP version than the fork's minimum.
- No package with direct edits in `libraries/vendor` was reinstalled. Until
  [php-minimum.md](php-minimum.md) is done, compare the changed packages before committing.

**Bundled JavaScript** (under `media/`):

- Keep the unminified and minified (`.min.js`) files in step.
- Put the library version in each file's header comment.
- Mark every change made in this repository with a `Modified by Joomla 3 fork:` comment that names
  the advisory or issue. Joomla already marks its own jQuery patches this way.

**Removed files.** Joomla Update copies new files over old ones but does not remove anything on its
own. Add every file or folder a new version drops to the `$files` or `$folders` arrays in
`administrator/components/com_admin/script.php`. Check the result with
`build/deleted_file_check.php` against the previous release package.

**Manifest versions.** Update `<version>` in the plugin manifest where it shows the library
version (`plugins/editors/tinymce/tinymce.xml`, `plugins/editors/codemirror/codemirror.xml`).

**Testing.** Test on the fork's minimum PHP version and on the newest PHP version the fork
supports. The [NCompPHPdev](https://github.com/NielBuys/NCompPHPdev) Docker image is set up for
this. Each plan lists what to test. Always test both:

- a fresh installation, and
- an update from the latest release, on a site with non-default settings for the library.

**Release notes.** Give each upgrade its own entry: the library, the old and new version, the
advisories it fixes, and anything site owners or extension developers need to know.

## Reporting a vulnerability

Do not report vulnerabilities in public issues or pull requests. Report them privately, as
described in [SECURITY.md](../../.github/SECURITY.md).
