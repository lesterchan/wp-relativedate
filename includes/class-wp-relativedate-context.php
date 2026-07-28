<?php
/**
 * Captures what core passed to the date filter currently running.
 *
 * The four callbacks in template-tags.php cannot accept core's later filter
 * arguments: each one's own second parameter is $display_ago_only, so widening
 * a hook would hand it a format string and turn "ago only" on site-wide. These
 * capture filters run one priority earlier, stash the arguments, and let the
 * callbacks read them without changing a single signature.
 *
 * Two things depend on it:
 *
 * The object. A block theme never sets the $post or $comment global -- core's
 * Comment Date block calls get_comment_date( $format, $comment ) directly --
 * so without this the plugin has nothing to read and passes the date through.
 *
 * The format. Those same blocks call the getter twice, once for the visible
 * text and once for the machine-readable datetime attribute, and relativising
 * the second turns datetime="2026-07-24T01:10:20+00:00" into datetime="Today".
 *
 * @package WP-RelativeDate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stash of the object and format belonging to the filter currently running.
 */
class WP_RelativeDate_Context {

	/**
	 * Priority of the capture filters. One below the callbacks that read them.
	 */
	const PRIORITY = 998;

	/**
	 * Object and format from the last get_the_date/get_the_time filter.
	 *
	 * @var array{object: WP_Post|null, format: string}|null
	 */
	private static $post = null;

	/**
	 * Object and format from the last get_comment_date/get_comment_time filter.
	 *
	 * @var array{object: WP_Comment|null, format: string}|null
	 */
	private static $comment = null;

	/**
	 * Register the capture filters.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'get_the_date', array( __CLASS__, 'capture_post' ), self::PRIORITY, 3 );
		add_filter( 'get_the_time', array( __CLASS__, 'capture_post' ), self::PRIORITY, 3 );
		add_filter( 'get_comment_date', array( __CLASS__, 'capture_comment_date' ), self::PRIORITY, 3 );
		add_filter( 'get_comment_time', array( __CLASS__, 'capture_comment_time' ), self::PRIORITY, 5 );
	}

	/**
	 * Stash the post and format. Callback for 'get_the_date' and 'get_the_time'.
	 *
	 * @param string       $value  The formatted date or time. Returned untouched.
	 * @param string       $format Format core was asked for.
	 * @param WP_Post|null $post   The post core resolved.
	 * @return string
	 */
	public static function capture_post( $value, $format = '', $post = null ) {
		self::$post = array(
			'object' => $post instanceof WP_Post ? $post : null,
			'format' => is_string( $format ) ? $format : '',
		);

		return $value;
	}

	/**
	 * Stash the comment and format. Callback for 'get_comment_date'.
	 *
	 * @param string          $value   The formatted date. Returned untouched.
	 * @param string          $format  Format core was asked for.
	 * @param WP_Comment|null $comment The comment core resolved.
	 * @return string
	 */
	public static function capture_comment_date( $value, $format = '', $comment = null ) {
		self::stash_comment( $format, $comment );

		return $value;
	}

	/**
	 * Stash the comment and format. Callback for 'get_comment_time'.
	 *
	 * @param string          $value     The formatted time. Returned untouched.
	 * @param string          $format    Format core was asked for.
	 * @param bool            $gmt       Whether GMT time was requested. Unused.
	 * @param bool            $translate Whether to translate. Unused.
	 * @param WP_Comment|null $comment   The comment core resolved.
	 * @return string
	 */
	public static function capture_comment_time( $value, $format = '', $gmt = false, $translate = true, $comment = null ) {
		self::stash_comment( $format, $comment );

		return $value;
	}

	/**
	 * Take the post context, clearing it.
	 *
	 * Reading is destructive so a stale capture cannot leak into a later direct
	 * call of one of the template tags. Every caller falls back to the loop
	 * global when this returns null, which is the classic-theme path.
	 *
	 * @return array{object: WP_Post|null, format: string}|null
	 */
	public static function take_post() {
		$context    = self::$post;
		self::$post = null;

		return $context;
	}

	/**
	 * Take the comment context, clearing it.
	 *
	 * @return array{object: WP_Comment|null, format: string}|null
	 */
	public static function take_comment() {
		$context       = self::$comment;
		self::$comment = null;

		return $context;
	}

	/**
	 * Drop both stashes.
	 *
	 * Nothing in the plugin calls this: the two take_*() methods already clear
	 * as they read, which is what keeps a capture from outliving the filter it
	 * belongs to. It exists for the test suite, which has to guarantee one test
	 * cannot inherit a stash from the last one. Deliberately kept rather than
	 * dead code.
	 *
	 * @return void
	 */
	public static function reset() {
		self::$post    = null;
		self::$comment = null;
	}

	/**
	 * Whether a format asks for a machine-readable timestamp.
	 *
	 * "Today" is not a date a <time datetime> attribute or human_time_diff()
	 * can do anything with, so these formats are left alone. Core's Post Date
	 * and Comment Date blocks use 'c' for the attribute and 'U' for their own
	 * relative-time variant.
	 *
	 * @param string $format Format core was asked for.
	 * @return bool
	 */
	public static function is_machine_format( $format ) {
		$machine = array( 'c', 'r', 'U', 'G', DATE_ATOM, DATE_RFC3339, DATE_RSS, DATE_COOKIE, DATE_ISO8601 );

		return in_array( (string) $format, $machine, true );
	}

	/**
	 * Shared by the two comment capture callbacks.
	 *
	 * @param string          $format  Format core was asked for.
	 * @param WP_Comment|null $comment The comment core resolved.
	 * @return void
	 */
	private static function stash_comment( $format, $comment ) {
		self::$comment = array(
			'object' => $comment instanceof WP_Comment ? $comment : null,
			'format' => is_string( $format ) ? $format : '',
		);
	}
}
