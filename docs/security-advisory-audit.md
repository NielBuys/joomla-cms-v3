# Security advisory audit

This is the list of advisories to check when auditing the fork's security. It covers three
sources:

1. [Joomla core and Framework advisories](#joomla-core-and-framework-advisories) from the
   Joomla Security Strike Team (JSST), since Joomla 3 reached end of life.
2. [PHP packages](#php-packages) in `libraries/vendor`.
3. [JavaScript libraries](#javascript-libraries) bundled under `media/`.

**Last audit:** 31 October 2026, against `3.10-dev` (3.11.x) and the 3.12 branches. The status
column says where each fix is in the fork.

The plans for upgrading the bundled libraries are in [docs/todo](todo/README.md). Vulnerabilities
are reported privately, as described in [SECURITY.md](../.github/SECURITY.md); this file only lists
advisories that are already public.

## How to run an audit

1. **Joomla core and Framework.** Read every announcement in the
   [JSST security centre](https://developer.joomla.org/security-centre.html) published since the
   last audit, and add it to the table below.
   - **Do not trust the version range.** JSST ranges often start at 4.0.0 even when Joomla 3 has
     the same code. [20260804] and [20260808] list 4.0.0 onwards, but Joomla 3 was affected.
     Find the fix in [joomla/joomla-cms](https://github.com/joomla/joomla-cms) (compare the release
     tag with the one before it), and check whether the same code exists in this fork.
   - A range that names `3.10.x-elts` (Joomla's paid extended support for 3.x) always applies.
   - Joomla 3 has no webservice API (`api/`), no CORS settings, no mail templates (`com_mails`),
     no workflows, no schema.org plugins and no MFA framework (it has the older two-factor
     authentication). Advisories that only touch those do not apply, but check the whole fix:
     one release often fixes the same flaw in other places too.
2. **The EOL Security Fixes plugin.** Check
   [TLWebdesign/Joomla-3-EOL-Security-Fixes](https://github.com/TLWebdesign/Joomla-3-EOL-Security-Fixes)
   for new releases, and its issues for advisories the maintainer decided not to backport (for
   example [#17](https://github.com/TLWebdesign/Joomla-3-EOL-Security-Fixes/issues/17) for the
   August 2026 release). Port new releases as described in its README's version list. Other forks
   of it, such as Streetblock/Joomla-3-EOL-Security-Fixes, sometimes carry backports that never
   went upstream; use them only as a cross-check.
3. **PHP packages.** On each release branch run:

   ```bash
   composer audit --locked --no-dev
   ```

   Every advisory either has a fix in the fork or an entry under `audit.ignore` in
   `composer.json` that says why.
4. **JavaScript libraries.** For each library in the table below, list the advisories for the
   shipped version, for example:

   ```bash
   gh api "/advisories?ecosystem=npm&affects=bootstrap@2.3.2&per_page=100" --jq '.[] | select(.withdrawn_at == null) | [.cve_id, .ghsa_id, .summary] | @tsv'
   ```

   Also check [osv.dev](https://osv.dev) for libraries the GitHub database does not cover, such
   as MooTools.
5. **This fork's own advisories.** List them in the
   [Security tab](https://github.com/NielBuys/joomla-cms-v3/security/advisories) and add them
   to the last table.
6. Record the result: update the tables and the "Last audit" date above.

## Joomla core and Framework advisories

All JSST announcements since Joomla 3 reached end of life (17 August 2023), newest first, as
listed in the security centre on 31 October 2026. "Fixed: EOL plugin" means the fix came from the
[EOL Security Fixes plugin](https://github.com/TLWebdesign/Joomla-3-EOL-Security-Fixes) release
named, ported in the fork pull request given. "To check" means nobody has yet compared the fix with
Joomla 3's code.

| Advisory | Title | CVE | Severity | Versions (JSST) | Fork status |
|---|---|---|---|---|---|
| [20260916](https://developer.joomla.org/security-centre/1096-20260916-core-xss-filter-bypass-in-inputfilter-via-whitespace-characters-in-html-data-uris.html) | Core - XSS filter bypass in InputFilter via whitespace characters in HTML data URIs | CVE-2026-92232 | Moderate | 1.5.0-5.4.8, 6.0.0-6.1.3 | Fixed: EOL plugin 1.1.5 (fork PR #27) |
| [20260915](https://developer.joomla.org/security-centre/1095-20260915-core-xss-filter-bypass-in-inputfilter-via-html5-entity-decode-mismatch.html) | Core - XSS filter bypass in InputFilter via HTML5 entity decode mismatch | CVE-2026-92231 | Moderate | 1.5.0-5.4.8, 6.0.0-6.1.3 | Fixed: EOL plugin 1.1.5 (fork PR #27) |
| [20260914](https://developer.joomla.org/security-centre/1094-20260914-core-mfa-authentication-bypass-through-rememberme-cookies.html) | Core - MFA Authentication Bypass through rememberme cookies | CVE-2026-92227 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260913](https://developer.joomla.org/security-centre/1093-20260913-core-improper-acl-checks-for-varous-webservice-edit-tasks.html) | Core - Improper ACL checks for varous webservice edit tasks | CVE-2026-92226 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260912](https://developer.joomla.org/security-centre/1092-20260912-core-xss-in-module-list.html) | Core - XSS in module list | CVE-2026-92225 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260910](https://developer.joomla.org/security-centre/1090-20260910-core-improper-acl-checks-for-workflow-stage-changes.html) | Core - Improper ACL checks for workflow stage changes | CVE-2026-92223 | Moderate | 5.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260909](https://developer.joomla.org/security-centre/1089-20260909-core-ssrf-vectors-in-various-core-extensions.html) | Core - SSRF vectors in various core extensions | CVE-2026-92222 | High | 3.0.0-5.4.8, 6.0.0-6.1.3 | Fixed: EOL plugin 1.1.5 (fork PR #27) |
| [20260908](https://developer.joomla.org/security-centre/1088-20260908-core-xss-in-html-mail-templates.html) | Core - XSS in HTML Mail Templates | CVE-2026-90918 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260911](https://developer.joomla.org/security-centre/1091-20260911-core-xss-in-link-toolbar-layout.html) | Core - XSS in link toolbar layout | CVE-2026-92224 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260907](https://developer.joomla.org/security-centre/1087-20260907-core-improper-acl-checks-in-outputs-for-tagged-items.html) | Core - Improper ACL checks in outputs for tagged items | CVE-2026-90917 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260906](https://developer.joomla.org/security-centre/1086-20260906-core-improper-acl-checks-in-content-history-comparsion-view.html) | Core - Improper ACL checks in content history comparison view | CVE-2026-90916 | Low | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260905](https://developer.joomla.org/security-centre/1085-20260905-core-arbitrary-directory-deletion-via-cache-purge-action.html) | Core - Arbitrary directory deletion via cache purge action | CVE-2026-90915 | High | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260904](https://developer.joomla.org/security-centre/1084-20260904-core-xss-in-the-generic-media-output-layouts.html) | Core - XSS in the generic media output layouts | CVE-2026-90914 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260903](https://developer.joomla.org/security-centre/1083-20260903-core-improper-acl-checks-for-user-level-webservice-endpoints.html) | Core - Improper ACL checks for access level webservice endpoints | CVE-2026-90913 | Moderate | 4.0.0-5.4.8, 6.0.0-6.1.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260902](https://developer.joomla.org/security-centre/1082-20260902-core-unauthorized-user-account-creation-via-profile-save-controller.html) | Core - Unauthorized user account creation via profile.save controller | CVE-2026-90907 | Moderate | 1.5.0-5.4.8, 6.0.0-6.1.3 | Fixed: EOL plugin 1.1.5 (fork PR #27) |
| [20260901](https://developer.joomla.org/security-centre/1081-20260901-core-xss-in-htmlhelper-link-method.html) | Core - XSS in HTMLHelper::link method | CVE-2026-90906 | Moderate | 1.5.0-5.4.8, 6.0.0-6.1.3 | **To check**: the range includes Joomla 3 |
| [20260810](https://developer.joomla.org/security-centre/1077-20260810-core-unrestricted-uploads-of-shtml-files.html) | Core - Unrestricted uploads of SHTML files | CVE-2026-73373 | Low | 1.0.0-5.4.7, 6.0.0-6.1.2 | Fixed in 3.11.19 (GHSA-w5wf-vvrg-x8wh) |
| [20260809](https://developer.joomla.org/security-centre/1076-20260809-core-improper-acl-checks-when-injection-schema-org-contact-data.html) | Core - Improper ACL checks when injection schema.org contact data | CVE-2026-73372 | Low | 5.1.0-5.4.7, 6.0.0-6.1.2 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260808](https://developer.joomla.org/security-centre/1075-20260808-core-improper-acl-checks-for-batch-copy-actions.html) | Core - Improper ACL checks for batch copy actions | CVE-2026-73371 | Low | 4.0.0-5.4.7, 6.0.0-6.1.2 | Fixed in 3.11.19 (GHSA-w5wf-vvrg-x8wh) |
| [20260807](https://developer.joomla.org/security-centre/1074-20260807-core-mfa-authentication-bypass.html) | Core - MFA Authentication Bypass | CVE-2026-73337 | Moderate | 4.0.0-5.4.7, 6.0.0-6.1.2 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260806](https://developer.joomla.org/security-centre/1073-20260806-core-xss-through-schema-org-outputs.html) | Core - XSS through schema.org outputs | CVE-2026-73336 | Moderate | 5.1.0-5.4.7, 6.0.0-6.1.2 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260805](https://developer.joomla.org/security-centre/1072-20260805-core-improper-acl-checks-for-category-webservice-endpoints.html) | Core - Improper ACL checks for category webservice endpoints | CVE-2026-72532 | Moderate | 4.0.0-5.4.7, 6.0.0-6.1.2 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260804](https://developer.joomla.org/security-centre/1071-20260804-core-improper-acl-checks-for-custom-fields-webservice-endpoints.html) | Core - Improper ACL checks for custom fields webservice endpoints | CVE-2026-72531 | Moderate | 4.0.0-5.4.7, 6.0.0-6.1.2 | Fixed in 3.11.19 (GHSA-w5wf-vvrg-x8wh): administrator controllers; Joomla 3 has no webservice API |
| [20260803](https://developer.joomla.org/security-centre/1070-20260803-core-inconsistent-acl-checks-for-mutating-webservice-endpoints.html) | Core - Inconsistent ACL checks for mutating webservice endpoints | CVE-2026-71574 | Moderate | 4.0.0-5.4.7, 6.0.0-6.1.2 | Not applicable: Joomla 3 has no `api/` folder |
| [20260802](https://developer.joomla.org/security-centre/1069-20260802-core-improper-cors-origin-validation.html) | Core - Improper CORS origin validation | CVE-2026-71573 | Moderate | 4.0.0-5.4.7, 6.0.0-6.1.2 | Not applicable: Joomla 3 has no API application or CORS settings |
| [20260801](https://developer.joomla.org/security-centre/1068-20260801-core-response-header-injection-in-download-views.html) | Core - Response header injection in download views | CVE-2026-71572 | Low | 3.0.0-5.4.7, 6.0.0-6.1.2 | Fixed in 3.11.19 (GHSA-w5wf-vvrg-x8wh) |
| [20260712](https://developer.joomla.org/security-centre/1066-20260712-core-incorrect-access-control-in-com-fields-webservice-endpoints.html) | Core - Incorrect Access Control in com_fields webservice endpoints | CVE-2026-48958 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260711](https://developer.joomla.org/security-centre/1065-20260711-core-incorrect-access-control-in-com-privacy-webservice-endpoints.html) | Core - Incorrect Access Control in com_privacy webservice endpoints | CVE-2026-48957 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260710](https://developer.joomla.org/security-centre/1064-20260710-core-incorrect-access-control-in-com-modules.html) | Core - Incorrect Access Control in com_modules | CVE-2026-48956 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260709](https://developer.joomla.org/security-centre/1063-20260709-core-incorrect-access-control-in-com-workflow.html) | Core - Incorrect Access Control in com_workflow | CVE-2026-48955 | Moderate | 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260708](https://developer.joomla.org/security-centre/1062-20260708-core-xss-through-language-overrides.html) | Core - XSS through language overrides | CVE-2026-48954 | Moderate | 3.0.0-5.4.6, 6.0.0-6.1.1 | Fixed: EOL plugin 1.1.4 (fork PR #23) |
| [20260707](https://developer.joomla.org/security-centre/1061-20260707-core-xss-in-the-generic-image-output-layout.html) | Core - XSS in the generic image output layout | CVE-2026-48953 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260706](https://developer.joomla.org/security-centre/1060-20260706-core-xss-in-com-installer.html) | Core - XSS in com_installer | CVE-2026-48952 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260705](https://developer.joomla.org/security-centre/1059-20260705-core-xss-in-various-modalreturn-layouts.html) | Core - XSS in various modalreturn layouts | CVE-2026-48951 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260704](https://developer.joomla.org/security-centre/1058-20260704-core-xss-in-com-templates.html) | Core - XSS in com_templates | CVE-2026-48950 | Moderate | 4.0.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260703](https://developer.joomla.org/security-centre/1057-20260703-core-xss-in-mfa-method-management.html) | Core - XSS in MFA method management | CVE-2026-48949 | Moderate | 4.2.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260702](https://developer.joomla.org/security-centre/1056-20260702-core-incorrect-access-control-in-com-contact-vcf-download.html) | Core - Incorrect Access Control in com_contact vcf download | CVE-2026-48948 | Low | 3.0.0-5.4.6, 6.0.0-6.1.1 | Fixed: EOL plugin 1.1.4 (fork PR #23) |
| [20260701](https://developer.joomla.org/security-centre/1055-20260701-core-incorrect-access-control-in-com-media-webservice-endpoints.html) | Core - Incorrect Access Control in com_media webservice endpoints | CVE-2026-48947 | Low | 4.1.0-5.4.6, 6.0.0-6.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260520](https://developer.joomla.org/security-centre/1052-20260520-framework-inadequate-content-filtering-within-the-cleanattributes-filter-code.html) | Framework - Inadequate content filtering within the cleanAttributes filter code | CVE-2026-48905 | Moderate | 3.0.0-5.4.5, 6.0.0-6.1.0 | Fixed: EOL plugin 1.0.11 (fork PR #20) |
| [20260519](https://developer.joomla.org/security-centre/1051-20260519-framework-inadequate-content-filtering-within-the-checkattribute-filter-code.html) | Framework - Inadequate content filtering within the checkAttribute filter code | CVE-2026-48903 | Moderate | 3.0.0-5.4.5, 6.0.0-6.1.0 | Fixed: EOL plugin 1.0.11 (fork PR #20) |
| [20260518](https://developer.joomla.org/security-centre/1050-20260518-core-transport-encryption-downgrade-for-password-and-username-reset-links.html) | Core - Transport encryption downgrade for password and username reset links | CVE-2026-48902 | Low | 3.9.0-5.4.5, 6.0.0-6.1.0 | Fixed: EOL plugin 1.0.11 (fork PR #20) |
| [20260517](https://developer.joomla.org/security-centre/1049-20260517-core-incorrect-cache-key-construction-for-inputfilter-objects.html) | Core - Incorrect Cache Key Construction for InputFilter objects | CVE-2026-48901 | Low | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260516](https://developer.joomla.org/security-centre/1048-20260516-core-incorrect-access-control-in-com-scheduler.html) | Core - Incorrect Access Control in com_scheduler | CVE-2026-48900 | Low | 4.1.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260515](https://developer.joomla.org/security-centre/1047-20260515-core-incorrect-access-control-in-sample-data-plugins.html) | Core - Incorrect Access Control in sample data plugins | CVE-2026-48899 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260514](https://developer.joomla.org/security-centre/1046-20260514-core-privilege-escalation-through-com-users-webservice-endpoints.html) | Core - Privilege escalation through com_users webservice endpoints | CVE-2026-48904 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260513](https://developer.joomla.org/security-centre/1045-20260513-core-privilege-escalation-through-com-users-batch-task.html) | Core - Privilege escalation through com_users batch task | CVE-2026-48898 | High | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260512](https://developer.joomla.org/security-centre/1044-20260512-core-mfa-authentication-bypass.html) | Core - MFA Authentication Bypass | CVE-2026-48897 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260511](https://developer.joomla.org/security-centre/1043-20260511-core-mfa-authentication-bypass.html) | Core - MFA Authentication Bypass | CVE-2026-48896 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260510](https://developer.joomla.org/security-centre/1042-20260510-core-path-traversal-in-com-media-webservice-endpoint.html) | Core - Path traversal in com_media webservice endpoint | CVE-2026-40384 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260509](https://developer.joomla.org/security-centre/1041-20260509-core-lfi-in-htmlview-layout-parameter.html) | Core - LFI in HTMLView layout parameter | CVE-2026-40383 | High | 3.2.1-5.4.5, 6.0.0-6.1.0 | Fixed: EOL plugin 1.0.11 (fork PR #20) |
| [20260508](https://developer.joomla.org/security-centre/1040-20260508-core-improper-access-check-in-com-config-webservice-endpoints.html) | Core - Improper access check in com_config webservice endpoints | CVE-2026-35223 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260507](https://developer.joomla.org/security-centre/1039-20260507-core-authenticated-blind-sqli-in-com-tags.html) | Core - Authenticated blind SQLi in com_tags | CVE-2026-352212 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260506](https://developer.joomla.org/security-centre/1038-20260506-core-authenticated-blind-sqli-in-com-finder.html) | Core - Authenticated blind SQLi in com_finder | CVE-2026-35221 | Moderate | 5.4.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260505](https://developer.joomla.org/security-centre/1037-20260505-core-csrf-in-user-activation-endpoint.html) | Core - CSRF in user activation endpoint | CVE-2026-35220 | Moderate | 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260504](https://developer.joomla.org/security-centre/1036-20260504-core-xss-in-readmore-links.html) | Core - XSS in readmore links | CVE-2026-30895 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260503](https://developer.joomla.org/security-centre/1035-20260503-core-xss-in-com-contenthistory.html) | Core - XSS in com_contenthistory | CVE-2026-30894 | Moderate | 3.0.0-5.4.5, 6.0.0-6.1.0 | Fixed: EOL plugin 1.0.11 (fork PR #20) |
| [20260502](https://developer.joomla.org/security-centre/1034-20260502-core-xss-in-com-associations.html) | Core - XSS in com_associations | CVE-2026-25901 | Moderate | 4.0.0-5.4.5, 6.0.0-6.1.0 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260501](https://developer.joomla.org/security-centre/1033-20260501-core-xss-in-feed-modules.html) | Core - XSS in feed modules | CVE-2026-25900 | Moderate | 3.0.0-5.4.5, 6.0.0-6.1.0 | Fixed: EOL plugin 1.0.11 (fork PR #20) |
| [20260301](https://developer.joomla.org/security-centre/1027-20260301-core-acl-hardening-in-com-ajax.html) | Core - ACL hardening in com_ajax | CVE-2026-21629 | Low | 3.0.0-5.4.3, 6.0.0-6.0.3 | Fixed: EOL plugin 1.0.10 (fork PR #18) |
| [20260302](https://developer.joomla.org/security-centre/1028-20260302-core-sql-injection-in-com-content-articles-webservice-endpoint.html) | Core - SQL injection in com_content articles webservice endpoint | CVE-2026-21630 | Low | 4.0.0-5.4.3, 6.0.0-6.0.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260303](https://developer.joomla.org/security-centre/1029-20260303-core-xss-vector-in-com-associations-comparison-view.html) | Core - XSS vector in com_associations comparison view | CVE-2026-21631 | Moderate | 4.0.0-5.4.3, 6.0.0-6.0.3 | Fixed: EOL plugin 1.0.10 (fork PR #18) |
| [20260304](https://developer.joomla.org/security-centre/1030-20260304-core-xss-vectors-in-various-article-title-outputs.html) | Core - XSS vectors in various article title outputs | CVE-2026-21632 | Moderate | 4.0.0-5.4.3, 6.0.0-6.0.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260305](https://developer.joomla.org/security-centre/1031-20260305-core-arbitrary-file-deletion-in-com-joomlaupdate.html) | Core - Arbitrary file deletion in com_joomlaupdate | CVE-2026-23898 | High | 4.0.0-5.4.3, 6.0.0-6.0.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260306](https://developer.joomla.org/security-centre/1032-20260306-core-improper-access-check-in-webservice-endpoints.html) | Core - Improper access check in webservice endpoints | CVE-2026-23899 | High | 4.0.0-5.4.3, 6.0.0-6.0.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20260101](https://developer.joomla.org/security-centre/1016-20260101-core-inadequate-content-filtering-for-data-urls.html) | Core - Inadequate content filtering for data URLs | CVE-2025-63082 | Moderate | 4.0.0-5.4.1, 6.0.0-6.0.1 | Fixed: EOL plugin 1.0.9 (fork PR #14) |
| [20260102](https://developer.joomla.org/security-centre/1017-20260102-core-xss-vector-in-the-pagebreak-plugin.html) | Core - XSS vectors in the pagebreak and pagenavigation plugins | CVE-2025-63083 | Moderate | 3.9.0-5.4.1, 6.0.0-6.0.1 | Fixed: EOL plugin 1.0.9 (fork PR #14) |
| [20250901](https://developer.joomla.org/security-centre/1010-20250901-core-inadequate-content-filtering-within-the-checkattribute-filter-code.html) | Core - Inadequate content filtering within the checkAttribute filter code | CVE-2025-54476 | Moderate | 3.0.0-3.10.20-elts, 4.0.0-4.4.13, 5.0.0-5.3.3 | Fixed: fork PR #11 (and the `j3_filter` fork) |
| [20250902](https://developer.joomla.org/security-centre/1011-20250902-core-user-enumeration-in-passkey-authentication-method.html) | Core - User-Enumeration in passkey authentication method | CVE-2025-54477 | Low | 4.0.0-4.4.13, 5.0.0-5.3.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20250401](https://developer.joomla.org/security-centre/963-20250401-framework-sql-injection-vulnerability-in-quotenamestr-method-of-database-package.html) | Framework - SQL injection vulnerability in quoteNameStr method of Database package | CVE-2025-25226 | Low | 1.0.0-2.1.1, 3.0.0-3.3.1 | Fixed: fork commit 82905c8227 and EOL plugin 1.0.9 (fork PR #14) |
| [20250402](https://developer.joomla.org/security-centre/964-20250402-core-mfa-authentication-bypass.html) | Core - MFA Authentication Bypass | CVE-2025-25227 | Moderate | 4.0.0 - 4.4.12, 5.0.0 - 5.2.5 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20250301](https://developer.joomla.org/security-centre/961-20250301-core-malicious-file-uploads-via-media-manager.html) | Core - Malicious file uploads via Media Manager | CVE-2025-22213 | Low | 4.0.0-4.4.11, 5.0.0-5.2.4 | Fixed: EOL plugin 1.0.9 (fork PR #14) |
| [20250103](https://developer.joomla.org/security-centre/956-20250103-core-read-acl-violation-in-multiple-core-views.html) | Core - Read ACL violation in multiple core views | CVE-2024-40749 | Moderate | 3.9.0-3.10.19-elts, 4.0.0-4.4.9, 5.0.0-5.2.2 | Fixed: EOL plugin 1.0.8 (fork PR #5) |
| [20250201](https://developer.joomla.org/security-centre/958-20250201-core-sql-injection-vulnerability-in-scheduled-tasks-component.html) | Core - SQL injection vulnerability in Scheduled Tasks component | CVE-2025-22207 | Low | 4.1.0-4.4.10, 5.0.0-5.2.3 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20250102](https://developer.joomla.org/security-centre/955-20250102-core-xss-vector-in-the-id-attribute-of-menu-lists.html) | Core - XSS vector in the id attribute of menu lists | CVE-2024-40748 | Moderate | 3.0.0-3.10.19-elts, 4.0.0-4.4.9, 5.0.0-5.2.2 | Fixed: EOL plugin 1.0.8 (fork PR #5) |
| [20250101](https://developer.joomla.org/security-centre/954-20250101-core-xss-vectors-in-module-chromes.html) | Core - XSS vectors in module chromes | CVE-2024-40747 | Moderate | 4.0.0-4.4.9, 5.0.0-5.2.2 | Fixed: EOL plugin 1.0.9 (fork PR #14) |
| [20240805](https://developer.joomla.org/security-centre/946-20240805-core-xss-vectors-in-outputfilter-strip-methods.html) | Core - XSS vectors in Outputfilter::strip* methods | CVE-2024-40743 | Moderate | 3.0.0-3.10.16-elts, 4.0.0-4.4.6, 5.0.0-5.1.2 | Fixed: EOL plugin 1.0.6 (fork PR #5) |
| [20240804](https://developer.joomla.org/security-centre/945-20240804-core-improper-acl-for-backend-profile-view.html) | Core - Improper ACL for backend profile view | CVE-2024-27187 | Moderate | 4.0.0-4.4.6, 5.0.0-5.1.2 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20240803](https://developer.joomla.org/security-centre/944-20240803-core-xss-in-html-mail-templates.html) | Core - XSS in HTML Mail Templates | CVE-2024-27186 | Moderate | not listed | Not applicable: mail templates (`com_mails`) only exist from Joomla 4 |
| [20240802](https://developer.joomla.org/security-centre/942-20240802-core-cache-poisoning-in-pagination.html) | Core - Cache Poisoning in Pagination | CVE-2024-27185 | Low | 3.0.0-3.10.16-elts, 4.0.0-4.4.6, 5.0.0-5.1.2 | Fixed: EOL plugin 1.0.6 (fork PR #5) |
| [20240801](https://developer.joomla.org/security-centre/941-20240801-core-inadequate-validation-of-internal-urls.html) | Core - Inadequate validation of internal URLs | CVE-2024-27184 | Low | 3.4.6-3.10.16-elts, 4.0.0-4.4.6, 5.0.0-5.1.2 | Fixed: EOL plugin 1.0.6 (fork PR #5) |
| [20240705](https://developer.joomla.org/security-centre/939-20240705-core-xss-in-com-fields-default-field-value.html) | Core - XSS in com_fields default field value | CVE-2024-26278 | Moderate | 3.7.0-3.10.15-elts, 4.0.0-4.4.5, 5.0.0-5.1.1 | Fixed: EOL plugin 1.0.5 (fork PR #5) |
| [20240704](https://developer.joomla.org/security-centre/938-20240704-core-xss-in-wrapper-extensions.html) | Core - XSS in Wrapper extensions | CVE-2024-26279 | Moderate | 3.0.0-3.10.15-elts, 4.0.0-4.4.5, 5.0.0-5.1.1 | Fixed: EOL plugin 1.0.5 (fork PR #5) |
| [20240703](https://developer.joomla.org/security-centre/937-20240703-core-xss-in-stringhelper-truncate-method.html) | Core - XSS in StringHelper::truncate method | CVE-2024-21731 | Moderate | 3.0.0-3.10.15-elts, 4.0.0-4.4.5, 5.0.0-5.1.1 | Fixed: EOL plugin 1.0.5 (fork PR #5) |
| [20240702](https://developer.joomla.org/security-centre/936-20240702-core-self-xss-in-fancyselect-list-field-layout.html) | Core - Self-XSS in fancyselect list field layout | CVE-2024-21730 | Low | 4.0.0-4.4.5, 5.0.0-5.1.1 | Fixed: EOL plugin 1.0.9 (fork PR #14) |
| [20240701](https://developer.joomla.org/security-centre/935-20240701-core-xss-in-accessible-media-selection-field.html) | Core - XSS in accessible media selection field | CVE-2024-21729 | Moderate | 4.0.0-4.4.5, 5.0.0-5.1.1 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20240205](https://developer.joomla.org/security-centre/929-20240205-core-inadequate-content-filtering-within-the-filter-code.html) | Core - Inadequate content filtering within the filter code | CVE-2024-21726 | Moderate | 3.7.0-3.10.14-elts, 4.0.0-4.4.2, 5.0.0-5.0.2 | Fixed: EOL plugin 1.0.0 - 1.0.4 (fork PR #5) |
| [20240204](https://developer.joomla.org/security-centre/928-20240204-core-xss-in-mail-address-outputs.html) | Core - XSS in mail address outputs | CVE-2024-21725 | High | 4.0.0-4.4.2, 5.0.0-5.0.2 | To check: range starts after 3.x; look for the same code in Joomla 3 |
| [20240203](https://developer.joomla.org/security-centre/927-20240203-core-xss-in-media-selection-fields.html) | Core - XSS in media selection fields | CVE-2024-21724 | Moderate | 1.6.0-3.10.14-elts, 4.0.0-4.4.2, 5.0.0-5.0.2 | Fixed: EOL plugin 1.0.0 - 1.0.4 (fork PR #5) |
| [20240202](https://developer.joomla.org/security-centre/926-20240202-core-open-redirect-in-installation-application.html) | Core - Open redirect in installation application | CVE-2024-21723 | Low | 1.5.0 - 3.10.14-elts, 4.0.0-4.4.2, 5.0.0-5.0.2 | Fixed: EOL plugin 1.0.0 - 1.0.4 (fork PR #5) |
| [20240201](https://developer.joomla.org/security-centre/925-20240201-core-insufficient-session-expiration-in-mfa-management-views.html) | Core - Insufficient session expiration in MFA management views | CVE-2024-21722 | Low | 3.2.0-3.10.14-elts, 4.0.0-4.4.2, 5.0.0-5.0.2 | Fixed: EOL plugin 1.0.0 - 1.0.4 (fork PR #5) |
| [20231101](https://developer.joomla.org/security-centre/919-20231101-core-exposure-of-environment-variables.html) | Core - Exposure of environment variables | CVE-2023-40626 | High | 1.6.0-4.4.0, 5.0.0 | Fixed: EOL plugin 1.0.0 - 1.0.4 (fork PR #5) |

## PHP packages

From `composer audit --locked --no-dev` on 31 October 2026.

| Package | Advisory | Problem | 3.11.x (`3.10-dev`) | 3.12 |
|---|---|---|---|---|
| joomla/filter 1.x | CVE-2025-54476 (PKSA-5x9t-bkbj-vmgp) | XSS through the input filter | Fixed in the `j3_filter` fork (fork PR #11); Composer still reports it because the version number is 1.x | Same; listed under `audit.ignore` |
| paragonie/random_compat 1.x | PKSA-cncr-q695-v65g | Insecure CSPRNG fallback (`openssl_random_pseudo_bytes()`) | Not reachable on PHP 7 and later, which have `random_bytes()` and never load the fallback | Fixed: 2.0.21 |
| paragonie/sodium_compat 1.17.1 | CVE-2025-69277 (PKSA-bjyb-w14t-61js), PKSA-32g2-byr9-drtw, PKSA-8x19-j2j3-bn67 | Incomplete input checks in `crypto_box` and Ed25519 point validation | Open: fixed only in 2.x, which needs PHP 8.3 and 64-bit PHP | Fixed: 2.5.2 ([plan](todo/sodium-compat.md)) |
| symfony/yaml 2.8.52 | CVE-2026-45304, CVE-2026-45305, CVE-2026-45133, CVE-2026-109413 | Parser denial of service | Open: fixed only in 5.4 and later | Fixed: 7.4.22 ([plan](todo/symfony-yaml.md)) |

Composer also reports `pear/cache_lite` as abandoned on 3.12; there is no advisory for it.

## JavaScript libraries

From the GitHub Advisory Database (npm) on 31 October 2026, for the versions `3.10-dev` ships.
Withdrawn advisories are left out; duplicate GitHub entries are merged into the CVE they repeat.

### jQuery 1.12.4 (`media/jui/js/jquery.js`)

Joomla's own build of 1.12.4 carries patches, marked "Modified by Joomla" in the file header.

| Advisory | Problem | Status |
|---|---|---|
| CVE-2015-9251 (GHSA-rmxg-73gg-4p98) | Cross-domain AJAX responses run as script | Fixed: Joomla patch (gh-2432) |
| CVE-2019-11358 (GHSA-6c3j-c64m-qhgq) | Prototype pollution in `jQuery.extend` | Fixed: Joomla patch (jquery/jquery#4333) |
| CVE-2020-11022 (GHSA-gxr4-xjj5-5px2) | XSS in `htmlPrefilter` | Fixed: Joomla patch (CMS issue 28948) |
| CVE-2020-11023 (GHSA-jpcq-cgw6-v4j6) | XSS in `htmlPrefilter` with `<option>` | Fixed: Joomla patch (CMS issue 28948) |

Scanners that only read the version number still flag the file. The plan to move to jQuery 3 is
in [todo/jquery.md](todo/jquery.md).

### jQuery Migrate 1.4.1 and jQuery UI 1.9.2

The GitHub database has no advisories for jQuery Migrate 1.4.1. Note that Migrate 1.x parses
loosely formed HTML strings, which made the Bootstrap scrollspy issue below reachable.

Joomla ships only the jQuery UI core and sortable widgets (`jquery.ui.core.js`,
`jquery.ui.sortable.js`).

| Advisory | Problem | Status |
|---|---|---|
| CVE-2021-41184 (GHSA-gpqq-952q-5327) | XSS in the `of` option of `.position()` | Fixed: Joomla patch (#37308) |
| CVE-2010-5312, CVE-2012-6662, CVE-2016-7103, CVE-2021-41182, CVE-2021-41183, CVE-2022-31160 | XSS in the dialog, tooltip, datepicker and checkboxradio widgets | Not applicable: those widgets are not shipped |

### Bootstrap 2.3.2 (`media/jui/js/bootstrap.js`)

| Advisory | Problem | Status |
|---|---|---|
| CVE-2019-8331 (GHSA-9v3m-8fp8-mj99) | XSS in tooltip and popover HTML and templates | Fixed in 3.11.19 (GHSA-w685-q8vw-wwp3) |
| CVE-2018-14040 (GHSA-3wqf-4x89-9g79) | XSS in collapse `data-parent` | Fixed in 3.11.19 (GHSA-w685-q8vw-wwp3) |
| CVE-2018-14042 (GHSA-7mvr-5x2g-wfc8) | XSS in tooltip `data-container` | Fixed in 3.11.19 (GHSA-w685-q8vw-wwp3) |
| CVE-2024-6485 (GHSA-vxmc-5x29-h64v) | XSS in button `data-loading-text` | Fixed in 3.11.19 (GHSA-w685-q8vw-wwp3) |
| CVE-2016-10735 (GHSA-4p24-vmcr-4gqj) | XSS in `data-target` (alert, collapse, dropdown, tab, modal, carousel) | Fixed: Joomla's build already uses `$(document).find()` |
| CVE-2018-20676 (GHSA-3mgp-fx93-9xv5) | XSS in tooltip `data-viewport` | Not applicable: 2.3.2's tooltip has no `viewport` option |
| CVE-2018-20677 (GHSA-ph58-4vrj-w6hr) | XSS in affix `target` | Not applicable: 2.3.2's affix has no `target` option |
| CVE-2018-14041 | XSS in scrollspy `data-target` | Not listed for 2.x, but reachable with jQuery Migrate; fixed in 3.11.19 (GHSA-w685-q8vw-wwp3) |
| CVE-2024-6484 | Carousel `data-slide` | Withdrawn as "not a security issue"; scanners still list it |

GHSA-w685-q8vw-wwp3 also fixes the typeahead data-api and modal `remote`, which have no CVE. The
plan is in [todo/bootstrap.md](todo/bootstrap.md).

### TinyMCE 4.5.12 (`media/editors/tinymce`)

No 4.x release fixes these; the full fix is the upgrade planned in [todo/tinymce.md](todo/tinymce.md).

| Advisory | Problem | Status |
|---|---|---|
| CVE-2026-47761 (GHSA-vg35-5wq7-3x7w) | XSS through media plugin `data-mce-*` placeholders | Fixed in 3.11.19 (GHSA-w685-q8vw-wwp3): the input filter removes `data-mce-*` and the media plugin is hardened |
| CVE-2026-47759 (GHSA-q742-qvgc-gc2f) | XSS through `data-mce-` prefixed `src`, `href`, `style` | Not reachable through filtered content: the input filter removes them (from 3.11.19) |
| CVE-2026-47762 (GHSA-v98h-vmpc-fpqv) | XSS through forged `mce:protected` comments | Not reachable through filtered content: checked against the input filter on 31 October 2026 |
| CVE-2024-38357 (GHSA-w9jx-4g6g-rp7x) | XSS through `noscript` elements | Not reachable through filtered content: checked against the input filter on 31 October 2026 |
| CVE-2024-38356 (GHSA-9hcv-j9pv-qmph) | XSS through `noneditable_regexp` | Open; needs a site setting to be exploitable |
| CVE-2024-29203 (GHSA-438c-3975-5x3f) | XSS in iframe handling | Open |
| CVE-2024-29881 (GHSA-5359-pvf2-pw78) | XSS through external SVG files in `object`/`embed` | Open |
| CVE-2023-48219 (GHSA-v626-r774-j7f8) | Mutation XSS through special characters in unescaped text | Open |
| CVE-2023-45819 (GHSA-hgqx-r2hp-jr38) | XSS in `notificationManager.open` | Open |
| CVE-2023-45818 (GHSA-v65r-p3vv-jjfv) | Mutation XSS in undo/redo, `getContent` and autosave | Open |
| CVE-2022-23494 (GHSA-gg8r-xjwq-4w92) | XSS in alerts | Open |
| CVE-2024-21908, CVE-2024-21910, CVE-2024-21911, GHSA-5vm8-hhgr-jcjp | XSS in the core and plugins | Open |
| CVE-2020-12648, CVE-2020-17480, CVE-2019-1010091 | XSS in older releases' content handling | Open |
| GHSA-h96f-fc7c-9r55 | ReDoS in the codesample plugin | Open; low severity |

### Other libraries

| Library | Version | Advisories | Status |
|---|---|---|---|
| MooTools Core and More (`media/system/js`) | 1.6.0, the last release | CVE-2021-23432 (prototype pollution), CVE-2021-32821 (ReDoS) | Open; plan in [todo/mootools.md](todo/mootools.md). The GitHub database does not list MooTools; see osv.dev |
| CodeMirror (`media/editors/codemirror`) | 5.60.0 | None for 5.60.0; CVE-2020-7760 was fixed in 5.58.2 | No action; plan in [todo/codemirror.md](todo/codemirror.md) |
| Chosen (`media/jui/js/chosen.jquery.js`) | 1.6.2 | None found | No action |
| punycode.js (`media/system/js/punycode*.js`) | 1.4.1 | None found | No action |
| html5shiv (`media/jui/js/html5.js`) | 3.7.3 | None found | No action |

## This fork's own advisories

| Advisory | Problem | Fixed in |
|---|---|---|
| [GHSA-j9fx-58gc-7927](https://github.com/NielBuys/joomla-cms-v3/security/advisories/GHSA-j9fx-58gc-7927) | Stored XSS through the SqueezeBox modal `rel` attribute | 3.11.18 |
| [GHSA-w685-q8vw-wwp3](https://github.com/NielBuys/joomla-cms-v3/security/advisories/GHSA-w685-q8vw-wwp3) | Stored XSS through Bootstrap 2 plugins and the TinyMCE media plugin | 3.11.19 |
| [GHSA-w5wf-vvrg-x8wh](https://github.com/NielBuys/joomla-cms-v3/security/advisories/GHSA-w5wf-vvrg-x8wh) | Ports of JSST [20260801], [20260804], [20260808] and [20260810] | 3.11.19 |
