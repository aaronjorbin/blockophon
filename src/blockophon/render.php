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
$blockophon_show_plugins    = (bool) ( $blockophon_attributes['showPlugins'] ?? true );
$blockophon_show_colors     = (bool) ( $blockophon_attributes['showColors'] ?? true );
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
	<?php
	if ( $blockophon_show_theme ) :
		$blockophon_t = $blockophon_data['theme'];

		if ( $blockophon_t['is_child'] ) {
			$blockophon_author_link  = $blockophon_t['parent_author_uri']
				? '<a href="' . esc_url( (string) $blockophon_t['parent_author_uri'] ) . '">' . esc_html( (string) $blockophon_t['parent_author'] ) . '</a>'
				: esc_html( (string) $blockophon_t['parent_author'] );
			$blockophon_theme_phrase = sprintf(
				/* translators: 1: site name  2: parent theme name  3: linked author name */
				__( "%1\$s's design started as %2\$s by %3\$s, and has been customized for this site.", 'blockophon' ),
				esc_html( $blockophon_site_name ),
				esc_html( (string) $blockophon_t['parent_name'] ),
				$blockophon_author_link
			);
		} else {
			$blockophon_author_link  = $blockophon_t['author_uri']
				? '<a href="' . esc_url( (string) $blockophon_t['author_uri'] ) . '">' . esc_html( (string) $blockophon_t['author'] ) . '</a>'
				: esc_html( (string) $blockophon_t['author'] );
			$blockophon_theme_phrase = sprintf(
				/* translators: 1: site name  2: theme name  3: linked author name */
				__( "%1\$s's design is %2\$s by %3\$s.", 'blockophon' ),
				esc_html( $blockophon_site_name ),
				esc_html( (string) $blockophon_t['name'] ),
				$blockophon_author_link
			);
		}
		?>
		<p><?php echo wp_kses_post( $blockophon_theme_phrase ); ?></p>
		<?php
		$blockophon_c = $blockophon_data['customizations'];
		if ( $blockophon_c['is_customized'] ) :
			$blockophon_custom_items = array();

			if ( $blockophon_c['modified_template_count'] > 0 ) {
				$blockophon_custom_items[] = sprintf(
					/* translators: %s: number of templates */
					_n( 'edits to %s template', 'edits to %s templates', $blockophon_c['modified_template_count'], 'blockophon' ),
					number_format_i18n( $blockophon_c['modified_template_count'] )
				);
			}

			if ( $blockophon_c['modified_part_count'] > 0 ) {
				$blockophon_custom_items[] = sprintf(
					/* translators: %s: number of template parts */
					_n( '%s template part', '%s template parts', $blockophon_c['modified_part_count'], 'blockophon' ),
					number_format_i18n( $blockophon_c['modified_part_count'] )
				);
			}

			if ( $blockophon_c['has_custom_global_styles'] ) {
				$blockophon_custom_items[] = __( 'adjusted global styles', 'blockophon' );
			}

			if ( $blockophon_c['has_custom_css'] ) {
				$blockophon_custom_items[] = __( 'custom CSS', 'blockophon' );
			}

			if ( 1 === count( $blockophon_custom_items ) ) {
				$blockophon_custom_list = $blockophon_custom_items[0];
			} elseif ( 2 === count( $blockophon_custom_items ) ) {
				/* translators: joins two items in a list */
				$blockophon_custom_list = $blockophon_custom_items[0] . __( ' and ', 'blockophon' ) . $blockophon_custom_items[1];
			} else {
				$blockophon_last = array_pop( $blockophon_custom_items );
				/* translators: Oxford-comma conjunction before final list item */
				$blockophon_custom_list = implode( ', ', $blockophon_custom_items ) . __( ', and ', 'blockophon' ) . $blockophon_last;
			}

			/* translators: %s: comma-separated list of customization types */
			$blockophon_custom_phrase = sprintf( __( 'It features %s.', 'blockophon' ), $blockophon_custom_list );
			?>
			<p><?php echo esc_html( $blockophon_custom_phrase ); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	if ( $blockophon_show_typography && ( $blockophon_data['heading_font'] || $blockophon_data['body_font'] ) ) :
		$blockophon_parts = array();
		if ( $blockophon_data['heading_font'] ) {
			/* translators: %s: font name */
			$blockophon_parts[] = sprintf( __( 'Headers are set in %s', 'blockophon' ), esc_html( (string) $blockophon_data['heading_font'] ) );
		}
		if ( $blockophon_data['body_font'] ) {
			/* translators: %s: font name */
			$blockophon_parts[] = sprintf( __( 'body copy is set in %s', 'blockophon' ), esc_html( (string) $blockophon_data['body_font'] ) );
		}
		$blockophon_glue = __( ' and ', 'blockophon' );
		?>
		<p><?php echo implode( $blockophon_glue, $blockophon_parts ) . '.'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- parts already escaped above ?></p>
	<?php endif; ?>
<?php endif; ?>

<?php if ( $blockophon_show_colors && ! empty( $blockophon_data['colors'] ) ) : ?>
	<p>
		<?php esc_html_e( 'The color palette is:', 'blockophon' ); ?>
		<span
			class="blockophon-swatches"
			data-wp-interactive="blockophon/blockophon"
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
<?php endif; ?>

<?php
if ( $blockophon_show_plugins ) :
	$blockophon_plugin_count = count( $blockophon_data['plugins'] );
	$blockophon_mu_count     = count( $blockophon_data['mu_plugins'] );
	$blockophon_dropin_count = count( $blockophon_data['drop_ins'] );
	?>
	<p>
		<?php
		/* translators: %s: number of active plugins */
		$blockophon_str_plugins = esc_html( sprintf( _n( 'This site runs %s plugin', 'This site runs %s plugins', $blockophon_plugin_count, 'blockophon' ), number_format_i18n( $blockophon_plugin_count ) ) );
		/* translators: %s: number of mu-plugins */
		$blockophon_str_mu = esc_html( sprintf( _n( '%s mu-plugin', '%s mu-plugins', $blockophon_mu_count, 'blockophon' ), number_format_i18n( $blockophon_mu_count ) ) );
		/* translators: %s: number of drop-ins */
		$blockophon_str_dropins = esc_html( sprintf( _n( 'and %s drop-in.', 'and %s drop-ins.', $blockophon_dropin_count, 'blockophon' ), number_format_i18n( $blockophon_dropin_count ) ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all three variables escaped above.
		echo $blockophon_str_plugins . ', ' . $blockophon_str_mu . ', ' . $blockophon_str_dropins;
		?>
	</p>

	<?php if ( ! empty( $blockophon_data['plugins'] ) ) : ?>
		<details class="blockophon-plugin-list">
			<summary><?php esc_html_e( 'Plugin details', 'blockophon' ); ?></summary>
			<ul>
				<?php foreach ( $blockophon_data['plugins'] as $blockophon_plugin_data ) : ?>
					<li><?php echo esc_html( (string) $blockophon_plugin_data['Name'] ); ?> <?php echo esc_html( (string) $blockophon_plugin_data['Version'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		</details>
	<?php endif; ?>
<?php endif; ?>

</div>
