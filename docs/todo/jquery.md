# Upgrade jQuery 1.12.4-joomla and jQuery Migrate 1.4.1

**Status:** planned. Independent of the PHP minimum.

**Why:** `media/jui/js/jquery.js` is not a jQuery release. It is jQuery 1.12.4 (May 2016) with
Joomla's hand-made security patches, marked "Modified by Joomla" and "ADDED by Joomla" at lines 14,
15, 216 and 9308:

- the XSS fix for gh-2432 (CMS issue 19464)
- a prototype pollution guard from jQuery PR #4333
- a replacement `htmlPrefilter()` for CVE-2020-11022 and CVE-2020-11023, adapted from Drupal

`jquery-migrate.js` is Migrate 1.4.1, also from 2016.

No one maintains those patches any more. Scanners read `jQuery.fn.jquery === "1.12.4"` and flag
the site as vulnerable whatever the patches say. jQuery is now at 4.0.0 (18 January 2026).

**Target:** two stages.

| Stage | What | Backward compatibility |
|---|---|---|
| 1 | jQuery latest 3.x with jQuery Migrate latest 3.x | Kept by Migrate 3, which restores most APIs that jQuery 3.0 removed, with console warnings |
| 2 | jQuery 4.0.x with Migrate 4.x, opt-in only | **Not** kept for 1.x-era code: Migrate 4 only covers 3.x → 4.0 changes and does not restore what 3.0 removed. Also needs jQuery UI 1.13.3 or later. |

Stage 1 is the same route Joomla 4 took (jQuery 3 plus Migrate 3). Stage 2 cannot become the
default without breaking old extensions, so it is a setting.

## Where it lives

- **Files:** `media/jui/js/jquery.js` and `.min.js`, `jquery-migrate.js` and `.min.js`, and
  `jquery-noconflict.js`.
- **jQuery UI:** `media/jui/js/jquery.ui.core.js` and `jquery.ui.sortable.js` are **jQuery UI
  1.9.2** (2013). jQuery 3 needs at least jQuery UI 1.12; jQuery 4 needs 1.13.3.
- **Loader:** `libraries/cms/html/jquery.php`. `framework($noConflict = true, $debug = null,
  $migrate = true)` loads Migrate by default. `ui()` loads the jQuery UI files.
- **Plugins that depend on jQuery,** all in `media/jui/js/`:
  - Bootstrap 2.3.2
  - Chosen (`chosen.jquery.js`) and `ajax-chosen`
  - `jquery.minicolors`, `jquery.simplecolors`, `jquery.searchtools` and `jquery.autocomplete`
  - `sortablelist.js` and `treeselectmenu`
  - `media/system/js/*` scripts that use jQuery
  - template scripts in Isis and Protostar

## Stage 1: jQuery 3.x + Migrate 3.x

1. **Get the releases.** Download the latest jQuery 3.x (3.7.1 at the time of writing) and jQuery
   Migrate 3.x (3.5.x), each with its `.min.js`, from https://code.jquery.com or npm. Read the
   jQuery 3.0 upgrade guide and the Migrate 3 warning list.
2. **Check that the Joomla patches are already upstream.** All three are fixed in jQuery 3.5.0 and
   later: gh-2432 in 3.0, prototype pollution in 3.4.0, and `htmlPrefilter` in 3.5.0. Confirm each
   one, then drop the patch rather than carrying it forward.
3. **Replace the files.**
   - Replace `jquery.js` and `jquery.min.js`. Keep the file names.
   - Replace `jquery-migrate.js` and `jquery-migrate.min.js`.
   - Leave `jquery-noconflict.js` alone.
4. **Upgrade jQuery UI** from 1.9.2 to the latest 1.13.x or 1.14.x.
   - Rebuild the same two bundles, with the same components: core, widget, mouse and position in
     `jquery.ui.core.js`, and sortable in `jquery.ui.sortable.js`. Keep the file names.
   - Check `sortablelist.js`, which drives list ordering in the administrator, against the new
     sortable API.
