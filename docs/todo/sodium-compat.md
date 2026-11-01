# Upgrade paragonie/sodium_compat 1.17.1 to the latest 2.x

**Status:** done on the `tasks/upgrade-sodium-compat` branch, for release 3.12: sodium_compat 2.5.2.

- `SodiumCipher` needed no change: 2.x still provides `ParagonIE\Sodium\Compat` (now through Composer's PSR-4
  autoloader instead of `lib/namespaced.php`) and every method it calls.
- A key pair and ciphertext made with 1.17.1 decrypt with 2.5.2, with and without the `sodium` extension, and an
  all-zero public key is rejected with a `SodiumException`.
- 2.x requires a 64-bit build of PHP. Composer's platform check stops the site with an error on 32-bit PHP; the
  fork accepts that.
- `paragonie/random_compat` stays, because `joomla/session` 1.x still requires it. On PHP 8 it does nothing.
- `com_admin/script.php` removes the files 1.17.1 had and 2.5.2 does not.

The plan below is kept for reference.

**Why:** CVE-2025-69277 (Moderate, GHSA-mrfv-m5wm-5w6w). `crypto_box` and related functions did
not check that a public key lies in the prime-order subgroup.

- Fixed in 1.24.0 on the 1.x line, and in 2.5.0 on the 2.x line.
- Later releases added further checks under a separate advisory (GHSA-733q-f2wq-9c8w).

So take the newest 2.x release, not 2.5.0 exactly.

**Target:** the latest 2.x. The 1.x line exists to support PHP 5.2–8.0, which the fork will no
longer need. 2.x is also the line that will keep receiving fixes.

**Risk: low.** sodium_compat mirrors the API of PHP's own `sodium` extension, which has been part
of PHP since 7.2. On most 8.3+ hosts the extension is loaded, and the library only fills in when a
host has compiled PHP without it.

## Where it is used

- `libraries/src/Crypt/Cipher/SodiumCipher.php` calls `ParagonIE\Sodium\Compat::crypto_box`,
  `crypto_box_open`, `crypto_box_keypair`, `crypto_box_publickey`, `crypto_box_secretkey` and
  `crypto_box_keypair_from_secretkey_and_publickey`.
- `libraries/vendor/composer/autoload_files.php` loads its `autoload.php`, which defines the
  `sodium_*` functions when the extension is missing. Third-party extensions may call those
  functions directly.

## Workflow

1. **Read the release notes.** Go through https://github.com/paragonie/sodium_compat/releases
   from 1.17.1 to the newest 2.x.
   - List anything 2.0 removed or renamed. In particular, check that the
     `ParagonIE\Sodium\Compat` class and every method `SodiumCipher` calls still exist.
   - List anything that now throws where it used to return, such as the new public-key checks.
   - If 2.x removed something `SodiumCipher` uses, change `SodiumCipher` to call the
     `sodium_*` functions instead. They exist natively or through the polyfill on every host.
2. **Pin the new version.** In `composer.json`, set `"paragonie/sodium_compat": "<new exact
   version>"`. Keep an exact pin, so an unrelated `composer update` cannot move it.
3. **Install it.** Run `composer update paragonie/sodium_compat --with-dependencies --no-dev`.
   2.x no longer requires `paragonie/random_compat`. If nothing else needs it (check with
   `composer why paragonie/random_compat`), remove it from `composer.json` as well. PHP 8 has
   `random_bytes()` and `random_int()` built in.
4. **Review the vendor diff.**
   - Add `.gitignore` lines for any new files that are not needed at runtime.
   - List the files 1.17.1 had and 2.x does not, and add them to the `$files` array in
     `com_admin/script.php` so Joomla Update deletes them on existing sites.
   - The same goes for `paragonie/random_compat` if you removed it.

## Testing

Run each test twice: once with the PHP `sodium` extension enabled, which uses native code, and once
with it disabled, which uses the library. The library path is the one this upgrade changes.

- **Round trip:** generate a key with `SodiumCipher::generateKey()`, then encrypt and decrypt a
  string.
- **Old data:** take a key pair and ciphertext produced by 1.17.1, from a build before the
  upgrade, and decrypt them with the new version. Data that sites already store must still
  decrypt.
- **Bad keys:** a deliberately invalid public key (an all-zero key, a small-order point) is now
  rejected.
- Run the unit tests that cover `JCrypt` and the ciphers, on PHP 8.3 and the newest PHP. Once the
  test suite runs again (see [php-minimum.md](php-minimum.md)), add the old-data test there.

## Done when

- `composer.lock` shows the latest 2.x, and `random_compat` is gone if nothing needs it.
- Old ciphertext still decrypts, and invalid public keys are rejected.
- The release notes say "sodium_compat 1.17.1 → 2.x.y (CVE-2025-69277)".
