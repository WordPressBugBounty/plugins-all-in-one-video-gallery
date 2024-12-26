/**
 * WordPress dependencies
 */
import ServerSideRender from '@wordpress/server-side-render';

import {
	Disabled,
	PanelBody,
	PanelRow,
	SelectControl,
	ToggleControl
} from '@wordpress/components';

import { InspectorControls,	useBlockProps } from '@wordpress/block-editor';

/**
 * Describes the structure of the block in the context of the editor.
 * This represents what the editor will render when the block is used.
 *
 * @return {WPElement} Element to render.
 */
function Edit( { attributes, setAttributes } ) {
	
	const { 
		template, 
		keyword,
		category,
		tag,
		sort,
		search_button,
		target 
	} = attributes;
	
	return (
		<>
			<InspectorControls>
				<PanelBody title={ aiovg_blocks.i18n.general_settings } className="aiovg-block-panel">
					<PanelRow>
						<SelectControl
							label={ aiovg_blocks.i18n.select_template }
							value={ template }
							options={ [
								{ label: aiovg_blocks.i18n.vertical, value: 'vertical' },
								{ label: aiovg_blocks.i18n.horizontal, value: 'horizontal' }
							] }
							onChange={ ( value ) => setAttributes( { template: value } ) }
							__nextHasNoMarginBottom
            				__next40pxDefaultSize
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.search_by_keywords }
							checked={ keyword }
							onChange={ () => setAttributes( { keyword: ! keyword } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.search_by_categories }
							checked={ category }
							onChange={ () => setAttributes( { category: ! category } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.search_by_tags }
							checked={ tag }
							onChange={ () => setAttributes( { tag: ! tag } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.sort_by_dropdown }
							checked={ sort }
							onChange={ () => setAttributes( { sort: ! sort } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<ToggleControl
							label={ aiovg_blocks.i18n.search_button }
							checked={ search_button }
							onChange={ () => setAttributes( { search_button: ! search_button } ) }
							__nextHasNoMarginBottom
						/>
					</PanelRow>

					<PanelRow>
						<SelectControl
							label={ aiovg_blocks.i18n.search_results_page }
							value={ target }
							options={ [
								{ label: aiovg_blocks.i18n.default_page, value: 'default' },
								{ label: aiovg_blocks.i18n.current_page, value: 'current' }
							] }
							onChange={ ( value ) => setAttributes( { target: value } ) }
							__nextHasNoMarginBottom
            				__next40pxDefaultSize
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<Disabled>
					<ServerSideRender
						block="aiovg/search"
						attributes={ attributes }
					/>
				</Disabled>	
			</div>
		</>
	);
}


export default Edit;