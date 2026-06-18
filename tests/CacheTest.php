<?php
/**
 * Integration tests for cache invalidation in Blockophon.
 *
 * @package Blockophon
 */

/**
 * Tests cache-invalidation hooks.
 */
class CacheTest extends WP_UnitTestCase {

	/**
	 * Warm the cache before each test.
	 *
	 * Load WordPress admin upgrader classes so that the upgrader_process_complete
	 * action can fire without missing-class errors from WP's own hooks.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( 'Language_Pack_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-language-pack-upgrader.php';
		}

		blockophon_get_data();
		$this->assertNotFalse( get_option( 'blockophon_colophon_data' ), 'Cache should be warm before each test.' );
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
	 * Cache is cleared when the active theme changes.
	 *
	 * @return void
	 */
	public function test_switch_theme_clears_cache(): void {
		do_action( 'switch_theme' );
		$this->assertFalse( get_option( 'blockophon_colophon_data' ), 'Cache should be cleared on switch_theme.' );
	}

	/**
	 * Cache is cleared when a plugin is activated.
	 *
	 * @return void
	 */
	public function test_activated_plugin_clears_cache(): void {
		do_action( 'activated_plugin', 'some-plugin/some-plugin.php' );
		$this->assertFalse( get_option( 'blockophon_colophon_data' ), 'Cache should be cleared on activated_plugin.' );
	}

	/**
	 * Cache is cleared when a plugin is deactivated.
	 *
	 * @return void
	 */
	public function test_deactivated_plugin_clears_cache(): void {
		do_action( 'deactivated_plugin', 'some-plugin/some-plugin.php' );
		$this->assertFalse( get_option( 'blockophon_colophon_data' ), 'Cache should be cleared on deactivated_plugin.' );
	}

	/**
	 * Cache is cleared after a plugin upgrade completes.
	 *
	 * @return void
	 */
	public function test_upgrader_process_complete_clears_cache_for_plugin_upgrade(): void {
		do_action( 'upgrader_process_complete', new stdClass(), array( 'type' => 'plugin' ) );
		$this->assertFalse( get_option( 'blockophon_colophon_data' ), 'Cache should be cleared after plugin upgrade.' );
	}

	/**
	 * Cache is cleared after a theme upgrade completes.
	 *
	 * @return void
	 */
	public function test_upgrader_process_complete_clears_cache_for_theme_upgrade(): void {
		do_action( 'upgrader_process_complete', new stdClass(), array( 'type' => 'theme' ) );
		$this->assertFalse( get_option( 'blockophon_colophon_data' ), 'Cache should be cleared after theme upgrade.' );
	}

	/**
	 * Cache should survive a core upgrade (only plugin/theme upgrades invalidate it).
	 *
	 * @return void
	 */
	public function test_upgrader_process_complete_does_not_clear_for_core_upgrade(): void {
		do_action( 'upgrader_process_complete', new stdClass(), array( 'type' => 'core' ) );
		$this->assertNotFalse( get_option( 'blockophon_colophon_data' ), 'Cache should survive a core upgrade.' );
	}
}
