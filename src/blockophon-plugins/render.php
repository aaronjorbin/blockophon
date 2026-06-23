<?php
/**
 * Renders the Blockophon plugins block on the front end.
 *
 * @package Blockophon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$blockophon_data       = blockophon_get_data();
$blockophon_attributes = (array) ( $attributes ?? array() );

$blockophon_show_plugins        = (bool) ( $blockophon_attributes['showPlugins'] ?? true );
$blockophon_show_plugin_details = (bool) ( $blockophon_attributes['showPluginDetails'] ?? true );

if ( ! $blockophon_show_plugins && ! $blockophon_show_plugin_details ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core function, always safe ?>>

<?php if ( $blockophon_show_plugins ) : ?>
	<?php
	$blockophon_plugin_count = count( $blockophon_data['plugins'] );
	$blockophon_mu_count     = count( $blockophon_data['mu_plugins'] );
	$blockophon_dropin_count = count( $blockophon_data['drop_ins'] );

	/* translators: %s: number of active plugins */
	$blockophon_str_plugins = esc_html( sprintf( _n( 'This site runs %s plugin', 'This site runs %s plugins', $blockophon_plugin_count, 'blockophon' ), number_format_i18n( $blockophon_plugin_count ) ) );
	/* translators: %s: number of mu-plugins */
	$blockophon_str_mu = esc_html( sprintf( _n( '%s mu-plugin', '%s mu-plugins', $blockophon_mu_count, 'blockophon' ), number_format_i18n( $blockophon_mu_count ) ) );
	/* translators: %s: number of drop-ins */
	$blockophon_str_dropins = esc_html( sprintf( _n( 'and %s drop-in.', 'and %s drop-ins.', $blockophon_dropin_count, 'blockophon' ), number_format_i18n( $blockophon_dropin_count ) ) );
	?>
	<p>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all three variables escaped above.
		echo $blockophon_str_plugins . ', ' . $blockophon_str_mu . ', ' . $blockophon_str_dropins;
		?>
	</p>
<?php endif; ?>

<?php if ( $blockophon_show_plugin_details && ! empty( $blockophon_data['plugins'] ) ) : ?>
	<details class="blockophon-plugin-list">
		<summary><?php esc_html_e( 'Plugin details', 'blockophon' ); ?></summary>
		<ul>
			<?php foreach ( $blockophon_data['plugins'] as $blockophon_plugin_data ) : ?>
				<li><?php echo esc_html( (string) $blockophon_plugin_data['Name'] ); ?> <?php echo esc_html( (string) $blockophon_plugin_data['Version'] ); ?></li>
			<?php endforeach; ?>
		</ul>
	</details>
<?php endif; ?>

</div>
