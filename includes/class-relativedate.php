<?php
/**
 * WP-RelativeDate bootstrap.
 *
 * @package WP-RelativeDate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's filters and shortcodes up.
 */
class RelativeDate {

	/**
	 * Sole instance.
	 *
	 * @var RelativeDate|null
	 */
	private static $instance = null;

	/**
	 * Retrieve, creating on first call.
	 *
	 * @return RelativeDate
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * The four filters are registered against global function names rather than
	 * methods of this class, at priority 999 and accepting a single argument.
	 * Both matter: remove_filter() with those names is the documented way to
	 * opt a template out, and each callback's second parameter is
	 * $display_ago_only, so accepting more of core's arguments would hand it a
	 * format string and turn "ago only" on site-wide. RelativeDate_Context
	 * exists precisely so those arguments can be read without widening a hook.
	 *
	 * All four are getters. Before 2.0.0 the post pair was on the_date and
	 * the_time, which fire only for those two template functions -- every
	 * default theme since Twenty Nineteen builds its post meta from
	 * get_the_date() instead, so the plugin did nothing there. Core's the_date()
	 * and the_time() call the getters internally, so hooking the getters alone
	 * covers both and cannot apply the relative form twice. The comment pair
	 * has always been on the getters, so this makes the two consistent.
	 *
	 * One thing this still does not reach, and cannot: core's Post Date block.
	 * Since WP 6.9 render_block_core_post_date() resolves the date through
	 * Block Bindings and formats it with wp_date(), never calling get_the_date()
	 * and applying no filter of its own, so a block theme's post date stays
	 * plain. Core's Comment Date block does still call get_comment_date(), which
	 * is why comments do work there.
	 */
	private function __construct() {
		RelativeDate_Context::register();

		add_filter( 'get_the_date', 'relative_post_date', 999 );
		add_filter( 'get_the_time', 'relative_post_time', 999 );
		add_filter( 'get_comment_date', 'relative_comment_date', 999 );
		add_filter( 'get_comment_time', 'relative_comment_time', 999 );

		add_shortcode( 'relativedate', array( $this, 'shortcode_date' ) );
		add_shortcode( 'relativetime', array( $this, 'shortcode_time' ) );
	}

	/**
	 * Handle [relativedate].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_date( $atts ) {
		$attributes = shortcode_atts(
			array(
				'date_format' => '',
				'ago_only'    => false,
			),
			$atts
		);

		$date = relative_post_the_date(
			$attributes['date_format'],
			'',
			'',
			self::to_bool( $attributes['ago_only'] ),
			false
		);

		return esc_html( $date );
	}

	/**
	 * Handle [relativetime].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_time( $atts ) {
		$post = get_post();

		if ( empty( $post ) ) {
			return '';
		}

		$attributes = shortcode_atts(
			array(
				'time_format' => '',
				'ago_only'    => false,
			),
			$atts
		);

		$time_format = $attributes['time_format'];

		if ( empty( $time_format ) ) {
			$time_format = get_option( 'time_format' );
		}

		$time = relative_post_time(
			mysql2date( $time_format, $post->post_date, false ),
			self::to_bool( $attributes['ago_only'] )
		);

		return esc_html( $time );
	}

	/**
	 * Read a shortcode attribute as a boolean.
	 *
	 * A plain (bool) cast reports the string "false" as true, so ago_only="false"
	 * -- the spelling the README has documented since 1.51 -- asked for the
	 * opposite of what it said. wp_validate_boolean() only special-cases that one
	 * literal and would still read "no" and "off" as true, so this uses PHP's
	 * boolean filter, which understands all of them.
	 *
	 * @param mixed $value Attribute value.
	 * @return bool
	 */
	private static function to_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
