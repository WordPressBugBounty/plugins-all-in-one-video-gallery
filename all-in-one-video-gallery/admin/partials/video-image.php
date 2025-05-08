<?php

/**
 * Video Metabox: [Tab: General] "Poster Image" accordion.
 *
 * @link    https://plugins360.com
 * @since   3.5.0
 *
 * @package All_In_One_Video_Gallery
 */

$featured_images_settings = get_option( 'aiovg_featured_images_settings' );

$image              = isset( $post_meta['image'] ) ? $post_meta['image'][0] : '';
$image_alt          = isset( $post_meta['image_alt'] ) ? $post_meta['image_alt'][0] : '';
$set_featured_image = isset( $post_meta['set_featured_image'] ) ? $post_meta['set_featured_image'][0] : 1;
?>

<div class="aiovg-flex aiovg-flex-col aiovg-gap-4">
	<div id="aiovg-field-image" class="aiovg-form-control">
		<label for="aiovg-image" class="aiovg-form-label"><?php esc_html_e( 'Poster Image', 'all-in-one-video-gallery' ); ?></label>
		<div class="aiovg-media-uploader">                                                
			<input type="text" name="image" id="aiovg-image" class="widefat" placeholder="<?php esc_attr_e( 'Enter your direct file URL (OR) upload your file using the button here', 'all-in-one-video-gallery' ); ?> &rarr;" value="<?php echo esc_attr( $image ); ?>" />
			<button type="button" class="aiovg-upload-media button" data-format="image">
				<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
			</button>
		</div>
	</div>				

	<?php do_action( 'aiovg_admin_after_image_field' ); ?> 

	<div id="aiovg-video-image-footer" class="aiovg-flex aiovg-flex-col aiovg-gap-4">
		<div class="aiovg-form-control">
			<label for="aiovg-image_alt"><?php esc_html_e( 'Image Alt Text', 'all-in-one-video-gallery' ); ?></label>
			<input type="text" name="image_alt" id="aiovg-image_alt" class="widefat" placeholder="<?php esc_attr_e( 'Optional', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $image_alt ); ?>" />
			<p class="description">
				<a href="https://www.w3.org/WAI/tutorials/images/decision-tree" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Learn how to describe the purpose of the image.', 'all-in-one-video-gallery' ); ?>
				</a>
			</p>
		</div>

		<?php if ( ! empty( $featured_images_settings['enabled'] ) ) : ?>
			<label>
				<input type="checkbox" name="set_featured_image" value="1" <?php checked( $set_featured_image, 1 ); ?>/>
				<?php esc_html_e( 'Store this image as a featured image', 'all-in-one-video-gallery' ); ?>
			</label>
		<?php endif; ?>
	</div>
</div>