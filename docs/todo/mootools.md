# MooTools 1.6.0: status and mitigation

**Status:** planned. Independent of the PHP minimum.

## Current state

The fork ships MooTools Core and More **1.6.0** in `media/system/js/`:

- `mootools-core.js` and `mootools-core-uncompressed.js`
- `mootools-more.js` and `mootools-more-uncompressed.js`

Official Joomla 3 shipped 1.4.5. The fork moved to 1.6.0 because 1.4.5 broke Google's scripts.

1.6.0, from January 2016, is the **last MooTools release**. The project is no longer maintained, and
there is no newer version to upgrade to.

## Known advisories

Both advisories affect every MooTools version, including 1.6.0. Neither has a fixed release, and
neither will get one.

| Advisory | Problem | Severity | Upstream status |
|---|---|---|---|
| [CVE-2021-23432](https://osv.dev/vulnerability/CVE-2021-23432) (GHSA-x6hx-7gh3-3q98, [Snyk](https://security.snyk.io/vuln/SNYK-JS-MOOTOOLS-1325536)) | Prototype pollution: untrusted input passed to `Object.merge()` can add properties to `Object.prototype` | Rated differently by different databases, from 5.4 Medium to 9.8 Critical | No fix |
| [CVE-2021-32821](https://osv.dev/vulnerability/CVE-2021-32821) ([GHSL-2020-345](https://securitylab.github.com/advisories/GHSL-2020-345-redos-mootools)) | Regular expression denial of service (ReDoS) in the CSS selector parser (`Slick.parse`): a crafted selector string can freeze the browser tab | 7.5 High (NVD) | Declared "wontfix" by the maintainer in May 2021 |

Both are client-side. They run in the browser of whoever views the page, not on the server.

Whether a site is affected depends on whether untrusted data reaches these code paths:

- **CVE-2021-23432** needs data from the page or a request to be merged into an object with
  `Object.merge()`, or with `Options.setOptions()`, which uses it.
- **CVE-2021-32821** needs an attacker-controlled string to be used as a CSS selector, for example
  in `$$()`, `document.getElements()` or `Element.getElement()`.

## Where MooTools is loaded

All the `JHtml` behaviours that load MooTools are deprecated, and log a deprecation warning when
used:

| Loader | What it loads |
|---|---|
| `JHtml::_('behavior.framework')` and `('behavior.framework', true)` | Core only, or Core and More |
| `JHtml::_('behavior.modal')` | Core, More and SqueezeBox (`media/system/js/modal.js`) |
| `JHtml::_('behavior.tooltip')` | Core and More |
| `JHtml::_('behavior.formvalidation')` | Core (the jQuery-based `behavior.formvalidator` does not) |
| `JHtml::_('behavior.tree')` | Core and `mootree.js` |
| `JHtml::_('script', ..., array('framework' => true))` | Core |

Core still uses them in these places:

- **`behavior.modal`:** the editor buttons layout, the media, user and content-history form fields,
  the privacy-consent and terms labels, and the article links layout in `com_content`.
- **`behavior.framework`:** the installer's Install view (kept so install scripts that use MooTools
  keep working), the versions toolbar button, and the deprecated `JHtml tabs` and `sliders`
  helpers.
- **The Hathor administrator template and the Beez3 site template.**

Third-party extensions and templates also load MooTools, through the same helpers or directly.

## Plan

Removing MooTools would break every extension and template that still uses it, so it stays. The
plan is to make the bundled copy safe and to stop core loading it where it isn't needed.

1. **Guard `Object.merge()` against prototype pollution** (CVE-2021-23432).
   - In `mootools-core-uncompressed.js`, change the internal merge helper so it skips the keys
     `__proto__`, `constructor` and `prototype`, the same way lodash and jQuery fixed their
     versions of this bug.
   - Rebuild the minified file. Mark the change `Modified by Joomla 3 fork: CVE-2021-23432`.
   - Check `Object.append()`, `Object.clone()` and `Hash` for the same pattern.
2. **Make the selector parser safe** (CVE-2021-32821).
   - In the Slick parser inside `mootools-core-uncompressed.js`, rewrite the regular expression that
     matches pseudo-class arguments so it cannot backtrack catastrophically. Use the GHSL-2020-345
     test case.
   - As a second guard, make `Slick.parse()` reject selector strings above a fixed length, for
     example 1,000 characters. Real selectors are far shorter.
   - Mark the change `Modified by Joomla 3 fork: CVE-2021-32821`.
3. **Audit core's own MooTools-based scripts** in `media/system/js/` for untrusted data reaching
   the functions above.
   - If you find a path that can be exploited, report it privately as described in
     [SECURITY.md](../../.github/SECURITY.md). Do not report it in a public issue or pull
     request.
4. **Stop core loading MooTools where it isn't needed.** Each item is its own small pull request:
   - Replace `behavior.modal` in the core layouts and form fields with the Bootstrap modal
     (`JHtml bootstrap.renderModal`), as the deprecation message already suggests. Keep
     `behavior.modal` itself working for extensions.
   - Remove the `behavior.framework` call from the versions toolbar layout if nothing in it uses
     MooTools.
   - Keep the installer's call. It exists for third-party install scripts.
5. **Tell people about it.**
   - Add a `media/system/js/SECURITY-PATCHES.txt` listing both patches. Scanners will keep
     reporting "MooTools 1.6.0" as vulnerable, so this lets anyone check what the copy contains.
   - Add a release-note entry. Recommend that extension developers move from MooTools to jQuery or
     plain JavaScript.

## Testing

**Advisory test cases.** Add them as Jasmine tests under `tests/javascript/`:

- `Object.merge({}, JSON.parse('{"__proto__": {"polluted": 1}}'))` must leave
  `({}).polluted` undefined.
- `Slick.parse(":a(" + "'".repeat(50000))` must return or throw within a few milliseconds.

**Existing behaviour.** These must work as before:

- the modal on the media, user and content-history fields
- editor buttons (Article, Image, Page Break, Read More)
- the Hathor and Beez3 templates
- one or two third-party extensions known to use MooTools, such as an older gallery or slider
  module

**Console.** No errors with debug on (`-uncompressed.js` files) or off.

## Done when

- Both advisories are mitigated in the bundled copy, with tests and a patch list.
- Core pages that no longer need MooTools don't load it.
- Extensions that still use MooTools keep working.
