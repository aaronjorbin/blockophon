<?php
/**
 * Integration tests for blockophon_get_customization_data() and
 * blockophon_detect_custom_global_styles().
 *
 * @package Blockophon
 */

/**
 * Tests the customization-detection layer.
 */
class CustomizationsTest extends WP_UnitTestCase {

	/**
	 * Active theme slug, resolved once per test run.
	 *
	 * @var string
	 */
	private static string $theme_slug;

	/**
	 * Resolve the active theme slug and ensure the wp_theme taxonomy term exists.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();
		self::$theme_slug = get_stylesheet();
		if ( ! term_exists( self::$theme_slug, 'wp_theme' ) ) {
			wp_insert_term( self::$theme_slug, 'wp_theme' );
		}
	}

	/**
	 * Remove the cached option and flush the object cache before each test.
	 *
	 * Flushing the object cache ensures that get_block_templates() does not
	 * return stale results from a previous test's WP_Query execution.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( 'blockophon_colophon_data' );
		wp_cache_flush();
	}

	/**
	 * Remove the cached option after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( 'blockophon_colophon_data' );
		parent::tear_down();
	}

	// -----------------------------------------------------------------------
	// blockophon_get_customization_data() — structure
	// -----------------------------------------------------------------------

	/**
	 * Return value must contain all expected keys with correct types.
	 *
	 * @return void
	 */
	public function test_returns_expected_keys(): void {
		$data = blockophon_get_customization_data();

		$this->assertArrayHasKey( 'modified_template_count', $data );
		$this->assertArrayHasKey( 'modified_part_count', $data );
		$this->assertArrayHasKey( 'modified_template_slugs', $data );
		$this->assertArrayHasKey( 'modified_part_slugs', $data );
		$this->assertArrayHasKey( 'has_custom_global_styles', $data );
		$this->assertArrayHasKey( 'has_custom_css', $data );
		$this->assertArrayHasKey( 'is_customized', $data );
	}

	/**
	 * On a fresh install with no user-authored templates, counts are zero.
	 *
	 * @return void
	 */
	public function test_no_customizations_on_clean_state(): void {
		$data = blockophon_get_customization_data();

		$this->assertSame( 0, $data['modified_template_count'] );
		$this->assertSame( 0, $data['modified_part_count'] );
		$this->assertFalse( $data['has_custom_global_styles'] );
		$this->assertFalse( $data['is_customized'] );
	}

	// -----------------------------------------------------------------------
	// Template detection
	// -----------------------------------------------------------------------

	/**
	 * A wp_template post that is not a WP suggestion increments the count.
	 *
	 * @return void
	 */
	public function test_user_template_is_counted(): void {
		$post_id = self::create_template_post( 'single' );
		$this->assertGreaterThan( 0, $post_id );

		$data = blockophon_get_customization_data();

		$this->assertGreaterThanOrEqual( 1, $data['modified_template_count'] );
		$this->assertTrue( $data['is_customized'] );
	}

	/**
	 * A wp_template post flagged as a WP suggestion is excluded from the count.
	 *
	 * @return void
	 */
	public function test_wp_suggestion_template_excluded(): void {
		$post_id = self::create_template_post( 'single-suggestion', array( 'is_wp_suggestion' => '1' ) );
		$this->assertGreaterThan( 0, $post_id );

		$data = blockophon_get_customization_data();

		$this->assertSame( 0, $data['modified_template_count'] );
		$this->assertFalse( $data['is_customized'] );
	}

	/**
	 * A user-authored template part increments the part count.
	 *
	 * @return void
	 */
	public function test_user_template_part_is_counted(): void {
		$post_id = self::create_template_post( 'header', array(), 'wp_template_part' );
		$this->assertGreaterThan( 0, $post_id );

		$data = blockophon_get_customization_data();

		$this->assertGreaterThanOrEqual( 1, $data['modified_part_count'] );
		$this->assertTrue( $data['is_customized'] );
	}

	/**
	 * Modified template slugs are included in the returned list.
	 *
	 * @return void
	 */
	public function test_modified_template_slugs_returned(): void {
		self::create_template_post( 'archive' );

		$data = blockophon_get_customization_data();

		$this->assertContains( 'archive', $data['modified_template_slugs'] );
	}

