<?php
/**
 * WordPress Core AI Client integration for Blockophon.
 *
 * Wraps wp_ai_client_prompt() (WordPress 7.0+) to generate colophon prose from
 * the structured site data. All functions gracefully degrade when no AI
 * connector is configured or when running on WordPress < 7.0.
 *
 * @package Blockophon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns true when a WordPress Core AI connector is configured and can generate text.
 *
 * Performs no API call; relies on wp_ai_client_prompt()->is_supported_for_text_generation()
 * which uses deterministic capability checks.
 *
 * @return bool
 */
function blockophon_is_ai_available(): bool {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP Core 7.0+ function.
	$available = wp_ai_client_prompt( 'test' )->is_supported_for_text_generation();
	return (bool) apply_filters( 'blockophon_is_ai_available', $available );
}

/**
 * Builds a plain-text factual summary of the site for use as an AI prompt.
 *
 * Respects the block's show* attributes so disabled sections are omitted from
 * the prompt and the AI does not mention them.
 *
 * @param array<string,mixed> $data       Structured site data from blockophon_get_data().
 * @param array<string,mixed> $attributes Block attributes.
 * @return string
 */
function blockophon_build_ai_prompt( array $data, array $attributes ): string {
	$show_theme      = (bool) ( $attributes['showTheme'] ?? true );
	$show_typography = (bool) ( $attributes['showTypography'] ?? true );
	$show_colors     = (bool) ( $attributes['showColors'] ?? true );
	$show_plugins    = (bool) ( $attributes['showPlugins'] ?? true );

	$lines = array(
		sprintf( 'Site name: %s', get_bloginfo( 'name' ) ),
	);

	if ( $show_theme && is_array( $data['theme'] ?? null ) ) {
		$t = (array) $data['theme'];
		if ( ! empty( $t['is_child'] ) ) {
			$lines[] = sprintf(
				'Theme: %s (child theme of %s by %s)',
				(string) ( $t['name'] ?? '' ),
				(string) ( $t['parent_name'] ?? '' ),
				(string) ( $t['parent_author'] ?? '' )
			);
		} else {
			$lines[] = sprintf(
				'Theme: %s by %s',
				(string) ( $t['name'] ?? '' ),
				(string) ( $t['author'] ?? '' )
			);
		}

		$c = is_array( $data['customizations'] ?? null ) ? (array) $data['customizations'] : array();
		if ( ! empty( $c['is_customized'] ) ) {
			$custom_parts = array();
			$tmpl_count   = (int) ( $c['modified_template_count'] ?? 0 );
			$part_count   = (int) ( $c['modified_part_count'] ?? 0 );
			if ( $tmpl_count > 0 ) {
				$custom_parts[] = sprintf(
					'%d modified %s',
					$tmpl_count,
					1 === $tmpl_count ? 'template' : 'templates'
				);
			}
			if ( $part_count > 0 ) {
				$custom_parts[] = sprintf(
					'%d modified template %s',
					$part_count,
					1 === $part_count ? 'part' : 'parts'
				);
			}
			if ( ! empty( $c['has_custom_global_styles'] ) ) {
				$custom_parts[] = 'custom global styles';
			}
			if ( ! empty( $c['has_custom_css'] ) ) {
				$custom_parts[] = 'custom CSS';
			}
			if ( ! empty( $custom_parts ) ) {
				$lines[] = sprintf( 'Theme customizations: %s', implode( ', ', $custom_parts ) );
			}
		}
	}

	if ( $show_typography ) {
		if ( ! empty( $data['heading_font'] ) ) {
			$lines[] = sprintf( 'Header font: %s', (string) $data['heading_font'] );
		}
		if ( ! empty( $data['body_font'] ) ) {
			$lines[] = sprintf( 'Body font: %s', (string) $data['body_font'] );
		}
	}

	if ( $show_colors && is_array( $data['colors'] ?? null ) && ! empty( $data['colors'] ) ) {
		$color_names = array_map(
			static function ( $color ): string {
				return is_array( $color ) ? (string) ( $color['name'] ?? '' ) : '';
			},
			(array) $data['colors']
		);
		$color_names = array_filter( $color_names );
		if ( ! empty( $color_names ) ) {
			$lines[] = sprintf( 'Color palette: %s', implode( ', ', $color_names ) );
		}
	}

	if ( $show_plugins ) {
		$plugins    = is_array( $data['plugins'] ?? null ) ? (array) $data['plugins'] : array();
		$mu_plugins = is_array( $data['mu_plugins'] ?? null ) ? (array) $data['mu_plugins'] : array();
		$drop_ins   = is_array( $data['drop_ins'] ?? null ) ? (array) $data['drop_ins'] : array();

		$plugin_count = count( $plugins );
		$mu_count     = count( $mu_plugins );
		$dropin_count = count( $drop_ins );

		$lines[] = sprintf(
			'Active plugins: %d %s, %d mu-%s, %d %s',
			$plugin_count,
			1 === $plugin_count ? 'plugin' : 'plugins',
			$mu_count,
			1 === $mu_count ? 'plugin' : 'plugins',
			$dropin_count,
			1 === $dropin_count ? 'drop-in' : 'drop-ins'
		);

		if ( ! empty( $plugins ) ) {
			$names = array_map(
				static function ( $plugin_data ): string {
					return is_array( $plugin_data ) ? (string) ( $plugin_data['Name'] ?? '' ) : '';
				},
				$plugins
			);
			$names = array_filter( $names );
			if ( ! empty( $names ) ) {
				$lines[] = sprintf( 'Plugin names: %s', implode( ', ', $names ) );
			}
		}
	}

	return implode( "\n", $lines );
}

