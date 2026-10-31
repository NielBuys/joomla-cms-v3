# Fix implicitly nullable parameters (PHP 8.4 deprecation)

**Status:** planned. Independent of the other plans; the fix applies to `3.10-dev` and `3.12-dev` alike.

## Why

PHP 8.4 deprecates parameters that have a class type and a `null` default but no `?`, such as
`Form $form = null`. PHP reports the deprecation when it compiles the file, so it appears every time
one of these classes is loaded, on normal page requests too, whenever `E_DEPRECATED` is reported or
logged. A future PHP version may turn it into an error.

A scan on 2026-10-31 found **234 parameters in 102 files**. The most visible are the form rules: every
form validation loads `FormRule` and its subclasses.

## The fix

Make the type explicitly nullable, and change nothing else:

```php
// Before
public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null, Form $form = null)

// After
public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
```

`?Type $x = null` means exactly what `Type $x = null` meant, so the change is backward compatible:

- Callers are unaffected.
- Third-party subclasses that override these methods with the old signature stay compatible, because
  both forms declare the same parameter type.
- Nullable types need PHP 7.1, which both branches already require.

## Workflow

1. Branch from `3.10-dev` as `fix/implicit-nullable`.
2. Find every occurrence by linting with PHP 8.4 or later, which reports them without running the code:

   ```bash
   git ls-files '*.php' | grep -v '^libraries/vendor/\|^tests/\|^build/' | xargs -n 1 php -d error_reporting=-1 -l 2>&1 | grep 'Implicitly marking'
   ```

3. Add the `?` to each parameter listed. Do not change defaults, names or docblocks beyond the type.
4. Run the lint again until it reports nothing, and run the unit tests.
5. Open a pull request into `3.10-dev`, then cherry-pick the commit onto `3.12-dev`.

Packages in `libraries/vendor` are out of scope here: fix those in their NielBuys fork.

## Files

Each row lists the method and the parameters that need the `?`.

### CMS library (`libraries/src`)

| File | Method: parameters |
|---|---|
| `libraries/src/Captcha/Google/HttpBridgePostRequestMethod.php` | `__construct()`: `$http` |
| `libraries/src/Component/Exception/MissingComponentException.php` | `__construct()`: `$previous` |
| `libraries/src/Feed/FeedParser.php` | `__construct()`: `$inputFilter` |
| `libraries/src/Form/FormRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/CalendarRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/CaptchaRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/ColorRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/CssIdentifierSubstringRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/EmailRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/EqualsRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/ExistsRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/FilePathRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/NotequalsRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/NumberRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/OptionsRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/PasswordRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/RulesRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/SubFormRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/TelRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/UrlRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Form/Rule/UsernameRule.php` | `test()`: `$form`, `$input` |
| `libraries/src/Http/Transport/SocketTransport.php` | `request()`: `$headers` |
| `libraries/src/Http/Transport/StreamTransport.php` | `request()`: `$headers` |
| `libraries/src/Http/Wrapper/FactoryWrapper.php` | `getHttp()`: `$options` |
| `libraries/src/Installer/InstallerExtension.php` | `__construct()`: `$element` |
| `libraries/src/MVC/Controller/AdminController.php` | `__construct()`: `$factory` |
| `libraries/src/MVC/Controller/FormController.php` | `__construct()`: `$factory` |
| `libraries/src/Router/Exception/RouteNotFoundException.php` | `__construct()`: `$previous` |
| `libraries/src/Table/Language.php` | `_getAssetParentId()`: `$table` |
| `libraries/src/Table/MenuType.php` | `_getAssetParentId()`: `$table` |
| `libraries/src/UCM/UCMBase.php` | `__construct()`: `$type`<br>`mapBase()`: `$type`<br>`store()`: `$table` |
| `libraries/src/UCM/UCMContent.php` | `__construct()`: `$table`, `$type`<br>`delete()`: `$type`<br>`mapData()`: `$type`<br>`save()`: `$type`<br>`store()`: `$table` |

### Components, plugins and installer

