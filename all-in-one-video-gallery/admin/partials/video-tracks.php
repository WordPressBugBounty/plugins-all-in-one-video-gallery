<?php

/**
 * Video Metabox: "Subtitles" tab.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$tracks = array();

if ( ! empty( $post_meta['track'] ) ) {
	foreach ( $post_meta['track'] as $track ) {
		$tracks[] = maybe_unserialize( $track );
	}
}
?>

<div class="aiovg-form-controls aiovg-repeatable-ui">
	<table id="aiovg-tracks" class="aiovg-repeatable-table form-table striped">
		<tbody>
			<?php foreach ( $tracks as $key => $track ) : ?>
				<tr>
					<td class="aiovg-sort-handle">
						<span class="dashicons dashicons-move"></span>
					</td>
					<td>
						<div class="aiovg-repeatable-fields">
							<label class="aiovg-repeatable-field aiovg-track-src">
								<?php esc_html_e( 'File URL', 'all-in-one-video-gallery' ); ?>           
								<input type="text" name="track_src[]" class="widefat" value="<?php echo esc_attr( $track['src'] ); ?>" />
							</label>

							<label class="aiovg-repeatable-field aiovg-track-label">
								<?php esc_html_e( 'Label', 'all-in-one-video-gallery' ); ?>			
								<input type="text" name="track_label[]" class="widefat" placeholder="<?php esc_attr_e( 'English', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $track['label'] ); ?>" />
							</label>
			
							<label class="aiovg-repeatable-field aiovg-track-srclang">
								<?php esc_html_e( 'Srclang', 'all-in-one-video-gallery' ); ?>
								<input type="text" name="track_srclang[]" class="widefat" placeholder="<?php esc_attr_e( 'en', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $track['srclang'] ); ?>" />
							</label>
					
							<div class="aiovg-repeatable-buttons">
								<button type="button" class="aiovg-button aiovg-upload-track button">
									<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
								</button>

								<button type="button" class="aiovg-button aiovg-button-delete button">
									<?php esc_html_e( 'Delete', 'all-in-one-video-gallery' ); ?>
								</button>
							</div>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<!-- Button: Add New Track -->
	<div class="aiovg-repeatable-button">
		<button type="button" class="aiovg-button aiovg-button-add button" data-href="#aiovg-template-track">
			<?php esc_html_e( 'Add New Track', 'all-in-one-video-gallery' ); ?>
		</button>
	</div>

	<template id="aiovg-template-track">
		<tr>
			<td class="aiovg-sort-handle">
				<span class="dashicons dashicons-move"></span>
			</td>
			<td>
				<div class="aiovg-repeatable-fields">
					<label class="aiovg-repeatable-field aiovg-track-src">
						<?php esc_html_e( 'File URL', 'all-in-one-video-gallery' ); ?>             
						<input type="text" name="track_src[]" class="widefat" />
					</label>

					<label class="aiovg-repeatable-field aiovg-track-label">
						<?php esc_html_e( 'Label', 'all-in-one-video-gallery' ); ?>		
						<input type="text" name="track_label[]" class="widefat" placeholder="<?php esc_attr_e( 'English', 'all-in-one-video-gallery' ); ?>" />
					</label>

					<label class="aiovg-repeatable-field aiovg-track-srclang">
						<?php esc_html_e( 'Srclang', 'all-in-one-video-gallery' ); ?>
						<input type="text" name="track_srclang[]" class="widefat" placeholder="<?php esc_attr_e( 'en', 'all-in-one-video-gallery' ); ?>" />
					</label>
			
					<div class="aiovg-repeatable-buttons">
						<button type="button" class="aiovg-button aiovg-upload-track button">
							<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
						</button>

						<button type="button" class="aiovg-button aiovg-button-delete button">
							<?php esc_html_e( 'Delete', 'all-in-one-video-gallery' ); ?>
						</button>
					</div>
				</div>
			</td>
		</tr>		
	</template>
</div>