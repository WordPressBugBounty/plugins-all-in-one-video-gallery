<?php

/**
 * Video Metabox: [Tab: General] "Additional Video Info" accordion.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$duration = isset( $post_meta['duration'] ) ? $post_meta['duration'][0] : '';
$views    = isset( $post_meta['views'] ) ? $post_meta['views'][0] : '';
$likes    = isset( $post_meta['likes'] ) ? $post_meta['likes'][0] : '';
$dislikes = isset( $post_meta['dislikes'] ) ? $post_meta['dislikes'][0] : '';
$download = isset( $post_meta['download'] ) ? $post_meta['download'][0] : 1;
?>

<div class="aiovg-flex aiovg-flex-col aiovg-gap-6">
	<div class="aiovg-grid aiovg-col-2">
		<div class="aiovg-form-control">
			<label for="aiovg-duration" class="aiovg-form-label"><?php esc_html_e( 'Video Duration', 'all-in-one-video-gallery' ); ?></label>
			<input type="text" name="duration" id="aiovg-duration" class="widefat" placeholder="00:00" value="<?php echo esc_attr( $duration ); ?>" />
		</div>

		<div class="aiovg-form-control">
			<label for="aiovg-views" class="aiovg-form-label"><?php esc_html_e( 'Views Count', 'all-in-one-video-gallery' ); ?></label>
			<input type="text" name="views" id="aiovg-views" class="widefat" value="<?php echo esc_attr( $views ); ?>" />
		</div>

		<div class="aiovg-form-control">
			<label for="aiovg-likes" class="aiovg-form-label"><?php esc_html_e( 'Likes Count', 'all-in-one-video-gallery' ); ?></label>
			<input type="text" name="likes" id="aiovg-likes" class="widefat" value="<?php echo esc_attr( $likes ); ?>" />
		</div>

		<div class="aiovg-form-control">
			<label for="aiovg-dislikes" class="aiovg-form-label"><?php esc_html_e( 'Dislikes Count', 'all-in-one-video-gallery' ); ?></label>
			<input type="text" name="dislikes" id="aiovg-dislikes" class="widefat" value="<?php echo esc_attr( $dislikes ); ?>" />
		</div>
	</div>

	<div id="aiovg-field-download" class="aiovg-form-control aiovg-toggle-fields aiovg-type-default">
		<label>
			<input type="checkbox" name="download" id="aiovg-download" value="1" <?php checked( $download, 1 ); ?> />
			<?php esc_html_e( 'Check this option to allow users to download this video.', 'all-in-one-video-gallery' ); ?>
		</label>
	</div>
</div>