5. **Audit the bundled plugins** with Migrate in warning mode.
   - Set `jQuery.migrateMute = false` and use the unminified files (Joomla debug on).
   - Walk the administrator and the front end, and collect every `JQMIGRATE:` warning.
   - Fix the warnings that come from core files (Bootstrap 2, Chosen, `searchtools`,
     `minicolors`, and so on) in the core files themselves, so core does not depend on Migrate.
   - Known breakers to look for:
     - `$('#')` and other invalid selectors, which throw in jQuery 3. The Bootstrap dropdown and
       collapse `href` handling can produce them.
     - `.load(fn)`, `.unload` and `.error` used as event shorthands
     - `.size()`, `.andSelf()` and `.selector`
     - `$(document).on('ready')`
     - synchronous expectations of `$.Deferred`, which became asynchronous (Promises/A+) in 3.0
     - `.width()` and `.height()` on hidden elements
6. **Keep Migrate loaded by default.** `framework()` already passes `$migrate = true`, and
   extensions rely on it. Ship the minified Migrate with `migrateMute = true` so production
   consoles stay quiet. With debug on, load the unminified file, unmuted, so developers see the
   warnings.
7. **Leave the public API alone.** Do not change `JHtml::_('jquery.framework')`,
   `JHtml::_('jquery.ui')` or the file paths. Extensions load
   `media/jui/js/jquery.min.js` directly.

### Stage 1 testing

**Administrator, every view, in Isis:**

- list ordering by drag and drop (sortable)
- batch processing
- search tools and filters
- Chosen selects, including the AJAX tag field
- colour pickers in the template style
- the menu item type modal
- the permissions tabs
- the media manager
- installer drag-and-drop upload
- TinyMCE and CodeMirror editors and the editor-xtd modals
- Joomla Update

**Front end in Protostar:**

- the menu
- the login form
- front-end article editing
- contact form validation
- Smart Search autocomplete

**Third-party extensions.** Install at least five popular Joomla 3 extensions that use jQuery
(for example JCE, Akeeba Backup, RSForm, a gallery and a slider module). Record any breakage, and
any Migrate warning that comes from them, in the release notes.

**Scanners.** `jQuery.fn.jquery` now reports 3.x, and a scanner such as retire.js no longer flags
the site.

## Stage 2: jQuery 4.0 as an opt-in

Only start this once Stage 1 has shipped and settled.

1. **Ship the files alongside Stage 1's.** Add jQuery 4.0.x and Migrate 4.x as
   `media/jui/js/jquery4/jquery.min.js` and `jquery-migrate.min.js`. Stage 1's files stay where
   they are.
2. **Add a setting** "jQuery version" (`3`, the default, or `4`) in Global Configuration → Server,
   or in a small system plugin.
   - `JHtml jquery.framework` picks the files from the setting.
   - Extensions that load the file by path still get jQuery 3, which is the safe failure.
3. **Check jQuery UI.** It must be 1.13.3 or later before Stage 2. Stage 1 step 4 covers this.
4. **Fix core for jQuery 4.**
   - Fix core code that uses APIs jQuery 4 removed: `$.isArray`, `$.parseJSON`, `$.trim`,
     `$.type`, `$.isFunction`, `$.isWindow`, `$.isNumeric`, `$.camelCase`, `$.nodeName`, `$.now`
     and `$.unique`.
   - Check the 4.0 upgrade guide for the full list. Fix these in core even for Stage 1, since
     they work in 3.x too.
5. **Explain the trade-off on the setting.** Its description says that jQuery 4 can break older
   extensions, and that the fix is to switch back to 3.

## Done when

- **Stage 1:** core runs on jQuery 3.x with no Migrate warnings from core files. The three Joomla
  patches are confirmed upstream and dropped. jQuery UI is current. Third-party extensions keep
  working through Migrate.
- **Stage 2:** jQuery 4 can be switched on and off per site, and the default stays 3.
