<?php
/**
 * Renders the Blockophon color palette block on the front end.
 *
 * @package Blockophon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blockophon_data = blockophon_get_data();

if ( empty( $blockophon_data['colors'] ) ) {
	return;
}
?>
<p <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core function, always safe ?>>
	<?php esc_html_e( 'The color palette is:', 'blockophon' ); ?>
	<span
		class="blockophon-swatches"
		data-wp-interactive="blockophon/colors"
	>
		<?php foreach ( $blockophon_data['colors'] as $blockophon_color ) : ?>
			<span
				class="blockophon-swatch-item"
				data-wp-context='{"active": false}'
			>
				<button
					class="blockophon-swatch"
					style="background:<?php echo esc_attr( (string) $blockophon_color['color'] ); ?>"
					data-wp-on--click="actions.toggleColorValue"
					aria-label="<?php echo esc_attr( $blockophon_color['name'] . ': ' . $blockophon_color['color'] ); ?>"
				></button><code
					class="blockophon-swatch__value"
					data-wp-bind--hidden="!context.active"
					hidden
				><?php echo esc_html( (string) $blockophon_color['color'] ); ?></code>
			</span>
		<?php endforeach; ?>
	</span>
</p>
