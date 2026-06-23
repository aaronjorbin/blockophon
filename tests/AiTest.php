<?php
/**
 * Integration tests for the WordPress Core AI Client integration.
 *
 * @package Blockophon
 */

/**
 * Tests blockophon_is_ai_available(), blockophon_generate_ai_colophon(),
 * blockophon_get_ai_text(), and blockophon_build_ai_prompt().
 */
class AiTest extends WP_UnitTestCase {

	/**
	 * Sample structured data array matching the shape returned by blockophon_get_data().
	 *
	 * @var array<string,mixed>
	 */
	private static array $sample_data;

	/**
	 * Default block attributes with all sections enabled.
	 *
	 * @var array<string,bool>
	 */
	private static array $all_on;

	/**
	 * Set up shared fixtures.
	 *
	 * @return void
	 */
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		self::$all_on = array(
			'showTheme'      => true,
			'showPlugins'    => true,
			'showColors'     => true,
			'showTypography' => true,
			'useAiText'      => true,
		);

		self::$sample_data = array(
			'theme'          => array(
				'name'              => 'Twenty Twenty-Five',
				'version'           => '1.0',
				'author'            => 'WordPress',
				'author_uri'        => 'https://wordpress.org',
				'theme_uri'         => 'https://wordpress.org',
				'is_child'          => false,
				'parent_name'       => null,
				'parent_author'     => null,
				'parent_author_uri' => null,
			),
			'customizations' => array(
				'modified_template_count'  => 0,
				'modified_part_count'      => 0,
				'modified_template_slugs'  => array(),
				'modified_part_slugs'      => array(),
				'has_custom_global_styles' => false,
				'has_custom_css'           => false,
				'is_customized'            => false,
			),
			'plugins'        => array(
				'hello.php' => array(
					'Name'    => 'Hello Dolly',
					'Version' => '1.7.2',
				),
			),
			'mu_plugins'     => array(),
			'drop_ins'       => array(),
			'colors'         => array(
				array(
					'name'  => 'Black',
					'slug'  => 'black',
					'color' => '#000000',
				),
				array(
					'name'  => 'White',
					'slug'  => 'white',
					'color' => '#ffffff',
				),
			),
			'font_families'  => array(),
			'font_sizes'     => array(),
			'body_font'      => 'Inter',
			'heading_font'   => 'Playfair Display',
		);
	}

	/**
	 * Clear the cached colophon option before each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( 'blockophon_colophon_data' );
	}

	/**
	 * Clear the cached colophon option after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( 'blockophon_colophon_data' );
		parent::tear_down();
	}

	// -----------------------------------------------------------------------
	// Availability checks
	// -----------------------------------------------------------------------

	/**
	 * Returns bool — wp_ai_client_prompt() is always present (WP 7.0+ required).
	 *
	 * @return void
	 */
	public function test_is_ai_available_returns_bool(): void {
		$this->assertIsBool( blockophon_is_ai_available() );
	}

	/**
	 * Returns WP_Error when AI is unavailable.
	 *
	 * @return void
	 */
	public function test_generate_ai_colophon_returns_wp_error_when_unavailable(): void {
		if ( blockophon_is_ai_available() ) {
			$this->markTestSkipped( 'AI connector is available — cannot test unavailability.' );
		}

		$result = blockophon_generate_ai_colophon( self::$sample_data, self::$all_on );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'blockophon_no_ai', $result->get_error_code() );
	}

	/**
	 * Calling blockophon_get_ai_text returns WP_Error when AI is unavailable.
	 *
	 * @return void
	 */
	public function test_get_ai_text_returns_wp_error_when_unavailable(): void {
		if ( blockophon_is_ai_available() ) {
			$this->markTestSkipped( 'AI connector is available — cannot test unavailability.' );
		}

		$result = blockophon_get_ai_text( self::$sample_data, self::$all_on );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * When availability is forced via filter, generate_ai_colophon reaches the API call lines.
	 * With no provider registered, the call returns a WP_Error (not an exception).
	 *
	 * @return void
	 */
	public function test_generate_ai_colophon_reaches_api_call_when_available(): void {
		add_filter( 'blockophon_is_ai_available', '__return_true', 99 );

		try {
			$result = blockophon_generate_ai_colophon( self::$sample_data, self::$all_on );
		} finally {
			remove_filter( 'blockophon_is_ai_available', '__return_true', 99 );
		}

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	/**
	 * Get_ai_text returns null when the AI client returns an empty string.
	 *
	 * @return void
	 */
	public function test_get_ai_text_returns_null_for_empty_ai_result(): void {
		$empty_callback = static function (): string {
			return '';
		};
		add_filter( 'blockophon_is_ai_available', '__return_true', 99 );
		add_filter( 'blockophon_pre_generate_ai_colophon', $empty_callback );

		try {
			$result = blockophon_get_ai_text( self::$sample_data, self::$all_on );
		} finally {
			remove_filter( 'blockophon_is_ai_available', '__return_true', 99 );
			remove_filter( 'blockophon_pre_generate_ai_colophon', $empty_callback );
		}

		$this->assertNull( $result );
	}

	/**
	 * Get_ai_text caches a successful AI result in the option and returns the string.
	 *
	 * @return void
	 */
	public function test_get_ai_text_caches_and_returns_successful_result(): void {
		$text_callback = static function (): string {
			return 'Generated colophon text.';
		};
		add_filter( 'blockophon_is_ai_available', '__return_true', 99 );
		add_filter( 'blockophon_pre_generate_ai_colophon', $text_callback );

		try {
			$result = blockophon_get_ai_text( self::$sample_data, self::$all_on );
		} finally {
			remove_filter( 'blockophon_is_ai_available', '__return_true', 99 );
			remove_filter( 'blockophon_pre_generate_ai_colophon', $text_callback );
		}

		$this->assertSame( 'Generated colophon text.', $result );
		$cached = get_option( 'blockophon_colophon_data', array() );
		$this->assertIsArray( $cached );
		$this->assertSame( 'Generated colophon text.', $cached['ai_text'] );
	}

	// -----------------------------------------------------------------------
	// Prompt builder
	// -----------------------------------------------------------------------

	/**
	 * Prompt always contains the site name.
	 *
	 * @return void
	 */
	public function test_prompt_contains_site_name(): void {
		$prompt = blockophon_build_ai_prompt( self::$sample_data, self::$all_on );

		$this->assertStringContainsString( get_bloginfo( 'name' ), $prompt );
	}

	/**
	 * Prompt includes theme name and author when showTheme is true.
	 *
	 * @return void
	 */
	public function test_prompt_includes_theme_when_show_theme(): void {
		$prompt = blockophon_build_ai_prompt( self::$sample_data, self::$all_on );

		$this->assertStringContainsString( 'Twenty Twenty-Five', $prompt );
		$this->assertStringContainsString( 'WordPress', $prompt );
	}

	/**
	 * Prompt omits theme info when showTheme is false.
	 *
	 * @return void
	 */
	public function test_prompt_excludes_theme_when_show_theme_false(): void {
		$attrs  = array_merge( self::$all_on, array( 'showTheme' => false ) );
		$prompt = blockophon_build_ai_prompt( self::$sample_data, $attrs );

		$this->assertStringNotContainsString( 'Theme:', $prompt );
		$this->assertStringNotContainsString( 'Twenty Twenty-Five', $prompt );
	}

	/**
	 * Prompt includes font names when showTypography is true.
	 *
	 * @return void
	 */
	public function test_prompt_includes_fonts_when_show_typography(): void {
		$prompt = blockophon_build_ai_prompt( self::$sample_data, self::$all_on );

		$this->assertStringContainsString( 'Inter', $prompt );
		$this->assertStringContainsString( 'Playfair Display', $prompt );
	}

	/**
	 * Prompt omits fonts when showTypography is false.
	 *
	 * @return void
	 */
	public function test_prompt_excludes_fonts_when_show_typography_false(): void {
		$attrs  = array_merge( self::$all_on, array( 'showTypography' => false ) );
		$prompt = blockophon_build_ai_prompt( self::$sample_data, $attrs );

		$this->assertStringNotContainsString( 'Inter', $prompt );
		$this->assertStringNotContainsString( 'Playfair Display', $prompt );
	}

	/**
	 * Colors and plugins are separate blocks; the prose prompt never includes them.
	 *
	 * @return void
	 */
	public function test_prompt_never_includes_colors_or_plugins(): void {
		$prompt = blockophon_build_ai_prompt( self::$sample_data, self::$all_on );

		$this->assertStringNotContainsString( 'Black', $prompt );
		$this->assertStringNotContainsString( 'White', $prompt );
		$this->assertStringNotContainsString( 'Active plugins:', $prompt );
		$this->assertStringNotContainsString( 'Hello Dolly', $prompt );
	}

	/**
	 * Prompt includes modified part count when template parts are customized.
	 *
	 * @return void
	 */
	public function test_prompt_includes_part_count_when_customized(): void {
		$data                                    = self::$sample_data;
		$data['customizations']['is_customized'] = true;
		$data['customizations']['modified_part_count']      = 2;
		$data['customizations']['modified_template_count']  = 0;
		$data['customizations']['has_custom_global_styles'] = false;
		$data['customizations']['has_custom_css']           = false;

		$prompt = blockophon_build_ai_prompt( $data, self::$all_on );

		$this->assertStringContainsString( '2 modified template', $prompt );
	}

	/**
	 * Prompt includes custom CSS note when has_custom_css is true.
	 *
	 * @return void
	 */
	public function test_prompt_includes_custom_css_when_customized(): void {
		$data                                     = self::$sample_data;
		$data['customizations']['is_customized']  = true;
		$data['customizations']['has_custom_css'] = true;
		$data['customizations']['modified_template_count']  = 0;
		$data['customizations']['modified_part_count']      = 0;
		$data['customizations']['has_custom_global_styles'] = false;

		$prompt = blockophon_build_ai_prompt( $data, self::$all_on );

		$this->assertStringContainsString( 'custom CSS', $prompt );
	}

	/**
	 * Prompt includes parent theme info for child themes.
	 *
	 * @return void
	 */
	public function test_prompt_includes_parent_theme_for_child_theme(): void {
		$child_data                           = self::$sample_data;
		$child_data['theme']['is_child']      = true;
		$child_data['theme']['name']          = 'My Child Theme';
		$child_data['theme']['parent_name']   = 'Twenty Twenty-Five';
		$child_data['theme']['parent_author'] = 'WordPress';

		$prompt = blockophon_build_ai_prompt( $child_data, self::$all_on );

		$this->assertStringContainsString( 'child theme of Twenty Twenty-Five', $prompt );
	}

	/**
	 * Prompt includes customization details when the theme is customized.
	 *
	 * @return void
	 */
	public function test_prompt_includes_customizations(): void {
		$custom_data                                    = self::$sample_data;
		$custom_data['customizations']['is_customized'] = true;
		$custom_data['customizations']['modified_template_count']  = 2;
		$custom_data['customizations']['has_custom_global_styles'] = true;

		$prompt = blockophon_build_ai_prompt( $custom_data, self::$all_on );

		$this->assertStringContainsString( 'Theme customizations:', $prompt );
		$this->assertStringContainsString( '2 modified templates', $prompt );
		$this->assertStringContainsString( 'custom global styles', $prompt );
	}

	// -----------------------------------------------------------------------
	// Cache
	// -----------------------------------------------------------------------

	/**
	 * Cached ai_text in the data array is returned without an API call.
	 *
	 * @return void
	 */
	public function test_get_ai_text_returns_cached_value(): void {
		$data_with_cache            = self::$sample_data;
		$data_with_cache['ai_text'] = 'Cached colophon text.';

		$result = blockophon_get_ai_text( $data_with_cache, self::$all_on );

		$this->assertSame( 'Cached colophon text.', $result );
	}
}
