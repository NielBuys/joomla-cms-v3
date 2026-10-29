# Upgrade TinyMCE 4.5.12 to TinyMCE 8

**Status:** planned. Independent of the PHP minimum.

**Why:** 4.5.12 was built in July 2020. Seventeen advisories have been published since, including
three High-severity XSS issues from June 2026: CVE-2026-47759, CVE-2026-47761 and CVE-2026-47762.
No 4.x release fixes any of them. The 2026 three are fixed in 7.9.3 and 8.5.1. The latest release
is 8.9.2 (23 September 2026); check for newer.

TinyMCE 8.9.2 bundles DOMPurify 3.4.11, which has three advisories fixed in 3.4.12. Use the first
TinyMCE release that bundles DOMPurify 3.4.12 or later. If none exists yet, say so in the release
notes.

**Target:** the latest TinyMCE 8.x, installed in place in the existing `tinymce` editor plugin.
This is the largest of these upgrades and needs the most testing.

## Why in place, and not a second editor plugin

One option is to add TinyMCE 8 as a second editor and leave existing sites on 4.5.12 until each
site owner switches. That cannot break anything, but every site that never switches stays on the
vulnerable editor.

This plan upgrades the existing plugin instead. The plugin keeps the name `tinymce`, so every
reference to it keeps working:

- `editor=tinymce` in Global Configuration and in user parameters
- `JEditor::getInstance('tinymce')` calls in extensions
- `Joomla.editors.instances[id]`
- the editor-xtd buttons

Compatibility is handled inside the plugin: saved settings are translated, and a compatibility
layer supports TinyMCE 4-style plugins.

Whether to also keep 4.5.12 available as a fallback is decided after testing. See "Optional:
legacy fallback" at the end.

## Licence

- TinyMCE 7 and later are GPL-2.0-or-later, which is compatible with this fork. TinyMCE 6 was MIT.
- Self-hosted GPL use needs `license_key: 'gpl'` in the init options. Without it, TinyMCE 8 shows
  a licence warning or refuses to load.
- Update `media/editors/tinymce/license.txt`.

## Where it lives

- **Library:** `media/editors/tinymce/`. It holds `tinymce.min.js`, `plugins/` (43 plugins),
  `themes/modern`, `skins/lightgray`, `langs/`, `templates/`, `changelog.txt` and `license.txt`.
- **Joomla's own JavaScript:** `media/editors/tinymce/js/`.
  - `tinymce.js` is the Joomla wrapper. It registers `Joomla.editors.instances` and runs the
    toggle button.
  - `tinymce-builder.js` is the toolbar builder in the plugin settings.
  - `plugins/dragdrop/` is the `jdragdrop` image upload plugin.
  - `tiny-close.js` handles closing modals.
- **Plugin:** `plugins/editors/tinymce/tinymce.php` (2,050 lines).
  - `getKnownButtons()` lists the TinyMCE 4 button names.
  - `getToolbarPreset()` holds the simple, medium and advanced sets.
  - The plugin list, `skin` and `skin_admin`, the `template` plugin list and `external_plugins`
    are built around lines 420–670.
  - There is a second, legacy code path from line 1300 onwards. Find out whether anything still
    reaches it before porting it; it may be safe to delete.
- **Plugin settings:** `plugins/editors/tinymce/tinymce.xml` (`<version>` 4.5.12),
  `form/setoptions.xml`, and the fields under `field/`.
- **Stored settings:** each site's `#__extensions.params` for `plg_editors_tinymce` holds
  `configuration.toolbars` (TinyMCE 4 button names per set), `configuration.setoptions`, and the
  skin settings.

## What changed between TinyMCE 4 and 8

The plugin must translate or replace each of these.

