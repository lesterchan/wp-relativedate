<?php
/**
 * relative_comment_date() and relative_comment_time().
 *
 * Both callbacks pass their input through untouched and only ever append to
 * it, so the fixtures below hand them the opaque markers DATE and TIME rather
 * than a formatted date: what is under test is the suffix the plugin adds, and
 * a realistic-looking string would only invite the reader to check it against
 * the fixture's own date, which the plugin never compares it to.
 *
 * They read the comment in scope rather than being handed one. Widening the
 * hook's accepted arguments is not an option: the second parameter of each
 * callback is $display_ago_only, so accepting core's second argument would hand
 * them a date format string and silently turn "ago only" on site-wide.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers ::relative_comment_date
 * @covers ::relative_comment_time
 */
class Test_RelativeDate_Comment extends RelativeDate_TestCase {

	/**
	 * Skip a fixture whose date falls in a different calendar year to today.
	 *
	 * @param int $seconds_ago Fixture offset.
	 * @return void
	 */
	protected function skip_if_crosses_year( $seconds_ago ) {
		if ( $this->ago( $seconds_ago )->format( 'Y' ) !== $this->now()->format( 'Y' ) ) {
			$this->markTestSkipped( 'Fixture crosses a year boundary; the plugin bails to the plain date there by design.' );
		}
	}

	/**
	 * Skip when the core function cannot be handed a comment.
	 *
	 * get_comment_time() only took ( $format, $gmt, $translate ) until WP 6.2,
	 * so on the 6.0 floor the comment can come from nowhere but the global and
	 * there is no argument for the plugin to capture. This asks the function
	 * itself rather than comparing version strings.
	 *
	 * @param string $function Core function name.
	 * @param int    $needed   Parameter count required.
	 * @return void
	 */
	protected function skip_without_comment_argument( $function, $needed ) {
		$reflection = new ReflectionFunction( $function );

		if ( $reflection->getNumberOfParameters() < $needed ) {
			$this->markTestSkipped( "{$function}() does not accept a comment argument on this version of WordPress." );
		}
	}

	public function test_a_comment_from_today_reads_today() {
		$this->make_comment( 0 );

		$this->assertSame( 'Today', relative_comment_date( 'DATE' ) );
	}

	public function test_a_comment_from_yesterday_reads_yesterday() {
		$this->skip_if_crosses_year( DAY_IN_SECONDS );
		$this->make_comment( DAY_IN_SECONDS );

		$this->assertSame( 'Yesterday', relative_comment_date( 'DATE' ) );
	}

	public function test_a_comment_from_this_week_appends_the_day_count() {
		$this->skip_if_crosses_year( 4 * DAY_IN_SECONDS );
		$this->make_comment( 4 * DAY_IN_SECONDS );

		$this->assertSame( 'DATE (4 days ago)', relative_comment_date( 'DATE' ) );
	}

	public function test_the_comment_day_count_can_be_rendered_on_its_own() {
		$this->skip_if_crosses_year( 4 * DAY_IN_SECONDS );
		$this->make_comment( 4 * DAY_IN_SECONDS );

		$this->assertSame( '4 days ago', relative_comment_date( 'DATE', true ) );
	}

	public function test_a_comment_from_weeks_ago_appends_the_week_count() {
		$this->skip_if_crosses_year( 15 * DAY_IN_SECONDS );
		$this->make_comment( 15 * DAY_IN_SECONDS );

		$this->assertSame( 'DATE (3 weeks ago)', relative_comment_date( 'DATE' ) );
	}

	public function test_a_comment_older_than_a_month_keeps_its_plain_date() {
		$this->skip_if_crosses_year( 31 * DAY_IN_SECONDS );
		$this->make_comment( 31 * DAY_IN_SECONDS );

		$this->assertSame( 'DATE', relative_comment_date( 'DATE' ) );
	}

	public function test_a_comment_from_a_previous_year_keeps_its_plain_date() {
		$this->make_comment( 400 * DAY_IN_SECONDS );

		$this->assertSame( 'DATE', relative_comment_date( 'DATE' ) );
	}

