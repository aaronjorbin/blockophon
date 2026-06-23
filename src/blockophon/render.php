<?php
/**
 * Renders the Blockophon colophon block on the front end.
 *
 * Available variables:
 *   $attributes (array): block attributes.
 *   $content    (string): inner content (unused — dynamic block).
 *   $block      (WP_Block): block instance.
 *
 * @package Blockophon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blockophon_data = blockophon_get_data();

$blockophon_attributes      = (array) ( $attributes ?? array() );
$blockophon_show_theme      = (bool) ( $blockophon_attributes['showTheme'] ?? true );
$blockophon_show_typography = (bool) ( $blockophon_attributes['showTypography'] ?? true );
$blockophon_use_ai_text     = (bool) ( $blockophon_attributes['useAiText'] ?? false );
$blockophon_custom_text     = (string) ( $blockophon_attributes['customText'] ?? '' );

$blockophon_ai_text       = null;
$blockophon_ai_error_html = '';
if ( '' === $blockophon_custom_text && $blockophon_use_ai_text ) {
	$blockophon_ai_result = blockophon_get_ai_text( $blockophon_data, $blockophon_attributes );
	if ( is_string( $blockophon_ai_result ) ) {
		$blockophon_ai_text = $blockophon_ai_result;
	} elseif ( is_wp_error( $blockophon_ai_result ) && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$blockophon_ai_error_html = '<p class="blockophon-ai-error" style="border:1px solid #d63638;padding:8px 12px;color:#d63638;border-radius:2px;">'
			. esc_html(
				sprintf(
					/* translators: %s: error message from AI connector */
					__( 'AI generation failed: %s', 'blockophon' ),
					$blockophon_ai_result->get_error_message()
				)
			)
			. '</p>';
	}
}

$blockophon_site_name = get_bloginfo( 'name' );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core function, always safe ?>>

<?php if ( '' !== $blockophon_ai_error_html ) : ?>
	<?php echo wp_kses_post( $blockophon_ai_error_html ); ?>
<?php endif; ?>
<?php if ( '' !== $blockophon_custom_text ) : ?>
	<?php echo wp_kses_post( $blockophon_custom_text ); ?>
<?php elseif ( $blockophon_ai_text ) : ?>
	<?php echo wp_kses_post( wpautop( $blockophon_ai_text ) ); ?>
<?php else : ?>
	<?php echo wp_kses_post( blockophon_get_prose_html( $blockophon_data, $blockophon_attributes ) ); ?>
<?php endif; ?>

</div>
