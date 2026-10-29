# Upgrade CodeMirror 5.60.0 to the latest

**Status:** planned. Independent of the PHP minimum.

**Why:** 5.60.0 is from January 2021. Version 5 is in maintenance mode, and its latest release is
5.65.x (5.65.21 was out by February 2026; check for newer). CodeMirror 6 is the current major
version. It is a complete rewrite with a different API, distributed as ES modules only.

No open advisories affect 5.60.0. The last known one, CVE-2020-7760 (a ReDoS in the JavaScript
mode), was fixed in 5.58.2. This upgrade is maintenance, not an urgent security fix.

**Plan:** two stages.

| Stage | What | Backward compatibility |
|---|---|---|
| 1 | 5.60.0 → latest 5.65.x, in place | Full: same API, same files, same options |
| 2 | CodeMirror 6 as a new, separate editor plugin, opt-in | Full: the existing `codemirror` plugin stays on 5.x |

Stage 2 is not done in place because extensions use the CodeMirror 5 API directly. They call
`CodeMirror.fromTextArea`, use `Joomla.editors.instances[id].instance` as a CM5 editor, or load
modes from `media/editors/codemirror/mode/`. CodeMirror 6 has none of these.

## Where it lives

- **Library:** `media/editors/codemirror/`, with folders `lib/`, `addon/`, `mode/`, `keymap/` and
  `theme/`, plus `LICENSE`.
- **Addons bundle:** `lib/addons.js` (and `.min.js`, `.css`, `.min.css`) concatenates 18 addon
  files. It is loaded by `plugins/editors/codemirror/layouts/editors/codemirror/init.php`. There
  is no build script in this repository, so the bundle is rebuilt by hand.
- **Plugin:** `plugins/editors/codemirror/`. It holds `codemirror.php`, which reads the `theme`,
  `keyMap`, `vimKeyBinding` and `basePath` parameters; `codemirror.xml`, whose `<version>` is
  5.60.0 and which lists the selectable themes; `fonts.json`; and the layouts.

## Stage 1: 5.60.0 → 5.65.x

1. **Download the release.** Get the latest 5.65.x from https://github.com/codemirror/codemirror5
   (or `npm pack codemirror@5`). Unpack it into a new, empty folder outside the repository. Read
   `CHANGELOG.md` from 5.60.0 onwards.
2. **Work out the addon list.** Read the 18 file headers in the current `lib/addons.js`. Each
   section starts with the CodeMirror copyright line, followed by the addon's UMD wrapper. Write
   the addon list (paths under `addon/`) into a comment at the top of the file, so the next
   upgrade does not have to work it out again.
3. **Copy the files** over the old ones:
   - `lib/codemirror.js` and `lib/codemirror.css`
   - all of `addon/`, `mode/`, `keymap/` and `theme/`
   - `LICENSE`
4. **Rebuild the bundles.**
   - Concatenate the addons from step 2, in the same order, into `lib/addons.js` and
     `lib/addons.css`.
   - Minify every `.js` with terser and every `.css` with any CSS minifier, producing the `.min.*`
     files. Keep the license header.
5. **Find what 5.65.x dropped.** Diff the folder listings between the old and new versions.
   - Removed modes, themes or addons go into `com_admin/script.php` for deletion.
   - New themes are added to the `theme` list in `codemirror.xml`.
   - If a theme was removed, keep the old CSS file. A site may have it selected, and deleting it
     would break that site.
6. **Update the plugin.** Set `<version>` in `codemirror.xml` to the new version.

### Stage 1 testing

- Template manager: edit a PHP, CSS, JS and LESS file in both Isis and Protostar.
- An article in the CodeMirror editor: switching between the CodeMirror and None editors, saving,
  and full screen.
- Every option in the plugin: themes, key maps (`default`, `vim`, `emacs`, `sublime`), line
  numbers, code folding, matching tags and brackets, auto-close, and fonts.
- The editor-xtd buttons (Article, Image, Page Break, Read More) insert at the cursor.
- The browser console stays clean, with debug on and with debug off (`.min` files).

## Stage 2: CodeMirror 6 as a new editor (opt-in)

Only start this once Stage 1 has shipped.

1. **Create a new plugin**, `plg_editors_codemirror6` (element `codemirror6`, media under
   `media/editors/codemirror6/`). Do not rename or replace `codemirror`.
2. **Bundle CodeMirror 6.** It ships as ES modules, so the plugin needs one pre-built browser
   bundle, kept in the repository like every other bundled script.
   - Add a small build folder `build/codemirror6/` with `package.json`, an entry file and an
     esbuild or rollup config. It outputs one IIFE file plus a `.min.js`.
   - Pin exact versions in its `package-lock.json`.
   - Include the packages: `@codemirror/view`, `state`, `commands`, `language`, `search`,
     `autocomplete` and `lint`; the languages `lang-php`, `lang-html`, `lang-css`, `lang-javascript`,
     `lang-xml`, `lang-sql`, `lang-json` and `lang-markdown`; and `@replit/codemirror-vim` and
     `@replit/codemirror-emacs` for key maps.
   - Document the build command at the top of the entry file.
3. **Give it the same Joomla API as the old editor.** Register in `Joomla.editors.instances[id]`
   with `getValue`, `setValue`, `getSelection`, `replaceSelection`, `disable` and `id`.
   - Expose the CM6 `EditorView` as `.instance`, and document that it is CM6, not CM5.
4. **Make the parameters match.**
   - The plugin reads the same parameter names as the CM5 plugin, so a site can copy its settings
     across.
   - Map each CM5 theme to the nearest CM6 theme. Fall back to the default light or dark theme
     where there is no match.
   - Map `keyMap` values to the vim and emacs extensions. `sublime` has no CM6 equivalent; use the
     default bindings and note this in the plugin description.
5. **Register it in the installer.** Add the plugin, disabled, to the installation SQL for every
   database driver. Add an update SQL file that installs it, disabled, on existing sites.
6. **Do not change anyone's default editor.** Site owners turn it on in Global Configuration →
   Default Editor, or per user.

### Stage 2 testing

- Run the same test list as Stage 1 in the new editor.
- Edit with `codemirror` and `codemirror6` side by side, as different users with different editor
  settings.
- Switch a user from one editor to the other and back. Their content and settings must survive.

## Done when

- **Stage 1:** the bundled version is the latest 5.65.x. Existing sites keep their theme and key
  map. Removed files are cleaned up on update.
- **Stage 2:** CodeMirror 6 is available as a separate editor on fresh installs and after an
  update, disabled until a site owner enables it.
