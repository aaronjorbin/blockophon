import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { showTheme, showPlugins, showColors, showTypography, useAiText } =
		attributes;
	const aiAvailable = window.blockophonEditorData?.aiAvailable;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Options', 'blockophon' ) }>
					<ToggleControl
						label={ __( 'Theme', 'blockophon' ) }
						checked={ showTheme }
						onChange={ ( v ) => setAttributes( { showTheme: v } ) }
					/>
					<ToggleControl
						label={ __( 'Plugins', 'blockophon' ) }
						checked={ showPlugins }
						onChange={ ( v ) =>
							setAttributes( { showPlugins: v } )
						}
					/>
					<ToggleControl
						label={ __( 'Color Palette', 'blockophon' ) }
						checked={ showColors }
						onChange={ ( v ) => setAttributes( { showColors: v } ) }
					/>
					<ToggleControl
						label={ __( 'Typography', 'blockophon' ) }
						checked={ showTypography }
						onChange={ ( v ) =>
							setAttributes( { showTypography: v } )
						}
					/>
				</PanelBody>
				{ aiAvailable && (
					<PanelBody title={ __( 'AI', 'blockophon' ) }>
						<ToggleControl
							label={ __(
								'Generate text with AI',
								'blockophon'
							) }
							help={ __(
								'Uses the configured AI connector to write the colophon prose.',
								'blockophon'
							) }
							checked={ useAiText }
							onChange={ ( v ) =>
								setAttributes( { useAiText: v } )
							}
						/>
					</PanelBody>
				) }
			</InspectorControls>
			<div { ...useBlockProps() }>
				<ServerSideRender
					block="blockophon/blockophon"
					attributes={ attributes }
				/>
			</div>
		</>
	);
}
