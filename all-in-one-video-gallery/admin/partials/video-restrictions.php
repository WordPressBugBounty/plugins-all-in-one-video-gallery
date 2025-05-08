<?php

/**
 * Video Metabox: "Restrictions" tab.
 *
 * @link    https://plugins360.com
 * @since   3.9.6
 *
 * @package All_In_One_Video_Gallery
 */

$access_control   = isset( $post_meta['access_control'] ) ? $post_meta['access_control'][0] : -1;
$restricted_roles = isset( $post_meta['restricted_roles'] ) ? $post_meta['restricted_roles'][0] : array();
?>

<div class="aiovg-flex aiovg-flex-col aiovg-gap-6">
	<div id="aiovg-field-access_control" class="aiovg-form-control">
		<label for="aiovg-access_control" class="aiovg-form-label"><?php esc_html_e( 'Who Can Access this Video?', 'all-in-one-video-gallery' ); ?></label>
		
		<select name="access_control" id="aiovg-access_control" class="widefat">
			<?php 
			$options = array(
				-1 => '— ' . __( 'Global', 'all-in-one-video-gallery' ) . ' —',
				0  => __( 'Everyone', 'all-in-one-video-gallery' ),
				1  => __( 'Logged out users', 'all-in-one-video-gallery' ),
				2  => __( 'Logged in users', 'all-in-one-video-gallery' )
			);

			foreach ( $options as $key => $label ) {
				printf( 
					'<option value="%s"%s>%s</option>', 
					esc_attr( $key ), 
					selected( $key, $access_control, false ), 
					esc_html( $label )
				);
			}
			?>
		</select>
	</div>

	<div id="aiovg-field-restricted_roles" class="aiovg-form-control"<?php if ( $access_control != 2 ) { echo ' style="display: none";'; } ?>>
		<label class="aiovg-form-label"><?php esc_html_e( 'Select User Roles Allowed to Access this Video', 'all-in-one-video-gallery' ); ?></label>
	
		<ul class="aiovg-checklist widefat">
			<?php
			$roles = aiovg_get_user_roles();

			foreach ( $roles as $role => $name ) : ?>
				<li>
					<label>
						<input type="checkbox" name="restricted_roles[]" <?php checked( is_array( $restricted_roles ) && in_array( $role, $restricted_roles ) ); ?> value="<?php echo esc_attr( $role ); ?>" />
						<?php echo esc_html( $name ); ?>
					</label>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="description">
			<?php esc_html_e( 'If no roles are selected, the global setting will be used. Users with editing permissions will always have access, regardless of role selection.', 'all-in-one-video-gallery' ); ?>
		</p>
	</div>
</div>