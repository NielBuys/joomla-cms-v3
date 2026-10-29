# Security Policy

This repository is a community fork of Joomla! 3. It is not made, endorsed or supported by Open
Source Matters or the Joomla! Project. The Joomla! Security Strike Team (JSST) does not handle
security issues in this fork, so please do not send reports about it to them.

## Reporting a vulnerability

Report vulnerabilities **privately**, through GitHub:

1. Open [Report a vulnerability](https://github.com/NielBuys/joomla-cms-v3/security/advisories/new).
   You can also reach it from the repository's **Security** tab.
2. Describe the issue, the affected files or URLs, and the steps to reproduce it.
3. Submit the report. Only you and the maintainers can see it.

Please **do not** report vulnerabilities in public issues, pull requests or discussions.

### What to report here

- Vulnerabilities in this fork's code, including changes made only in this fork.
- Vulnerabilities in the original Joomla 3 code that are still present in this fork. Joomla 3 has
  reached end of life, so the Joomla project no longer fixes them.
- Vulnerabilities in the third-party libraries this fork bundles, for example in
  `libraries/vendor` or `media/`, when they can be reached through this fork.

### What not to report here

- Vulnerabilities in third-party Joomla extensions. Report them to the extension's developer, and
  to the [Vulnerable Extensions List](https://vel.joomla.org/submit-vel).
- Vulnerabilities in Joomla 4, 5 or 6. Report them to the
  [Joomla Security Strike Team](https://developer.joomla.org/security/contact-the-team.html).
- Help with a site that has already been attacked. Ask in the
  [Joomla! Forum](https://forum.joomla.org/viewforum.php?f=714).

## Supported versions

Security fixes are made only for the latest release of this fork. Update to it before reporting.

## How reports are handled

This fork is maintained by volunteers, so response times are best effort. The aim is to:

- acknowledge a report within 14 days,
- confirm or reject it, with the reason, once it has been investigated, and
- keep you informed in the private advisory until a fix is released.

## Disclosure

- A confirmed vulnerability is only made public after a release that fixes it is available.
- The fix is announced in the release notes and published as a GitHub security advisory.
  Where appropriate, a CVE is requested through GitHub.
- Announcements describe the issue and the fix, but do not include step-by-step exploit
  instructions.
- Reporters are credited in the advisory unless they ask not to be. You can choose your full name,
  a pseudonym, or no credit at all.
