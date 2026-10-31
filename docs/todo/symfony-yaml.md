# Upgrade symfony/yaml 2.8.52 to 7.4 LTS

**Status:** done on the `tasks/upgrade-symfony-yaml` branch, for release 3.12: symfony/yaml 7.4.22, with
`joomla/registry` 1.6.6 from the NielBuys fork. See [Result](#result) below; the plan is kept for reference.

**Why:** four denial-of-service advisories. The first three, all rated Low, were published in May
2026:

| CVE | Problem |
|---|---|
| CVE-2026-45304 | Unbounded alias expansion ("billion laughs") |
| CVE-2026-45133 | Unbounded recursion on deeply nested input; exhausts the stack |
| CVE-2026-45305 | Catastrophic regex backtracking in `Parser::cleanup()` |

Symfony fixed them in 5.4.52, 6.4.40, 7.4.12 and 8.0.12. A fourth, CVE-2026-109413 (unbounded
recursion in the inline lexer), was fixed later in 5.4.54, 6.4.45, 7.4.18 and 8.1.6. No 2.x or 3.x
release has any of the fixes.

**Target:** the latest 7.4.x (7.4.18 or later).

| Line | Needs PHP | Security fixes until |
|---|---|---|
| 6.4 LTS | 8.1 | November 2027 |
| 7.4 LTS | 8.2 | November 2029 |
| 8.0 | 8.4 | short-lived; not an LTS |

With the PHP 8.3 minimum, 7.4 LTS is available and receives security fixes for longest. None of the
Yaml methods `joomla/registry` calls changed between 6.4 and 7.4, so what this plan says about 6.4
holds for 7.4.

## Where it is used

- Only through `joomla/registry`. `libraries/vendor/joomla/registry/src/Format/Yaml.php` does
  three things:
  - creates `Symfony\Component\Yaml\Parser` and `Dumper`
  - calls `parse(trim($data))`
  - calls `dump($array, 2, 0)`

  All of these still exist with compatible signatures in 7.4.
- `joomla/registry` only *suggests* symfony/yaml and has no version constraint on it, so Composer
  will not object.
- Core itself never uses the `YAML` registry format. Only third-party extensions call
  `$registry->loadString($yaml, 'YAML')` or `toString('YAML')`.

## Backward compatibility: what 2.8 → 7.4 changes

The PHP API stays the same for the registry, but the **parser is stricter**. YAML that an
extension stores and that parsed under 2.8 can throw a `ParseException` under 7.4:

| Input | 2.8 | 7.4 |
|---|---|---|
| Duplicate mapping keys | The first key wins, silently | `ParseException` |
| Unquoted scalars starting with `@`, `` ` ``, `\|`, `>` or `%` | Parsed as a string, with a deprecation in 3.x | `ParseException` |
| `!php/object` tags | Off by default | Off by default; must be enabled with a flag |
| Dumping multi-line strings | Quoted, with `\n` | Quoted the same way unless `DUMP_MULTI_LINE_LITERAL_BLOCK` is passed; the registry does not pass it |

The table is a checklist. Confirm each row against the Symfony 3.0, 4.0, 5.0, 6.0 and 7.0 Yaml
`CHANGELOG.md` and `UPGRADE-*.md` files.

## Workflow

1. **Read the upstream changes.** Read `CHANGELOG.md` for `symfony/yaml` from 2.8 to 7.4, and the
   `UPGRADE-3.0` through `UPGRADE-7.0` Yaml sections. Fix the table above if it is wrong.
2. **Make a YAML test corpus.**
   - Collect real YAML: any `.yml` or `.yaml` in popular Joomla 3 extensions, and the YAML
     samples under `tests/unit` for registry formats.
   - Add hand-written edge cases for every row of the table.
   - Before upgrading, run the corpus through `Registry::loadString(..., 'YAML')` and save the
     output as JSON.
3. **Change composer.json** to `"symfony/yaml": "^7.4.18"`. Run
   `composer update symfony/yaml --with-dependencies --no-dev`.
   - It adds `symfony/deprecation-contracts`.
   - `symfony/polyfill-ctype` may move.
   - Nothing else should change. Check the vendor diff, especially that none of the directly edited
     packages listed in [php-minimum.md](php-minimum.md) was touched.
4. **Add `.gitignore` lines** for the 7.4 package's `Tests/`, `CHANGELOG.md`, `README.md`,
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
   - List every file that 2.8.52 had and 7.4 does not.
   - Add them to the `$files` array in `com_admin/script.php`.

## Testing

- **Corpus:** every difference is either accepted, with the reason written down, or handled in
  the registry fork.
- **New limits:** an alias bomb, a 10,000-level nested document and the regex samples from the
  advisories all fail fast with a `ParseException`.
- **Round trip:** `Registry` → `toString('YAML')` → `loadString(..., 'YAML')` gives the same data.
- **No deprecations:** none from `symfony/yaml` or `deprecation-contracts` on the newest PHP.

## Done when

- `installed.json` shows symfony/yaml 7.4.x, with the four CVEs closed by an upstream release.
- The four symfony/yaml entries are removed from `audit.ignore` in `composer.json`.
- Stored YAML that loaded before still loads, or the release notes list exactly what now fails
  and why.
- The release notes say "symfony/yaml 2.8.52 → 7.4.x (CVE-2026-45304, CVE-2026-45305,
  CVE-2026-45133, CVE-2026-109413)".

## Result

A corpus of 42 documents ran through `Registry::loadString(..., 'YAML')` before and after the upgrade, on PHP 8.6.
`tests/unit/suites/libraries/joomla/registry/JRegistryFormatYamlTest.php` keeps the important cases.

**Handled in the `JoomlaRegistry` fork (1.6.6), so the YAML loads as it did under 2.8:**

- Duplicate keys in a block mapping: the first value is kept, as 2.8 did. 7.4 alone throws.
- Plain scalars that start with `@`, `%`, a backtick or `|`: read as strings, as 2.8 did. 7.4 alone throws.

The fork repairs only the line the parser reports and parses again. Every other error is still thrown.

**Fixed by the upgrade:** literal (`|`) and folded (`>`) block scalars. 2.8 failed on them with a `TypeError` on
PHP 8.

**Accepted changes:**

| Input | 2.8 | 7.4 |
|---|---|---|
| `0755` | Octal: 493 | The string `"0755"` (YAML 1.2); `0o755` is now octal |
| `1_000` | The string `"1_000"` | 1000 |
| `-0` | 0 | The string `"-0"` |
| `!!binary` and `!!str` tags | Kept as text, with the tag | Decoded |
| `!php/object` tags | Loaded as `null` | `ParseException` |
| A duplicate key inside an inline mapping, `{a: 1, a: 2}` | Loaded | `ParseException` |
| Dumping a string with a `'` | `'it''s'` | `"it's"`, which reads back the same |

**Limits.** The fork also caps a parsed document at one million values. 7.4 allows up to 128 aliases, and a
short document can use them to describe a structure far larger than memory, which the registry's JSON conversion
would expand. An alias bomb now fails with a `ParseException` in about 50 ms. Deep nesting stops at 7.4's
128-level limit.
