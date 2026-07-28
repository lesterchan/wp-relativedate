<?php
/**
 * The relative_post_the_date() tag, a drop-in replacement for the_date().
 *
 * @package WP-RelativeDate
 */

/**
 * @covers ::relative_post_the_date
 */
class Test_RelativeDate_Template_Tag extends WP_RelativeDate_TestCase {

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

	/**
	 * Capture what the tag echoes.
	 *
	 * @param mixed ...$args Arguments for relative_post_the_date().
	 * @return string
	 */
	protected function render( ...$args ) {
		ob_start();
		relative_post_the_date( ...$args );

		return ob_get_clean();
	}

	public function test_it_returns_today_for_a_post_from_today() {
		$this->make_post( 0 );

		$this->assertSame( 'Today', relative_post_the_date( '', '', '', false, false ) );
	}

	public function test_it_echoes_by_default() {
		$this->make_post( 0 );

		$this->assertSame( 'Today', $this->render() );
	}

	public function test_it_honours_a_custom_date_format() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text( 'Y-m-d' ) . ' (3 days ago)',
			relative_post_the_date( 'Y-m-d', '', '', false, false )
		);
	}

	public function test_it_can_render_the_day_count_on_its_own() {
		$this->skip_if_crosses_year( 3 * DAY_IN_SECONDS );
		$this->make_post( 3 * DAY_IN_SECONDS );

		$this->assertSame( '3 days ago', relative_post_the_date( '', '', '', true, false ) );
	}

	public function test_a_post_from_a_previous_year_keeps_its_plain_date() {
		$this->make_post( 400 * DAY_IN_SECONDS );

		$this->assertSame(
			$this->post_date_text(),
			relative_post_the_date( '', '', '', false, false )
		);
	}

	/**
	 * $before and $after exist to carry markup -- that is what they are for in
	 * core's the_date(), which this tag is documented as a drop-in for, and the
	 * README tells theme authors to swap one for the other.
	 *
	 * 1.51.1 wrapped the whole assembled string in esc_html() on the way out,
	 * so <h2> arrived at the browser as literal text on every post using the
	 * tag. The two shortcodes keep their escaping: their output is assembled
	 * from shortcode attributes and goes into post content, and neither takes
	 * a $before or an $after.
	 */
	public function test_before_and_after_reach_the_page_as_markup() {
		$this->make_post( 0 );

		$this->assertSame(
			'<h2 class="date">Today</h2>',
			$this->render( '', '<h2 class="date">', '</h2>' )
		);
	}

	public function test_before_and_after_are_returned_as_markup_too() {
		$this->make_post( 0 );

		$this->assertSame(
			'<h2 class="date">Today</h2>',
			relative_post_the_date( '', '<h2 class="date">', '</h2>', false, false )
		);
	}

	public function test_no_post_in_scope_renders_nothing() {
		unset( $GLOBALS['post'] );

		$this->assertSame( '', (string) relative_post_the_date( '', '', '', false, false ) );
	}
}
