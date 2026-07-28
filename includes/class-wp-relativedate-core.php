<?php
/**
 * The relative date and time calculations.
 *
 * Both methods take the raw MySQL datetime to measure from and the string
 * WordPress had already formatted for display, and return that string with the
 * relative phrase folded in. Nothing here reads a global or echoes anything.
 *
 * @package WP-RelativeDate
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns a stored datetime into "Today", "3 days ago", "5 minutes ago".
 */
class WP_RelativeDate_Core {

	/**
	 * Render a date relative to today.
	 *
	 * A date in a different calendar year is left alone: "2 weeks ago" for
	 * something posted last December is less use to a reader than the date
	 * itself, and the day-of-year arithmetic below cannot span a year anyway.
	 *
	 * @param string $mysql_date Stored local datetime, e.g. a post_date or comment_date.
	 * @param string $formatted  The date as WordPress already formatted it.
	 * @param string $before     Markup to open with.
	 * @param string $after      Markup to close with.
	 * @param bool   $ago_only   Return only the relative phrase, dropping $formatted.
	 * @return string
	 */
	public static function relative_date( $mysql_date, $formatted, $before = '', $after = '', $ago_only = false ) {
		$now  = current_datetime();
		$then = self::to_datetime( $mysql_date );

		if ( null === $then || $then->format( 'Y' ) !== $now->format( 'Y' ) ) {
			return $before . $formatted . $after;
		}

		$day_diff = (int) $now->format( 'z' ) - (int) $then->format( 'z' );

		/*
		 * A negative difference inside the same year means the date is still to
		 * come. 32 puts it past the end of the ladder, which is where a date
		 * with no useful relative form belongs.
		 */
		if ( $day_diff < 0 ) {
			$day_diff = 32;
		}

		if ( 0 === $day_diff ) {
			return $before . __( 'Today', 'wp-relativedate' ) . $after;
		}

		if ( 1 === $day_diff ) {
			return $before . __( 'Yesterday', 'wp-relativedate' ) . $after;
		}

		if ( $day_diff < 7 ) {
			$ago = sprintf(
				/* translators: %s: Number of days. */
				_n( '%s day ago', '%s days ago', $day_diff, 'wp-relativedate' ),
				number_format_i18n( $day_diff )
			);
		} elseif ( $day_diff < 31 ) {
			// Day seven is the first to round into weeks, the day branch being "< 7".
			$weeks = (int) ceil( $day_diff / 7 );

			$ago = sprintf(
				/* translators: %s: Number of weeks. */
				_n( '%s week ago', '%s weeks ago', $weeks, 'wp-relativedate' ),
				number_format_i18n( $weeks )
			);
		} else {
			return $before . $formatted . $after;
		}

		if ( $ago_only ) {
			return $before . $ago . $after;
		}

		return $before . $formatted . ' (' . $ago . ')' . $after;
	}

	/**
	 * Render a time relative to now.
	 *
	 * Only the current calendar day gets a relative form. Anything earlier has
	 * already been handled by relative_date(), and "27 hours ago" would sit
	 * oddly beside the "Yesterday" printed next to it.
	 *
	 * @param string $mysql_date Stored local datetime, e.g. a post_date or comment_date.
	 * @param string $formatted  The time as WordPress already formatted it.
	 * @param bool   $ago_only   Return only the relative phrase, dropping $formatted.
	 * @return string
	 */
	public static function relative_time( $mysql_date, $formatted, $ago_only = false ) {
		$now  = current_datetime();
		$then = self::to_datetime( $mysql_date );

		if ( null === $then || $then->format( 'Y-m-d' ) !== $now->format( 'Y-m-d' ) ) {
			return $formatted;
		}

		$seconds = $now->getTimestamp() - $then->getTimestamp();

		/*
		 * Content dated ahead of the server clock -- a scheduled post being
		 * previewed, a comment from a client whose clock runs fast -- has no
		 * "ago" to report. Before 2.0.0 the ladder below was three "less than"
		 * branches with no floor, so a negative difference matched the first
		 * one and rendered "(-300 seconds ago)".
		 */
		if ( $seconds < 0 ) {
			return $formatted;
		}

		if ( $seconds < MINUTE_IN_SECONDS ) {
			$ago = sprintf(
				/* translators: %s: Number of seconds. */
				_n( '%s second ago', '%s seconds ago', $seconds, 'wp-relativedate' ),
				number_format_i18n( $seconds )
			);
		} elseif ( $seconds < HOUR_IN_SECONDS ) {
			$minutes = (int) ( $seconds / MINUTE_IN_SECONDS );

			$ago = sprintf(
				/* translators: %s: Number of minutes. */
				_n( '%s minute ago', '%s minutes ago', $minutes, 'wp-relativedate' ),
				number_format_i18n( $minutes )
			);
		} else {
			$hours = (int) ( $seconds / HOUR_IN_SECONDS );

			$ago = sprintf(
				/* translators: %s: Number of hours. */
				_n( '%s hour ago', '%s hours ago', $hours, 'wp-relativedate' ),
				number_format_i18n( $hours )
			);
		}

		if ( $ago_only ) {
			return $ago;
		}

		return $formatted . ' (' . $ago . ')';
	}

	/**
	 * Parse a stored local datetime.
	 *
	 * The empty check is not defensive padding: date_create_immutable( '' )
	 * cheerfully returns the current time rather than false, so an unset date
	 * would otherwise read as "Today" instead of being left alone.
	 *
	 * @param string $mysql_date Stored local datetime.
	 * @return DateTimeImmutable|null Null when the value is not a usable date.
	 */
	private static function to_datetime( $mysql_date ) {
		if ( empty( $mysql_date ) || '0000-00-00 00:00:00' === $mysql_date ) {
			return null;
		}

		$datetime = date_create_immutable( $mysql_date, wp_timezone() );

		return false === $datetime ? null : $datetime;
	}
}
