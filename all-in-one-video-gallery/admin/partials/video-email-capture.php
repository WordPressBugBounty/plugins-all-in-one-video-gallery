<?php

/**
 * Video Metabox: "Email Capture" tab.
 *
 * @link    https://plugins360.com
 * @since   4.7.6
 *
 * @package All_In_One_Video_Gallery
 */

$email_capture = isset( $post_meta['email_capture'] ) ? $post_meta['email_capture'][0] : -1;
?>

<div class="aiovg-form-controls">
	<div id="aiovg-field-email_capture" class="aiovg-form-control">
		<label for="aiovg-email_capture" class="aiovg-form-label"><?php esc_html_e( 'Email Capture', 'all-in-one-video-gallery' ); ?></label>

		<select name="email_capture" id="aiovg-email_capture" class="widefat">
			<?php
			$options = array(
				-1 => '— ' . __( 'Global', 'all-in-one-video-gallery' ) . ' —',
				1  => __( 'Enable', 'all-in-one-video-gallery' ),
				0  => __( 'Disable', 'all-in-one-video-gallery' ),
			);

			foreach ( $options as $key => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $key ),
					selected( $key, $email_capture, false ),
					esc_html( $label )
				);
			}
			?>
		</select>

		<p class="description">
			<?php esc_html_e( 'Show an email collection form on this video to capture leads.', 'all-in-one-video-gallery' ); ?>
		</p>
	</div>
</div>
