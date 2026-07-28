<?php
/**
 * The public surface: five global functions, four hooks, two shortcodes.
 *
 * These names are the plugin's API. Themes call relative_post_the_date()
 * directly, and remove_filter() against the four callback names is the only
 * way to opt a template out of the automatic date rewriting -- the plugin has
 * no settings screen. Moving any of them onto a class would break both
 * silently, so 2.0.0 keeps them and forwards to WP_RelativeDate_Core.
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
class WP_RelativeDate_BackCompat_Test extends WP_RelativeDate_TestCase {

	/**
	 * Callback name => [ hook, priority, accepted args ].
	 *
	 * @return array<string, array{0: string, 1: int, 2: int}>
	 */
	public static function hooks() {
		return array(
			'relative_post_date'    => array( 'get_the_date', 999, 1 ),
			'relative_post_time'    => array( 'get_the_time', 999, 1 ),
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
	 * The post pair moved to the getters in 2.0.0 and must not be on both.
	 *
	 * Core's the_date() builds its output by calling get_the_date(), so a
	 * registration on each would apply the relative form twice and render
	 * "July 24, 2026 (3 days ago) (3 days ago)".
	 */
	public function test_the_post_callbacks_are_not_also_on_the_old_hooks() {
		$this->assertFalse( has_filter( 'the_date', 'relative_post_date' ) );
		$this->assertFalse( has_filter( 'the_time', 'relative_post_time' ) );
	}

	/**
	 * Every hook the plugin relativises on is a getter. The four setter
	 * functions -- the_date(), the_time(), comment_date(), comment_time() --
	 * all route through one of these in core, so this is the complete set.
	 */
	public function test_the_plugin_only_relativises_on_getter_hooks() {
		foreach ( array_keys( self::hooks() ) as $callback ) {
			$hook = self::hooks()[ $callback ][0];

			$this->assertStringStartsWith( 'get_', $hook, "{$callback}() must be on a getter hook." );
		}
	}

	/**
	 * The documented escape hatch: a theme that wants core's plain dates back
	 * on one template removes the filter.
	 */
	public function test_remove_filter_still_disables_the_post_date_rewriting() {
		$this->make_post( 0 );

		$this->assertTrue( remove_filter( 'get_the_date', 'relative_post_date', 999 ) );

		$this->assertSame( $this->post_date_text(), get_the_date( '', $this->post ) );

		add_filter( 'get_the_date', 'relative_post_date', 999 );
	}

	public function test_remove_filter_still_disables_the_comment_date_rewriting() {
		$this->make_comment( 0 );

		$this->assertTrue( remove_filter( 'get_comment_date', 'relative_comment_date', 999 ) );

		$this->assertSame( 'DATE', apply_filters( 'get_comment_date', 'DATE', '', null ) );

		add_filter( 'get_comment_date', 'relative_comment_date', 999 );
	}
}
