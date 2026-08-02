<?php
/**
 * Plugin Name: WP-RelativeDate
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Displays relative date alongside with your post/comments actual date. Like 'Today', 'Yesterday', '2 Days Ago', '2 Weeks Ago', '2 'Seconds Ago', '2 Minutes Ago', '2 Hours Ago'.
 * Version: 2.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Lester 'GaMerZ' Chan
 * Author URI: https://lesterchan.net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-relativedate
 * Domain Path: /languages
 *
 * @package WP-RelativeDate
 */

/*
	Copyright 2026  Lester Chan  (email : lesterchan@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) || exit;

/**
 * WP-RelativeDate version, used to cache-bust the stylesheet.
 *
 * Nothing records the last-run value: this plugin stores nothing at all, so
 * there is no upgrade for it to drive. See STANDARDS.md 2.1.
 */
define( 'WP_RELATIVEDATE_VERSION', '2.0.0' );

/**
 * WP-RelativeDate slug, which is also the text domain.
 */
define( 'WP_RELATIVEDATE_SLUG', 'wp-relativedate' );

/**
 * WP-RelativeDate main file.
 */
define( 'WP_RELATIVEDATE_MAIN_FILE', __FILE__ );

/**
 * WP-RelativeDate directory, with a trailing slash.
 */
define( 'WP_RELATIVEDATE_DIR', plugin_dir_path( __FILE__ ) );

/**
 * WP-RelativeDate URL, with a trailing slash.
 */
define( 'WP_RELATIVEDATE_URL', plugin_dir_url( __FILE__ ) );

require_once WP_RELATIVEDATE_DIR . 'includes/class-wp-relativedate-core.php';
require_once WP_RELATIVEDATE_DIR . 'includes/class-wp-relativedate-context.php';
require_once WP_RELATIVEDATE_DIR . 'includes/template-tags.php';
require_once WP_RELATIVEDATE_DIR . 'includes/class-wp-relativedate.php';

WP_RelativeDate::get_instance();