| File | Method: parameters |
|---|---|
| `components/com_contact/models/rules/contactemail.php` | `test()`: `$form`, `$input` |
| `components/com_contact/models/rules/contactemailmessage.php` | `test()`: `$form`, `$input` |
| `components/com_contact/models/rules/contactemailsubject.php` | `test()`: `$form`, `$input` |
| `components/com_users/models/rules/loginuniquefield.php` | `test()`: `$form`, `$input` |
| `components/com_users/models/rules/logoutuniquefield.php` | `test()`: `$form`, `$input` |
| `installation/application/web.php` | `loadDocument()`: `$document`<br>`loadSession()`: `$session` |
| `installation/form/rule/username.php` | `test()`: `$form`, `$input` |
| `plugins/privacy/consents/consents.php` | `onPrivacyExportRequest()`: `$user` |
| `plugins/privacy/contact/contact.php` | `onPrivacyExportRequest()`: `$user` |
| `plugins/privacy/content/content.php` | `onPrivacyExportRequest()`: `$user` |
| `plugins/privacy/message/message.php` | `onPrivacyExportRequest()`: `$user` |
| `plugins/privacy/user/user.php` | `onPrivacyCanRemoveData()`: `$user`<br>`onPrivacyExportRequest()`: `$user`<br>`onPrivacyRemoveData()`: `$user` |

### Platform library (`libraries/joomla`)

