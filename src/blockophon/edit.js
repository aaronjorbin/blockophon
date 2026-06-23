import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { showTheme, showTypography, useAiText, customText } = attributes;
	const aiAvailable = window.blockophonEditorData?.aiAvailable;
	const [ isConverting, setIsConverting ] = useState( false );
	const [ convertError, setConvertError ] = useState( '' );

	const handleConvert = useCallback( async () => {
		setIsConverting( true );
		setConvertError( '' );
		try {
			let html;
			if ( useAiText && aiAvailable ) {
				const response = await apiFetch( {
					path: '/blockophon/v1/ai-text',
				} );
				if ( ! response?.text ) {
					setConvertError(
						__(
							'No AI text available yet. View the block on the front end first to generate it.',
							'blockophon'
						)
					);
					return;
				}
				html = response.text
					.split( /\n\n+/ )
					.filter( ( p ) => p.trim() )
					.map( ( p ) => `<p>${ p.trim() }</p>` )
					.join( '' );
			} else {
				const params = new URLSearchParams( {
					show_theme: showTheme ? '1' : '0',
					show_typography: showTypography ? '1' : '0',
				} );
				const response = await apiFetch( {
					path: `/blockophon/v1/prose?${ params }`,
				} );
				html = response?.html || '';
			}
			if ( ! html ) {
				setConvertError(
					__(
						'Nothing to convert. Enable Theme or Typography in Display Options.',
						'blockophon'
					)
				);
				return;
			}
			setAttributes( { customText: html } );
		} catch {
			setConvertError( __( 'Failed to fetch text.', 'blockophon' ) );
		} finally {
			setIsConverting( false );
		}
	}, [ setAttributes, useAiText, aiAvailable, showTheme, showTypography ] );

	const blockProps = useBlockProps();

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
						label={ __( 'Typography', 'blockophon' ) }
						checked={ showTypography }
						onChange={ ( v ) =>
							setAttributes( { showTypography: v } )
						}
					/>
				</PanelBody>

				{ aiAvailable && (
					<PanelBody title={ __( 'AI', 'blockophon' ) }>
						{ ! customText && (
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
						) }
					</PanelBody>
				) }

				<PanelBody title={ __( 'Editable Text', 'blockophon' ) }>
					{ customText ? (
						<Button
							variant="secondary"
							onClick={ () =>
								setAttributes( { customText: '' } )
							}
						>
							{ __( 'Reset to generated text', 'blockophon' ) }
						</Button>
					) : (
						<>
							{ convertError && (
								<p
									style={ {
										color: '#d63638',
										fontSize: '12px',
										margin: '0 0 8px',
									} }
								>
									{ convertError }
								</p>
							) }
							<Button
								variant="secondary"
								onClick={ handleConvert }
								isBusy={ isConverting }
								disabled={ isConverting }
							>
								{ __(
									'Convert to editable text',
									'blockophon'
								) }
							</Button>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			{ customText ? (
				<div { ...blockProps }>
					<RichText
						tagName="div"
						multiline="p"
						value={ customText }
						onChange={ ( value ) =>
							setAttributes( { customText: value } )
						}
						placeholder={ __(
							'Write your colophon…',
							'blockophon'
						) }
					/>
				</div>
			) : (
				<div { ...blockProps }>
					<ServerSideRender
						block="blockophon/blockophon"
						attributes={ attributes }
					/>
				</div>
			) }
		</>
	);
}