	// -----------------------------------------------------------------------
	// Global styles detection
	// -----------------------------------------------------------------------

	/**
	 * An empty wp_global_styles post does not trigger the flag.
	 *
	 * @return void
	 */
	public function test_empty_global_styles_not_detected(): void {
		self::create_global_styles_post( '{}' );

		$this->assertFalse( blockophon_detect_custom_global_styles() );
	}

	/**
	 * A wp_global_styles post with real content triggers the flag.
	 *
	 * @return void
	 */
	public function test_non_empty_global_styles_detected(): void {
		self::create_global_styles_post(
			(string) wp_json_encode( array( 'styles' => array( 'color' => array( 'text' => '#ff0000' ) ) ) )
		);

		$this->assertTrue( blockophon_detect_custom_global_styles() );
	}

	/**
	 * A global-styles post with whitespace-only content is not detected.
	 *
	 * @return void
	 */
	public function test_whitespace_only_global_styles_not_detected(): void {
		self::create_global_styles_post( '   ' );

		$this->assertFalse( blockophon_detect_custom_global_styles() );
	}

	/**
	 * A draft global-styles post with non-empty content is still detected.
	 *
	 * @return void
	 */
	public function test_draft_global_styles_detected(): void {
		self::create_global_styles_post(
			(string) wp_json_encode( array( 'styles' => array() ) ),
			'draft'
		);

		$this->assertTrue( blockophon_detect_custom_global_styles() );
	}

	/**
	 * Global styles flag flows through to is_customized.
	 *
	 * @return void
	 */
	public function test_global_styles_sets_is_customized(): void {
		self::create_global_styles_post(
			(string) wp_json_encode( array( 'settings' => array( 'color' => array( 'palette' => array() ) ) ) )
		);

		$data = blockophon_get_customization_data();

		$this->assertTrue( $data['has_custom_global_styles'] );
		$this->assertTrue( $data['is_customized'] );
	}

	// -----------------------------------------------------------------------
	// Cache invalidation
	// -----------------------------------------------------------------------

	/**
	 * Saving a wp_template post clears the cache.
	 *
	 * @return void
	 */
	public function test_cache_cleared_on_template_save(): void {
		blockophon_get_data();
		$this->assertNotFalse( get_option( 'blockophon_colophon_data' ) );

		self::create_template_post( 'single-cache' );

		$this->assertFalse( get_option( 'blockophon_colophon_data' ) );
	}

	/**
	 * Saving a wp_global_styles post clears the cache.
	 *
	 * @return void
	 */
	public function test_cache_cleared_on_global_styles_save(): void {
		blockophon_get_data();
		$this->assertNotFalse( get_option( 'blockophon_colophon_data' ) );

		self::create_global_styles_post(
			(string) wp_json_encode( array( 'styles' => array() ) )
		);

		$this->assertFalse( get_option( 'blockophon_colophon_data' ) );
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Inserts a wp_template (or wp_template_part) post with the current theme taxonomy.
	 *
	 * @param string               $slug      Post slug.
	 * @param array<string,string> $meta      Optional post meta.
	 * @param string               $post_type Post type ('wp_template' or 'wp_template_part').
	 * @return int Inserted post ID, or 0 on failure.
	 */
	private static function create_template_post( string $slug, array $meta = array(), string $post_type = 'wp_template' ): int {
		$post_id = (int) wp_insert_post(
			array(
				'post_type'    => $post_type,
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_content' => '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->',
				'meta_input'   => $meta,
			)
		);

		if ( $post_id > 0 ) {
			wp_set_post_terms( $post_id, self::$theme_slug, 'wp_theme' );
		}

		return $post_id;
	}

	/**
	 * Inserts a wp_global_styles post.
	 *
	 * @param string $content     JSON content for the post.
	 * @param string $post_status Post status (default 'publish').
	 * @return int Inserted post ID, or 0 on failure.
	 */
	private static function create_global_styles_post( string $content, string $post_status = 'publish' ): int {
		return (int) wp_insert_post(
			array(
				'post_type'    => 'wp_global_styles',
				'post_status'  => $post_status,
				'post_content' => $content,
			)
		);
	}
}
