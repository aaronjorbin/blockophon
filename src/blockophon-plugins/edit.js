import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
	const { showPlugins, showPluginDetails } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Options', 'blockophon' ) }>
					<ToggleControl
						label={ __( 'Plugin count', 'blockophon' ) }
						checked={ showPlugins }
						onChange={ ( v ) =>
							setAttributes( { showPlugins: v } )
						}
					/>
					<ToggleControl
						label={ __( 'Plugin details', 'blockophon' ) }
						checked={ showPluginDetails }
						onChange={ ( v ) =>
							setAttributes( { showPluginDetails: v } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<ServerSideRender
					block="blockophon/plugins"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
