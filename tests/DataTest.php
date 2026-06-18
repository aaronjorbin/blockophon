<?php
/**
 * Integration tests for blockophon_get_data().
 *
 * Run via: npm run test:php
 *
 * @package Blockophon
 */

/**
 * Tests the data layer.
 */
class DataTest extends WP_UnitTestCase {

	/**
	 * Delete cached option before each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( 'blockophon_colophon_data' );
	}

	/**
	 * Delete cached option after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( 'blockophon_colophon_data' );
		parent::tear_down();
	}

	/**
	 * Data returned should be an array.
	 *
	 * @return void
	 */
	public function test_get_data_returns_array(): void {
		$data = blockophon_get_data();
		$this->assertIsArray( $data );
	}

	/**
	 * Returned array should contain all expected top-level keys.
	 *
	 * @return void
	 */
	public function test_get_data_has_required_keys(): void {
		$data = blockophon_get_data();
		$this->assertArrayHasKey( 'theme', $data );
		$this->assertArrayHasKey( 'plugins', $data );
		$this->assertArrayHasKey( 'mu_plugins', $data );
		$this->assertArrayHasKey( 'drop_ins', $data );
		$this->assertArrayHasKey( 'colors', $data );
		$this->assertArrayHasKey( 'font_families', $data );
		$this->assertArrayHasKey( 'body_font', $data );
		$this->assertArrayHasKey( 'heading_font', $data );
	}

	/**
	 * Theme sub-array should include name, version, author, and is_child flag.
	 *
	 * @return void
	 */
	public function test_theme_data_has_required_keys(): void {
		$data  = blockophon_get_data();
		$theme = $data['theme'];
		$this->assertArrayHasKey( 'name', $theme );
		$this->assertArrayHasKey( 'version', $theme );
		$this->assertArrayHasKey( 'author', $theme );
		$this->assertArrayHasKey( 'is_child', $theme );
	}

	/**
	 * A second call should return the value stored in the option (cache hit).
	 *
	 * @return void
	 */
	public function test_data_is_cached_on_second_call(): void {
		blockophon_get_data();
		$this->assertNotFalse( get_option( 'blockophon_colophon_data' ) );

		$cached = get_option( 'blockophon_colophon_data' );
		$data2  = blockophon_get_data();
		$this->assertSame( $cached, $data2, 'Second call should return cached data.' );
	}

	// -----------------------------------------------------------------------
	// blockophon_resolve_font_name()
	// -----------------------------------------------------------------------

	/**
	 * Returns the human-readable name when the CSS var slug matches a font family.
	 *
	 * @return void
	 */
	public function test_resolve_font_name_returns_matching_name(): void {
		$families = array(
			array(
				'slug' => 'inter',
				'name' => 'Inter',
			),
			array(
				'slug' => 'playfair',
				'name' => 'Playfair Display',
			),
		);

		$result = blockophon_resolve_font_name( 'var(--wp--preset--font-family--inter)', $families );

		$this->assertSame( 'Inter', $result );
	}

	/**
	 * Returns null when the CSS var slug has no matching font family.
	 *
	 * @return void
	 */
	public function test_resolve_font_name_returns_null_when_no_slug_match(): void {
		$families = array(
			array(
				'slug' => 'inter',
				'name' => 'Inter',
			),
		);

		$result = blockophon_resolve_font_name( 'var(--wp--preset--font-family--unknown-font)', $families );

		$this->assertNull( $result );
	}

	/**
	 * The backing option must not be autoloaded.
	 *
	 * @return void
	 */
	public function test_option_is_not_autoloaded(): void {
		blockophon_get_data();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$autoload = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT autoload FROM {$wpdb->options} WHERE option_name = %s",
				'blockophon_colophon_data'
			)
		);

		$this->assertContains( $autoload, array( 'off', 'no', '0' ), 'Option must not be autoloaded.' );
	}
}