/**
 * Calls the WordPress Core AI Client to generate a colophon paragraph from site data.
 *
 * Returns WP_Error when AI is unavailable or the generation fails.
 *
 * @param array<string,mixed> $data       Structured site data from blockophon_get_data().
 * @param array<string,mixed> $attributes Block attributes.
 * @return string|\WP_Error
 */
function blockophon_generate_ai_colophon( array $data, array $attributes ) {
	if ( ! blockophon_is_ai_available() ) {
		return new \WP_Error(
			'blockophon_no_ai',
			__( 'No AI connector is available.', 'blockophon' )
		);
	}

	$prompt = blockophon_build_ai_prompt( $data, $attributes );

	/**
	 * Short-circuits AI generation, returning a string result without calling the AI client.
	 * Return null to proceed with the default wp_ai_client_prompt() call.
	 *
	 * @param string|null $pre_result Replacement result, or null to use the AI client.
	 * @param string      $prompt     The constructed prompt string.
	 */
	$pre_result = apply_filters( 'blockophon_pre_generate_ai_colophon', null, $prompt );
	if ( null !== $pre_result ) {
		return (string) $pre_result;
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP Core 7.0+ function.
	return wp_ai_client_prompt( $prompt )
		->using_system_instruction(
			'You are writing a colophon for a WordPress site. Write 1-3 short, conversational paragraphs. Be factual and friendly. Do not add headings or bullet points.'
		)
		->generate_text();
}

/**
 * Returns AI-generated colophon text, reading from cache when available.
 *
 * On a successful generation the result is stored inside the existing
 * blockophon_colophon_data option so subsequent renders skip the API call.
 * The cached value is invalidated automatically by blockophon_refresh_cache()
 * which deletes the entire option.
 *
 * @param array<string,mixed> $data       Structured site data (possibly already cached).
 * @param array<string,mixed> $attributes Block attributes.
 * @return string|\WP_Error|null Generated text, WP_Error on AI failure, or null if no connector.
 */
function blockophon_get_ai_text( array $data, array $attributes ) {
	if ( isset( $data['ai_text'] ) && is_string( $data['ai_text'] ) && '' !== $data['ai_text'] ) {
		return $data['ai_text'];
	}

	$result = blockophon_generate_ai_colophon( $data, $attributes );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( '' === $result ) {
		return null;
	}

	$cached = get_option( 'blockophon_colophon_data', array() );
	if ( is_array( $cached ) ) {
		$cached['ai_text'] = $result;
		update_option( 'blockophon_colophon_data', $cached, false );
	}

	return $result;
}
