<?php

/**
 * Videos: "Restrictions" meta box.
 *
 * @link    https://plugins360.com
 * @since   3.9.6
 *
 * @package All_In_One_Video_Gallery
 */
?>

<div class="aiovg">
	<div id="aiovg-field-access_control">
		<p>
			<strong><?php esc_html_e( 'Who Can Access this Video?', 'all-in-one-video-gallery' ); ?></strong>
		</p>
	
		<select name="access_control" class="widefat">
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

	<div id="aiovg-field-restricted_roles"<?php if ( $access_control != 2 ) { echo ' style="display: none";'; } ?>>
		<p>
			<strong><?php esc_html_e( 'Select User Roles Allowed to Access this Video', 'all-in-one-video-gallery' ); ?></strong>
		</p>	
	
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

		<p>
			<em><?php esc_html_e( 'If no roles are selected, the global setting will be used. Users with editing permissions will always have access, regardless of role selection.', 'all-in-one-video-gallery' ); ?></em>
		</p>
	</div>

	<?php wp_nonce_field( 'aiovg_save_video_restrictions', 'aiovg_video_restrictions_nonce' ); // Nonce ?>
</div>