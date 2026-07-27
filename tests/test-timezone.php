<?php
/**
 * The site timezone.
 *
 * Every other test in this suite pins the clock with a numeric gmt_offset,
 * which is the only way to guarantee a midday local time and keep the
 * second-level fixtures off the midnight boundary. That leaves a real gap:
 * wp_timezone() returns a DateTimeZone built from timezone_string when one is
 * set, and a named zone carries DST rules that a fixed offset does not.
 *
 * These tests use the day ladder rather than the time ladder wherever they can,
 * because a day-of-year difference is exact for any fixture dated a whole
 * number of days back, whatever the local hour happens to be.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers RelativeDate_Core::relative_date
 * @covers RelativeDate_Core::relative_time
 */
class Test_RelativeDate_Timezone extends RelativeDate_TestCase {

	/**
	 * Switch the site to a named timezone.
	 *
	 * The gmt_offset option is cleared alongside it: WP prefers timezone_string
	 * when both are set, and leaving a stale offset behind would hide a bug
	 * where the plugin read the wrong one.
	 *
	 * @param string $timezone A PHP timezone identifier.
	 * @return void
	 */
	protected function use_timezone( $timezone ) {
		update_option( 'timezone_string', $timezone );
		update_option( 'gmt_offset', 0 );

		$this->assertSame( $timezone, wp_timezone()->getName(), 'The site timezone did not take.' );
	}

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
	 * Skip when local time is close enough to midnight to move a fixture's day.
	 *
	 * @param int $seconds Headroom the fixture needs on either side.
	 * @return void
	 */
	protected function skip_near_midnight( $seconds ) {
		$now      = $this->now();
		$midnight = (int) $now->format( 'H' ) * HOUR_IN_SECONDS + (int) $now->format( 'i' ) * MINUTE_IN_SECONDS + (int) $now->format( 's' );

		if ( $midnight < $seconds || $midnight > DAY_IN_SECONDS - $seconds ) {
			$this->markTestSkipped( 'Local time is too close to midnight for a second-level fixture.' );
		}
	}

	public function test_a_named_timezone_reads_today_as_today() {
		$this->use_timezone( 'Asia/Singapore' );
		$this->make_post( 0 );

		$this->assertSame( 'Today', relative_post_date( $this->post_date_text() ) );
	}

	public function test_a_named_timezone_counts_days_from_local_midnight() {
		$this->use_timezone( 'Asia/Singapore' );
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (3 days ago)',
			relative_post_date( $this->post_date_text() )
		);
	}

	/**
	 * A zone that actually observes DST, which a numeric offset never does.
	 */
	public function test_a_dst_observing_timezone_counts_days_correctly() {
		$this->use_timezone( 'America/New_York' );
		$this->skip_if_crosses_year( 5 * DAY_IN_SECONDS );
		$this->make_post( 5 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (5 days ago)',
			relative_post_date( $this->post_date_text() )
		);
	}

	public function test_a_named_timezone_counts_minutes() {
		$this->use_timezone( 'Asia/Singapore' );
		$this->skip_near_midnight( 10 * MINUTE_IN_SECONDS );
		$this->make_post( 330 );

		$this->assertSame(
			$this->post_time_text() . ' (5 minutes ago)',
			relative_post_time( $this->post_time_text() )
		);
	}

	/**
	 * Comment dates go through the same clock as post dates.
	 */
	public function test_a_named_timezone_applies_to_comments_too() {
		$this->use_timezone( 'Asia/Singapore' );
		$this->skip_if_crosses_year( 2 * DAY_IN_SECONDS );
		$this->make_comment( 2 * DAY_IN_SECONDS );

		$this->assertSame( 'DATE (2 days ago)', relative_comment_date( 'DATE' ) );
	}
}