	public function test_a_comment_from_minutes_ago_appends_the_minute_count() {
		$this->make_comment( 330 );

		$this->assertSame( 'TIME (5 minutes ago)', relative_comment_time( 'TIME' ) );
	}

	public function test_the_comment_minute_count_can_be_rendered_on_its_own() {
		$this->make_comment( 330 );

		$this->assertSame( '5 minutes ago', relative_comment_time( 'TIME', true ) );
	}

	public function test_a_comment_from_hours_ago_appends_the_hour_count() {
		$this->make_comment( (int) ( 2.5 * HOUR_IN_SECONDS ) );

		$this->assertSame( 'TIME (2 hours ago)', relative_comment_time( 'TIME' ) );
	}

	public function test_a_comment_from_another_calendar_day_keeps_its_plain_time() {
		$this->make_comment( DAY_IN_SECONDS );

		$this->assertSame( 'TIME', relative_comment_time( 'TIME' ) );
	}

	/**
	 * A comment awaiting moderation can carry a timestamp a few seconds ahead
	 * of the server clock. Before 2.0.0 that rendered "(-300 seconds ago)": the
	 * difference is negative, and every branch of the ladder is "less than", so
	 * the first one matched.
	 */
	public function test_a_future_comment_on_the_same_day_gets_no_relative_suffix() {
		$this->make_comment( -5 * MINUTE_IN_SECONDS );

		$this->assertSame( 'TIME', relative_comment_time( 'TIME' ) );
	}

	/**
	 * With nothing in scope at all -- no global, no capture -- the callbacks
	 * hand their input straight back rather than raising. Before 2.0.0 they
	 * dereferenced the global unconditionally, which raised "Attempt to read
	 * property on null", a fatal under WP_DEBUG.
	 */
	public function test_no_comment_in_scope_returns_the_date_untouched() {
		unset( $GLOBALS['comment'] );

		$this->assertSame( 'DATE', relative_comment_date( 'DATE' ) );
	}

	public function test_no_comment_in_scope_returns_the_time_untouched() {
		unset( $GLOBALS['comment'] );

		$this->assertSame( 'TIME', relative_comment_time( 'TIME' ) );
	}

	public function test_the_filters_are_wired_up() {
		$this->make_comment( 330 );

		$this->assertSame( 'Today', apply_filters( 'get_comment_date', 'DATE', '', null ) );
		$this->assertSame(
			'TIME (5 minutes ago)',
			apply_filters( 'get_comment_time', 'TIME', '', false, true, null )
		);
	}

	/**
	 * The block-theme path. Core's Comment Date block calls
	 * get_comment_date( $format, $comment ) and never sets the $comment global,
	 * so before 2.0.0 the plugin had nothing to read -- it raised a fatal, and
	 * once that was guarded it simply did nothing. RelativeDate_Context now
	 * captures the comment core passed and hands it over.
	 */
	public function test_a_comment_passed_as_an_argument_gets_the_relative_form() {
		$comment = $this->make_comment( 0 );
		unset( $GLOBALS['comment'] );

		$this->assertSame( 'Today', get_comment_date( '', $comment ) );
	}

	public function test_a_comment_time_passed_as_an_argument_gets_the_relative_form() {
		$this->skip_without_comment_argument( 'get_comment_time', 4 );

		$comment = $this->make_comment( 330 );
		unset( $GLOBALS['comment'] );

		$this->assertStringEndsWith( ' (5 minutes ago)', get_comment_time( '', false, true, $comment ) );
	}

	/**
	 * The same block asks for 'c' to fill a <time datetime> attribute, and for
	 * 'U' to feed human_time_diff(). Neither can do anything with "Today".
	 */
	public function test_machine_readable_formats_are_left_alone() {
		$comment = $this->make_comment( 0 );

		$this->assertMatchesRegularExpression(
			'/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
			get_comment_date( 'c', $comment )
		);
		$this->assertMatchesRegularExpression( '/^\d+$/', get_comment_date( 'U', $comment ) );
		$this->assertMatchesRegularExpression( '/^\d+$/', get_comment_time( 'U', false, true, $comment ) );
	}
}
