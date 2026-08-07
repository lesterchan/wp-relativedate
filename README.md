# WP-RelativeDate
Contributors: GamerZ  
Donate link: https://lesterchan.net/site/donation/  
Tags: date, time, relative, ago, comments  
Requires at least: 6.8  
Tested up to: 7.0  
Stable tag: 2.0.0  
Requires PHP: 8.2  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays relative date alongside with your post/comments actual date.

## Description
Like 'Today', 'Yesterday', '2 Days Ago', '2 Weeks Ago', '2 'Seconds Ago', '2 Minutes Ago', '2 Hours Ago'.

There is nothing to configure and no settings screen. WP-RelativeDate rewrites your post and comment dates as soon as it is activated, and leaves anything more than a month old, or from a previous year, exactly as your theme printed it.

### Features
* Post/Comment Date
 * Today
 * Yesterday
 * X days ago
 * X weeks ago
* Post/Comment Time
 * X seconds ago
 * X minutes ago
 * X hours ago

### Donations
I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.

## Usage
You need not do anything. WP-RelativeDate will automatically modify your post/comment date or time display. No text will be added if the post or comment is more than a month old.

### Examples
* Post/Comment Date
 * Today
 * Yesterday
 * 10th January 2007 (2 days ago)
 * 25th January 2007 (2 weeks ago)
* Post/Comment Time
 * 21:10 (2 seconds ago)
 * 21:15 (5 minutes ago)
 * 22:15 (2 hours ago)

### Shortcodes
* `[relativedate]`
 * `[relativedate date_format="jS F Y" ago_only="false"]`
* `[relativetime]`
 * `[relativetime time_format="H:i" ago_only="false"]`

`ago_only` drops the date or time and leaves only the relative phrase. It accepts `true`/`false`, `1`/`0`, `yes`/`no` and `on`/`off`.

### Template Tags
* `relative_post_the_date( $format, $before, $after, $ago_only, $display )` — a drop-in replacement for `the_date()`. `$before` and `$after` carry markup, exactly as they do in `the_date()`.

### Turning It Off For One Template
The plugin has no settings screen. To leave a template's dates alone, remove the filter before the loop:

`remove_filter( 'get_the_date', 'relative_post_date', 999 );`

The four callbacks are `relative_post_date` on `get_the_date`, `relative_post_time` on `get_the_time`, `relative_comment_date` on `get_comment_date` and `relative_comment_time` on `get_comment_time`, all at priority 999.

Removing the `get_the_date` filter also covers `the_date()`, which builds its output by calling `get_the_date()`. The same goes for `the_time()`, `comment_date()` and `comment_time()`.

## Frequently Asked Questions

### Display Relative Date in every posts
* If you want to display Relative Date in every posts, use `relative_post_the_date()` instead of `the_date()` in your theme.

### Why did my theme's `<h2>` around the date start showing up as text?
* That was a bug in 1.51.1, fixed in 2.0.0. `relative_post_the_date()` was escaping its own `$before` and `$after` arguments. Upgrading is the fix; no theme change is needed.

### My post dates are still plain on a block theme
* This affects WordPress 6.9 and later only. From 6.9 the core Post Date block resolves the date through Block Bindings and formats it itself, without calling `get_the_date()` or offering a filter, so no plugin can change what it prints. On WordPress 6.8 that block still calls `get_the_date()` and relative post dates work on block themes too.
* Comment dates are unaffected on every supported version — the Comment Date block still calls `get_comment_date()`. Post dates also work normally on classic themes and anywhere a template calls `the_date()`, `get_the_date()`, `the_time()` or `get_the_time()`.
* To get a relative post date in a block template, use the `[relativedate]` shortcode or call `relative_post_the_date()` from a block pattern or template part.

### I used `ago_only="false"` and now the date is back
* `ago_only="false"` has always been documented as meaning false, but until 2.0.0 the plugin read it as true. If you were relying on that, use `ago_only="true"` to keep the ago-only output.

### Does the plugin store anything in my database?
* One row, and a tiny one. There is nothing to configure, so the plugin stores no settings at all - only `wp_relativedate_version`, which records the version last run so that an upgrade knows what it is upgrading from. Deleting the plugin from the Plugins screen removes it.

## Screenshots

1. Every post date on the front page, written as how long ago it was
2. The `relativedate` and `relativetime` shortcodes, in a post published minutes ago

## Changelog
### 2.0.0
* BREAKING: Requires WordPress 6.8 and PHP 8.2, up from 6.0 and 7.4.
* BREAKING: The post callbacks moved from `the_date`/`the_time` to `get_the_date`/`get_the_time`. If you opted a template out with `remove_filter( 'the_date', 'relative_post_date', 999 )`, name the getter instead.
* NEW: Restructured into `includes/`, with the date and time calculations in a `WP_RelativeDate_Core` class.
* NEW: Added the `wp_relativedate_version` row, and an `uninstall.php` that deletes it on a single site and across a network.
* NEW: Added a PHPUnit test suite and GitHub Actions CI.
* CHANGED: The two shortcode callbacks are now methods on the `WP_RelativeDate` class. Shortcodes are removed by tag, so `remove_shortcode( 'relativedate' )` is unaffected.
* FIXED: Post dates now work on any theme using `get_the_date()` or `get_the_time()`, which is every classic theme since Twenty Nineteen. The plugin previously only hooked `the_date()` and `the_time()`, so it appeared to do nothing on most themes.
* FIXED: Comment dates now work on block themes. Core's Comment Date block passes the comment as an argument and never sets the global the plugin used to read.
* FIXED: `relative_post_the_date()` no longer escapes `$before` and `$after`, which had been rendering `<h2>` and friends as literal text since 1.51.1.
* FIXED: `ago_only="false"` now means false. The documented spelling had always been read as true.
* FIXED: Post and comment dates no longer raise "Attempt to read property on null" when there is no post or comment in scope, which a recent-comments widget could trigger.
* FIXED: Content dated ahead of the server clock no longer renders "(-300 seconds ago)".
* FIXED: Removed `load_plugin_textdomain()`, which trips `_doing_it_wrong` on WordPress 6.7 and later.

## Upgrade Notice

### 2.0.0

Requires WordPress 6.8 and PHP 8.2.

**Relative dates will start appearing where they never did before.** Since 1.20 the plugin hooked `the_date()` and `the_time()`, which almost no modern theme calls — every default theme since Twenty Nineteen builds its post meta from `get_the_date()` instead. The plugin now hooks the getters, so a theme that showed plain dates for years will show "Today" and "3 days ago" the moment you update. To switch it off for one template, see "Turning It Off For One Template" above.

**If you had turned the plugin off for a template, the line has changed.** `remove_filter( 'the_date', 'relative_post_date', 999 )` no longer removes anything; use `get_the_date`, and likewise `get_the_time` in place of `the_time`. The two comment filters are unchanged.

**`ago_only="false"` now does what it says.** Until 2.0.0 the shortcode read the string "false" as true, so `[relativedate ago_only="false"]` printed only "3 days ago" instead of the date. Change those shortcodes to `ago_only="true"` if you were relying on it.

**Markup passed to `relative_post_the_date()` reaches the page again.** Since 1.51.1 the tag escaped its own `$before` and `$after` arguments, so a theme passing `<h2>` got the literal characters printed.

The plugin now stores one row, `wp_relativedate_version`, and deletes it on uninstall. There is still no settings screen, and no template tag or shortcode changed name.
