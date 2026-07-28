<?php
/**
 * The relative_post_date() callback, on the 'get_the_date' filter.
 *
 * These lock the rendered strings the plugin has produced since 1.20. The
 * refactor in 2.0.0 is only allowed to change the code behind them.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers ::relative_post_date
 */
class Test_RelativeDate_Post_Date extends WP_RelativeDate_TestCase {

	/**
	 * Skip a fixture whose date falls in a different calendar year to today.
	 *
	 * The plugin deliberately bails out to the plain date across a year
	 * boundary, so "10 days ago" has no relative form during the first days of
	 * January. There is no way to move the suite's notion of "now" by months --
	 * current_time() is read inside the plugin -- so the honest thing is to
	 * skip the handful of runs that land in that window and to cover the
	 * boundary rule itself in its own test below.
	 *
	 * @param int $seconds_ago Fixture offset.
	 * @return void
	 */
	protected function skip_if_crosses_year( $seconds_ago ) {
		if ( $this->ago( $seconds_ago )->format( 'Y' ) !== $this->now()->format( 'Y' ) ) {
			$this->markTestSkipped( 'Fixture crosses a year boundary; see test_a_post_from_a_previous_year_keeps_its_plain_date.' );
		}
	}

	public function test_a_post_from_today_reads_today() {
		$this->make_post( 0 );

		$this->assertSame( 'Today', relative_post_date( $this->post_date_text() ) );
	}

	public function test_a_post_from_yesterday_reads_yesterday() {
		$this->skip_if_crosses_year( DAY_IN_SECONDS );
		$this->make_post( DAY_IN_SECONDS );

		$this->assertSame( 'Yesterday', relative_post_date( $this->post_date_text() ) );
	}

	public function test_a_post_from_this_week_appends_the_day_count() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (3 days ago)',
			relative_post_date( $this->post_date_text() )
		);
	}

	public function test_the_day_count_can_be_rendered_on_its_own() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			'3 days ago',
			relative_post_date( $this->post_date_text(), '', '', '', true )
		);
	}

	/**
	 * Seven days is the first value that rounds into weeks: the day branch is
	 * "< 7", so day seven itself is ceil( 7 / 7 ) = one week.
	 */
	public function test_seven_days_rounds_up_into_one_week() {
		$this->skip_if_crosses_year( 7 * DAY_IN_SECONDS );
		$this->make_post( 7 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (1 week ago)',
			relative_post_date( $this->post_date_text() )
		);
	}

	public function test_ten_days_rounds_up_to_two_weeks() {
		$this->skip_if_crosses_year( 10 * DAY_IN_SECONDS );
		$this->make_post( 10 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (2 weeks ago)',
			relative_post_date( $this->post_date_text() )
		);
	}

	public function test_a_post_older_than_a_month_keeps_its_plain_date() {
		$this->skip_if_crosses_year( 31 * DAY_IN_SECONDS );
		$this->make_post( 31 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text(),
			relative_post_date( $this->post_date_text() )
		);
	}

	public function test_a_post_from_a_previous_year_keeps_its_plain_date() {
		$this->make_post( 400 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text(),
			relative_post_date( $this->post_date_text() )
		);
	}

	public function test_before_and_after_wrap_the_result() {
		$this->make_post( 0 );

		$this->assertSame(
			'<b>Today</b>',
			relative_post_date( $this->post_date_text(), '', '<b>', '</b>' )
		);
	}

	public function test_before_and_after_wrap_a_previous_year_date_too() {
		$this->make_post( 400 * DAY_IN_SECONDS );

		$this->assertSame(
			'<b>' . $this->post_date_text() . '</b>',
			relative_post_date( $this->post_date_text(), '', '<b>', '</b>' )
		);
	}

	/**
	 * Core's the_date() passes an empty string for the second and later posts
	 * sharing a day, and expects nothing back. Before 2.0.0 this fell out of a
	 * comparison against an undefined $previous_day global that was always
	 * null; the behaviour it produced is the contract, not the mechanism.
	 */
	public function test_an_empty_date_renders_nothing_and_leaks_no_wrapper() {
		$this->make_post( 0 );

		$this->assertSame(
			'',
			(string) relative_post_date( '', '', '<b>', '</b>' )
		);
	}

	/**
	 * Core's get_the_date() is what every default theme since Twenty Nineteen
	 * builds its post meta from, and before 2.0.0 the plugin did not touch it.
	 */
	public function test_get_the_date_returns_the_relative_form() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$post = $this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (3 days ago)',
			get_the_date( '', $post )
		);
	}

	/**
	 * Core's the_date() builds its output by calling get_the_date(), so the
	 * relative form has to arrive exactly once. Two registrations would render
	 * "July 24, 2026 (3 days ago) (3 days ago)".
	 */
	public function test_the_date_applies_the_relative_form_exactly_once() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		// is_new_day() gates the_date(); without this it returns an empty string.
		$GLOBALS['currentday']  = mysql2date( 'd.m.y', $this->post->post_date, false );
		$GLOBALS['previousday'] = '';

		ob_start();
		the_date( '', '<b>', '</b>' );
		$out = ob_get_clean();

		$this->assertSame( '<b>' . $this->post_date_text() . ' (3 days ago)</b>', $out );
	}

	/**
	 * Core's the_date() prints nothing for the second and later posts sharing a
	 * day, and never reaches the getter to do it.
	 */
	public function test_the_date_still_prints_nothing_for_a_repeated_day() {
		$this->make_post( 0 );

		$GLOBALS['currentday']  = 'same';
		$GLOBALS['previousday'] = 'same';

		ob_start();
		the_date( '', '<b>', '</b>' );

		$this->assertSame( '', ob_get_clean() );
	}

	/**
	 * Core's Post Date block calls get_the_date() twice -- once for the visible
	 * text and once for the <time datetime> attribute. Relativising the second
	 * would turn a valid ISO 8601 timestamp into the word "Today".
	 */
	public function test_machine_readable_formats_are_left_alone() {
		$post = $this->make_post( 0 );

		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
			get_the_date( 'c', $post )
		);
		$this->assertMatchesRegularExpression( '/^\d+$/', get_the_date( 'U', $post ) );
		$this->assertMatchesRegularExpression( '/^\d+$/', get_the_time( 'U', $post ) );
	}

	public function test_a_future_post_keeps_its_plain_date() {
		$this->make_post( -2 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text(),
			relative_post_date( $this->post_date_text() )
		);
	}

	/**
	 * Core's get_the_date() is filterable from places with no post in scope,
	 * and a warning-as-exception there takes the whole page down under WP_DEBUG.
	 */
	public function test_no_post_in_scope_returns_the_input_untouched() {
		unset( $GLOBALS['post'] );

		$this->assertSame( 'July 27, 2026', relative_post_date( 'July 27, 2026' ) );
	}
}
