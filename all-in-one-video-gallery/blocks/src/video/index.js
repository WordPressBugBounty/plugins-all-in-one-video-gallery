/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';

/**
 * Register the block.
 */
registerBlockType( metadata.name, {
	attributes: {
		blob: {
			type: 'string',
			role: 'local'
		},
		src: {
			type: 'string'
		},
		id: {
			type: 'number'
		},
		poster: {
			type: 'string'
		},
		width: {
			type: 'number',
			default: aiovg_blocks.video.width
		},
		ratio: {
			type: 'number',
			default: aiovg_blocks.video.ratio
		},
		autoplay: {
			type: 'boolean',
			default: aiovg_blocks.video.autoplay
		},
		loop: {
			type: 'boolean',
			default: aiovg_blocks.video.loop
		},
		muted: {
			type: 'boolean',
			default: aiovg_blocks.video.muted
		},
		playpause: {
			type: 'boolean',
			default: aiovg_blocks.video.playpause
		},
		current: {
			type: 'boolean',
			default: aiovg_blocks.video.current
		},
		progress: {
			type: 'boolean',
			default: aiovg_blocks.video.progress
		},
		duration: {
			type: 'boolean',
			default: aiovg_blocks.video.duration
		},
		speed: {
			type: 'boolean',
			default: aiovg_blocks.video.speed
		},
		quality: {
			type: 'boolean',
			default: aiovg_blocks.video.quality
		},			
		volume: {
			type: 'boolean',
			default: aiovg_blocks.video.volume
		},
		pip: {
			type: 'boolean',
			default: aiovg_blocks.video.pip
		},
		fullscreen: {
			type: 'boolean',
			default: aiovg_blocks.video.fullscreen
		},
		share: {
			type: 'boolean',
			default: aiovg_blocks.video.share
		},
		embed: {
			type: 'boolean',
			default: aiovg_blocks.video.embed
		},
		download: {
			type: 'boolean',
			default: aiovg_blocks.video.download
		},
		// -1 Global, 1 Enable, 0 Disable. "boolean" stays accepted so blocks saved
		// before 4.9.4 keep their stored true/false instead of silently falling back
		// to the default. build_shortcode_attributes() casts those to 1/0 on render.
		email_capture: {
			type: [ 'number', 'boolean' ],
			default: -1
		}
	},

	edit: Edit
} );
