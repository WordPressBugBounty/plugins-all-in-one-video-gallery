/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useLayoutEffect, useRef } from '@wordpress/element';
import { getBlobByURL, isBlobURL, revokeBlobURL } from '@wordpress/blob';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Handles uploading a media file from a blob URL on mount.
 *
 * @param {Object}   args              Upload media arguments.
 * @param {string}   args.url          Blob URL.
 * @param {?Array}   args.allowedTypes Array of allowed media types.
 * @param {Function} args.onChange     Function called when the media is uploaded.
 * @param {Function} args.onError      Function called when an error happens.
 */
export function useUploadMediaFromBlobURL( args = {} ) {
	const latestArgsRef = useRef( args );
	const hasUploadStartedRef = useRef( false );
	const { getSettings } = useSelect( blockEditorStore );

	useLayoutEffect( () => {
		latestArgsRef.current = args;
	} );

	useEffect( () => {
		// Uploading is a special effect that can't be canceled via the cleanup method.
		// The extra check avoids duplicate uploads in development mode (React.StrictMode).
		if ( hasUploadStartedRef.current ) {
			return;
		}

		if (
			! latestArgsRef.current.url ||
			! isBlobURL( latestArgsRef.current.url )
		) {
			return;
		}

		const file = getBlobByURL( latestArgsRef.current.url );
		if ( ! file ) {
			return;
		}

		const { url, allowedTypes, onChange, onError } = latestArgsRef.current;
		const { mediaUpload } = getSettings();

		hasUploadStartedRef.current = true;

		mediaUpload( {
			filesList: [ file ],
			allowedTypes,
			onFileChange: ( [ media ] ) => {
				if ( isBlobURL( media?.url ) ) {
					return;
				}

				revokeBlobURL( url );
				onChange( media );
				hasUploadStartedRef.current = false;
			},
			onError: ( message ) => {
				revokeBlobURL( url );
				onError( message );
				hasUploadStartedRef.current = false;
			},
		} );
	}, [ getSettings ] );
}