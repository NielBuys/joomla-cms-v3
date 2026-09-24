Fork of Joomla! CMS v3
======================

What is this?
---------------------
* This is a Fork of Joomla! CMS v3 installation/upgrade package. The purpose of this fork is to renew the end-of-life Joomla 3 framework. It aims to stay current with security updates, PHP versions, and maintain backward compatibility without disrupting existing codebases.
* Joomla's [Official website](https://www.joomla.org).
* Fork of Joomla! CMS v3 [version history](https://github.com/NielBuys/joomla-cms/releases).

What is Joomla?
---------------------
* [Joomla!](https://www.joomla.org/about-joomla.html) is a **Content Management System** (CMS) which enables you to build websites and powerful online applications.
* It is a simple and powerful web server application which requires a server with PHP and either MySQL, PostgreSQL or SQL Server to run. You can find [full technical requirements here](https://downloads.joomla.org/technical-requirements).
* Joomla! is **free and Open Source software** distributed under the GNU General Public License version 2 or later.

Is Joomla! for you?
---------------------
* Joomla! is [the right solution for most content web projects](https://docs.joomla.org/Special:MyLanguage/Portal:Learn_More).
* View Joomla's [core features here](https://www.joomla.org/core-features.html).
* Try it out for yourself on our [free hosting service](https://launch.joomla.org).

How to find a Joomla! translation?
---------------------
* Repository of [accredited language packs](https://community.joomla.org/translations.html).
* You can also [add languages](https://docs.joomla.org/Special:MyLanguage/J3.x:Setup_a_Multilingual_Site/Installing_New_Language) directly to your website via your Joomla! administration panel.
* Learn how to [setup a Multilingual Joomla! Site](https://docs.joomla.org/Special:MyLanguage/J3.x:Setup_a_Multilingual_Site)

Learn Joomla!
---------------------
* Read ['Getting Started with Joomla!'](https://docs.joomla.org/Special:MyLanguage/J3.x:Getting_Started_with_Joomla!) to learn the basics.
* Before installing, read the ['Beginners' Guide'](https://docs.joomla.org/Special:MyLanguage/Portal:Beginners).

What are the benefits of Joomla?
---------------------
* The functionality of a Joomla! website can be extended by installing extensions that you can create (or download) to suit your needs.
* There are many ready-made extensions that you can download and install.
* Check out the [Joomla! Extensions Directory (JED)](https://extensions.joomla.org).

Is it easy to change the layout display?
---------------------
* The layout is controlled by templates that you can edit.
* There are a lot of ready-made professional templates that you can download.
* Template management information is [available here](https://docs.joomla.org/Special:MyLanguage/Portal:Template_Management).

Ready to install Joomla?
---------------------
* Check the minimum requirements (PHP 8.5 ready)
	PHP minimum v7.1, recommended v8.2 
        (Magic Quotes GPC, MB String Overload = off)
        (Zlib Compression Support, XML Support, INI Parser Support, JSON Support, MB Language = Default, Intl support), 
    MySQL minimum v5.1, recommended v5.5.3 + (InnoDB support required)
    Apache minimum v2.0, recommended v2.4 + (with mod_mysql, mod_xml, and mod_zlib)
* Download the Fork of Joomla! CMS v3 [latest version](https://github.com/NielBuys/joomla-cms-v3/releases)    
* How do you [install Joomla](https://docs.joomla.org/Special:MyLanguage/J3.x:Installing_Joomla)?
* You could start your Joomla! experience by [building your site on a local test server](https://docs.joomla.org/Special:MyLanguage/Installing_Joomla_locally).
When ready, it can be moved to an online hosting account of your choice.

Updates are free!
---------------------
* Always use the [latest version](https://github.com/NielBuys/joomla-cms/releases).
* To update an Official Joomla! v3.10.12 with the Fork of Joomla! CMS v3, [download the latest "Joomla_3.*.*-Stable-Update_Package.zip" version](https://github.com/NielBuys/joomla-cms/releases). Navigate to Components -> Joomla! Update -> Upload & Update. Browse for the downloaded file and click the Upload & Install button. Important: Back up your website before proceeding.

Where can you get support and help?
---------------------
* [The Joomla! Documentation](https://docs.joomla.org/Special:MyLanguage/Main_Page).
* [Frequently Asked Questions](https://docs.joomla.org/Special:MyLanguage/Category:FAQ) (FAQ).
* Find the [information you need](https://docs.joomla.org/Special:MyLanguage/Start_here).
* Find [help and other users](https://www.joomla.org/about-joomla/create-and-share.html).
* Post questions at [our forums](https://forum.joomla.org).
* [Joomla Resources Directory](https://community.joomla.org/service-providers-directory/) (JRD).

Do you already have a Joomla! site that isn't built with Joomla! 3.x?
---------------------
* What's [new in Joomla! 3.x](https://www.joomla.org/3)?
* What's [new in the Fork of Joomla! CMS v3](https://github.com/NielBuys/joomla-cms-v3/releases)?
* What are the [main differences between 2.5 and 3.x](https://docs.joomla.org/Special:MyLanguage/What_are_the_major_differences_between_Joomla!_2.5_and_3.x%3F)?
* How to [migrate from 2.5.x to 3.x](https://docs.joomla.org/Special:MyLanguage/Joomla_2.5_to_3.x_Step_by_Step_Migration).
* How to [migrate from 1.5.x to 3.x](https://docs.joomla.org/Special:MyLanguage/Joomla_1.5_to_3.x_Step_by_Step_Migration).

Do you want to improve Joomla?
--------------------
* Where to [request a feature](https://github.com/NielBuys/joomla-cms-v3/issues)?
* How do you [report a bug](https://docs.joomla.org/Special:MyLanguage/Filing_bugs_and_issues) on the [Issue Tracker](https://github.com/NielBuys/joomla-cms-v3/issues)?
* Get Involved: Joomla! is community developed software. [Join the community](https://volunteers.joomla.org).
* Documentation for [Developers](https://docs.joomla.org/Special:MyLanguage/Portal:Developers).
* Documentation for [Web designers](https://docs.joomla.org/Special:MyLanguage/Web_designers).

Releasing the fork
--------------------
The build packages the **newest git tag**, not your working tree (`build/build.php` runs
`git describe --tags` on the most recent tagged commit). If the new tag is missing, the build
still succeeds but quietly rebuilds the previous version. So the tag must exist in your local
repository before you build.

1. **Version up.** Bump `PATCH_VERSION` and `RELDATE` in `libraries/src/Version.php` and
   `<version>` in `administrator/manifests/files/joomla.xml`. Commit as "Version Up 3.11.x".
2. **Update server entry.** Add a new `<update>` block for the version to the top of
   `updates.xml` (leave the older blocks in place) and commit it. Do this *before* tagging,
   or the copy of `updates.xml` inside the package will lag one release behind.
3. **Tag and push.** `git tag -a 3.11.x -m "Joomla 3.11.x (fork release)"`, then
   `git push origin 3.10-dev 3.11.x`. If you create the tag on GitHub instead, run
   `git fetch --tags` locally before building.
4. **Build.** From the repository root run `php build/build.php` with **no `--remote`**.
   With no `--remote` it builds the newest tag in release mode, which also writes
   `checksums.txt` and `github_release.txt`. Passing `--remote` skips both files.
   - Inside a Docker container the repository is owned by a different user, so git refuses
     it and the build cannot read the tag. Run
     `git config --global --add safe.directory /var/www/html/joomla-cms` first.
5. **Check.** The last line should read "Build of version 3.11.x complete!" with the
   version you tagged. The 12 archives are in `build/tmp/packages/`; `checksums.txt` and
   `github_release.txt` are one level up in `build/tmp/`.
6. **Publish.** Create the GitHub release on the tag, attach all 12 archives, and paste
   `github_release.txt` into the body (its download links point at this repository's `origin`).

Sites on 3.11.14 or later are offered the release in Joomla Update once `updates.xml` is
pushed to `3.10-dev` and the Update Package is attached to the release. Sites on 3.11.13 or
older have no fork update server yet, so they need one manual install of the Update Package
first. Before running Joomla Update on those sites, check that their `#__update_sites`
entry does not still point at `update.joomla.org`, which would offer stock Joomla files.

Copyright
---------------------
* Copyright (C) 2005 - 2021 Open Source Matters. All rights reserved.
* Distributed under the GNU General Public License version 2 or later
* See [License details](https://docs.joomla.org/Special:MyLanguage/Joomla_Licenses)
