<?php
/**
 * Release invariants, asserted against the source rather than at runtime.
 *
 * These are the things a restructuring quietly breaks and nothing notices until
 * a release fails its pre-flight months later: a header field that drifted out
 * of the canonical order, a new directory shipped without its silence guard, a
 * version bumped in one file of three.
 *
 * @package WP-RelativeDate
 */

/**
 * @coversNothing
 */
class Test_RelativeDate_Source extends WP_UnitTestCase {

	const VERSION = '2.0.0';

	/**
	 * The main plugin file.
	 *
	 * @return string
	 */
	protected function plugin_file() {
		return relativedate_test_read( 'wp-relativedate.php' );
	}

	/**
	 * The readme.
	 *
	 * @return string
	 */
	protected function readme() {
		return relativedate_test_read( 'README.md' );
	}

	/**
	 * Every directory in the repo that holds at least one PHP file.
	 *
	 * @return string[] Absolute paths, plugin root included.
	 */
	protected function php_directories() {
		$root  = dirname( __DIR__ );
		$found = array();

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			$path = $file->getPathname();

			// vendor/ and node_modules/ are not ours and never ship.
			if ( false !== strpos( $path, '/vendor/' ) || false !== strpos( $path, '/node_modules/' ) ) {
				continue;
			}

			if ( 'php' === strtolower( $file->getExtension() ) ) {
				$found[ dirname( $path ) ] = true;
			}
		}

