<?php
/**
 * The public surface: five global functions, four hooks, two shortcodes.
 *
 * These names are the plugin's API. Themes call relative_post_the_date()
 * directly, and remove_filter() against the four callback names is the only
 * way to opt a template out of the automatic date rewriting -- the plugin has
 * no settings screen. Moving any of them onto a class would break both
 * silently, so 2.0.0 keeps them and forwards to RelativeDate_Core.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers ::relative_post_date
 * @covers ::relative_post_time
 * @covers ::relative_comment_date
 * @covers ::relative_comment_time
 * @covers ::relative_post_the_date
 */
class Test_RelativeDate_BackCompat extends RelativeDate_TestCase {

	/**
	 * Callback name => [ hook, priority, accepted args ].
	 *
	 * @return array<string, array{0: string, 1: int, 2: int}>
	 */
	public static function hooks() {
		return array(
			'relative_post_date'    => array( 'the_date', 999, 4 ),
			'relative_post_time'    => array( 'the_time', 999, 1 ),
			'relative_comment_date' => array( 'get_comment_date', 999, 1 ),
			'relative_comment_time' => array( 'get_comment_time', 999, 1 ),
		);
	}

	public function test_every_public_function_exists() {
		$expected = array(
			'relative_post_date',
			'relative_post_time',
			'relative_comment_date',
			'relative_comment_time',
			'relative_post_the_date',
		);

		foreach ( $expected as $function ) {
			$this->assertTrue( function_exists( $function ), "{$function}() is part of the public API." );
		}
	}

	public function test_every_callback_is_hooked_at_its_historical_priority() {
		foreach ( self::hooks() as $callback => $hook ) {
			$this->assertSame(
				$hook[1],
				has_filter( $hook[0], $callback ),
				"{$callback}() must stay on {$hook[0]} at priority {$hook[1]}."
			);
		}
	}

	/**
	 * The accepted-argument counts are load-bearing, not incidental. Each
	 * callback's second parameter is $display_ago_only, so widening any of the
	 * three single-argument hooks would pass core's $format string into it and
	 * turn "ago only" on for every date on the site.
	 */
	public function test_every_callback_keeps_its_accepted_argument_count() {
		global $wp_filter;

		foreach ( self::hooks() as $callback => $hook ) {
			$registered = $wp_filter[ $hook[0] ][ $hook[1] ][ $callback ];

			$this->assertSame(
				$hook[2],
				$registered['accepted_args'],
				"{$callback}() must keep accepting {$hook[2]} argument(s)."
			);
		}
	}

	/**
	 * The documented escape hatch: a theme that wants core's plain dates back
	 * on one template removes the filter.
	 */
	public function test_remove_filter_still_disables_the_post_date_rewriting() {
		$this->make_post( 0 );

		$this->assertTrue( remove_filter( 'the_date', 'relative_post_date', 999 ) );

		$date = $this->post_date_text();
		$this->assertSame( $date, apply_filters( 'the_date', $date, '', '', '' ) );

		add_filter( 'the_date', 'relative_post_date', 999, 4 );
	}

	public function test_remove_filter_still_disables_the_comment_date_rewriting() {
		$this->make_comment( 0 );

		$this->assertTrue( remove_filter( 'get_comment_date', 'relative_comment_date', 999 ) );

		$this->assertSame( 'DATE', apply_filters( 'get_comment_date', 'DATE', '', null ) );

		add_filter( 'get_comment_date', 'relative_comment_date', 999 );
	}
}
