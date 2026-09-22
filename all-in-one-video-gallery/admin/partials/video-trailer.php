<?php

/**
 * Video Metabox: [Tab: Trailer / Teaser].
 *
 * @link    https://plugins360.com
 * @since   4.7.6
 *
 * @package All_In_One_Video_Gallery
 */

$trailer_type = isset( $post_meta['trailer_type'] ) ? $post_meta['trailer_type'][0] : '';
$trailer_src  = isset( $post_meta['trailer_src'] ) ? $post_meta['trailer_src'][0] : '';

$can_upload_trailer_to_bunny_stream = false;
if ( aiovg_current_user_can( 'edit_aiovg_video', $post->ID ) ) {
	$can_upload_trailer_to_bunny_stream = aiovg_has_bunny_stream_enabled();
}

$trailer_bunny_stream_video_id = isset( $post_meta['trailer_bunny_stream_video_id'] ) ? $post_meta['trailer_bunny_stream_video_id'][0] : '';
?>

<div class="aiovg-form-controls">
	<div class="aiovg-form-control">
		<label for="aiovg-trailer-type" class="aiovg-form-label"><?php esc_html_e( 'Source Type', 'all-in-one-video-gallery' ); ?></label>
		<?php
		$options = array(
			'' => __( 'Video / Streaming URL', 'all-in-one-video-gallery' ),
		);

		if ( current_user_can( 'unfiltered_html' ) ) {
			$options['embedcode'] = __( 'Third-Party Player Code', 'all-in-one-video-gallery' );
		}
		?>
		<select name="trailer_type" id="aiovg-trailer-type" class="widefat">
			<?php
			foreach ( $options as $key => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $key ),
					selected( $key, $trailer_type, false ),
					esc_html( $label )
				);
			}
			?>
		</select>
	</div>

	<div id="aiovg-field-trailer-src" class="aiovg-form-control aiovg-trailer-fields aiovg-trailer-type-default<?php if ( ! empty( $trailer_bunny_stream_video_id ) ) echo ' aiovg-is-bunny-stream'; ?>">
		<label for="aiovg-trailer-src" class="aiovg-form-label"><?php esc_html_e( 'Trailer Source', 'all-in-one-video-gallery' ); ?></label>
		<div class="aiovg-media-uploader">
			<input type="text" name="trailer_src" id="aiovg-trailer-src" class="widefat" placeholder="<?php esc_attr_e( 'Enter URL: YouTube, Vimeo, Dailymotion, Rumble, Facebook, MP4, HLS, MPEG-DASH, etc.', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( 'embedcode' !== $trailer_type ? $trailer_src : '' ); ?>" />
			<button type="button" class="aiovg-upload-media button" data-format="mp4">
				<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
			</button>
			<?php if ( $can_upload_trailer_to_bunny_stream ) : ?>
				<input type="hidden" name="trailer_bunny_stream_video_id" id="aiovg-trailer_bunny_stream_video_id" value="<?php echo esc_attr( $trailer_bunny_stream_video_id ); ?>" />
				<input type="hidden" name="deletable_trailer_bunny_stream_video_ids" id="aiovg-deletable_trailer_bunny_stream_video_ids" value="" />
				<input type="file" accept="video/*" style="display: none;" />
				<button type="button" id="aiovg-trailer-bunny-stream-upload-button" class="button aiovg-bunny-stream-upload-button">
					<span class="dashicons dashicons-cloud-upload"></span>
					<?php esc_html_e( 'Bunny Stream', 'all-in-one-video-gallery' ); ?>
				</button>
			<?php endif; ?>
		</div>
		<div class="aiovg-upload-status"></div>
	</div>

	<?php if ( current_user_can( 'unfiltered_html' ) ) : ?>
		<div class="aiovg-form-control aiovg-trailer-fields aiovg-trailer-type-embedcode">
			<label for="aiovg-trailer-embedcode" class="aiovg-form-label"><?php esc_html_e( 'Trailer Source', 'all-in-one-video-gallery' ); ?></label>
			<textarea name="trailer_embedcode" id="aiovg-trailer-embedcode" class="widefat" rows="6" placeholder="<?php esc_attr_e( 'Enter your Player Code', 'all-in-one-video-gallery' ); ?>"><?php echo ( 'embedcode' === $trailer_type ) ? esc_textarea( $trailer_src ) : ''; ?></textarea>
			<p class="description">
				<?php
				printf(
					'<span class="aiovg-text-error"><strong>%s</strong></span>: %s',
					esc_html__( 'Warning', 'all-in-one-video-gallery' ),
					esc_html__( 'This field allows "iframe" and "script" tags. So, make sure the code you\'re adding with this field is harmless to your website.', 'all-in-one-video-gallery' )
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>
