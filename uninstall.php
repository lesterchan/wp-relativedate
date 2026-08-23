<?php
/**
 * Uninstall WP-RelativeDate.
 *
 * Runs with the plugin inactive, so nothing here may depend on the plugin's
 * own classes or constants being loaded. The row name is therefore spelled out
 * rather than read from a constant.
 *
 * @package WP-RelativeDate
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete the plugin's options for the current site.
 *
 * WP-RelativeDate stores nothing (STANDARDS.md 2.1): no settings, no tables,
 * and not the version markers either. So on a current install this finds
 * nothing to delete, and that is the point.
 *
 * It is still here for the one case that is not current: an early build of the
 * unreleased 2.0.0 did write wp_relativedate_version, and nothing writes it now,
 * so uninstall is the only thing that will ever take it off a site that ran that
 * build. If the plugin ever does grow a row, it gets deleted here too --
 * tests/test-metadata.php asserts over wp_options with a LIKE rather than naming
 * rows, so a forgotten row fails the suite.
 *
 * @return void
 */
function wp_relativedate_uninstall_site() {
	delete_option( 'wp_relativedate_version' );
}

if ( is_multisite() ) {
	// 'number' => 0 lifts WP_Site_Query's default cap of 100, which would
	// otherwise skip every site past the hundredth while reporting success.
	$wp_relativedate_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $wp_relativedate_site_ids as $wp_relativedate_site_id ) {
		// Inside the loop: switch_to_blog() pushes onto a stack, so restoring once
		// after the loop unwinds it by exactly one.
		switch_to_blog( (int) $wp_relativedate_site_id );

		wp_relativedate_uninstall_site();

		restore_current_blog();
	}
} else {
	wp_relativedate_uninstall_site();
}