| Area | TinyMCE 4 | TinyMCE 8 |
|---|---|---|
| Theme and skin | `modern` theme, `lightgray` skin | `silver` theme, `oxide` and `oxide-dark` skins, separate content CSS |
| Plugins merged into core | `textcolor`, `colorpicker`, `contextmenu`, `paste`, `hr`, `print`, `noneditable`, `textpattern`, `tabfocus` | Remove them from `plugins`. The buttons still exist. |
| Plugins removed or moved to paid plans | `bbcode`, `fullpage`, `legacyoutput`, `spellchecker`, `imagetools`, `toc`, `template`, `layer` | Drop them. Use `browser_spellcheck: true` for spellchecker. Replace `template`; see below. |
| Renamed buttons | `styleselect`, `formatselect`, `fontselect`, `fontsizeselect` | `styles`, `blocks`, `fontfamily`, `fontsize` |
| Plugin API | `editor.addButton`, `editor.addMenuItem`, `windowManager.open({ body })` | `editor.ui.registry.addButton` / `addMenuItem`, dialogs built from `body: { type: 'panel', items }` |
| Language files | `langs/xx.js` in v4 format | TinyMCE 8 language packs, from the `tinymce-i18n` package |
| Browsers | IE 11 supported | Evergreen browsers only |
| Licence | LGPL | GPL-2.0-or-later, with `license_key: 'gpl'` |

Before you start, confirm each row against the official TinyMCE 5, 6, 7 and 8 migration guides.
The guides are the authority. This table is only a checklist.

## Workflow

1. **Get the release.** Download the latest TinyMCE 8.x community package. Use npm `tinymce@8`
   or the Tiny download page, not the cloud build. Unpack it into a new, empty folder outside the repository.
   Read the 5.0, 6.0, 7.0 and 8.0 migration guides and the changelog since 4.5.12.
2. **Replace the library.** In `media/editors/tinymce/`, replace `tinymce.min.js`, `plugins/`,
   `themes/`, `skins/`, `icons/`, `models/` and `license.txt` with the new versions.
   - Keep `js/`, `templates/` and `langs/`. `langs/` is replaced in step 9.
   - Every v4 file that no longer exists goes into `com_admin/script.php` for deletion. That
     includes the whole of `themes/modern` and `skins/lightgray`.
3. **Translate the settings at runtime.** Add one function in `tinymce.php`, for example
   `translateLegacyParams()`, that rewrites the stored values each time the editor loads. Do not
   change the database, so a rollback stays possible.
   - Rename buttons: `styleselect`→`styles`, `formatselect`→`blocks`, `fontselect`→`fontfamily`,
     `fontsizeselect`→`fontsize`.
   - Drop plugin names that are now core or that no longer exist.
   - Map skins: `lightgray`→`oxide`. Map any dark admin skin to `oxide-dark`.
   - Replace `template` with `jtemplate` (step 6) and `spellchecker` with
     `browser_spellcheck: true`.
   - Pass custom plugins and buttons from the `custom_plugin` and `custom_button` settings through
     unchanged. The compatibility layer in step 5 handles them.
   - Add a unit test that uses the saved TinyMCE 4 settings of a real site.
4. **Update the init options.**
   - Add `license_key: 'gpl'`.
   - Remove options that 8.x rejects or ignores. The 6.0 and 7.0 guides list removed options.
   - Keep `content_css` from the template's `editor.css`; that logic works unchanged.
   - Keep `relative_urls`, `remove_script_host` and `document_base_url` as they are, because
     existing content depends on them.
5. **Add a compatibility layer for TinyMCE 4 plugins** (`js/tinymce-v4-shim.js`). Many
   third-party editor plugins were written for the TinyMCE 4 API. Load this file after
   `tinymce.min.js` and before `external_plugins`.
   - In `tinymce.on('AddEditor')`, define `editor.addButton(name, spec)` and
     `editor.addMenuItem(name, spec)`. Each one maps to `editor.ui.registry.*`, translating `cmd`,
     `onclick` and `icon`.
   - Wrap `editor.windowManager.open` so that a v4 `body: [...]` array is converted to a v5+
     `panel`.
   - Log a `console.warn` naming the plugin, so extension developers can see what needs updating.
   - Document its limits in the release notes. It covers buttons and simple dialogs, not every
     TinyMCE 4 API.