		return array_keys( $found );
	}

	public function test_the_version_agrees_across_all_three_places() {
		$this->assertStringContainsString( ' * Version: ' . self::VERSION, $this->plugin_file() );
		$this->assertStringContainsString( "define( 'WP_RELATIVEDATE_VERSION', '" . self::VERSION . "' );", $this->plugin_file() );
		$this->assertStringContainsString( 'Stable tag: ' . self::VERSION, $this->readme() );
	}

	public function test_the_changelog_has_a_section_for_this_version() {
		$this->assertStringContainsString( '### ' . self::VERSION . "\n", $this->readme() );
	}

	/**
	 * The order is neither alphabetical nor intuitive -- Requires at least and
	 * Requires PHP sit before Author -- so it is copied, never composed.
	 */
	public function test_the_plugin_header_fields_are_in_the_canonical_order() {
		$expected = array(
			'Plugin Name',
			'Plugin URI',
			'Description',
			'Version',
			'Requires at least',
			'Requires PHP',
			'Author',
			'Author URI',
			'License',
			'License URI',
			'Text Domain',
			'Domain Path',
		);

		preg_match( '#^<\?php\s*/\*\*(.+?)\*/#s', $this->plugin_file(), $matches );
		$this->assertNotEmpty( $matches, 'The plugin file must open with a docblock header.' );

		preg_match_all( '/^\s*\*\s*([A-Z][A-Za-z ]*?):\s/m', $matches[1], $fields );

		$this->assertSame( $expected, $fields[1] );
	}

	/**
	 * The readme order differs from the PHP one on purpose: Requires PHP comes
	 * after Stable tag here. They are not to be harmonised.
	 */
	public function test_the_readme_header_fields_are_in_the_canonical_order() {
		$expected = array(
			'Contributors',
			'Donate link',
			'Tags',
			'Requires at least',
			'Tested up to',
			'Stable tag',
			'Requires PHP',
			'License',
			'License URI',
		);

		$header = substr( $this->readme(), 0, (int) strpos( $this->readme(), "\n\n" ) );

		preg_match_all( '/^([A-Z][A-Za-z ]*?):\s/m', $header, $fields );

		$this->assertSame( $expected, $fields[1] );
	}

	public function test_both_files_declare_the_same_floors() {
		$this->assertStringContainsString( ' * Requires at least: 6.0', $this->plugin_file() );
		$this->assertStringContainsString( ' * Requires PHP: 7.4', $this->plugin_file() );
		$this->assertStringContainsString( 'Requires at least: 6.0', $this->readme() );
		$this->assertStringContainsString( 'Requires PHP: 7.4', $this->readme() );
	}

	/**
	 * Header lines need two trailing spaces to render as separate lines.
	 *
	 * Markdown joins consecutive lines into one paragraph unless each is ended
	 * with a hard line break, so a missing pair renders as
	 * "License: GPLv2 or later License URI: https://..." on GitHub. It is
	 * invisible in the source and in a diff, which is exactly why it wants a
	 * test. The last line needs none, having nothing after it to run into.
	 */
	public function test_every_readme_header_line_but_the_last_ends_in_a_hard_break() {
		$header = substr( $this->readme(), 0, (int) strpos( $this->readme(), "\n\n" ) );
		$lines  = explode( "\n", $header );

		// The first line is the "# WP-RelativeDate" heading, not a header field.
		$fields = array_slice( $lines, 1 );
		$last   = array_pop( $fields );

		foreach ( $fields as $line ) {
			$this->assertStringEndsWith(
				'  ',
				$line,
				"Needs two trailing spaces or it merges with the line below: {$line}"
			);
		}

		$this->assertStringStartsWith( 'License URI:', $last );
	}

	public function test_the_readme_lists_at_most_five_tags() {
		preg_match( '/^Tags:\s*(.+?)\s*$/m', $this->readme(), $matches );

		$this->assertNotEmpty( $matches, 'The readme must carry a Tags line.' );
		$this->assertLessThanOrEqual( 5, count( explode( ',', $matches[1] ) ) );
	}

	/**
	 * Bare versions: "### 2.0.0", never "### Version 2.0.0".
	 */
	public function test_every_changelog_heading_is_a_bare_version() {
		$this->assertSame( 0, preg_match( '/^### Version /m', $this->readme() ) );
	}

	/**
	 * The catalogue comes from translate.wordpress.org, and since WP 6.7 calling
	 * load_plugin_textdomain() this early trips _doing_it_wrong.
	 */
	public function test_the_plugin_does_not_load_its_own_textdomain() {
		$this->assertStringNotContainsString( 'load_plugin_textdomain', relativedate_test_source_code() );
	}

	public function test_the_readme_has_no_translations_section() {
		$this->assertSame( 0, preg_match( '/^### Translations/m', $this->readme() ) );
	}

	/**
	 * The old forums.lesterchan.net is gone, and the rest of these had drifted
	 * to http over twenty years. Code spans are exempt: they document input.
	 */
	public function test_no_insecure_or_dead_links_remain() {
		$readme = preg_replace( '/`[^`]*`/', '', $this->readme() );

		$this->assertSame( 0, preg_match( '#http://#', $readme ), 'Every readme link must use https.' );
		$this->assertSame( 0, preg_match( '#http://#', $this->plugin_file() ) );
		$this->assertStringNotContainsString( 'forums.lesterchan.net', $readme );
	}

	public function test_every_directory_holding_php_has_a_silence_guard() {
		foreach ( $this->php_directories() as $directory ) {
			$this->assertFileExists(
				$directory . '/index.php',
				"{$directory} ships PHP and so needs an index.php silence guard."
			);
		}
	}

	public function test_the_guards_use_the_docblock_form() {
		foreach ( $this->php_directories() as $directory ) {
			$guard = (string) file_get_contents( $directory . '/index.php' );

			// phpcbf cannot fix the one-line "// Silence is golden." form.
			$this->assertStringContainsString( '/**', $guard, "{$directory}/index.php must use the docblock form." );
			$this->assertStringContainsString( 'Silence is golden.', $guard );
		}
	}

	/**
	 * The translatable strings, frozen at their 1.51.1 spelling.
	 *
	 * WP-RelativeDate has translations on translate.wordpress.org going back
	 * years, and a msgid is a byte-for-byte lookup key: changing one character,
	 * or letting phpcbf renumber a placeholder from %s to %1$s, silently drops
	 * every translation of that string and falls back to English.
	 *
	 * A restructuring is exactly when that happens, so the whole set is pinned
	 * here rather than trusted. Adding a genuinely new string means adding it to
	 * this list deliberately; changing an existing one should be a decision
	 * about abandoning its translations, not a side effect of a refactor.
	 */
	public function test_no_translatable_string_has_changed_since_1_51_1() {
		$expected = array(
			'%s day ago',
			'%s days ago',
			'%s hour ago',
			'%s hours ago',
			'%s minute ago',
			'%s minutes ago',
			'%s second ago',
			'%s seconds ago',
			'%s week ago',
			'%s weeks ago',
			'Today',
			'Yesterday',
		);

		$code = relativedate_test_source_code();

		preg_match_all( "/(?:__|_n|_x|esc_html__|esc_attr__)\(\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $singles );
		preg_match_all( "/_n\(\s*'(?:[^'\\\\]|\\\\.)*'\s*,\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $plurals );

		$found = array_unique( array_merge( $singles[1], $plurals[1] ) );
		sort( $found );

		$this->assertSame( $expected, $found );
	}

	/**
	 * Every one of them must carry the plugin's own text domain.
	 */
	public function test_every_translation_call_uses_the_plugin_text_domain() {
		$code = relativedate_test_source_code();

		preg_match_all( '/(?:__|_n)\((.*?)\);/s', $code, $calls );

		foreach ( $calls[1] as $arguments ) {
			$this->assertStringContainsString(
				"'wp-relativedate'",
				$arguments,
				"A translation call is missing the text domain: {$arguments}"
			);
		}
	}

	public function test_the_gpl_licence_is_shipped() {
		$licence = relativedate_test_read( 'LICENSE' );

		$this->assertStringContainsString( 'GNU GENERAL PUBLIC LICENSE', $licence );
		$this->assertStringContainsString( 'Version 2, June 1991', $licence );
	}

	/**
	 * The catalogue is built by translate.wordpress.org, and Travis has been
	 * dead for these repos for years.
	 */
	public function test_no_abandoned_build_or_translation_artefacts_ship() {
		$root = dirname( __DIR__ );

		$this->assertFileDoesNotExist( $root . '/.travis.yml' );
		$this->assertDirectoryDoesNotExist( $root . '/languages' );

		foreach ( array( 'pot', 'po', 'mo' ) as $extension ) {
			$this->assertSame(
				array(),
				(array) glob( $root . '/*.' . $extension ),
				"No .{$extension} files: translate.wordpress.org builds the catalogue."
			);
		}
	}
}
