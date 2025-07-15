<?php

/**
 * Admin form: Search widget.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */
?>

<div class="aiovg aiovg-widget-form aiovg-widget-form-search">
	<div class="aiovg-widget-section">
		<div class="aiovg-widget-field aiovg-widget-field-title">
			<label class="aiovg-widget-label" for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'all-in-one-video-gallery' ); ?></label> 
			<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" class="widefat aiovg-widget-input-title" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-template">
			<label class="aiovg-widget-label" for="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>"><?php esc_html_e( 'Select Template', 'all-in-one-video-gallery' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'template' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>" class="widefat aiovg-widget-input-template"> 
				<?php
				$options = array(
					'vertical'   => __( 'Vertical', 'all-in-one-video-gallery' ),
					'horizontal' => __( 'Horizontal', 'all-in-one-video-gallery' )	
				);
			
				foreach ( $options as $key => $value ) {
					printf( 
						'<option value="%s"%s>%s</option>', 
						$key, 
						selected( $key, $instance['template'], false ), 
						esc_html( $value )
					);
				}
				?>
			</select>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-has_keyword">		
			<label for="<?php echo esc_attr( $this->get_field_id( 'has_keyword' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'has_keyword' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'has_keyword' ) ); ?>" class="aiovg-widget-input-has_keyword" value="1" <?php checked( 1, $instance['has_keyword'] ); ?> /> 
				<?php esc_html_e( 'Search By Video Title, Description', 'all-in-one-video-gallery' ); ?>
			</label>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-has_category">		 
			<label for="<?php echo esc_attr( $this->get_field_id( 'has_category' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'has_category' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'has_category' ) ); ?>" class="aiovg-widget-input-has_category" value="1" <?php checked( 1, $instance['has_category'] ); ?> />
				<?php esc_html_e( 'Search By Categories', 'all-in-one-video-gallery' ); ?>
			</label>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-has_tag">		
			<label for="<?php echo esc_attr( $this->get_field_id( 'has_tag' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'has_tag' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'has_tag' ) ); ?>" class="aiovg-widget-input-has_tag" value="1" <?php checked( 1, $instance['has_tag'] ); ?> /> 
				<?php esc_html_e( 'Search By Tags', 'all-in-one-video-gallery' ); ?>
			</label>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-has_sort">		
			<label for="<?php echo esc_attr( $this->get_field_id( 'has_sort' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'has_sort' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'has_sort' ) ); ?>" class="aiovg-widget-input-has_sort" value="1" <?php checked( 1, $instance['has_sort'] ); ?> /> 
				<?php esc_html_e( 'Sort By Dropdown', 'all-in-one-video-gallery' ); ?>
			</label>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-has_search_button">		
			<label for="<?php echo esc_attr( $this->get_field_id( 'has_search_button' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'has_search_button' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'has_search_button' ) ); ?>" class="aiovg-widget-input-has_search_button" value="1" <?php checked( 1, $instance['has_search_button'] ); ?> /> 
				<?php esc_html_e( 'Search Button', 'all-in-one-video-gallery' ); ?>
			</label>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-has_reset_button">		
			<label for="<?php echo esc_attr( $this->get_field_id( 'has_reset_button' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'has_reset_button' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'has_reset_button' ) ); ?>" class="aiovg-widget-input-has_reset_button" value="1" <?php checked( 1, $instance['has_reset_button'] ); ?> /> 
				<?php esc_html_e( 'Reset Button', 'all-in-one-video-gallery' ); ?>
			</label>
		</div>

		<div class="aiovg-widget-field aiovg-widget-field-target">
			<label class="aiovg-widget-label" for="<?php echo esc_attr( $this->get_field_id( 'target' ) ); ?>"><?php esc_html_e( 'Search Results Page', 'all-in-one-video-gallery' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'target' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'target' ) ); ?>" class="widefat aiovg-widget-input-target"> 
				<?php
				$options = array(
					'default' => __( "Use Plugin's Default Search Results Page", 'all-in-one-video-gallery' ),
					'current' => __( 'Display Results on Current Page', 'all-in-one-video-gallery' )	
				);
			
				foreach ( $options as $key => $value ) {
					printf( 
						'<option value="%s"%s>%s</option>', 
						$key, 
						selected( $key, $instance['target'], false ), 
						esc_html( $value )
					);
				}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'The selected "Search Results Page" must include the [aiovg_search] shortcode, which will be replaced by the search results.', 'all-in-one-video-gallery' ); ?></p>
		</div>
	</div>
</div>