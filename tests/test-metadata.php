<?php
/**
 * The release invariants, asserted from the source and from the stored rows.
 *
 * Everything §7.2 asks of all nineteen plugins now lives in
 * Plugin_Metadata_TestCase. What is left here is what only WP-RelativeDate can
 * say: the version it ships, its class prefix, the breaks its Upgrade Notice
 * has to cover, the twelve translatable strings frozen at their 1.51.1
 * spelling, and the fact that this plugin writes no option row at all.
 *
 * @package WP-RelativeDate
 */

/**
 * WP-RelativeDate's half of the shared metadata contract.
 *
 * @coversNothing
 */
class WP_RelativeDate_Metadata_Test extends Plugin_Metadata_TestCase {

	/**
	 * The version this release ships.
	 *
	 * @return string
	 */
	protected function expected_version() {
		return '2.0.0';
	}

	/**
	 * The prefix every class the plugin declares carries.
	 *
	 * @return string
	 */
	protected function class_prefix() {
		return 'WP_RelativeDate';
	}

	/**
	 * What a site owner updating from the released 1.51.1 would notice.
	 *
	 * The plugin moved from the printing template tags to their getters, which
	 * is why relative dates appear where they never did before and why every
	 * remove_filter() line written against the old names stopped working. The
	 * shortcode's ago_only attribute changed meaning, and a template tag that
	 * had been escaping its own wrapper markup stopped.
	 *
	 * @return string[]
	 */
	protected function upgrade_notice_subjects() {
		return array(
			'WordPress 6.8',
			'PHP 8.2',
			'`the_date()`',
			'`the_time()`',
			'`get_the_date`',
			'`get_the_time`',
			'`the_time`',
			'relative_post_date',
			'ago_only="false"',
			'ago_only="true"',
			'relative_post_the_date()',
			'`wp_relativedate_version`',
		);
	}

	/**
	 * This plugin keeps no version marker row (§2.1).
	 *
	 * Four template-tag filters and two shortcodes, with no settings, no schema
	 * and no migration -- so there is nothing for a marker to mark.
	 *
	 * @return bool
	 */
	protected function has_version_row() {
		return false;
	}

	/**
	 * This plugin keeps no settings row either, and so has no sanitiser.
	 *
	 * @return bool
	 */
	protected function has_settings_row() {
		return false;
	}

	/**
	 * The one row uninstall will ever find.
	 *
	 * Nothing writes it now. An early build of the unreleased 2.0.0 did write
	 * wp_relativedate_version, so uninstall.php is the only thing that will
	 * ever take it off a site that ran that build -- and the shared uninstall
	 * test needs a row to exist before it can prove one was removed.
	 *
	 * @return void
	 */
	protected function seed_option_rows() {
		update_option( 'wp_relativedate_version', array( 'plugin' => '2.0.0' ) );
	}

	/**
	 * At most five tags: wordpress.org shows five and ignores the rest.
	 */
	public function test_the_readme_lists_at_most_five_tags() {
		preg_match( '/^Tags:\s*(.+?)\s*$/m', $this->readme(), $matches );

		$this->assertNotEmpty( $matches, 'The readme must carry a Tags line.' );
		$this->assertLessThanOrEqual( 5, count( explode( ',', $matches[1] ) ) );
	}

	/**
	 * No Translations section: translate.wordpress.org is the only route in.
	 */
	public function test_the_readme_has_no_translations_section() {
		$this->assertSame( 0, preg_match( '/^### Translations/m', $this->readme() ) );
	}

	/**
	 * The old forums.lesterchan.net is gone, and the rest had drifted to http.
	 *
	 * Code spans are exempt: they document input rather than link anywhere.
	 */
	public function test_no_insecure_or_dead_links_remain() {
		$readme = (string) preg_replace( '/`[^`]*`/', '', $this->readme() );

		$this->assertSame( 0, preg_match( '#http://#', $readme ), 'Every readme link must use https.' );
		$this->assertSame( 0, preg_match( '#http://#', $this->plugin_file() ) );
		$this->assertStringNotContainsString( 'forums.lesterchan.net', $readme );
	}

	/**
	 * The translatable strings, frozen at their 1.51.1 spelling.
	 *
	 * WP-RelativeDate has translations on translate.wordpress.org going back
	 * years, and a msgid is a byte-for-byte lookup key: changing one character,
	 * or letting phpcbf renumber a placeholder from %s to %1$s, silently drops
	 * every translation of that string and falls back to English.
	 *
	 * A restructuring is exactly when that happens, so the whole set is pinned
	 * here rather than trusted. Adding a genuinely new string means adding it
	 * to this list deliberately; changing an existing one should be a decision
	 * about abandoning its translations, not a side effect of a refactor.
	 */
	public function test_no_translatable_string_has_changed_since_1_51_1() {
		$expected = array(
			'%s day ago',
			'%s days ago',
			'%s hour ago',
			'%s hours ago',
			'%s minute ago',
			'%s minutes ago',
			'%s second ago',
			'%s seconds ago',
			'%s week ago',
			'%s weeks ago',
			'Today',
			'Yesterday',
		);

		$code = wp_relativedate_test_source_code();

		preg_match_all( "/(?:__|_n|_x|esc_html__|esc_attr__)\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $singles );
		preg_match_all( "/_n\(\s*'(?:[^'\\\\]|\\\\.)*'\s*,\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $plurals );

		$found = array_unique( array_merge( $singles[1], $plurals[1] ) );
		sort( $found );

		$this->assertSame( $expected, $found );
	}

	/**
	 * Every one of them carries the plugin's own text domain.
	 */
	public function test_every_translation_call_uses_the_plugin_text_domain() {
		preg_match_all( '/(?:__|_n)\((.*?)\);/s', wp_relativedate_test_source_code(), $calls );

		$this->assertNotEmpty( $calls[1], 'The plugin makes at least one translation call.' );

		foreach ( $calls[1] as $arguments ) {
			$this->assertStringContainsString(
				"'wp-relativedate'",
				$arguments,
				"A translation call is missing the text domain: {$arguments}"
			);
		}
	}

	/**
	 * The plugin writes no option row at all, ever.
	 *
	 * Stronger than the two shared opt-out assertions, which each name one row.
	 * WP-RelativeDate keeps no state between requests, so under §2.1 it stores
	 * nothing -- not a settings row, not the version markers, and not some
	 * third row a later change might invent. Booting the plugin and then
	 * finding the table empty is the whole test.
	 */
	public function test_the_plugin_stores_nothing() {
		do_action( 'plugins_loaded' );
		do_action( 'init' );

		$this->assertSame(
			array(),
			$this->stored_option_names(),
			'WP-RelativeDate wrote an option row; it is meant to store nothing at all.'
		);
	}
}
