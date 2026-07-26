<?php
/**
 * Plugin Name: WP-RelativeDate
 * Plugin URI: https://lesterchan.net/portfolio/programming/php/
 * Description: Displays relative date alongside with your post/comments actual date. Like 'Today', 'Yesterday', '2 Days Ago', '2 Weeks Ago', '2 'Seconds Ago', '2 Minutes Ago', '2 Hours Ago'.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
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
	Copyright 2026 Lester Chan  (email : lesterchan@gmail.com)

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
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

defined( 'ABSPATH' ) || exit;

/**
 * WP-RelativeDate version.
 */
define( 'WP_RELATIVEDATE_VERSION', '2.0.0' );

/**
 * WP-RelativeDate main file.
 */
define( 'WP_RELATIVEDATE_MAIN_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-relativedate-core.php';
require_once __DIR__ . '/includes/class-relativedate-context.php';
require_once __DIR__ . '/includes/template-tags.php';
require_once __DIR__ . '/includes/class-relativedate.php';

RelativeDate::get_instance();
