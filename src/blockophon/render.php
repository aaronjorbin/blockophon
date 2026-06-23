<?php
/**
 * Renders the Blockophon colophon block on the front end.
 *
 * When isConverted is false, the prose (theme + typography or AI text) is
 * output before the inner blocks content ($content). When isConverted is
 * true the user has replaced the prose with editable paragraph blocks inside
 * the container, so only $content is rendered.
 *
 * Available variables:
 *   $attributes (array): block attributes.
 *   $content    (string): inner blocks rendered HTML.
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
$blockophon_is_converted    = (bool) ( $blockophon_attributes['isConverted'] ?? false );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core function, always safe ?>>

<?php if ( ! $blockophon_is_converted ) : ?>
	<?php
	$blockophon_ai_error_html = '';
	$blockophon_ai_text       = null;

	if ( $blockophon_use_ai_text ) {
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

	if ( '' !== $blockophon_ai_error_html ) :
		echo wp_kses_post( $blockophon_ai_error_html );
	endif;

	if ( $blockophon_ai_text ) :
		echo wp_kses_post( wpautop( $blockophon_ai_text ) );
	else :
		echo wp_kses_post( blockophon_get_prose_html( $blockophon_data, $blockophon_attributes ) );
	endif;
	?>
<?php endif; ?>

<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks HTML ?>

</div>
