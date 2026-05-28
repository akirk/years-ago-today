# Changelog

## _(in-progress)_
* New: Add front-end block and `[years-ago-today]` shortcode to display the same listing as the admin dashboard widget
    * New: Add `get_widget_markup()` and `get_widget_inner_markup()` to centralize listing markup generation
    * New: Register PHP-only block `coffee2code/years-ago-today` on WordPress 7.0+
    * New: Add public CSS for block and shortcode output
* New: Send full HTML of posts in the email alongside plain text version. Fixes #5. Props akirk.
    * New: Extract HTML email generation logic out from `get_email_body()` into new `get_html_email()`
    * New: Add `get_html_email_template()` to return the template used for HTML emails, with placeholders for subject, body, and footer
    * New: Add `get_resized_content()` to rewrite image tags in post content to use smaller-sized versions
    * New: Add `get_html_email_image_size()` to get image size used in HTML emails
    * New: Add filter 'c2c_years_ago_today-html_email_image_size' to customize image size used in HTML emails
    * Change: Move amending of footer into `get_email_body()` and add optional arg to disable that behavior
* New: Add email previewer to preview variations of both plain text and HTML emails
* New: Add support for including additional post types via new 'c2c_years_ago_today-post_types' filter
* Change: Add date to email subject
* Change: Add optional `$include_year` argument to `get_formatted_date_string()`, allowing for full date with year
* Fix: Restore proper registration of activation/deactivation hooks. Fixes #4. Props akirk.
* Fix: Ensure timing of emails is consistent with site's timezone rather than the server's
* Change: Improve performance of `get_posts()`
    * Change: Cache post IDs and only query for IDs when more is needed
    * New: Add `clear_transient_on_publish()`, `get_post_ids_cache_key()`, and `query_post_ids()`
    * New: Hook 'save_post' to clear transient when backporting a post to the current day in any past year
* Change: Improve the sending of emails
    * Change: Group users together (via BCC:) and email them in batches rather than individually when sending to multiple users
    * New: Add `get_bcc_batch_size()` to get number of people to email per BCC: batch
    * New: Add filter 'c2c_years_ago_today-batch_size' to customize the number of people to email per BCC: batch
    * New: Add `get_bcc_to_email_address()` to get the 'To:' email address used for Bcc-batched emails
    * New: Add filter 'c2c_years_ago_today-to_address' to customize the 'To:' email address used for Bcc-batched emails
    * New: Extract email handling logic out from `cron_email()` into new `send_email_of_type()`
    * New: Extract email address grouping logic out from `cron_email()` into new `get_users_to_email_grouped_by_content_type()`
