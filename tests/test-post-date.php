<?php
/**
 * relative_post_date() -- the 'the_date' filter callback.
 *
 * These lock the rendered strings the plugin has produced since 1.20. The
 * refactor in 2.0.0 is only allowed to change the code behind them.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers ::relative_post_date
 */
class Test_RelativeDate_Post_Date extends RelativeDate_TestCase {

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
	 * the_date() passes an empty string for the second and later posts sharing
	 * a day, and expects nothing back. Before 2.0.0 this fell out of a
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
	 * Core hands the filter $before . $date . $after already concatenated, so
	 * the plugin strips the tags back off and re-wraps. Locking this stops a
	 * refactor from doubling the wrapper.
	 */
	public function test_the_filter_does_not_double_the_wrapper_core_already_applied() {
		$this->make_post( 0 );

		$date = $this->post_date_text();

		$this->assertSame(
			'<b>Today</b>',
			apply_filters( 'the_date', '<b>' . $date . '</b>', '', '<b>', '</b>' )
		);
	}

	public function test_a_future_post_keeps_its_plain_date() {
		$this->make_post( -2 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text(),
			relative_post_date( $this->post_date_text() )
		);
	}

	/**
	 * get_the_date() is filterable from places with no post in scope, and a
	 * warning-as-exception there takes the whole page down under WP_DEBUG.
	 */
	public function test_no_post_in_scope_returns_the_input_untouched() {
		unset( $GLOBALS['post'] );

		$this->assertSame( 'July 27, 2026', relative_post_date( 'July 27, 2026' ) );
	}
}
