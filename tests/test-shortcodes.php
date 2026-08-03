<?php
/**
 * The [relativedate] and [relativetime] shortcodes.
 *
 * @package WP-RelativeDate
 */

/**
 * @covers WP_RelativeDate::shortcode_date
 * @covers WP_RelativeDate::shortcode_time
 */
class WP_RelativeDate_Shortcodes_Test extends WP_RelativeDate_TestCase {

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

	public function test_both_shortcodes_are_registered() {
		$this->assertTrue( shortcode_exists( 'relativedate' ), 'The relativedate shortcode is registered.' );
		$this->assertTrue( shortcode_exists( 'relativetime' ), 'The relativetime shortcode is registered.' );
	}

	public function test_relativedate_renders_the_relative_date() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (3 days ago)',
			do_shortcode( '[relativedate]' )
		);
	}

	public function test_relativedate_honours_date_format() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text( 'Y-m-d' ) . ' (3 days ago)',
			do_shortcode( '[relativedate date_format="Y-m-d"]' )
		);
	}

	public function test_relativedate_ago_only_true_drops_the_date() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame( '3 days ago', do_shortcode( '[relativedate ago_only="true"]' ) );
	}

	/**
	 * The ago_only="false" spelling is what the README has documented since
	 * 1.51, and it never worked: the attribute arrived as the string "false",
	 * which a (bool) cast reports as true, so the documented way of asking for
	 * the full date produced the opposite. Every other WordPress shortcode reads
	 * the way wp_validate_boolean() does, and now so does this one.
	 */
	public function test_relativedate_ago_only_false_keeps_the_date() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (3 days ago)',
			do_shortcode( '[relativedate ago_only="false"]' )
		);
	}

	public function test_relativedate_ago_only_accepts_the_other_falsey_spellings() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$expected = $this->post_date_text() . ' (3 days ago)';

		$this->assertSame( $expected, do_shortcode( '[relativedate ago_only="0"]' ) );
		$this->assertSame( $expected, do_shortcode( '[relativedate ago_only="no"]' ) );
		$this->assertSame( $expected, do_shortcode( '[relativedate ago_only="off"]' ) );
	}

	public function test_relativedate_defaults_to_showing_the_date() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text() . ' (3 days ago)',
			do_shortcode( '[relativedate]' )
		);
	}

	public function test_relativetime_renders_the_relative_time() {
		$this->make_post( 330 );

		$this->assertSame(
			$this->post_time_text() . ' (5 minutes ago)',
			do_shortcode( '[relativetime]' )
		);
	}

	public function test_relativetime_honours_time_format() {
		$this->make_post( 330 );

		$this->assertSame(
			$this->post_time_text( 'H:i' ) . ' (5 minutes ago)',
			do_shortcode( '[relativetime time_format="H:i"]' )
		);
	}

	public function test_relativetime_ago_only_true_drops_the_time() {
		$this->make_post( 330 );

		$this->assertSame( '5 minutes ago', do_shortcode( '[relativetime ago_only="true"]' ) );
	}

	public function test_relativetime_ago_only_false_keeps_the_time() {
		$this->make_post( 330 );

		$this->assertSame(
			$this->post_time_text() . ' (5 minutes ago)',
			do_shortcode( '[relativetime ago_only="false"]' )
		);
	}

	public function test_shortcodes_render_nothing_with_no_post_in_scope() {
		unset( $GLOBALS['post'] );

		$this->assertSame( '', do_shortcode( '[relativedate]' ) );
		$this->assertSame( '', do_shortcode( '[relativetime]' ) );
	}
}
