/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import ServerSideRender from '@wordpress/server-side-render';

import { isBlobURL } from '@wordpress/blob';

import {
	BaseControl,
	Button,
	Disabled,
	PanelBody,
	PanelRow,
	Placeholder,	
	Spinner,
	TextControl,	
	ToggleControl
} from '@wordpress/components';

import { 
	BlockControls,
	BlockIcon,	
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
	MediaReplaceFlow,	
	useBlockProps
} from '@wordpress/block-editor';

import { useRef, useState } from '@wordpress/element';

import { __, sprintf } from '@wordpress/i18n';

import { useInstanceId } from '@wordpress/compose';

import { useDispatch } from '@wordpress/data';

import { video as icon } from '@wordpress/icons';

import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { useUploadMediaFromBlobURL } from '../hooks';

const ALLOWED_MEDIA_TYPES = [ 'video' ];
const VIDEO_POSTER_ALLOWED_MEDIA_TYPES = [ 'image' ];

/**
 * Describes the structure of the block in the context of the editor.
 * This represents what the editor will render when the block is used.
 *
 * @return {WPElement} Element to render.
 */
function Edit( { isSelected: isSingleSelected, attributes, className, setAttributes } ) {

	const instanceId = useInstanceId( Edit );

	const posterImageButton = useRef();

	const {
		src,
		id,
		poster,
		width,
		ratio,
		autoplay,
		loop,
		muted,
		playpause,
		current,
		progress,
		duration,		
		speed,
		quality,
		volume,
		pip,
		fullscreen,
		share,
		embed,
		download
	} = attributes;	

	const [ temporaryURL, setTemporaryURL ] = useState( attributes.blob );

	useUploadMediaFromBlobURL( {
		url: temporaryURL,
		allowedTypes: ALLOWED_MEDIA_TYPES,
		onChange: onSelectVideo,
		onError: onUploadError,
	} );

	function onSelectVideo( media ) {
		if ( ! media || ! media.url ) {
			// In this case there was an error
			// previous attributes should be removed
			// because they may be temporary blob urls.
			setAttributes( {
				blob: undefined,
				src: undefined,
				id: undefined,
				poster: undefined,				
			} );

			setTemporaryURL();
			return;
		}

		if ( isBlobURL( media.url ) ) {
			setTemporaryURL( media.url );
			return;
		}

		// Sets the block's attribute and updates the edit component from the
		// selected media.
		setAttributes( {
			blob: undefined,
			src: media.url,
			id: media.id,
			poster:	media.image?.src !== media.icon ? media.image?.src : undefined,
		} );

		setTemporaryURL();
	}

	function onSelectURL( newSrc ) {
		if ( newSrc !== src ) {
			setAttributes( {
				blob: undefined, 
				src: newSrc, 
				id: undefined, 
				poster: undefined,
			} );

			setTemporaryURL();
		}
	}

	const { createErrorNotice } = useDispatch( noticesStore );
	function onUploadError( message ) {
		createErrorNotice( message, { type: 'snackbar' } );
	}

	// Much of this description is duplicated from MediaPlaceholder.
	const placeholder = ( content ) => {
		return (
			<Placeholder
				className="block-editor-media-placeholder"
				withIllustration={ ! isSingleSelected }
				icon={ icon }
				label={ aiovg_blocks.i18n.media_placeholder_label }
				instructions={ aiovg_blocks.i18n.media_placeholder_description }
			>
				{ content }
			</Placeholder>
		);
	};

	const classes = classnames( className, {
		'is-transient': !! temporaryURL,
	} );

	const blockProps = useBlockProps( {
		className: classes,
	} );

	if ( ! src && ! temporaryURL ) {
		return (
			<div { ...blockProps }>
				<MediaPlaceholder
					icon={ <BlockIcon icon={ icon } /> }
					onSelect={ onSelectVideo }
					onSelectURL={ onSelectURL }
					accept="video/*"
					allowedTypes={ ALLOWED_MEDIA_TYPES }
					value={ attributes }
					onError={ onUploadError }
					placeholder={ placeholder }
				/>
			</div>
		);
	}

	function onSelectPoster( image ) {
		setAttributes( { poster: image.url } );
	}

	function onRemovePoster() {
		setAttributes( { poster: undefined } );

		// Move focus back to the Media Upload button.
		posterImageButton.current.focus();
	}

	const videoPosterDescription = `video-block__poster-image-description-${ instanceId }`;
	
	return (
		<>
			{ isSingleSelected && (
				<BlockControls group="other">
					<MediaReplaceFlow
						mediaId={ id }
						mediaURL={ src }
						allowedTypes={ ALLOWED_MEDIA_TYPES }
						accept="video/*"
						onSelect={ onSelectVideo }
						onSelectURL={ onSelectURL }
						onError={ onUploadError }
						onReset={ () => onSelectVideo( undefined ) }
					/>
				</BlockControls>
			) }
			<InspectorControls>
				<PanelBody title={ aiovg_blocks.i18n.general_settings } className="aiovg-block-panel">
					<PanelRow>
						<TextControl
							label={ aiovg_blocks.i18n.width }
							help={ aiovg_blocks.i18n.width_help }
							value={ width > 0 ? width : '' }
							onChange={ ( value ) => setAttributes( { width: isNaN( value ) ? 0 : value } ) }
							__nextHasNoMarginBottom
            				__next40pxDefaultSize
						/>
					</PanelRow>
					
					<PanelRow>
						<TextControl
							label={ aiovg_blocks.i18n.ratio }
							help={ aiovg_blocks.i18n.ratio_help }
							value={ ratio > 0 ? ratio : '' }
							onChange={ ( value ) => setAttributes( { ratio: isNaN( value ) ? 0 : value } ) }
							__nextHasNoMarginBottom
            				__next40pxDefaultSize
						/>
					</PanelRow>					

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.autoplay }							
							checked={ autoplay }
							onChange={ () => setAttributes( { autoplay: ! autoplay } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.loop }							
							checked={ loop }
							onChange={ () => setAttributes( { loop: ! loop } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.muted }							
							checked={ muted }
							onChange={ () => setAttributes( { muted: ! muted } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<MediaUploadCheck>
							<BaseControl className="editor-video-poster-control">
								<BaseControl.VisualLabel>
									{ aiovg_blocks.i18n.poster_image }
								</BaseControl.VisualLabel>
								<MediaUpload
									title={ aiovg_blocks.i18n.select_image }
									onSelect={ onSelectPoster }
									allowedTypes={ VIDEO_POSTER_ALLOWED_MEDIA_TYPES	}
									render={ ( { open } ) => (
										<Button
											variant="primary"
											onClick={ open }
											ref={ posterImageButton }
											aria-describedby={ videoPosterDescription }
            								__next40pxDefaultSize
										>
											{ ! poster ? aiovg_blocks.i18n.select_image : aiovg_blocks.i18n.replace_image }
										</Button>
									) }
								/>
								<p id={ videoPosterDescription } hidden>
									{ poster ? sprintf( 'The current poster image url is %s', poster ) : 'There is no poster image currently selected' }
								</p>
								{ !! poster && (
									<Button
										variant="tertiary"
										onClick={ onRemovePoster }
           								__next40pxDefaultSize										
									>
										{ aiovg_blocks.i18n.remove_image }
									</Button>
								) }
							</BaseControl>
						</MediaUploadCheck>
					</PanelRow>	
				</PanelBody>	

				<PanelBody title={ aiovg_blocks.i18n.player_controls } className="aiovg-block-panel">	
					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.play_pause }							
							checked={ playpause }
							onChange={ () => setAttributes( { playpause: ! playpause } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.current_time }							
							checked={ current }
							onChange={ () => setAttributes( { current: ! current } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.progressbar }							
							checked={ progress }
							onChange={ () => setAttributes( { progress: ! progress } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.duration }							
							checked={ duration }
							onChange={ () => setAttributes( { duration: ! duration } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>					

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.speed }							
							checked={ speed }
							onChange={ () => setAttributes( { speed: ! speed } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.quality }							
							checked={ quality }
							onChange={ () => setAttributes( { quality: ! quality } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.volume }							
							checked={ volume }
							onChange={ () => setAttributes( { volume: ! volume } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.pip }							
							checked={ pip }
							onChange={ () => setAttributes( { pip: ! pip } ) }
							__nextHasNoMarginBottom
						/>	
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.fullscreen }							
							checked={ fullscreen }
							onChange={ () => setAttributes( { fullscreen: ! fullscreen } ) }
							__nextHasNoMarginBottom
						/>	
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.share }							
							checked={ share }
							onChange={ () => setAttributes( { share: ! share } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.embed }							
							checked={ embed }
							onChange={ () => setAttributes( { embed: ! embed } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.download }							
							checked={ download }
							onChange={ () => setAttributes( { download: ! download } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>	
				</PanelBody>		
			</InspectorControls>

			<div { ...blockProps }>
				{ src && (
					<Disabled>
						<ServerSideRender
							block="aiovg/video"
							attributes={ attributes }
						/>
					</Disabled>
				) }	
				{ !! temporaryURL && <Spinner /> }
			</div>
		</>
	);
}

export default Edit;