6. **Replace the `template` plugin.** TinyMCE 7 removed the free `template` plugin, but Joomla 3
   sites use `media/editors/tinymce/templates/*.html` and `template_list.js`.
   - Port Joomla 5's own `jtemplate` plugin into `js/plugins/jtemplate/`. It is GPL, and lives
     under `build/media_source/plg_editors_tinymce/js/plugins/` in joomla/joomla-cms 5.x; check
     the exact path.
   - Feed it the same template list `tinymce.php` already builds.
7. **Port the Joomla plugins.** Rewrite `js/plugins/dragdrop/plugin.js` (`jdragdrop`) and
   `js/tinymce.js` from the v4 API to the v8 API. Joomla 4's
   `build/media_source/plg_editors_tinymce/js/` has v5 and v6 versions of both to work from.
   `Joomla.editors.instances[id]` must keep `getValue`, `setValue`, `replaceSelection`,
   `getSelection`, `disable` and `id`, plus the toggle-editor button.
8. **Rework the toolbar builder.** `js/tinymce-builder.js` and `field/tinymcebuilder.php` render
   the v4 icon font.
   - Switch them to the v8 SVG icon pack (`icons/default/icons.js`) and the new button names.
   - Saved toolbar sets must load into the builder already translated (step 3).
   - Saving writes the new names. That is fine: the translation in step 3 is idempotent.
9. **Replace the language packs.** Replace `langs/` with TinyMCE 8 language packs, from the
   `tinymce-i18n` npm package or Tiny's language page.
   - Keep the existing mapping from Joomla language tags to TinyMCE file names (`af-ZA` →
     `af_ZA`, and so on).
   - Check that the Afrikaans fixes from 3.11.17 still apply.
10. **Update the versions.** Set `<version>` in `tinymce.xml` to the TinyMCE version. Update
    `changelog.txt`.

## Testing

**Fresh install and update.** On both, every combination of:

- front end and administrator
- Isis and Protostar
- the simple, medium and advanced sets
- a custom set that uses every button

**An update from the latest release, on a site with saved custom settings.** The editor must open
with the same buttons, without the plugin settings being saved again.

**Content round trip.** Open and save existing articles that contain the following, and diff the
saved HTML before and after:

- tables
- images with `jdragdrop` uploads
- `{loadmodule}` and `{loadposition}` tags
- page breaks and read-more
- iframes and embedded video, both allowed and filtered by the text filter settings
- inline styles
- `<script>` for users without text-filter rights

TinyMCE 8 sanitizes more strictly; record every difference.

**Editor-xtd buttons.** Article, Contact, Image, Menu, Module, Page Break and Read More, plus one
popular third-party button such as JCE or Regular Labs, all insert at the cursor.

**Templates.** The `jtemplate` plugin lists and inserts both the `.html` templates and the
`template_list.js` templates.

**Compatibility layer.** Install one or two third-party TinyMCE 4 plugins that use `addButton`,
and confirm their buttons appear, with a console warning.

**Languages.** The editor's UI language follows the user's language for at least en-GB, af-ZA,
de-DE and nl-NL.

**Security.** The three 2026 CVEs' proof-of-concept payloads, from the advisories, no longer run.

## Optional: legacy fallback

Decide this after testing. If widely used third-party plugins break and the compatibility layer
cannot support them, ship TinyMCE 4.5.12 as a separate plugin:

- Name it `plg_editors_tinymce4`, with media in `media/editors/tinymce4/`.
- Install it **disabled**, and describe it as "insecure, for compatibility only".
- Remove it again after one or two releases.

A site that depends on one broken extension then has a way out, but has to choose it deliberately.

## Done when

- `tinymce.min.js` reports 8.x, and existing sites open the editor with their old toolbars and no
  re-save.
- The editor-xtd buttons and `Joomla.editors.instances` work unchanged.
- The release notes cover the version change, the advisories fixed, the compatibility layer and
  its limits, and the plugins that were dropped.
