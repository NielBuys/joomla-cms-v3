# CLAUDE.md

Working rules for AI coding agents (such as Claude Code) in this repository, a community fork of
Joomla 3. People who want to work the same way can follow them too.

## 1. Everything here is public: nothing personal

Code comments, docs, commit messages, branch names, pull requests and issues in this repository
are visible to anyone. This file is too.

- Do not write personal details: names (other than the author fields git and GitHub fill in),
  email addresses, phone numbers, pronouns, private conversations or anyone's preferences.
- Do not write local details: paths on a developer's machine, host names, container names,
  local database names or local URLs.
- Never write credentials: passwords, tokens, API keys or private keys, not even as examples.
- Never write unpublished security information. A vulnerability is only described in public
  after its advisory is published. Until then, the fix is prepared in the private security
  advisory and its temporary private fork. See [SECURITY.md](.github/SECURITY.md).
- Comments explain the code, not the history of the conversation that produced it.
- Before committing, read the diff and the commit message once more for anything personal or
  private.

## 2. Independent review, without running the tests twice

Before a code change is committed, pushed or put in a pull request, it gets an independent
review.

- The reviewer (a fresh agent or another person) gets the code and the questions to answer,
  not the author's conclusions.
- The author runs the tests first, and gives the reviewer the commands, the test trees and the
  output files. The reviewer **does not run the same tests again**. It reads those results,
  judges them itself (including whether each test really tests what its name says), and runs
  only the new cases it writes, filtered so only they run.
- The reviewer reports each finding with a file and line, a concrete scenario, and whether it
  was "confirmed by running" or "inferred".
- Fix what the review finds, then have the fixes reviewed again, narrowly: only the fix, and
  only new tests for it.
- Do not let review rounds run on forever. New findings outside the change's scope go on a list
  for the next release.
- Test what will ship: on the branch's minimum PHP version and the newest one, and, if the
  branch will be merged onto a newer base, merged onto that base.

## 3. Branches and PHP versions

| Branch | Releases | PHP |
|---|---|---|
| `3.10-dev` | 3.11.x | 8.0 or later |
| `3.12-dev` | 3.12.x | 8.3 or later, 64-bit only |

The fork's goal is current and future PHP versions, not old ones. Do not add code for PHP
versions below the branch's minimum.

## 4. Security advisories

- Advisories describe this fork only: its affected and fixed versions, and what to do. Do not
  make statements about other Joomla versions or other Joomla 3 distributions.
- Ports of Joomla Security Strike Team (JSST) fixes start their commit subject with the
  announcement id, for example `[20260801] ...`, and name the CVE.
- Before asking for a CVE, check that no existing CVE already covers the issue.

## 5. Dependencies

- Do not hand-edit `libraries/vendor`. A fix to a bundled PHP package goes into a fork of that
  package: commit, tag a new patch version, then `composer update <package>`. (`3.10-dev`
  still has older hand edits; 3.12 replaces them with forks.)
- Commit `libraries/vendor` only after `composer install --no-dev`.

## 6. Releases

- Follow "Releasing the fork" in [README.md](README.md) step by step.
- Do not start a release until the maintainer asks for one.
- There are three update channels (Default, Joomla Next and Testing; see the README). Before
  adding a release to the update server, ask the maintainer which channels get it.
- Update each chosen channel's files (`updates.xml` and `list.xml`, `updates_next.xml` and
  `list_next.xml`, `updates_test.xml` and `list_test.xml`) last, in one commit, and only after the
  GitHub release and its archives are published. Otherwise sites are offered a download that
  does not exist yet.
