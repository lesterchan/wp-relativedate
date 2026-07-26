<?php
/**
 * Shared base class for the WP-RelativeDate test cases.
 *
 * @package WP-RelativeDate
 */

/**
 * Pins the site clock and builds posts and comments at controlled offsets.
 */
abstract class RelativeDate_TestCase extends WP_UnitTestCase {

	/**
	 * A post used as the global $post by the fixture helpers.
	 *
	 * @var WP_Post|null
	 */
	protected $post;

	/**
	 * Pin the site clock to midday and clear the loop globals.
	 *
	 * Every assertion in this suite is about the distance between "now" and a
	 * post date, so a suite that runs at 00:00:03 UTC would see "30 seconds
	 * ago" land on the previous calendar day and flip half the expectations.
	 * Choosing a gmt_offset that puts local time at 12:MM leaves eleven hours
	 * of headroom on either side of midnight, which no fixture here comes
	 * close to spending.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'timezone_string', '' );
		update_option( 'gmt_offset', 12 - (int) gmdate( 'G' ) );

		unset( $GLOBALS['post'], $GLOBALS['comment'] );
		$GLOBALS['currentday']  = '';
		$GLOBALS['previousday'] = '';
	}

	/**
	 * Drop the globals the fixtures set, so one test cannot leak into the next.
	 *
	 * @return void
	 */
	public function tear_down() {
		unset( $GLOBALS['post'], $GLOBALS['comment'] );

		parent::tear_down();
	}

	/**
	 * The site's current local time.
	 *
	 * @return DateTimeImmutable
	 */
	protected function now() {
		return current_datetime();
	}

	/**
	 * A local timestamp the given number of seconds in the past.
	 *
	 * @param int $seconds_ago Seconds before now. Negative values are in the future.
	 * @return DateTimeImmutable
	 */
	protected function ago( $seconds_ago ) {
		return $this->now()->modify( sprintf( '%+d seconds', -$seconds_ago ) );
	}

	/**
	 * Create a post dated a given number of seconds ago and make it the global $post.
	 *
	 * The GMT date is passed explicitly rather than left for wp_insert_post() to
	 * derive, because a post dated in the future is otherwise rewritten to
	 * 'future' status with a date of its own choosing.
	 *
	 * @param int $seconds_ago Seconds before now.
	 * @return WP_Post
	 */
	protected function make_post( $seconds_ago ) {
		$local = $this->ago( $seconds_ago );
		$utc   = $local->setTimezone( new DateTimeZone( 'UTC' ) );

		$post_id = self::factory()->post->create(
			array(
				'post_status'   => 'publish',
				'post_date'     => $local->format( 'Y-m-d H:i:s' ),
				'post_date_gmt' => $utc->format( 'Y-m-d H:i:s' ),
			)
		);

		$this->post = get_post( $post_id );

		// The plugin reads the loop global, exactly as the_date() and the_time() do.
		$GLOBALS['post'] = $this->post;

		return $this->post;
	}

	/**
	 * Create a comment dated a given number of seconds ago and make it the global $comment.
	 *
	 * @param int $seconds_ago Seconds before now.
	 * @return WP_Comment
	 */
	protected function make_comment( $seconds_ago ) {
		$local = $this->ago( $seconds_ago );
		$utc   = $local->setTimezone( new DateTimeZone( 'UTC' ) );

		$comment_id = self::factory()->comment->create(
			array(
				'comment_post_ID'  => self::factory()->post->create(),
				'comment_date'     => $local->format( 'Y-m-d H:i:s' ),
				'comment_date_gmt' => $utc->format( 'Y-m-d H:i:s' ),
			)
		);

		$comment = get_comment( $comment_id );

		$GLOBALS['comment'] = $comment;

		return $comment;
	}

	/**
	 * The site-formatted date of the current global post, as the plugin would render it.
	 *
	 * Derived rather than hard-coded: the expectations are about the relative
	 * suffix the plugin adds, not about WordPress's default date_format, and
	 * hard-coding "July 27, 2026" would make the suite fail once a year for
	 * reasons that have nothing to do with this plugin.
	 *
	 * @param string $format Date format. Defaults to the site's date_format.
	 * @return string
	 */
	protected function post_date_text( $format = '' ) {
		if ( '' === $format ) {
			$format = get_option( 'date_format' );
		}

		return mysql2date( $format, $this->post->post_date );
	}

	/**
	 * The site-formatted time of the current global post.
	 *
	 * @param string $format Time format. Defaults to the site's time_format.
	 * @return string
	 */
	protected function post_time_text( $format = '' ) {
		if ( '' === $format ) {
			$format = get_option( 'time_format' );
		}

		return mysql2date( $format, $this->post->post_date, false );
	}
}
