# Upgrade symfony/yaml 2.8.52 to 6.4 LTS

**Status:** planned. Depends on [php-minimum.md](php-minimum.md), because symfony/yaml 6.4 needs
PHP 8.1.

**Why:** three denial-of-service advisories, all rated Low, published May 2026:

| CVE | Problem |
|---|---|
| CVE-2026-45304 | Unbounded alias expansion ("billion laughs") |
| CVE-2026-45133 | Unbounded recursion on deeply nested input; exhausts the stack |
| CVE-2026-45305 | Catastrophic regex backtracking in `Parser::cleanup()` |

Symfony fixed them in 5.4.52, 6.4.40, 7.4.12 and 8.0.12. No 2.x or 3.x release has the fixes.

**Target:** the latest 6.4.x (6.4.40 or later).

| Line | Needs PHP | Security fixes until |
|---|---|---|
| 6.4 LTS | 8.1 | November 2027 |
| 7.4 LTS | 8.2 | November 2029 |
| 8.0 | 8.4 | short-lived; not an LTS |

With a PHP 8.1 minimum, 6.4 is the newest line available. If the minimum moves to 8.2, move to 7.4
LTS. That is a small step: none of the Yaml methods `joomla/registry` calls changed between 6.4
and 7.4.

## Where it is used

- Only through `joomla/registry`. `libraries/vendor/joomla/registry/src/Format/Yaml.php` does
  three things:
  - creates `Symfony\Component\Yaml\Parser` and `Dumper`
  - calls `parse(trim($data))`
  - calls `dump($array, 2, 0)`

  All of these still exist with compatible signatures in 6.4.
- `joomla/registry` only *suggests* symfony/yaml and has no version constraint on it, so Composer
  will not object.
- Core itself never uses the `YAML` registry format. Only third-party extensions call
  `$registry->loadString($yaml, 'YAML')` or `toString('YAML')`.

## Backward compatibility: what 2.8 → 6.4 changes

The PHP API stays the same for the registry, but the **parser is stricter**. YAML that an
extension stores and that parsed under 2.8 can throw a `ParseException` under 6.4:

| Input | 2.8 | 6.4 |
|---|---|---|
| Duplicate mapping keys | The last key wins, silently | `ParseException` |
| Unquoted scalars starting with `@`, `` ` ``, `\|`, `>` or `%` | Parsed as a string, with a deprecation in 3.x | `ParseException` |
| `!php/object` tags | Off by default | Off by default; must be enabled with a flag |
| Dumping multi-line strings | Quoted, with `\n` | Quoted the same way unless `DUMP_MULTI_LINE_LITERAL_BLOCK` is passed; the registry does not pass it |

The table is a checklist. Confirm each row against the Symfony 3.0, 4.0, 5.0 and 6.0 Yaml
`CHANGELOG.md` and `UPGRADE-*.md` files.

## Workflow

1. **Read the upstream changes.** Read `CHANGELOG.md` for `symfony/yaml` from 2.8 to 6.4, and the
   `UPGRADE-3.0` through `UPGRADE-6.0` Yaml sections. Fix the table above if it is wrong.
2. **Make a YAML test corpus.**
   - Collect real YAML: any `.yml` or `.yaml` in popular Joomla 3 extensions, and the YAML
     samples under `tests/unit` for registry formats.
   - Add hand-written edge cases for every row of the table.
   - Before upgrading, run the corpus through `Registry::loadString(..., 'YAML')` and save the
     output as JSON.
3. **Change composer.json** to `"symfony/yaml": "^6.4.40"`. Run
   `composer update symfony/yaml --with-dependencies --no-dev`.
   - It adds `symfony/deprecation-contracts`.
   - `symfony/polyfill-ctype` may move.
   - Nothing else should change. Check the vendor diff, especially that none of the directly edited
     packages listed in [php-minimum.md](php-minimum.md) was touched.
4. **Add `.gitignore` lines** for the 6.4 package's `Tests/`, `CHANGELOG.md`, `README.md`,
   `composer.json` and `Resources/bin`, matching how other Symfony packages are ignored.
5. **Re-run the corpus** and diff the output against step 2. For every difference, decide one of:
   - **Correct and harmless,** such as types that were stricter all along: accept it.
   - **A new exception on YAML that used to load:** make `joomla/registry`'s `Yaml::stringToObject()`
     match the old behaviour where that is safe. Do this in the NielBuys fork of `joomla/registry`
     created in [php-minimum.md](php-minimum.md). For example, catch the duplicate-key
     `ParseException` and parse again with the duplicate removed, keeping the last value, as 2.8
     did.

     Never catch the exceptions raised by the three new limits. Input that hits one of them must
     still fail.
6. **Delete the old files on updated sites.** Composer replaces `libraries/vendor/symfony/yaml`
   in this repository, but Joomla Update only copies files over and leaves old ones behind.
   - List every file that 2.8.52 had and 6.4 does not.
   - Add them to the `$files` array in `com_admin/script.php`.

## Testing

- **Corpus:** every difference is either accepted, with the reason written down, or handled in
  the registry fork.
- **New limits:** an alias bomb, a 10,000-level nested document and the regex samples from the
  advisories all fail fast with a `ParseException`.
- **Round trip:** `Registry` → `toString('YAML')` → `loadString(..., 'YAML')` gives the same data.
- **No deprecations:** none from `symfony/yaml` or `deprecation-contracts` on the newest PHP.

## Done when

- `installed.json` shows symfony/yaml 6.4.x, with the three CVEs closed by an upstream release.
- Stored YAML that loaded before still loads, or the release notes list exactly what now fails
  and why.
- The release notes say "symfony/yaml 2.8.52 → 6.4.x (CVE-2026-45304, CVE-2026-45305,
  CVE-2026-45133)".
