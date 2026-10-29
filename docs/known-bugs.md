# Known bugs

Open issues in this fork that are not yet fixed, with their status and any workaround.
An entry is removed once its fix ships in a release.

Last updated: 2026-10-29.

- To report a new bug, open an issue: https://github.com/NielBuys/joomla-cms-v3/issues
- For a security vulnerability, **do not** use the public tracker — report it privately, as
  described in [SECURITY.md](../.github/SECURITY.md).

## Open

### Associated-article modal opens at the wrong size
- **Area:** com_content article view — the linked-article ("Read more" associated items) popup —
  and the Beez3 template's copy of it.
- **Files:** `components/com_content/views/article/tmpl/default_links.php`,
  `templates/beez3/html/com_content/article/default_links.php`.
- **Symptom:** the popup does not use its intended 600×600 size. The link's `rel` attribute has
  extra text after the options object (`{…} noopener noreferrer`), which is not valid options
  markup.
- **Impact:** cosmetic. The popup still opens, at a default size; article content is unaffected.
- **Workaround:** none needed.
- **Status:** fix planned — correct the `rel` markup in both files.

## Related

- Bundled third-party library security advisories (TinyMCE, Bootstrap, jQuery, symfony/yaml,
  sodium_compat, CodeMirror, MooTools) are not listed here. They are tracked as upgrade plans in
  [docs/todo](todo/README.md).
