/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Edit from './edit';
import metadata from './block.json';
import { getVideoAttributes } from '../utils';

/**
 * Register the block.
 */
registerBlockType( metadata.name, {
	attributes: getVideoAttributes(),
	edit: Edit
} );
