<?php
/**
 * Template tags and filter callbacks for WP-RelativeDate.
 *
 * These five function names are the plugin's public API and do not move.
 * relative_post_the_date() is called directly by themes, and the other four are
 * the registered filter callbacks -- remove_filter() against those names is the
 * only way to opt a template out of the automatic rewriting, the plugin having
 * no settings screen. Since 2.0.0 they are thin wrappers over RelativeDate_Core.
 *
 * Their signatures do not move either. The second parameter of each callback is
 * $display_ago_only, so none of the hooks may be widened to accept more of
 * core's arguments: doing so would pass a date format string into it and turn
 * "ago only" on for every date on the site.
 *
 * @package WP-RelativeDate
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'relative_post_date' ) ) {
	/**
	 * Rewrite a post date. Callback for 'the_date'.
	 *
	 * @param string $the_date         The date as core assembled it, wrapper included.
	 * @param string $d                Date format. Unused; core's third filter argument.
	 * @param string $before           Markup to open with.
	 * @param string $after            Markup to close with.
	 * @param bool   $display_ago_only Return only the relative phrase.
	 * @return string
	 */
	function relative_post_date( $the_date, $d = '', $before = '', $after = '', $display_ago_only = false ) {
		$post = get_post();

		if ( empty( $post ) ) {
			return $the_date;
		}

		/*
		 * Core hands this filter $before . $date . $after already concatenated,
		 * so the wrapper comes back off here and is reapplied below rather than
		 * being doubled.
		 */
		$the_date = wp_strip_all_tags( $the_date );

		/*
		 * the_date() passes an empty string for the second and later posts
		 * sharing a day, and expects nothing back -- wrapper included.
		 */
		if ( '' === $the_date ) {
			return $the_date;
		}

		return RelativeDate_Core::relative_date( $post->post_date, $the_date, $before, $after, $display_ago_only );
	}
}

if ( ! function_exists( 'relative_post_time' ) ) {
	/**
	 * Rewrite a post time. Callback for 'the_time'.
	 *
	 * @param string $current_timeformat The time as core formatted it.
	 * @param bool   $display_ago_only   Return only the relative phrase.
	 * @return string
	 */
	function relative_post_time( $current_timeformat, $display_ago_only = false ) {
		$post = get_post();

		if ( empty( $post ) ) {
			return $current_timeformat;
		}

		return RelativeDate_Core::relative_time( $post->post_date, $current_timeformat, $display_ago_only );
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
		/*
		 * get_comment_date() is routinely called with an explicit comment ID
		 * from outside the comment loop -- the recent-comments widget does
		 * exactly that -- and there is no $comment global to read there.
		 */
		$comment = get_comment();

		if ( empty( $comment ) ) {
			return $current_dateformat;
		}

		return RelativeDate_Core::relative_date( $comment->comment_date, $current_dateformat, '', '', $display_ago_only );
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
		$comment = get_comment();

		if ( empty( $comment ) ) {
			return $current_timeformat;
		}

		return RelativeDate_Core::relative_time( $comment->comment_date, $current_timeformat, $display_ago_only );
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

		$output = RelativeDate_Core::relative_date( $post->post_date, $the_date, $before, $after, $display_ago_only );

		if ( $display ) {
			/*
			 * $before and $after carry theme-supplied markup, exactly as they do
			 * in core's the_date() that this tag replaces, and core echoes them
			 * raw too. 1.51.1 wrapped this in esc_html() and every theme passing
			 * a wrapper got its tags rendered as literal text.
			 */
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		return $output;
	}
}
