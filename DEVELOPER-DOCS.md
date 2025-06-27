# Developer Documentation

## Hooks

The plugin is further customizable via seven hooks. Such code should ideally be put into a mu-plugin or site-specific plugin (which is beyond the scope of this readme to explain).


### `c2c_years_ago_today-email_cron_time` _(filter)_

The `c2c_years_ago_today-email_cron_time` hook allows you to customize the time of day to email the Years Ago Today email to those who have opted-in to it. By default this is "9:00 am".

#### Arguments:

* **$time** _(string)_: The time of day to email the Years Ago Today email to those who have opted-in to it. Default "9:00 am".

#### Example:

```php
// Send daily Years Ago Today emails at end of day.
add_filter( 'c2c_years_ago_today-email_cron_time', function ( $time ) { return "6:00 pm"; } );
```


### `c2c_years_ago_today-email-if-no-posts` _(filter)_

The `c2c_years_ago_today-email-if-no-posts` filter is used to override whether the daily Years Ago Today email is sent out on days that don't have any posts in prior years. By default this value is false, meaning no email is sent in such circumstances.

#### Arguments:

* **$send** _(boolean)_: Send the daily Years Ago Today email on days that have no prior year posts? Default false.

#### Example:

```php
// Send daily Years Ago Today email even if there aren't any posts posted in prior years.
add_filter( 'c2c_years_ago_today-email-if-no-posts', '__return_true' );
```


### `c2c_years_ago_today-email-body-no-posts` _(filter)_

The `c2c_years_ago_today-email-body-no-posts` filter is used to customize the content of the body of the daily Years Ago Today email when it is sent on days that had no posts in prior years. 

#### Arguments:

* **$text** _(string)_: The content of the email. You can optionally include "%1$s" as a placeholder for the site name and "%2$s" as a placeholder for the date. Default '`No posts were published to the site %1$s on <strong>%2$s</strong> in any past year.`'.

#### Example:

```php
// Define custom email text for daily Years Ago Today email when there are no posts to list.
add_filter( 'c2c_years_ago_today-email-body-no-posts', function ( $text ) { return "Sorry, no posts were made on this day in any prior year."; } );
```


### `c2c_years_ago_today-first_published_year` _(filter)_

The `c2c_years_ago_today-first_published_year` filter allows defining the earliest year to be considered when finding earlier published posts. By default this is false, which causes the plugin to determine the earliest year via a database query. The queried value does get cached, though may not persist depending on your site setup. This filter can be used to prevent the need for the query or to set a year later than the earliest published year (in case you'd prefer not to feature or be reminded of the early years).

#### Arguments:

* **$year** _(string|false)_: The year of the first published post. False indicates the year should be queried for. Default false.

#### Example:

```php
// Set the earliest published post year for Years Ago Today.
add_filter( 'c2c_years_ago_today-first_published_year', function ( $year ) { return '2009'; } );
```


### `c2c_years_ago_today-post_types` _(filter)_

The `c2c_years_ago_today-post_types` filter allows customization of the post types that are considered for inclusion by the plugin. By default, only the 'post' post type is included.

#### Arguments:

* **$post_types** _(string[])_: The included post types. Default `['posts']`.

#### Example:

```php
// Also include the 'book' post type for Years Ago Today consideration.
add_filter( 'c2c_years_ago_today-post_types', function ( $post_types ) { $post_types[] = 'book'; return $post_types; } );
```


### `c2c_years_ago_today-batch_size` _(filter)_

The `c2c_years_ago_today-batch_size` filter allows customization of the number of daily emails sent per batch.

#### Arguments:

* **$batch_size** _(int)_: The batch size. Default 40.

#### Example:

```php
// Include more emails per batch when sending daily Years Ago Today emails.
add_filter( 'c2c_years_ago_today-batch_size', static fn() => 50 );
```


### `c2c_years_ago_today-to_address` _(filter)_

The `c2c_years_ago_today-to_address` filter allows customization of the email address specified for the "To:" field of batched emails.

Note: This email address is only used if multiple users are set to receive the daily email. In instances where only one user is being sent an email (either overall or as the odd person left over after batching), then that user's email address will be directly used as the 'To:' value.

#### Arguments:

* **$to_email** _(string)_: The email address. Default "noreply@{site-domain}".

#### Example:

```php
// Customize the 'To:' address used for batched (aka BCC:) emails sent daily by Years Ago Today.
add_filter( 'c2c_years_ago_today-to_address', static fn() => 'yearsagotoday@example.org' );
```
