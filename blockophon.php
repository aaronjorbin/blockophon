<?php
/**
 * Plugin Name:       Blockophon
 * Description:       Colophon block
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      7.4
 * Author:            Aaron Jorbin
 * Author URI:        https://aaron.jorb.in
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
 * Renders the prose HTML (theme phrase + typography phrase) for the main colophon block.
 *
 * Used both by render.php (the non-AI, non-custom fallback) and the /prose REST endpoint
 * (which powers "Convert to editable text" in the editor).
 *
 * @param array<string,mixed> $data       Structured site data from blockophon_get_data().
 * @param array<string,mixed> $attributes Block attributes; only showTheme and showTypography are read.
 * @return string HTML string, safe for wp_kses_post output.
 */
function blockophon_get_prose_html( array $data, array $attributes ): string {
	$show_theme      = (bool) ( $attributes['showTheme'] ?? true );
	$show_typography = (bool) ( $attributes['showTypography'] ?? true );
	$site_name       = get_bloginfo( 'name' );
	$output          = '';

	if ( $show_theme && is_array( $data['theme'] ?? null ) ) {
		$t = (array) $data['theme'];

		if ( ! empty( $t['is_child'] ) ) {
			$blockophon_author_link = ! empty( $t['parent_author_uri'] )
				? '<a href="' . esc_url( (string) $t['parent_author_uri'] ) . '">' . esc_html( (string) $t['parent_author'] ) . '</a>'
				: esc_html( (string) $t['parent_author'] );
			$theme_phrase           = sprintf(
				/* translators: 1: site name  2: parent theme name  3: linked author name */
				__( "%1\$s's design started as %2\$s by %3\$s, and has been customized for this site.", 'blockophon' ),
				esc_html( $site_name ),
				esc_html( (string) $t['parent_name'] ),
				$blockophon_author_link
			);
		} else {
			$blockophon_author_link = ! empty( $t['author_uri'] )
				? '<a href="' . esc_url( (string) $t['author_uri'] ) . '">' . esc_html( (string) $t['author'] ) . '</a>'
				: esc_html( (string) $t['author'] );
			$theme_phrase           = sprintf(
				/* translators: 1: site name  2: theme name  3: linked author name */
				__( "%1\$s's design is %2\$s by %3\$s.", 'blockophon' ),
				esc_html( $site_name ),
				esc_html( (string) $t['name'] ),
				$blockophon_author_link
			);
		}

		$combined_phrase = $theme_phrase;
		$c               = is_array( $data['customizations'] ?? null ) ? (array) $data['customizations'] : array();

		if ( ! empty( $c['is_customized'] ) ) {
			$custom_items = array();

			if ( ! empty( $c['modified_template_count'] ) && $c['modified_template_count'] > 0 ) {
				$custom_items[] = sprintf(
					/* translators: %s: number of templates */
					_n( 'edits to %s template', 'edits to %s templates', (int) $c['modified_template_count'], 'blockophon' ),
					number_format_i18n( (int) $c['modified_template_count'] )
				);
			}

			if ( ! empty( $c['modified_part_count'] ) && $c['modified_part_count'] > 0 ) {
				$custom_items[] = sprintf(
					/* translators: %s: number of template parts */
					_n( '%s template part', '%s template parts', (int) $c['modified_part_count'], 'blockophon' ),
					number_format_i18n( (int) $c['modified_part_count'] )
				);
			}

			if ( ! empty( $c['has_custom_global_styles'] ) ) {
				$custom_items[] = __( 'adjusted global styles', 'blockophon' );
			}

			if ( ! empty( $c['has_custom_css'] ) ) {
				$custom_items[] = __( 'custom CSS', 'blockophon' );
			}

			if ( ! empty( $custom_items ) ) {
				if ( 1 === count( $custom_items ) ) {
					$custom_list = $custom_items[0];
				} elseif ( 2 === count( $custom_items ) ) {
					/* translators: joins two items in a list */
					$custom_list = $custom_items[0] . __( ' and ', 'blockophon' ) . $custom_items[1];
				} else {
					$last = array_pop( $custom_items );
					/* translators: Oxford-comma conjunction before final list item */
					$custom_list = implode( ', ', $custom_items ) . __( ', and ', 'blockophon' ) . $last;
				}
				/* translators: %s: comma-separated list of customization types */
				$custom_phrase    = sprintf( __( 'It features %s.', 'blockophon' ), $custom_list );
				$combined_phrase .= ' ' . esc_html( $custom_phrase );
			}
		}

		$output .= '<p>' . wp_kses_post( $combined_phrase ) . '</p>';
	}

	if ( $show_typography && ( ! empty( $data['heading_font'] ) || ! empty( $data['body_font'] ) ) ) {
		$parts = array();
		if ( ! empty( $data['heading_font'] ) ) {
			/* translators: %s: font name */
			$parts[] = sprintf( __( 'Headers are set in %s', 'blockophon' ), esc_html( (string) $data['heading_font'] ) );
		}
		if ( ! empty( $data['body_font'] ) ) {
			/* translators: %s: font name */
			$parts[] = sprintf( __( 'body copy is set in %s', 'blockophon' ), esc_html( (string) $data['body_font'] ) );
		}
		$output .= '<p>' . implode( __( ' and ', 'blockophon' ), $parts ) . '.</p>';
	}

	return $output;
}

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

/**
 * Registers Blockophon REST API routes.
 *
 * @return void
 */
function blockophon_register_rest_routes(): void {
	register_rest_route(
		'blockophon/v1',
		'/ai-text',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => static function (): WP_REST_Response {
				$blockophon_cached  = get_option( 'blockophon_colophon_data', array() );
				$blockophon_ai_text = is_array( $blockophon_cached ) && isset( $blockophon_cached['ai_text'] )
					? (string) $blockophon_cached['ai_text']
					: '';
				return new WP_REST_Response( array( 'text' => $blockophon_ai_text ) );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_rest_route(
		'blockophon/v1',
		'/prose',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => static function ( WP_REST_Request $request ): WP_REST_Response {
				$attributes = array(
					'showTheme'      => (bool) $request->get_param( 'show_theme' ),
					'showTypography' => (bool) $request->get_param( 'show_typography' ),
				);
				$html       = blockophon_get_prose_html( blockophon_get_data(), $attributes );
				return new WP_REST_Response( array( 'html' => $html ) );
			},
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'show_theme'      => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'show_typography' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'blockophon_register_rest_routes' );
