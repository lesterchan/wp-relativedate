<?php
/**
 * The plugin's two stored rows.
 *
 * WP-RelativeDate has no settings screen and nothing a site owner can change:
 * the relative phrasing is the whole plugin, and the two shortcodes take their
 * arguments inline. wp_relativedate_options is therefore an empty array today.
 * It exists because every plugin in this family stores its settings in exactly
 * one row of exactly that name (§2.1), so a site, a migration or a support
 * question never has to ask which shape this particular plugin chose. The day a
 * setting does arrive it goes in here and nothing around it moves.
 *
 * wp_relativedate_version is the pair of upgrade markers, kept deliberately
 * outside the settings array so that a settings save and an upgrade can never
 * overwrite each other.
 *
 * @package WP-RelativeDate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads, sanitises and upgrades the two option rows.
 */
class WP_RelativeDate_Options {

	/**
	 * Settings row. Autoloaded.
	 */
	const OPTION = 'wp_relativedate_options';

	/**
	 * Upgrade markers row, holding 'plugin' and 'db'. Autoloaded.
	 */
	const VERSION = 'wp_relativedate_version';

	/**
	 * Register the upgrade check.
	 *
	 * Hooked to plugins_loaded rather than to activation: an activation hook
	 * does not run for a plugin that was network-activated before this version,
	 * nor for one dropped into mu-plugins, and the check costs one autoloaded
	 * read once the markers agree.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ) );
	}

	/**
	 * The stored settings, with any key the current version expects filled in.
	 *
	 * @return array
	 */
	public static function get() {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * The shipped settings.
	 *
	 * Empty, and honestly so: there is nothing here for a site owner to set.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array();
	}

	/**
	 * Clean a settings array.
	 *
	 * The one place values are cleaned, whether they arrive from a form or from
	 * the upgrade routine below. It is a function of its input alone -- it never
	 * reaches back into get_option() -- so it cannot resurrect a stale value,
	 * and it cannot store an upgrade marker, which lives in its own row.
	 *
	 * With no settings to clean it returns the defaults, which discards anything
	 * an older version or another plugin may have left in the row.
	 *
	 * @param mixed $input Posted or stored settings.
	 * @return array
	 */
	public static function sanitize( $input ) {
		unset( $input );

		return self::defaults();
	}

	/**
	 * Bring the stored rows up to the running version.
	 *
	 * Both markers are written in one call at the end, so an upgrade that dies
	 * half way never records itself as finished.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$markers = get_option( self::VERSION, array() );

		if ( ! is_array( $markers ) ) {
			$markers = array();
		}

		$plugin = isset( $markers['plugin'] ) ? (string) $markers['plugin'] : '';
		$db     = isset( $markers['db'] ) ? (string) $markers['db'] : '';

		if ( WP_RELATIVEDATE_VERSION === $plugin && WP_RELATIVEDATE_DB_VERSION === $db ) {
			return;
		}

		update_option( self::OPTION, self::sanitize( self::get() ), true );

		update_option(
			self::VERSION,
			array(
				'plugin' => WP_RELATIVEDATE_VERSION,
				'db'     => WP_RELATIVEDATE_DB_VERSION,
			),
			true
		);
	}
}
