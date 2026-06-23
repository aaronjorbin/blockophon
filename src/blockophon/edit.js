import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	InnerBlocks,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { useState, useCallback, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';
import './editor.scss';

const ALLOWED_BLOCKS = [
	'core/paragraph',
	'core/list',
	'core/heading',
	'blockophon/colors',
	'blockophon/plugins',
];

const DEFAULT_TEMPLATE = [
	[ 'blockophon/colors', {} ],
	[ 'blockophon/plugins', {} ],
];

const PROSE_BLOCK_NAMES = new Set( [
	'core/paragraph',
	'core/list',
	'core/heading',
] );

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { showTheme, showTypography, useAiText, isConverted } = attributes;
	const aiAvailable = window.blockophonEditorData?.aiAvailable;

	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ generateError, setGenerateError ] = useState( '' );
	const [ aiOptions, setAiOptions ] = useState( [] );

	const [ isConverting, setIsConverting ] = useState( false );
	const [ convertError, setConvertError ] = useState( '' );

	const [ prosePreview, setProsePreview ] = useState( '' );

	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
	const innerBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);

	// Live prose preview when not yet converted.
	useEffect( () => {
		if ( isConverted ) {
			setProsePreview( '' );
			return;
		}
		const params = new URLSearchParams( {
			show_theme: showTheme ? '1' : '0',
			show_typography: showTypography ? '1' : '0',
		} );
		apiFetch( { path: `/blockophon/v1/prose?${ params }` } )
			.then( ( response ) => setProsePreview( response?.html ?? '' ) )
			.catch( () => setProsePreview( '' ) );
	}, [ isConverted, showTheme, showTypography ] );

	// Parse HTML into core/paragraph (or heading) blocks, prepend to inner
	// blocks, preserving existing blockophon sub-blocks.
	const insertParagraphBlocks = useCallback(
		( html ) => {
			const parser = new window.DOMParser();
			const doc = parser.parseFromString( html, 'text/html' );
			const elements = Array.from( doc.body.children );

			const proseBlocks =
				elements.length > 0
					? elements.map( ( el ) => {
							const tag = el.tagName.toUpperCase();
							if ( /^H[1-6]$/.test( tag ) ) {
								return createBlock( 'core/heading', {
									content: el.innerHTML,
									level: parseInt( tag[ 1 ], 10 ),
								} );
							}
							return createBlock( 'core/paragraph', {
								content: el.innerHTML,
							} );
					  } )
					: html
							.split( /\n\n+/ )
							.filter( ( p ) => p.trim() )
							.map( ( p ) =>
								createBlock( 'core/paragraph', {
									content: p.trim(),
								} )
							);

			const nonProseBlocks = innerBlocks.filter(
				( block ) => ! PROSE_BLOCK_NAMES.has( block.name )
			);

			replaceInnerBlocks( clientId, [
				...proseBlocks,
				...nonProseBlocks,
			] );
			setAttributes( { isConverted: true } );
		},
		[ clientId, innerBlocks, replaceInnerBlocks, setAttributes ]
	);

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
			insertParagraphBlocks( html || `<p>${ text }</p>` );
			setAiOptions( [] );
		},
		[ insertParagraphBlocks ]
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
			const html = response?.html ?? '';
			if ( ! html ) {
				setConvertError(
					__(
						'Nothing to convert. Enable Theme or Typography in Display Options.',
						'blockophon'
					)
				);
				return;
			}
			insertParagraphBlocks( html );
		} catch {
			setConvertError( __( 'Failed to fetch text.', 'blockophon' ) );
		} finally {
			setIsConverting( false );
		}
	}, [ showTheme, showTypography, insertParagraphBlocks ] );

	const handleReset = useCallback( () => {
		const nonProseBlocks = innerBlocks.filter(
			( block ) => ! PROSE_BLOCK_NAMES.has( block.name )
		);
		replaceInnerBlocks( clientId, nonProseBlocks );
		setAttributes( { isConverted: false } );
	}, [ clientId, innerBlocks, replaceInnerBlocks, setAttributes ] );

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

				{ aiAvailable && ! isConverted && (
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
					{ isConverted ? (
						<Button variant="secondary" onClick={ handleReset }>
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

			<div { ...blockProps }>
				{ ! isConverted && prosePreview && (
					<div
						className="blockophon-prose-preview"
						// Output is from our own REST endpoint, sanitized via
						// wp_kses_post in blockophon_get_prose_html().
						dangerouslySetInnerHTML={ { __html: prosePreview } }
					/>
				) }
				{ ! isConverted && ! prosePreview && (
					<p className="blockophon-prose-placeholder">
						{ sprintf(
							/* translators: %s: AI or auto-generated */
							__(
								'%s prose will appear here on the front end.',
								'blockophon'
							),
							aiAvailable && useAiText
								? 'AI-generated'
								: 'Auto-generated'
						) }
					</p>
				) }
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ DEFAULT_TEMPLATE }
					templateLock={ false }
				/>
			</div>
		</>
	);
}
