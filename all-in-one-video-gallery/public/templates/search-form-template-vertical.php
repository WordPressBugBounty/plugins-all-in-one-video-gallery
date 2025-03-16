<?php

/**
 * Search Form: Vertical Layout.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$is_form_submitted = false;
if ( isset( $_GET['vi'] ) || isset( $_GET['ca'] ) || isset( $_GET['ta'] ) ) {
	$is_form_submitted = true;
}

$search_form_mode = 'search';
if ( ! $attributes['has_search_button'] ) {
	$search_form_mode = 'live';	
	if ( isset( $attributes['filters_mode'] ) ) {
		$search_form_mode = $attributes['filters_mode'];
	}
}

$search_page_id  = (int) $attributes['search_page_id'];
$search_page_url = aiovg_get_search_page_url( $search_page_id );
?>

<div class="aiovg aiovg-search-form aiovg-search-form-template-vertical aiovg-search-form-mode-<?php echo esc_attr( $search_form_mode ); ?>">
	<form method="get" action="<?php echo esc_url( $search_page_url ); ?>">
    	<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
       		<input type="hidden" name="page_id" value="<?php echo $search_page_id; ?>" />
    	<?php endif; ?>        
              
		<?php if ( $attributes['has_keyword'] ) : ?> 
			<div class="aiovg-form-group aiovg-field-keyword">
				<input type="text" name="vi" class="aiovg-form-control" placeholder="<?php esc_attr_e( 'Enter your Keyword', 'all-in-one-video-gallery' ); ?>" value="<?php echo isset( $_GET['vi'] ) ? esc_attr( stripslashes( $_GET['vi'] ) ) : ''; ?>" />
				<button type="submit" class="aiovg-button"> 
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
						<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
					</svg>
				</button>
			</div>
		<?php endif; ?> 
		
		<!-- Hook for developers to add new fields -->
        <?php do_action( 'aiovg_search_form_fields', $attributes ); ?>
		
		<?php if ( $attributes['has_category'] ) : ?>  
			<div class="aiovg-form-group aiovg-field-category">
				<?php
				$categories_selected = array();

				if ( isset( $_GET['ca'] ) ) {
					$categories_selected = (array) $_GET['ca'];
				} elseif ( isset( $attributes['categories_selected'] ) ) {
					$categories_selected = is_array( $attributes['categories_selected'] ) ? $attributes['categories_selected'] : explode( ',', $attributes['categories_selected'] );
				}

				$categories_selected = array_filter( array_map( 'intval', $categories_selected ) );

				if ( empty( $categories_selected ) ) {
					if ( $term_slug = get_query_var( 'aiovg_category' ) ) {        
						if ( $term = get_term_by( 'slug', sanitize_text_field( $term_slug ), 'aiovg_categories' ) ) {  
							$categories_selected = (array) $term->term_id;
						}
					}
				}

				$categories_args = array(
					'show_option_none'  => '— ' . esc_html__( 'Select Categories', 'all-in-one-video-gallery' ) . ' —',
					'option_none_value' => '',
					'taxonomy'          => 'aiovg_categories',
					'name' 			    => 'ca[]',
					'class'             => 'aiovg-form-control',
					'orderby'           => 'name',
					'order'             => 'asc',
					'selected'          => $categories_selected,
					'hierarchical'      => true,
					'depth'             => 10,
					'show_count'        => false,
					'hide_empty'        => false
				);

				$categories_excluded = get_terms(array(
					'taxonomy'   => 'aiovg_categories',
					'hide_empty' => false,
					'fields'     => 'ids',
					'meta_key'   => 'exclude_search_form',
    				'meta_value' => 1
				));

				if ( ! empty( $categories_excluded ) && ! is_wp_error( $categories_excluded ) ) {
					$categories_args['exclude']	= array_map( 'intval', $categories_excluded );
				}

				$categories_args = apply_filters( 'aiovg_search_form_categories_args', $categories_args );
				aiovg_dropdown_terms( $categories_args );
				?>
			</div>
		<?php endif; ?>

		<?php if ( $attributes['has_tag'] ) : ?>  
			<div class="aiovg-form-group aiovg-field-tag">
				<?php
				$tags_selected = array();

				if ( isset( $_GET['ta'] ) ) {
					$tags_selected = (array) $_GET['ta'];
				} elseif ( isset( $attributes['tags_selected'] ) ) {
					$tags_selected = is_array( $attributes['tags_selected'] ) ? $attributes['tags_selected'] : explode( ',', $attributes['tags_selected'] );
				}

				$tags_selected = array_filter( array_map( 'intval', $tags_selected ) );

				if ( empty( $tags_selected ) ) {
					if ( $term_slug = get_query_var( 'aiovg_tag' ) ) {        
						if ( $term = get_term_by( 'slug', sanitize_text_field( $term_slug ), 'aiovg_tags' ) ) {  
							$tags_selected = (array) $term->term_id;
						}
					}
				}

				$tags_args = array(
					'show_option_none'  => '— ' . esc_html__( 'Select Tags', 'all-in-one-video-gallery' ) . ' —',
					'option_none_value' => '',
					'taxonomy'          => 'aiovg_tags',
					'name' 			    => 'ta[]',
					'class'             => 'aiovg-form-control',
					'orderby'           => 'name',
					'order'             => 'asc',
					'selected'          => $tags_selected,
					'hierarchical'      => false,
					'show_count'        => false,
					'hide_empty'        => false
				);

				$tags_args = apply_filters( 'aiovg_search_form_tags_args', $tags_args );
				aiovg_dropdown_terms( $tags_args );
				?>
			</div>
		<?php endif; ?>
		
		<?php if ( $attributes['has_sort'] ) : ?>  
			<div class="aiovg-form-group aiovg-field-sort">
				<?php
				$sort_options  = aiovg_get_search_form_sort_options();
				$sort_selected = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : '';			

				echo '<select name="sort" class="aiovg-form-control">';
				echo sprintf( '<option value="">— %s —</option>', __( 'Sort By', 'all-in-one-video-gallery' ) );

				foreach ( $sort_options as $key => $value ) {
					echo sprintf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $key ),
						selected( $key, $sort_selected, false ), 
						esc_html( $value )
					);
				}

				echo '</select>';
				?>
			</div>
		<?php endif; ?>
		
		<?php if ( $attributes['has_search_button'] ) : ?>
			<div class="aiovg-form-group aiovg-field-submit aiovg-flex aiovg-gap-2 aiovg-items-center">
				<input type="submit" class="aiovg-button" value="<?php esc_attr_e( 'Search Videos', 'all-in-one-video-gallery' ); ?>" /> 
				<?php if ( $is_form_submitted ) : ?>
					<input type="button" onclick="location.href='<?php echo esc_url( $search_page_url ); ?>';" value="<?php esc_attr_e( 'Reset', 'all-in-one-video-gallery' ); ?>" />
				<?php endif; ?>	
			</div>
		<?php endif; ?>          
	</form> 
</div>
