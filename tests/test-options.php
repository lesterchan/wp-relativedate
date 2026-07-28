<?php
/**
 * The two stored rows and the upgrade routine that writes them.
 *
 * WP-RelativeDate has nothing for a site owner to configure, so the settings
 * row is empty. That is the point worth pinning: empty is a deliberate shape,
 * not a row that failed to be created, and the version markers must stay out
 * of it however small it is.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers WP_RelativeDate_Options
 */
class WP_RelativeDate_Options_Test extends WP_RelativeDate_TestCase {

	public function test_the_row_names_are_the_canonical_pair() {
		$this->assertSame( 'wp_relativedate_options', WP_RelativeDate_Options::OPTION );
		$this->assertSame( 'wp_relativedate_version', WP_RelativeDate_Options::VERSION );
	}

	public function test_the_upgrade_check_runs_on_plugins_loaded() {
		$this->assertSame(
			10,
			has_action( 'plugins_loaded', array( 'WP_RelativeDate_Options', 'maybe_upgrade' ) ),
			'The upgrade check has to be registered for the rows ever to appear.'
		);
	}

	public function test_the_shipped_settings_are_empty() {
		$this->assertSame( array(), WP_RelativeDate_Options::defaults() );
	}

	public function test_the_settings_row_exists_and_holds_the_defaults() {
		delete_option( WP_RelativeDate_Options::OPTION );
		delete_option( WP_RelativeDate_Options::VERSION );

		WP_RelativeDate_Options::maybe_upgrade();

		$this->assertSame( array(), get_option( WP_RelativeDate_Options::OPTION ) );
	}

	public function test_both_markers_are_written_together() {
		delete_option( WP_RelativeDate_Options::VERSION );

		WP_RelativeDate_Options::maybe_upgrade();

		$this->assertSame(
			array(
				'plugin' => WP_RELATIVEDATE_VERSION,
				'db'     => WP_RELATIVEDATE_DB_VERSION,
			),
			get_option( WP_RelativeDate_Options::VERSION )
		);
	}

	public function test_an_older_marker_pair_is_brought_forward() {
		update_option(
			WP_RelativeDate_Options::VERSION,
			array(
				'plugin' => '1.51.1',
				'db'     => '0',
			)
		);

		WP_RelativeDate_Options::maybe_upgrade();

		$markers = get_option( WP_RelativeDate_Options::VERSION );

		$this->assertSame( WP_RELATIVEDATE_VERSION, $markers['plugin'] );
		$this->assertSame( WP_RELATIVEDATE_DB_VERSION, $markers['db'] );
	}

	/**
	 * A run with the markers already current must not touch the rows. The check
	 * happens on every request, so a write here would be a write on every
	 * request.
	 */
	public function test_a_second_run_changes_nothing() {
		WP_RelativeDate_Options::maybe_upgrade();

		update_option( WP_RelativeDate_Options::OPTION, array( 'left_alone' => true ) );

		WP_RelativeDate_Options::maybe_upgrade();

		$this->assertSame(
			array( 'left_alone' => true ),
			get_option( WP_RelativeDate_Options::OPTION ),
			'The markers already agreed, so the upgrade routine had nothing to do.'
		);
	}

	/**
	 * A row someone has replaced with a string, which get_option() will hand
	 * back exactly as stored, must not become an array offset error.
	 */
	public function test_a_corrupt_settings_row_falls_back_to_the_defaults() {
		update_option( WP_RelativeDate_Options::OPTION, 'not an array' );

		$this->assertSame( array(), WP_RelativeDate_Options::get() );
	}

	public function test_a_corrupt_marker_row_is_rewritten_rather_than_trusted() {
		update_option( WP_RelativeDate_Options::VERSION, 'not an array' );

		WP_RelativeDate_Options::maybe_upgrade();

		$this->assertSame(
			array(
				'plugin' => WP_RELATIVEDATE_VERSION,
				'db'     => WP_RELATIVEDATE_DB_VERSION,
			),
			get_option( WP_RelativeDate_Options::VERSION )
		);
	}

	public function test_get_returns_the_stored_settings() {
		update_option( WP_RelativeDate_Options::OPTION, array( 'future_setting' => 'kept' ) );

		$this->assertSame( array( 'future_setting' => 'kept' ), WP_RelativeDate_Options::get() );
	}

	public function test_the_sanitiser_discards_everything_it_is_handed() {
		$this->assertSame( array(), WP_RelativeDate_Options::sanitize( array( 'anything' => 'at all' ) ) );
		$this->assertSame( array(), WP_RelativeDate_Options::sanitize( 'not an array' ) );
	}

	/**
	 * Both rows are autoloaded: they are read on every request that renders a
	 * date, and two extra queries for two tiny rows is the wrong trade.
	 */
	public function test_both_rows_are_autoloaded() {
		delete_option( WP_RelativeDate_Options::OPTION );
		delete_option( WP_RelativeDate_Options::VERSION );

		WP_RelativeDate_Options::maybe_upgrade();

		wp_cache_delete( 'alloptions', 'options' );

		$this->assertArrayHasKey( WP_RelativeDate_Options::OPTION, wp_load_alloptions() );
		$this->assertArrayHasKey( WP_RelativeDate_Options::VERSION, wp_load_alloptions() );
	}
}