* Change: Output full month instead of 3-letter abbreviations
* Change: Revamp dashboard output, more semantically putting each year within `section` tags
* Change: Align `get_first_published_year()` query with `get_posts()`
* Change: Incorporate a hash of post types into the first published year cache key
* Fix: Properly clear the first published year cache entry on deactivation by using the correct cache key
* Change: Ensure list bullets are consistently visible in all dark mode schemes
* New: Add descriptive help text to profile setting for accessibility and clarity
* New: Enqueue JS on profile pages
* New: Enqueue separate admin CSS file rather than outputting into `head`
* Change: Also enqueue CSS on profile pages
* Change: Change caching to support multisite usage
* Hardening: Prevent translated and generated strings from containing unintended markup
* Hardening: Prevent PHP warnings from any potential malicious form submission field value
* Change: Update plugin description
* Change: Use consistent phrasing across UI and documentation
* Change: Rename `add_user_email_footer()` to `get_email_footer()` and remove arguments and just return the footer based on a format ('text' or 'html')
* Change: Add context to translation of dashboard widget title to differentiate it from plugin's name
* Change: Allow year headings in plain text emails to be translated
* Change: Remove `is_admin()` guard from `init()`, which isn't strictly necessary and impedes unit tests
* Change: Update uses of `current_time()` and `mysql2date()` to more modern `wp_date()`
* New: Add `get_optin_label()` to centralize the optin label since it is used in two places
* Change: Discontinue use of HEREDOC syntax
* Change: Discontinue unnecessary explicit loading of textdomain
* Change: Add missing translator comments for strings with one placeholder
* Change: Prevent unwarranted PHPCS complaints
* Fix: Fix translator string that referenced an extraneous placeholder
* Change: Note compatibility through WP 7.0+
* Change: Drop compatibilities with versions of WP older than 5.6
* Change: Note compatibility through PHP 8.5+
* Change: Update copyright date (2026)
* New: Add `.gitattributes` file to exclude files from GitHub packaging
* Unit tests:
    * New: Add multisite tests
    * New: Define `WP_RUNNING_TESTS` constant
    * Change: Explicitly define return type for overridden methods
    * Change: Use `assertStringContainsString()` instead of deprecated `assertContains()`
    * New: Add unit tests for plugin activation/deactivation
    * New: Add unit tests for `cron_email()`
    * Change: Update uses of `current_time()` to more modern `wp_date()`
    * Change: Remove use of deprecated (in PHP 8.5, noop'd in 8.1) `setAccessible()`
    * New: Add `get_formatted_date()` helper

## 1.6 _(2024-08-09)_
* Fix: Convert use of deprecated string interpolation syntax to prevent notice under PHP8.2. Props Simounet.
* Change: Note compatibility through WP 6.6+
* Change: Update copyright date (2024)
* New: Add `.gitignore` file
* Change: Remove development and testing-related files from release packaging
* Unit tests:
    * Hardening: Prevent direct web access to `bootstrap.php`
    * Allow tests to run against current versions of WordPress
    * New: Add `composer.json` for PHPUnit Polyfill dependency
    * Change: In bootstrap, store path to plugin file in a constant
    * Change: In bootstrap, add backcompat for PHPUnit pre-v6.0

## 1.5.1 _(2023-06-11)_
* Change: Note compatibility through WP 6.3+
* Change: Update copyright date (2023)
* New: Add link to DEVELOPER-DOCS.md in README.md

## 1.5 _(2022-10-12)_

### Highlights:

This minor release improves cron handling, removes the little HTML used from emails, prevents a PHP8 warning, adds DEVELOPER-DOCS.md, notes compatibility through WP 6.0+, and reorganizes unit test files.

### Details:

* Fix: Make `__wakeup()` public to prevent PHP8 warnings. Props Simounet, koolinus.
* Change: Move cron initialization into new `cron_init()`
* Change: Register cron task earlier. Props Simounet.
* Change: Remove HTML from email. Props Simounet.
* New: Add DEVELOPER-DOCS.md and move hooks documentation into it
* Change: Pare plugin tags down to 5
* Change: Note compatibility through WP 6.0+
* Change: Update copyright date (2022)
* Unit tests:
    * Change: Restructure unit test directories
        * Change: Move `phpunit/` into `tests/phpunit/`
        * Change: Move `phpunit/bin/` into `tests/`
    * Change: Remove 'test-' prefix from unit test file
    * Change: In bootstrap, store path to plugin file constant

## 1.4 _(2021-04-19)_

### Highlights:

This minor release adds the ability for admins to edit the value of the setting for other users and also notes compatibility through WP 5.7+.

### Details:

* New: Permit admins to see and edit the value of the setting for other users
* New: Add HTML5 compliance by omitting `type` attribute for `style` tag
* Change: Note compatibility through WP 5.7+
* Change: Drop compatibility for versions of WP older than 4.9
* Change: Update copyright date (2021)

## 1.3.4 _(2020-09-14)_
* Change: Remove some excess space characters within output markup
* Change: Restructure unit test file structure
    * New: Create new subdirectory `phpunit/` to house all files related to unit testing
    * Change: Move `bin/` to `phpunit/bin/`
    * Change: Move `tests/bootstrap.php` to `phpunit/`
    * Change: Move `tests/` to `phpunit/tests/`
    * Change: Rename `phpunit.xml` to `phpunit.xml.dist` per best practices
* Change: Note compatibility through WP 5.5+
* New: Unit tests: Add tests for `add_daily_email_optin_checkbox()`, `dashboard_setup()`

## 1.3.3 _(2020-06-05)_
* New: Add TODO.md and move existing TODO list from top of main plugin file into it (and added to it)
* Change: Note compatibility through WP 5.4+
* Change: Update links to coffee2code.com to be HTTPS
* Fix: Use full path to CHANGELOG.md in the Changelog section of readme.txt
* Unit tests:
    * New: Add test and data provider for hooking actions and filters
    * New: Add tests for configuration defaults such as option name, cron name, etc
    * New: Add tests for `add_admin_css()`, `admin_css()`
    * Change: Use `expectOutputRegex()` instead of doing explicit output buffering
    * Change: Remove unnecessary unregistering of hooks and thusly delete `tearDown()`
    * Change: Use HTTPS for link to WP SVN repository in bin script for configuring unit tests

## 1.3.2 _(2019-11-16)_
* Fix: Fix incorrect date query handling
* Change: Note compatibility through WP 5.3+
* Change: Update copyright date (2020)

## 1.3.1 _(2019-06-10)_
* Fix: Fix incorect handling of timestamp argument when supplied to `get_formatted_date_string()` (bug not triggered within plugin itself)
* Change: Update unit test install script and bootstrap to use latest WP unit test repo
* Change: Note compatibility through WP 5.2+
* Change: Add link to CHANGELOG.md in README.md
* Change: Remove 'Domain Path' plugin header
* Fix: Correct typo in GitHub URL

## 1.3 _(2019-03-17)_
* New: Add and use `get_formatted_date_string()` to format the date string used when referring to a given day
* New: Add unit tests for untested hooks
* New: Add CHANGELOG.md file and move all but most recent changelog entries into it
* New: Add "Hooks" section to readme.txt to document hooks provided by the plugin
* New: Add inline documentation for all hooks
* Change: Initialize plugin on 'plugins_loaded' action instead of on load
* Change: Merge `do_init()` into `init()`
* Change: Do placeholder substitutions of site name and day strings after `c2c_years_ago_today-email-body-no-posts` filter is run, so those using the hook have those values available
* Change: Reformat conditional logic handling in `get_email_body()` for improved readability
* Change: Allow date strings to be translated in a plugin-specific way
* CHange: Cast return value of `c2c_years_ago_today-email-if-no-posts` filter as boolean
* Change: Split paragraph in README.md's "Support" section into two
* Change: Note compatibility through WP 5.1+
* Change: Update copyright date (2019)
* Change: Update License URI to be HTTPS

## 1.2.2 _(2017-11-08)_
* New: Add README.md
* Change: Add GitHub link to readme
* Change: Note compatibility through WP 4.9+
* Change: Update copyright date (2018)
* Change: Minor whitespace tweaks in unit test bootstrap

## 1.2.1 _(2017-05-09)_
* Fix: Properly constrain CSS `li` styling to apply only to plugin's dashboard widget and not any other dashboard widgets

## 1.2 _(2017-02-20)_
* New: Add footer to daily emails to provide context about what the email is, why it is being sent, and where to go to discontinue it
* Change: Make prefatory post listing text (in widget and email) more informative
    * Include month and day of the month instead of saying "this day"
    * Include count of the number of posts being listed
    * Use separate singular and plural strings
* Change: Use built-in WP date query syntax for finding older posts
    * Delete `add_year_clause_to_query()`
    * Move some of the date handling code from `add_year_clause_to_query()` into `get_posts()` for use in date_query
* Change: Split out functionality from `cron_email()` into single-purpose functions
    * Add `get_email_subject()` for getting email subject
    * Add `get_email_body()` for getting email body
    * Bail if either return empty string
* Change: Prevent object instantiation
    * Add private `__construct()`
    * Add private `__wakeup()`
* Change: Update unit test bootstrap
    * Default `WP_TESTS_DIR` to `/tmp/wordpress-tests-lib` rather than erroring out if not defined via environment variable
    * Enable more error output for unit tests
* Change: Note compatibility through WP 4.7+
* Change: Remove support for WordPress older than WP 4.6 (should still work for earlier versions back to WP 4.1)
* Change: Minor inline code documnetation tweaks (fix typos, spacing)
* Change: Update copyright date (2017)
* New: Add LICENSE file
* Change: Update screenshots

## 1.1 _(2016-01-21)_
* Bugfix: Fix for bug when posts across two days were returned for today by using site's time and not GMT.
* New: Add filter `c2c_years_ago_ago-email_cron_time`.
* Change: Change incorrectly named filter from `c2c_years_ago_ago-first_published_year` to `c2c_years_ago_today-first_published_year`.
* Change: Add support for language packs:
    * Don't load textdomain from file.
    * Remove .pot file and /lang subdirectory.
    * Fix an incorrectly defined textdomain.
* Change: Note compatibility through WP 4.4+.
* Change: Explicitly declare methods in unit tests as public.
* Change: Update copyright date (2016).
* New: Create empty index.php to prevent files from being listed if web server has enabled directory listings.

## 1.0.1 _(2015-08-03)_
* Bugfix: Change default value for `c2c_years_ago_today-email-if-no-posts` filter from true to false. The original intent was by default not to send the email on days without past posts.
* Bugfix: Load language files from the 'lang' sub-directory.
* Change: Use `dirname(__FILE__)` instead of `__DIR__` since the latter is only available on PHP 5.3+
* Update: Note compatibility through WP 4.3+

## 1.0
* Initial public release
