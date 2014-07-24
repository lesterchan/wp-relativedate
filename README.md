# WP-RelativeDate
Contributors: GamerZ  
Donate link: http://lesterchan.net/site/donation/  
Tags: date, relative, relativedate, days, ago, weeks, since, hours, seconds, minutes, today, yesterday  
Requires at least: 2.8  
Tested up to: 3.9  
Stable tag: trunk  

Displays relative date alongside with your post/comments actual date.

## Description
Like 'Today', 'Yesterday', '2 Days Ago', '2 Weeks Ago', '2 'Seconds Ago', '2 Minutes Ago', '2 Hours Ago'.

### Build Status
[![Build Status](https://travis-ci.org/lesterchan/wp-relativedate.svg?branch=master)](https://travis-ci.org/lesterchan/wp-relativedate)

### Development
[https://github.com/lesterchan/wp-relativedate](https://github.com/lesterchan/wp-relativedate "https://github.com/lesterchan/wp-relativedate")

### Translations
[http://dev.wp-plugins.org/browser/wp-relativedate/i18n/](http://dev.wp-plugins.org/browser/wp-relativedate/i18n/ "http://dev.wp-plugins.org/browser/wp-relativedate/i18n/")

### Donations
I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appericiate it. If not feel free to use it without any obligations.

## Changelog

### Version 1.50 (01-06-2009)
* NEW: Use _n() Instead Of __ngettext() And _n_noop() Instead Of __ngettext_noop()

### Version 1.40 (12-12-2008)
* FIXED: Pass In Default Values For $d, $before And $after In relative_post_date()

### Version 1.31 (16-07-2008)
* NEW: Works For WordPress 2.6
* NEW: Better Translation Using __ngetext() by <a href="http://hweia.ru/" title="http://hweia.ru/">Anna Ozeritskaya</a>

### Version 1.30 (01-06-2008)
* NEW: Uses /wp-relativedate/ Folder Instead Of /relativedate/
* NEW: Uses wp-relativedate.php Instead Of relativedate.php

### Version 1.20 (01-10-2007)
* New: relative_post_the_date(); Alternative To WordPress the_date()

### Version 1.11 (01-06-2007)
* FIXED: Post Of The Same Date But Different Year Still Will Not Display Relative Date

### Version 1.10 (01-02-2007)
* NEW: Works For WordPress 2.1 Only
* NEW: Localization WP-RelativeDate

### Version 1.00 (01-03-2006)
* NEW: Initial Release

## Installation

1. Open `wp-content/plugins` Folder
2. Put: `Folder: wp-relativedate`
3. Activate `WP-RelativeDate` Plugin

### General Usage
You need not do anything. WP-RelativeDate will automatically modify your post/comment date or time display. No text will be added if the post or comment is more than a month old.

It will add the following text accordingly:
* Post/Comment Date
 * Today
 * Yesterday
 * X days ago
 * X weeks ago
* Post/Comment Time
 * X seconds ago
 * X minutes ago
 * X hours ago

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

## Upgrading

1. Deactivate `WP-RelativeDate` Plugin
2. Open `wp-content/plugins` Folder
3. Put/Overwrite: `Folder: wp-relativedate`
4. Activate `WP-RelativeDate` Plugin

## Upgrade Notice

N/A

## Screenshots

1. Comment - Seconds Ago
2. Post - Days Ago
3. Post - Today
4. Post - Weeks Ago
5. Post - Yesterday

## Frequently Asked Questions

### Display Relative Date in every posts
* If you want to display Relative Date in every posts, use `relative_post_the_date()` instead of `the_date()` in your theme.
