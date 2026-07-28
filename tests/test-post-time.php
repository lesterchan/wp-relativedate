<?php
/**
 * The relative_post_time() callback, on the 'get_the_time' filter.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers ::relative_post_time
 */
class WP_RelativeDate_Post_Time_Test extends WP_RelativeDate_TestCase {

	/**
	 * Assert a "N seconds ago" phrase, allowing for the clock moving between
	 * the fixture being built and the plugin reading current_time().
	 *
	 * @param int    $expected Seconds the fixture was dated in the past.
	 * @param string $actual   The rendered phrase.
	 * @return void
	 */
	protected function assertSecondsPhrase( $expected, $actual ) {
		$this->assertMatchesRegularExpression( '/^\d+ seconds ago$/', $actual );
		$this->assertGreaterThanOrEqual( $expected, (int) $actual );
		$this->assertLessThanOrEqual( $expected + 5, (int) $actual );
	}

	public function test_a_post_from_seconds_ago_appends_the_second_count() {
		$this->make_post( 30 );

		$time = $this->post_time_text();
		$out  = relative_post_time( $time );

		$this->assertStringStartsWith( $time . ' (', $out );
		$this->assertStringEndsWith( ')', $out );
		$this->assertSecondsPhrase( 30, substr( $out, strlen( $time ) + 2, -1 ) );
	}

	public function test_the_second_count_can_be_rendered_on_its_own() {
		$this->make_post( 30 );

		$this->assertSecondsPhrase( 30, relative_post_time( $this->post_time_text(), true ) );
	}

	/**
	 * 330 seconds rather than 300: the plugin floors the division, so an exact
	 * multiple of a minute would flip to "4 minutes ago" if the clock advanced
	 * by one second between the fixture and the assertion.
	 */
	public function test_a_post_from_minutes_ago_appends_the_minute_count() {
		$this->make_post( 330 );

		$this->assertSame(
			$this->post_time_text() . ' (5 minutes ago)',
			relative_post_time( $this->post_time_text() )
		);
	}

	public function test_a_post_from_hours_ago_appends_the_hour_count() {
		$this->make_post( (int) ( 3.5 * HOUR_IN_SECONDS ) );

		$this->assertSame(
			$this->post_time_text() . ' (3 hours ago)',
			relative_post_time( $this->post_time_text() )
		);
	}

	/**
	 * 90 and 5400 seconds rather than 60 and 3600: both sit in the middle of
	 * their floored bucket, so the singular cannot flip to a plural because the
	 * clock moved a second between the fixture and the assertion.
	 */
	public function test_a_single_minute_and_hour_are_singular() {
		$this->make_post( 90 );
		$this->assertSame( '1 minute ago', relative_post_time( $this->post_time_text(), true ) );

		$this->make_post( 5400 );
		$this->assertSame( '1 hour ago', relative_post_time( $this->post_time_text(), true ) );
	}

	public function test_a_post_from_another_calendar_day_keeps_its_plain_time() {
		$this->make_post( DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_time_text(),
			relative_post_time( $this->post_time_text() )
		);
	}

	/**
	 * A scheduled post previewed before it goes live rendered "(-300 seconds
	 * ago)" before 2.0.0: the difference is negative, and every branch of the
	 * ladder is "less than", so the first one matched.
	 */
	public function test_a_future_post_on_the_same_day_gets_no_relative_suffix() {
		$this->make_post( -5 * MINUTE_IN_SECONDS );

		$this->assertSame(
			$this->post_time_text(),
			relative_post_time( $this->post_time_text() )
		);
	}

	public function test_no_post_in_scope_returns_the_input_untouched() {
		unset( $GLOBALS['post'] );

		$this->assertSame( '12:00 pm', relative_post_time( '12:00 pm' ) );
	}

	public function test_the_filter_is_wired_up() {
		$post = $this->make_post( 330 );

		$this->assertSame(
			$this->post_time_text() . ' (5 minutes ago)',
			get_the_time( '', $post )
		);
	}

	/**
	 * Core's the_time() builds its output by calling get_the_time(), so the
	 * relative form has to arrive exactly once rather than twice.
	 */
	public function test_the_time_applies_the_relative_form_exactly_once() {
		$this->make_post( 330 );

		ob_start();
		the_time();

		$this->assertSame( $this->post_time_text() . ' (5 minutes ago)', ob_get_clean() );
	}
}
