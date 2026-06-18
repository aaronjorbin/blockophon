<?php
/**
 * Plugin Name:       Blockophon
 * Description:       Colophon block
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blockophon
 *
 * @package Blockophon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/ai.php';

/**
 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
 * based on the registered block metadata. Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 *
 * @return void
 */
function blockophon_blockophon_block_init(): void {
	wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
}
add_action( 'init', 'blockophon_blockophon_block_init' );

/**
 * Passes AI availability state to the block editor via an inline script.
 *
 * @return void
 */
function blockophon_enqueue_editor_data(): void {
	if ( ! wp_script_is( 'blockophon-blockophon-editor-script', 'registered' ) ) {
		return;
	}
	wp_add_inline_script(
		'blockophon-blockophon-editor-script',
		'var blockophonEditorData = ' . wp_json_encode( array( 'aiAvailable' => blockophon_is_ai_available() ) ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'blockophon_enqueue_editor_data' );
