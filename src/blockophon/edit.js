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

	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ generateError, setGenerateError ] = useState( '' );
	const [ aiOptions, setAiOptions ] = useState( [] );

	const [ isConverting, setIsConverting ] = useState( false );
	const [ convertError, setConvertError ] = useState( '' );

	const handleGenerateOptions = useCallback( async () => {
		setIsGenerating( true );
		setGenerateError( '' );
		setAiOptions( [] );
		try {
			const response = await apiFetch( {
				path: '/blockophon/v1/ai-options',
				method: 'POST',
				data: { showTheme, showTypography },
			} );
			if ( response?.options?.length ) {
				setAiOptions( response.options );
			} else {
				setGenerateError(
					__( 'No options were returned.', 'blockophon' )
				);
			}
		} catch ( err ) {
			setGenerateError(
				err?.message ||
					__( 'Failed to generate options.', 'blockophon' )
			);
		} finally {
			setIsGenerating( false );
		}
	}, [ showTheme, showTypography ] );

	const handleUseOption = useCallback(
		( text ) => {
			const html = text
				.split( /\n\n+/ )
				.filter( ( p ) => p.trim() )
				.map( ( p ) => `<p>${ p.trim() }</p>` )
				.join( '' );
			setAttributes( { customText: html || `<p>${ text }</p>` } );
			setAiOptions( [] );
		},
		[ setAttributes ]
	);

	const handleConvert = useCallback( async () => {
		setIsConverting( true );
		setConvertError( '' );
		try {
			const params = new URLSearchParams( {
				show_theme: showTheme ? '1' : '0',
				show_typography: showTypography ? '1' : '0',
			} );
			const response = await apiFetch( {
				path: `/blockophon/v1/prose?${ params }`,
			} );
			const html = response?.html || '';
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
	}, [ setAttributes, showTheme, showTypography ] );

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

				{ aiAvailable && ! customText && (
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
						{ useAiText && (
							<>
								{ generateError && (
									<p
										style={ {
											color: '#d63638',
											fontSize: '12px',
											margin: '0 0 8px',
										} }
									>
										{ generateError }
									</p>
								) }
								{ aiOptions.length > 0 ? (
									<>
										{ aiOptions.map( ( option, i ) => (
											<div
												key={ i }
												style={ {
													border: '1px solid #ddd',
													borderRadius: '2px',
													padding: '8px',
													marginBottom: '8px',
													fontSize: '12px',
													lineHeight: '1.5',
												} }
											>
												<p
													style={ {
														margin: '0 0 6px',
														maxHeight: '80px',
														overflow: 'hidden',
													} }
												>
													{ option.length > 200
														? option.slice(
																0,
																200
														  ) + '…'
														: option }
												</p>
												<Button
													variant="secondary"
													onClick={ () =>
														handleUseOption(
															option
														)
													}
													size="small"
												>
													{ __(
														'Use this',
														'blockophon'
													) }
												</Button>
											</div>
										) ) }
										<Button
											variant="tertiary"
											onClick={ handleGenerateOptions }
											isBusy={ isGenerating }
											disabled={ isGenerating }
										>
											{ __(
												'Generate more',
												'blockophon'
											) }
										</Button>
									</>
								) : (
									<Button
										variant="secondary"
										onClick={ handleGenerateOptions }
										isBusy={ isGenerating }
										disabled={ isGenerating }
									>
										{ __(
											'Generate options',
											'blockophon'
										) }
									</Button>
								) }
							</>
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
