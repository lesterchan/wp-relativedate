<?php
/*
Plugin Name: WP-RelativeDate
Plugin URI: https://lesterchan.net/portfolio/programming/php/
Description: Displays relative date alongside with your post/comments actual date. Like 'Today', 'Yesterday', '2 Days Ago', '2 Weeks Ago', '2 'Seconds Ago', '2 Minutes Ago', '2 Hours Ago'.
Version: 1.51.1
Author: Lester 'GaMerZ' Chan
Author URI: https://lesterchan.net
Text Domain: wp-relativedate
*/


/*
    Copyright 2026  Lester Chan  (email : lesterchan@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

### Create Text Domain For Translations
add_action( 'plugins_loaded', 'relativedate_textdomain' );
function relativedate_textdomain() {
    load_plugin_textdomain( 'wp-relativedate', false, dirname( plugin_basename( __FILE__ ) ) );
}

### Function: Display Post Relative Date (Today/Yesterday/Days Ago/Weeks Ago)
add_filter('the_date', 'relative_post_date', 999, 4);
function relative_post_date($the_date, $d = '', $before = '', $after = '', $display_ago_only = false) {
    $post = get_post();
    if(empty($post)) {
        return $the_date;
    }
    $the_date = wp_strip_all_tags($the_date);
    ### the_date() Passes An Empty String For The Second And Later Posts Sharing A
    ### Day And Expects Nothing Back. This Used To Fall Out Of A Comparison Against
    ### $previous_day, A Global Nothing Ever Set, So It Was Always NULL.
    if('' === $the_date) {
        return $the_date;
    }
    if(current_datetime()->format('Y') != mysql2date('Y', $post->post_date, false)) {
        return $before.$the_date.$after;
    }
    $day_diff = ((int) current_datetime()->format('z') - (int) mysql2date('z', $post->post_date, false));
    if($day_diff < 0) { $day_diff = 32; }
    if($day_diff == 0) {
        return $before.__('Today', 'wp-relativedate').$after;
    } elseif($day_diff == 1) {
        return $before. __('Yesterday', 'wp-relativedate').$after;
    } elseif ($day_diff < 7) {
        if($display_ago_only) {
            return $before.sprintf(_n('%s day ago', '%s days ago', $day_diff, 'wp-relativedate'), number_format_i18n($day_diff)).$after;
        } else {
            return $before.$the_date.' ('.sprintf(_n('%s day ago', '%s days ago', $day_diff, 'wp-relativedate'), number_format_i18n($day_diff)).')'.$after;
        }
    } elseif ($day_diff < 31) {
        $week_diff = (int) ceil($day_diff/7);
        if($display_ago_only) {
            return $before.sprintf(_n('%s week ago', '%s weeks ago', $week_diff, 'wp-relativedate'), number_format_i18n($week_diff)).$after;
        } else {
            return $before.$the_date.' ('.sprintf(_n('%s week ago', '%s weeks ago', $week_diff, 'wp-relativedate'), number_format_i18n($week_diff)).')'.$after;
        }
    } else {
        return $before.$the_date.$after;
    }
}


### Alternative To WordPress the_date().
function relative_post_the_date($d = '', $before = '', $after = '', $display_ago_only = false, $display = true) {
    $post = get_post();
    if(empty($post)) {
        if($display) {
            return;
        }
        return '';
    }
    if (empty($d)) {
        $the_date = mysql2date(get_option('date_format'), $post->post_date);
    } else {
        $the_date = mysql2date($d, $post->post_date);
    }
    if(current_datetime()->format('Y') != mysql2date('Y', $post->post_date, false)) {
        $output = $before.$the_date.$after;
    } else {
        $day_diff = ((int) current_datetime()->format('z') - (int) mysql2date('z', $post->post_date, false));
        if($day_diff < 0) { $day_diff = 32; }
        if($day_diff == 0) {
            $output = $before.__('Today', 'wp-relativedate').$after;
        } elseif($day_diff == 1) {
            $output = $before. __('Yesterday', 'wp-relativedate').$after;
        } elseif ($day_diff < 7) {
            if($display_ago_only) {
                $output = $before.sprintf(_n('%s day ago', '%s days ago', $day_diff, 'wp-relativedate'), number_format_i18n($day_diff)).$after;
            } else {
                $output = $before.$the_date.' ('.sprintf(_n('%s day ago', '%s days ago', $day_diff, 'wp-relativedate'), number_format_i18n($day_diff)).')'.$after;
            }
        } elseif ($day_diff < 31) {
            $week_diff = (int) ceil($day_diff/7);
            if($display_ago_only) {
                $output = $before.sprintf(_n('%s week ago', '%s weeks ago', $week_diff, 'wp-relativedate'), number_format_i18n($week_diff)).$after;
            } else {
                $output = $before.$the_date.' ('.sprintf(_n('%s week ago', '%s weeks ago', $week_diff, 'wp-relativedate'), number_format_i18n($week_diff)).')'.$after;
            }
        } else {
            $output = $before.$the_date.$after;
        }
    }
    if($display) {
        ### $before And $after Carry Theme-Supplied Markup, Exactly As They Do In
        ### Core's the_date() That This Tag Replaces. Escaping The Assembled String
        ### Renders <h2> As Literal Text On Every Post Using The Tag.
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    } else {
        return $output;
    }
}


### Function: Display Post Relative Time (Seconds Ago/Minutes Ago/Hours Ago)
add_filter('the_time', 'relative_post_time', 999);
function relative_post_time($current_timeformat, $display_ago_only = false) {
    $post = get_post();
    if(empty($post)) {
        return $current_timeformat;
    }
    return relativedate_time_ago($current_timeformat, $post->post_date, $display_ago_only);
}


### Function: Display Comment Relative Date (Today/Yesterday/Days Ago/Weeks Ago)
add_filter('get_comment_date', 'relative_comment_date', 999);
function relative_comment_date($current_dateformat, $display_ago_only = false) {
    ### get_comment_date() Is Routinely Called With An Explicit Comment ID From
    ### Outside The Comment Loop, Where There Is No $comment Global To Read.
    $comment = get_comment();
    if(empty($comment)) {
        return $current_dateformat;
    }
    $comment_date = $comment->comment_date;
    if(current_datetime()->format('Y') != mysql2date('Y', $comment_date, false)) {
        return $current_dateformat;
    }
    $day_diff = ((int) current_datetime()->format('z') - (int) mysql2date('z', $comment_date, false));
    if($day_diff < 0) { $day_diff = 32; }
    if($day_diff == 0) {
        return __('Today', 'wp-relativedate');
    } elseif($day_diff == 1) {
        return __('Yesterday', 'wp-relativedate');
    } elseif ($day_diff < 7) {
        if($display_ago_only) {
            return sprintf(_n('%s day ago', '%s days ago', $day_diff, 'wp-relativedate'), number_format_i18n($day_diff));
        } else {
            return $current_dateformat.' ('.sprintf(_n('%s day ago', '%s days ago', $day_diff, 'wp-relativedate'), number_format_i18n($day_diff)).')';
        }
    } elseif ($day_diff < 31) {
        $week_diff = (int) ceil($day_diff/7);
        if($display_ago_only) {
            return sprintf(_n('%s week ago', '%s weeks ago', $week_diff, 'wp-relativedate'), number_format_i18n($week_diff));
        } else {
            return $current_dateformat.' ('.sprintf(_n('%s week ago', '%s weeks ago', $week_diff, 'wp-relativedate'), number_format_i18n($week_diff)).')';
        }
    } else {
        return $current_dateformat;
    }
}


### Function: Display Comment  Relative Time (Seconds Ago/Minutes Ago/Hours Ago)
add_filter('get_comment_time', 'relative_comment_time', 999);
function relative_comment_time($current_timeformat, $display_ago_only = 0) {
    $comment = get_comment();
    if(empty($comment)) {
        return $current_timeformat;
    }
    return relativedate_time_ago($current_timeformat, $comment->comment_date, $display_ago_only);
}


### Function: Append The Seconds/Minutes/Hours Ago Suffix To A Same-Day Timestamp
function relativedate_time_ago($current_timeformat, $mysql_date, $display_ago_only = false) {
    $now = current_datetime();
    $then = date_create_immutable($mysql_date, wp_timezone());
    if(false === $then) {
        return $current_timeformat;
    }
    $time_diff = ($now->getTimestamp() - $then->getTimestamp());
    ### Content Dated Ahead Of The Server Clock -- A Scheduled Post Being Previewed,
    ### Or A Comment From A Client Whose Clock Runs Fast -- Has No "Ago" To Report.
    ### Every Branch Below Is A "Less Than", So A Negative Difference Used To Match
    ### The First One And Render "(-300 Seconds Ago)".
    if($then->format('Y-m-d') !== $now->format('Y-m-d') || $time_diff < 0) {
        return $current_timeformat;
    }
    $format_ago = '';
    if($time_diff < 60) {
        $format_ago = sprintf(_n('%s second ago', '%s seconds ago', $time_diff, 'wp-relativedate'), number_format_i18n($time_diff));
    } elseif ($time_diff < 3600) {
        $format_ago = sprintf(_n('%s minute ago', '%s minutes ago', intval($time_diff/60), 'wp-relativedate'), number_format_i18n(intval($time_diff/60)));
    } elseif ($time_diff < 86400) {
        $format_ago = sprintf(_n('%s hour ago', '%s hours ago', intval($time_diff/3600), 'wp-relativedate'), number_format_i18n(intval($time_diff/3600)));
    }
    if('' === $format_ago) {
        return $current_timeformat;
    }
    if($display_ago_only) {
        return $format_ago;
    } else {
        return $current_timeformat.' ('.$format_ago.')';
    }
}

### Function: Short Codes
add_shortcode( 'relativedate', 'relative_shortcode_date' );
function relative_shortcode_date( $atts ) {
    $attributes = shortcode_atts( array( 'date_format' => '', 'ago_only' => false ), $atts );
    ### filter_var() Rather Than A Cast: The README Documents ago_only="false",
    ### And (bool) "false" Is True, So The Documented Way Of Asking For The Full
    ### Date Has Always Produced The Opposite. FILTER_VALIDATE_BOOLEAN Rather Than
    ### wp_validate_boolean(), Which Only Special-Cases "false" And Would Still
    ### Read "no" And "off" As True.
    return esc_html( relative_post_the_date( $attributes['date_format'], '', '', filter_var( $attributes['ago_only'], FILTER_VALIDATE_BOOLEAN ), false ) );
}
add_shortcode( 'relativetime', 'relative_shortcode_time' );
function relative_shortcode_time( $atts ) {
    $post = get_post();
    if( empty( $post ) ) {
        return '';
    }
    $attributes = shortcode_atts( array( 'time_format' => '', 'ago_only' => false ), $atts );
    $time_format = $attributes['time_format'];
    if( empty( $attributes['time_format'] ) ) {
        $time_format = get_option( 'time_format' );
    }
    $current_time = mysql2date( $time_format, $post->post_date, false );

    return esc_html( relative_post_time( $current_time, filter_var( $attributes['ago_only'], FILTER_VALIDATE_BOOLEAN ) ) );
}
