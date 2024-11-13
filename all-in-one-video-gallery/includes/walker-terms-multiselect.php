<?php

/**
 * Walker Terms MultiSelect.
 *
 * @link    https://plugins360.com
 * @since   3.8.4
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

// This is required to be sure Walker_CategoryDropdown class is available
require_once ABSPATH . 'wp-includes/class-walker-category-dropdown.php';

/**
 * AIOVG_Walker_Terms_MultiSelect class.
 *
 * @since 3.8.4
 */
class AIOVG_Walker_Terms_MultiSelect extends Walker_CategoryDropdown {
	
	/**
	 * Starts the element output.
	 *
	 * @since 3.8.4
	 * @param string  $output            Used to append additional content (passed by reference).
	 * @param WP_Term $data_object       Category data object.
	 * @param int     $depth             Depth of category. Used for padding.
	 * @param array   $args              Uses 'selected', 'show_count', and 'value_field' keys, if they exist.
	 *                                   See wp_dropdown_categories().
	 * @param int     $current_object_id Optional. ID of the current category. Default 0.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = array(), $current_object_id = 0 ) {
		// Restores the more descriptive, specific name for use within this method.
		$category = $data_object;

		/** This filter is documented in wp-includes/category-template.php */
		$cat_name = apply_filters( 'list_cats', $category->name, $category );

		if ( $args['show_count'] ) {
			$cat_name .= '&nbsp;&nbsp;(' . number_format_i18n( $category->count ) . ')';
		}

		$value = (int) $category->term_id;

		$selected = in_array( $value, $args['selected'] ) ? true : false;

		$classes = array( 'aiovg-dropdown-item' );		
		if ( $selected ) $classes[] = 'aiovg-item-selected';
		$classes[] = 'level-' . $depth;

		$output .= "\t" . sprintf( '<div class="%s" style="padding-left:%dem;">', implode( ' ', $classes ), ( $depth + 1 ) );
		$output .= sprintf( '<div class="aiovg-item-name">%s</div>', esc_html( $cat_name ) );
		$output .= sprintf( 
			'<input type="checkbox" name="%s" value="%d" tabindex="-1"%s/>', 
			esc_attr( $args['name'] ), 
			esc_attr( $value ),
			( $selected ? ' checked="checked"' : '' )
		);
		
		$output .= "</div>\n";
	}

}
