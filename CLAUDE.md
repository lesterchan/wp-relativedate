# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What it is

Four filters and two shortcodes. It rewrites a date WordPress has already
formatted into "Today", "Yesterday", "3 days ago", "5 minutes ago" — the date
itself with the phrase appended in brackets, or the phrase alone. No admin
screen, no settings, no options, no database. `includes/` is under 700 lines and
`WP_RelativeDate_Core` is pure: raw MySQL datetime in, string out, no globals.

## Storage: none

Not even a version marker row. A plugin with no settings and no tables has
nothing to migrate and nothing to stamp. `uninstall.php` still deletes
`wp_relativedate_version` — nothing writes it, and no released version ever did;
it exists to clean up sites that ran an early unreleased 2.0.0 build.

## Traps

* **The four callbacks are global functions, hooked at priority 999 with one
  argument, and none of that is accidental.** `remove_filter( 'get_the_date',
  'relative_post_date', 999 )` is the only way to opt a template out, since
  there is no settings screen — so the names and the priority are public API.
  And **the hooks must not be widened to accept more arguments**: each
  callback's *second* parameter is `$display_ago_only`, so passing core's extra
  arguments would hand a date format string into it and turn "ago only" on for
  every date on the site.
* **`WP_RelativeDate_Context` exists solely to work around that.** It hooks the
  same four filters one priority earlier (998), stashes the object and the
  format, and lets the callbacks read them without any signature moving. Two
  things need it: block themes never set the `$post`/`$comment` global (core's
  Comment Date block passes the comment as an argument), and those blocks call
  the getter twice — once for visible text, once for the `datetime` attribute —
  so `is_machine_format()` is what stops `datetime="Today"`.
* **Reading the context is destructive** (`take_post()` clears as it reads), so
  a stash cannot outlive the filter that set it. `reset()` has no callers in the
  plugin and is kept for the suite; do not delete it as dead code.
* **Core's Post Date block cannot be reached, and this is not a bug to fix.**
  Since WP 6.9 `render_block_core_post_date()` resolves through Block Bindings
  and formats with `wp_date()`, never calling `get_the_date()` and applying no
  filter. Comment dates in a block theme *do* work, because the Comment Date
  block still calls `get_comment_date()`.
* **`relative_post_the_date()` must not escape its own output.** 1.51.1 wrapped
  it in `esc_html()` and every theme passing `<h2>` as `$before` got the literal
  tags printed. It uses `wp_kses_post()`, which is the compromise
  (`includes/template-tags.php`).
* **`to_bool()` uses `FILTER_VALIDATE_BOOLEAN`, not `wp_validate_boolean()`.**
  A plain cast read the string `"false"` as true, which is the spelling the
  README has documented since 1.51; core's helper special-cases only that one
  literal and would still read `"no"` and `"off"` as true.
* `WP_RelativeDate_Core::to_datetime()`'s empty check is load-bearing:
  `date_create_immutable( '' )` returns *the current time*, not `false`, so an
  unset date would read as "Today".
* A date in a different calendar year is deliberately left alone — the
  day-of-year arithmetic cannot span a year, and the real date is more use to a
  reader anyway.

## Tests

`bin/test.sh` runs PHPUnit, `bin/test-multisite.sh` the network pass, and
`bin/test-e2e.sh` the Playwright suite. **Run them rather than trusting a note
about their last result** — CI is the authority, and this file cannot be.

`tests/` splits by surface: `test-post-date.php` / `test-post-time.php` /
`test-comment.php` for the three ladders, `test-timezone.php` for the
`wp_timezone()`/`current_datetime()` pairing, `test-backcompat.php` for the
function names and signatures that must not move. `tests/e2e/relativedate.spec.js`
is the only place the block-theme behaviour above is actually observed.

**A time-relative fixture asserting a calendar concept is a bug waiting for the
clock.** The e2e "yesterday" fixture was `Date.now() - 26 hours`, which lands two
calendar days back when the suite runs between midnight and 02:00 — CI ran at
01:44 UTC and the plugin was correctly saying "2 days ago". Fixtures go through
a `daysAgo()` helper pinned to midday.

## Known discrepancy

The README's 2.0.0 Upgrade Notice ends "The plugin now stores one row,
`wp_relativedate_version`, and deletes it on uninstall." That is untrue —
commit `82157c3` ("Store nothing at all") removed it.
