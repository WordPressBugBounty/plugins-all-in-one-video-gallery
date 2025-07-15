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
?>

<div class="aiovg-form-grid">
	<div class="aiovg-form-control">
		<label for="aiovg-duration" class="aiovg-form-label"><?php esc_html_e( 'Video Duration', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="duration" id="aiovg-duration" class="widefat" placeholder="00:00" value="<?php echo esc_attr( $duration ); ?>" />
	</div>

	<div class="aiovg-form-control">
		<label for="aiovg-views" class="aiovg-form-label"><?php esc_html_e( 'Views Count', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="views" id="aiovg-views" class="widefat" placeholder="19820" value="<?php echo esc_attr( $views ); ?>" />
	</div>

	<div class="aiovg-form-control">
		<label for="aiovg-likes" class="aiovg-form-label"><?php esc_html_e( 'Likes Count', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="likes" id="aiovg-likes" class="widefat" placeholder="182" value="<?php echo esc_attr( $likes ); ?>" />
	</div>

	<div class="aiovg-form-control">
		<label for="aiovg-dislikes" class="aiovg-form-label"><?php esc_html_e( 'Dislikes Count', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="dislikes" id="aiovg-dislikes" class="widefat" placeholder="9" value="<?php echo esc_attr( $dislikes ); ?>" />
	</div>
</div>