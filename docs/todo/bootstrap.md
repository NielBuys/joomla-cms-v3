# Bootstrap 2.3.2: fix the XSS advisories in place

**Status:** planned. Independent of the PHP minimum.

**Why:** the Bootstrap JavaScript in `media/jui/js/bootstrap.js` is 2.3.2 ("Custom version for
Joomla!"). It has six XSS advisories. Upstream fixed them in 3.4.0, 3.4.1 or 4.x, and one of them,
CVE-2024-6485, has no fixed release at all.

**Target:** stay on 2.3.2 and backport the fixes. Moving to Bootstrap 3, 4 or 5 is not an option
without breaking backward compatibility:

- The administrator (Isis), Protostar, every core layout and most Joomla 3 extensions emit
  Bootstrap 2 markup: `row-fluid`, `span*`, `btn-mini`, `hide`, `input-append`, the
  `data-toggle` APIs.
- The `JHtml bootstrap.*` helpers emit Bootstrap 2 markup and call Bootstrap 2 JavaScript plugins.

A new Bootstrap major is a template rewrite, Joomla 4 scale, and is out of scope.

**Scope:** JavaScript only. The advisories are all in the JavaScript plugins. The CSS
(`media/jui/css/bootstrap*.css`, built from `media/jui/less`) does not change.

## The advisories

Confirm the list first. Look up the npm `bootstrap` package at version 2.3.2 on
https://osv.dev, and record the exact advisory IDs in this file before patching.

| CVE | Plugin | Attack | Upstream fix |
|---|---|---|---|
| CVE-2018-14040 | collapse | `data-parent` is passed to `$()` | 3.4.0 / 4.1.2 |
| CVE-2018-14041 | scrollspy | `data-target` is passed to `$()` | 3.4.0 / 4.1.2 |
| CVE-2018-14042 | tooltip | `data-container` is passed to `$()` | 3.4.0 / 4.1.2 |
| CVE-2019-8331 | tooltip, popover | `data-template` and HTML content are not sanitized | 3.4.1 / 4.3.1 |
| CVE-2024-6485 | button | `data-loading-text` is written with `.html()` | none |
| CVE-2024-6484 | carousel | `data-slide` / `href` | none. The CVE record was rejected as "not a security issue", but scanners still list it. |

## Where it lives

- **Sources:** `build/bootstrap/js/bootstrap-*.js`. There is one file per plugin, plus Joomla
  variants for dropdown, collapse and tooltip (`*-joomla.js`). `build/bootstrap/Makefile`
  concatenates them into `media/jui/js/bootstrap.js` and `bootstrap.min.js`.
- **Separate copies:** `media/jui/js/bootstrap-tooltip-extended.js` and its `.min.js`. Patch it
  the same way as the tooltip.
- **Callers:** `libraries/cms/html/bootstrap.php` (`JHtml bootstrap.tooltip`, `popover`,
  `collapse`, `button`, `carousel`, `affix` and `modal`), and `hasTooltip` / `hasPopover`
  throughout the core layouts.

## Workflow

1. **Collect the upstream patches.**
   - Check out twbs/bootstrap. Read the commits behind each 3.4.0 and 3.4.1 fix.
   - For CVE-2024-6485 and CVE-2024-6484, read the GitHub advisories and HeroDevs' write-ups for
     the attack.
2. **Patch the sources in `build/bootstrap/js/`, never only the built file.** Mark each change
   `Modified by Joomla 3 fork: CVE-xxxx`.
   - **collapse, scrollspy, tooltip container** (14040, 14041, 14042): use
     `$(document).find(selector)` instead of `$(selector)` wherever an option or `data-*`
     attribute is used as a selector. `find` treats the value only as a selector, never as HTML.
     Keep the `href` fallback the Joomla variants already strip with the IE7 regex.
   - **Tooltip/popover sanitizer** (8331): port `sanitizeHtml()`, `DefaultWhitelist` and the
     `sanitize`, `sanitizeFn` and `whiteList` options from Bootstrap 3.4.1's `tooltip.js` into the
     2.3.2 tooltip and popover. Run `template`, and `title`/`content` when `html: true`, through
     it.
   - **Button** (6485): in `Button.prototype.setState`, run the loading text through the same
     sanitizer before calling `.html()`. Do not switch to `.text()`, because sites use spinner
     markup in `data-loading-text`.
   - **Carousel** (6484): only accept `data-slide` values `prev` and `next`, and only accept
     `data-slide-to` values that are integers. Resolve the `href` target with
     `$(document).find()`.
3. **Make the sanitizer backward compatible.** This is the one change that can visibly break
   sites. Core and extension tooltips and popovers contain HTML: `<strong>`, `<br>`, `<span
   class>`, links, sometimes images and tables.
   - Start from the 3.4.1 allow list. Add what core uses. Grep the layouts for `hasTooltip` and
     `hasPopover`, and for `JHtml::_('bootstrap.tooltip'` and `('bootstrap.popover'`, and check
     what markup they produce.
   - Expose the defaults as `$.fn.tooltip.defaults.whiteList`, so a template or extension can
     extend them globally.
   - Let `JHtml bootstrap.tooltip` and `bootstrap.popover` accept `sanitize`, `whiteList` and
     `sanitizeFn` in `$params` and pass them through.
4. **Rebuild** `media/jui/js/bootstrap.js` and `bootstrap.min.js` with `build/bootstrap/Makefile`
   (or the same concatenation and uglify by hand).
   - Keep the "Custom version for Joomla!" header and add the patch list to it.
   - Patch `bootstrap-tooltip-extended(.min).js` the same way.
5. **Leave the version number alone.** Do not change the version Bootstrap reports. Scanners will
   keep flagging "2.3.2", so record the backported fixes in the release notes, and in a short
   `media/jui/js/SECURITY-PATCHES.txt` next to the file, so anyone checking the site can see
   them.

## Testing

**Proof-of-concept payloads.** For each CVE, a test page with the payload from its advisory must
show no alert. Add them as Jasmine tests under `tests/javascript/` (Karma is configured in
`karma.conf.js`).

**Administrator walk-through.** Go through the administrator in Isis and check:

- the tooltips on list views, toolbar buttons, the article edit form and the permissions tab
- the popovers on the global configuration and permission matrices
- collapses (sidebar filters, the search tools toggle)
- tabs and sliders (`JHtml bootstrap.startTabSet`, `startAccordion`)
- modals (media manager, article select)
- dropdowns (the user menu, the toolbar "Actions")
- typeahead, and buttons with loading state

**Front end** in Protostar: the menu dropdown, carousel modules if any, and tooltips on the login
and contact forms.

**Extensions.** At least two popular third-party extensions that use Bootstrap 2 popovers with
HTML content. Their content must still render. If anything is stripped, extend the allow list or
document the per-call `sanitize: false` option.

**Console.** No errors with debug on (unminified) or off.

## Done when

- Every advisory confirmed on OSV is fixed in the source and the built files, with a test.
- Core tooltips and popovers look the same as before.
- The release notes list the backported CVEs and the new sanitizer options.