| File | Method: parameters |
|---|---|
| `libraries/joomla/application/web/router.php` | `__construct()`: `$input` |
| `libraries/joomla/database/exception/executing.php` | `__construct()`: `$previous` |
| `libraries/joomla/database/factory.php` | `getExporter()`: `$db`<br>`getImporter()`: `$db`<br>`getQuery()`: `$db`<br>`setInstance()`: `$instance` |
| `libraries/joomla/facebook/facebook.php` | `__construct()`: `$client`, `$oauth`, `$options` |
| `libraries/joomla/facebook/oauth.php` | `__construct()`: `$client`, `$input`, `$options` |
| `libraries/joomla/facebook/object.php` | `__construct()`: `$client`, `$oauth`, `$options`<br>`createConnection()`: `$headers`<br>`sendRequest()`: `$headers` |
| `libraries/joomla/github/github.php` | `__construct()`: `$client`, `$options` |
| `libraries/joomla/github/http.php` | `__construct()`: `$options`, `$transport` |
| `libraries/joomla/github/object.php` | `__construct()`: `$client`, `$options` |
| `libraries/joomla/github/package/activity/notifications.php` | `getList()`: `$since`<br>`getListRepository()`: `$since`<br>`markRead()`: `$lastReadAt`<br>`markReadRepository()`: `$lastReadAt` |
| `libraries/joomla/github/package/issues.php` | `create()`: `$labels`<br>`edit()`: `$labels`<br>`getList()`: `$since`<br>`getListByRepository()`: `$since` |
| `libraries/joomla/github/package/issues/comments.php` | `getRepositoryList()`: `$since` |
| `libraries/joomla/github/package/repositories/commits.php` | `getList()`: `$since`, `$until` |
| `libraries/joomla/google/auth/oauth2.php` | `__construct()`: `$client`, `$options` |
| `libraries/joomla/google/data.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/adsense.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/calendar.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/picasa.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/picasa/album.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/picasa/photo.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/plus.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/plus/activities.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/plus/comments.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/data/plus/people.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/google/embed.php` | `__construct()`: `$options`, `$uri` |
| `libraries/joomla/google/embed/maps.php` | `__construct()`: `$http`, `$options`, `$uri` |
| `libraries/joomla/google/google.php` | `__construct()`: `$auth`, `$options` |
| `libraries/joomla/linkedin/linkedin.php` | `__construct()`: `$client`, `$oauth`, `$options` |
| `libraries/joomla/linkedin/oauth.php` | `__construct()`: `$client`, `$input`, `$options` |
| `libraries/joomla/linkedin/object.php` | `__construct()`: `$client`, `$oauth`, `$options` |
| `libraries/joomla/mediawiki/categories.php` | `enumerateCategories()`: `$acprop`<br>`getCategories()`: `$clcategories`, `$clprop`, `$clshow`<br>`getCategoryMembers()`: `$cmnamespace`, `$cmprop`, `$cmtype`<br>`getChangeTags()`: `$tgprop` |
| `libraries/joomla/mediawiki/http.php` | `__construct()`: `$options`, `$transport`<br>`get()`: `$headers`<br>`post()`: `$headers` |
| `libraries/joomla/mediawiki/images.php` | `enumerateImages()`: `$aiprop`<br>`getImageInfo()`: `$liprop` |
| `libraries/joomla/mediawiki/links.php` | `enumerateLinks()`: `$alprop`<br>`getLinks()`: `$plnamespace`, `$pltitles` |
| `libraries/joomla/mediawiki/mediawiki.php` | `__construct()`: `$client`, `$options` |
| `libraries/joomla/mediawiki/object.php` | `__construct()`: `$client`, `$options` |
| `libraries/joomla/mediawiki/pages.php` | `getBackLinks()`: `$blnamespace`<br>`getIWBackLinks()`: `$iwblprop`<br>`getPageInfo()`: `$inprop`, `$intoken`<br>`getPageTemplates()`: `$tlnamespace`<br>`getRevisions()`: `$rvprop` |
| `libraries/joomla/mediawiki/search.php` | `openSearch()`: `$namespace`<br>`search()`: `$srinfo`, `$srnamespace`, `$srprop` |
| `libraries/joomla/mediawiki/sites.php` | `getEvents()`: `$leprop`<br>`getProtectedTitles()`: `$ptlevel`, `$ptnamespace`, `$ptprop`<br>`getRecentChanges()`: `$rcnamespace`, `$rcprop`, `$rcshow`, `$rctoken`<br>`getSiteInfo()`: `$siinlanguagecode`, `$siprop` |
| `libraries/joomla/mediawiki/users.php` | `getCurrentUserInfo()`: `$uiprop`<br>`getUserContribs()`: `$ucnamespace`, `$ucprop`, `$ucshow`<br>`getUserInfo()`: `$usprop` |
| `libraries/joomla/oauth1/client.php` | `__construct()`: `$application`, `$client`, `$input`, `$options` |
| `libraries/joomla/oauth2/client.php` | `__construct()`: `$application`, `$http`, `$input`, `$options` |
| `libraries/joomla/openstreetmap/oauth.php` | `__construct()`: `$client`, `$input`, `$options` |
| `libraries/joomla/openstreetmap/object.php` | `__construct()`: `$client`, `$oauth`, `$options` |
| `libraries/joomla/openstreetmap/openstreetmap.php` | `__construct()`: `$client`, `$oauth`, `$options` |
| `libraries/joomla/twitter/oauth.php` | `__construct()`: `$application`, `$client`, `$input`, `$options` |
| `libraries/joomla/twitter/object.php` | `__construct()`: `$client`, `$oauth`, `$options` |
| `libraries/joomla/twitter/twitter.php` | `__construct()`: `$client`, `$oauth`, `$options` |

### FOF (`libraries/fof`)

| File | Method: parameters |
|---|---|
| `libraries/fof/database/query.php` | `__construct()`: `$db` |
| `libraries/fof/encrypt/aes.php` | `__construct()`: `$phpfunc`<br>`isSupported()`: `$phpfunc` |
| `libraries/fof/encrypt/aes/interface.php` | `isSupported()`: `$phpfunc` |
| `libraries/fof/encrypt/aes/mcrypt.php` | `isSupported()`: `$phpfunc` |
| `libraries/fof/encrypt/aes/openssl.php` | `isSupported()`: `$phpfunc` |
| `libraries/fof/form/header.php` | `__construct()`: `$form` |
| `libraries/fof/input/jinput/cli.php` | `__construct()`: `$source` |
| `libraries/fof/input/jinput/cookie.php` | `__construct()`: `$source` |
| `libraries/fof/input/jinput/files.php` | `__construct()`: `$source` |
| `libraries/fof/input/jinput/json.php` | `__construct()`: `$source` |

## Done when

- The lint in step 2 reports nothing on PHP 8.4 or later.
- The unit tests pass.
- The release notes mention the fix.
