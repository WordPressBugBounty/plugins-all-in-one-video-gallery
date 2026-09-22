<?php

/**
 * Video Metabox: "Email Capture" tab.
 *
 * @link    https://plugins360.com
 * @since   4.7.6
 *
 * @package All_In_One_Video_Gallery
 */

$email_capture              = isset( $post_meta['email_capture'] ) ? $post_meta['email_capture'][0] : -1;
$email_capture_notification = isset( $post_meta['email_capture_notification_email'] ) ? $post_meta['email_capture_notification_email'][0] : '';

$email_lead_captured_settings = aiovg_get_option( 'aiovg_email_lead_captured_settings' );
$global_notification_email    = isset( $email_lead_captured_settings['notification_email'] ) ? $email_lead_captured_settings['notification_email'] : '';
$notify_video_author          = isset( $email_lead_captured_settings['notify_video_author'] ) ? (int) $email_lead_captured_settings['notify_video_author'] : 0;
?>

<div class="aiovg-form-controls">
	<div id="aiovg-field-email_capture" class="aiovg-form-control">
		<label for="aiovg-email_capture" class="aiovg-form-label"><?php esc_html_e( 'Email Capture', 'all-in-one-video-gallery' ); ?></label>

		<select name="email_capture" id="aiovg-email_capture" class="widefat">
			<?php
			$options = array(
				-1 => '— ' . __( 'Default', 'all-in-one-video-gallery' ) . ' —',
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
			<?php
			printf(
				/* translators: %s: the state the site-wide setting currently resolves to, "On" or "Off". */
				esc_html__( 'Show an email collection form on this video to capture leads. Choose "Default" to follow the site-wide Email Capture setting, currently %s.', 'all-in-one-video-gallery' ),
				aiovg_is_email_capture_global_enabled() ? esc_html__( 'On', 'all-in-one-video-gallery' ) : esc_html__( 'Off', 'all-in-one-video-gallery' )
			);
			?>
		</p>
	</div>

	<div id="aiovg-field-email_capture_notification_email" class="aiovg-form-control">
		<label for="aiovg-email_capture_notification_email" class="aiovg-form-label"><?php esc_html_e( 'Notification Email', 'all-in-one-video-gallery' ); ?></label>

		<input type="text" name="email_capture_notification_email" id="aiovg-email_capture_notification_email" class="widefat" value="<?php echo esc_attr( $email_capture_notification ); ?>" placeholder="leads@example.com, sales@example.com" />

		<p class="description">
			<?php
			esc_html_e( 'Send the leads captured on this video to these addresses instead of the site-wide ones. Separate multiple addresses with commas.', 'all-in-one-video-gallery' );
			echo ' ';

			if ( ! empty( $global_notification_email ) ) {
				printf(
					/* translators: %s: the email address, or comma-separated addresses, configured site-wide. */
					esc_html__( 'Leave empty to use the site-wide address, currently %s.', 'all-in-one-video-gallery' ),
					esc_html( $global_notification_email )
				);
			} elseif ( $notify_video_author ) {
				esc_html_e( 'Leave empty to use the site-wide address, which is not set at the moment — so leads from this video would be emailed only to the video\'s author.', 'all-in-one-video-gallery' );
			} else {
				esc_html_e( 'Leave empty to use the site-wide address, which is not set at the moment — so leads from this video would be saved, but not emailed to anyone.', 'all-in-one-video-gallery' );
			}
			?>
		</p>
	</div>
</div>
