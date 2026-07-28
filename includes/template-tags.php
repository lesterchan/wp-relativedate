<?php
/**
 * Template tags and filter callbacks for WP-RelativeDate.
 *
 * These five function names are the plugin's public API and do not move.
 * relative_post_the_date() is called directly by themes, and the other four are
 * the registered filter callbacks -- remove_filter() against those names is the
 * only way to opt a template out of the automatic rewriting, the plugin having
 * no settings screen. Since 2.0.0 they are thin wrappers over WP_RelativeDate_Core.
 *
 * Their signatures do not move either. The second parameter of each callback is
 * $display_ago_only, so none of the hooks may be widened to accept more of
 * core's arguments: doing so would pass a date format string into it and turn
 * "ago only" on for every date on the site. Whatever they need from those
 * arguments comes from WP_RelativeDate_Context, which captures them one priority
 * earlier.
 *
 * The hooks themselves did move in 2.0.0: the post pair went from the_date and
 * the_time to get_the_date and get_the_time, which is where the comment pair
 * already was. A theme that opted out with
 * remove_filter( 'the_date', 'relative_post_date', 999 ) needs to name the
 * getter now.
 *
 * @package WP-RelativeDate
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'relative_post_date' ) ) {
	/**
	 * Rewrite a post date. Callback for 'get_the_date'.
	 *
	 * @param string $the_date         The date as core formatted it.
	 * @param string $d                Date format. Unused; kept for callers from before 2.0.0.
	 * @param string $before           Markup to open with.
	 * @param string $after            Markup to close with.
	 * @param bool   $display_ago_only Return only the relative phrase.
	 * @return string
	 */
	function relative_post_date( $the_date, $d = '', $before = '', $after = '', $display_ago_only = false ) {
		$context = WP_RelativeDate_Context::take_post();

		// Leave <time datetime> attributes and 'U' timestamps alone.
		if ( null !== $context && WP_RelativeDate_Context::is_machine_format( $context['format'] ) ) {
			return $the_date;
		}

		$post = ( null !== $context && $context['object'] instanceof WP_Post ) ? $context['object'] : get_post();

		/*
		 * the_date() returns an empty string for the second and later posts
		 * sharing a day, without ever reaching the getter. Nothing should come
		 * back for one either way -- wrapper included.
		 */
		if ( empty( $post ) || '' === $the_date ) {
			return $the_date;
		}

		return WP_RelativeDate_Core::relative_date( $post->post_date, $the_date, $before, $after, $display_ago_only );
	}
}

if ( ! function_exists( 'relative_post_time' ) ) {
	/**
	 * Rewrite a post time. Callback for 'get_the_time'.
	 *
	 * @param string $current_timeformat The time as core formatted it.
	 * @param bool   $display_ago_only   Return only the relative phrase.
	 * @return string
	 */
	function relative_post_time( $current_timeformat, $display_ago_only = false ) {
		$context = WP_RelativeDate_Context::take_post();

		if ( null !== $context && WP_RelativeDate_Context::is_machine_format( $context['format'] ) ) {
			return $current_timeformat;
		}

		$post = ( null !== $context && $context['object'] instanceof WP_Post ) ? $context['object'] : get_post();

		if ( empty( $post ) ) {
			return $current_timeformat;
		}

		return WP_RelativeDate_Core::relative_time( $post->post_date, $current_timeformat, $display_ago_only );
	}
}

if ( ! function_exists( 'relative_comment_date' ) ) {
	/**
	 * Rewrite a comment date. Callback for 'get_comment_date'.
	 *
	 * @param string $current_dateformat The date as core formatted it.
	 * @param bool   $display_ago_only   Return only the relative phrase.
	 * @return string
	 */
	function relative_comment_date( $current_dateformat, $display_ago_only = false ) {
		$context = WP_RelativeDate_Context::take_comment();

		if ( null !== $context && WP_RelativeDate_Context::is_machine_format( $context['format'] ) ) {
			return $current_dateformat;
		}

		/*
		 * get_comment_date() is routinely called with the comment passed as an
		 * argument and no global set -- the recent-comments widget does it, and
		 * so does core's Comment Date block, which is how every block theme
		 * renders a comment date.
		 */
		$comment = ( null !== $context && $context['object'] instanceof WP_Comment ) ? $context['object'] : get_comment();

		if ( empty( $comment ) ) {
			return $current_dateformat;
		}

		return WP_RelativeDate_Core::relative_date( $comment->comment_date, $current_dateformat, '', '', $display_ago_only );
	}
}

if ( ! function_exists( 'relative_comment_time' ) ) {
	/**
	 * Rewrite a comment time. Callback for 'get_comment_time'.
	 *
	 * @param string $current_timeformat The time as core formatted it.
	 * @param bool   $display_ago_only   Return only the relative phrase.
	 * @return string
	 */
	function relative_comment_time( $current_timeformat, $display_ago_only = false ) {
		$context = WP_RelativeDate_Context::take_comment();

		if ( null !== $context && WP_RelativeDate_Context::is_machine_format( $context['format'] ) ) {
			return $current_timeformat;
		}

		$comment = ( null !== $context && $context['object'] instanceof WP_Comment ) ? $context['object'] : get_comment();

		if ( empty( $comment ) ) {
			return $current_timeformat;
		}

		return WP_RelativeDate_Core::relative_time( $comment->comment_date, $current_timeformat, $display_ago_only );
	}
}

if ( ! function_exists( 'relative_post_the_date' ) ) {
	/**
	 * Print or return the current post's relative date. A drop-in for the_date().
	 *
	 * @param string $d                Date format. Defaults to the site's date_format.
	 * @param string $before           Markup to open with.
	 * @param string $after            Markup to close with.
	 * @param bool   $display_ago_only Return only the relative phrase.
	 * @param bool   $display          Echo instead of returning.
	 * @return string|void Markup, or nothing when $display is true.
	 */
	function relative_post_the_date( $d = '', $before = '', $after = '', $display_ago_only = false, $display = true ) {
		$post = get_post();

		if ( empty( $post ) ) {
			if ( $display ) {
				return;
			}

			return '';
		}

		$the_date = mysql2date( empty( $d ) ? get_option( 'date_format' ) : $d, $post->post_date );

		$output = WP_RelativeDate_Core::relative_date( $post->post_date, $the_date, $before, $after, $display_ago_only );

		if ( $display ) {
			/*
			 * $before and $after carry theme-supplied markup, exactly as they do
			 * in core's the_date() that this tag replaces. 1.51.1 wrapped this in
			 * esc_html() and every theme passing a wrapper got its tags rendered
			 * as literal text, so escaping the string is not an option.
			 *
			 * wp_kses_post() rather than no filtering at all: it keeps every tag
			 * a theme would plausibly wrap a date in -- headings, spans, divs,
			 * links -- while a $before assembled from something a visitor
			 * supplied cannot smuggle a <script> through the tag.
			 */
			echo wp_kses_post( $output );
			return;
		}

		return $output;
	}
}
