<?php

/**
 * Video Metabox: "Chapters" tab.
 *
 * @link    https://plugins360.com
 * @since   3.6.0
 *
 * @package All_In_One_Video_Gallery
 */

$chapters = array();

if ( ! empty( $post_meta['chapter'] ) ) {
	foreach ( $post_meta['chapter'] as $chapter ) {
		$chapters[] = maybe_unserialize( $chapter );
	}
}
?>

<div class="aiovg-form-controls aiovg-repeatable-ui">
	<p class="description">
		<?php printf( __( 'The chapters can also be included in the video description. Kindly <a href="%s" target="_blank" rel="noopener noreferrer">follow this link</a>.', 'all-in-one-video-gallery' ), 'https://plugins360.com/all-in-one-video-gallery/adding-chapters/' ); ?>
	</p>

	<table id="aiovg-chapters" class="aiovg-repeatable-table form-table striped">
		<tbody>
			<?php foreach ( $chapters as $key => $chapter ) : ?>
				<tr>
					<td class="aiovg-sort-handle">
						<span class="dashicons dashicons-move"></span>
					</td>
					<td>
						<div class="aiovg-repeatable-fields">
							<label class="aiovg-repeatable-field aiovg-chapter-time">
								<?php esc_html_e( 'Time', 'all-in-one-video-gallery' ); ?>				
								<input type="text" name="chapter_time[]" class="widefat" placeholder="<?php esc_attr_e( 'HH:MM:SS', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $chapter['time'] ); ?>" />
							</label>	

							<label class="aiovg-repeatable-field aiovg-chapter-label">
								<?php esc_html_e( 'Label', 'all-in-one-video-gallery' ); ?>			
								<input type="text" name="chapter_label[]" class="widefat" placeholder="<?php esc_attr_e( 'Chapter Title', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $chapter['label'] ); ?>" />
							</label>													
					
							<button type="button" class="aiovg-button aiovg-button-delete button">
								<?php esc_html_e( 'Delete', 'all-in-one-video-gallery' ); ?>
							</button>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<!-- Button: Add New Chapter -->
	<div class="aiovg-repeatable-button">
		<button type="button" class="aiovg-button aiovg-button-add button" data-href="#aiovg-template-chapter">
			<?php esc_html_e( 'Add New Chapter', 'all-in-one-video-gallery' ); ?>
		</button>
	</div>

	<template id="aiovg-template-chapter">
		<tr>
			<td class="aiovg-sort-handle">
				<span class="dashicons dashicons-move"></span>
			</td>
			<td>
				<div class="aiovg-repeatable-fields">
					<label class="aiovg-repeatable-field aiovg-chapter-time">
						<?php esc_html_e( 'Time', 'all-in-one-video-gallery' ); ?>		
						<input type="text" name="chapter_time[]" class="widefat" placeholder="<?php esc_attr_e( 'HH:MM:SS', 'all-in-one-video-gallery' ); ?>" />
					</label>

					<label class="aiovg-repeatable-field aiovg-chapter-label">
						<?php esc_html_e( 'Label', 'all-in-one-video-gallery' ); ?>			
						<input type="text" name="chapter_label[]" class="widefat" placeholder="<?php esc_attr_e( 'Chapter Title', 'all-in-one-video-gallery' ); ?>" />
					</label>
			
					<button type="button" class="aiovg-button aiovg-button-delete button">
						<?php esc_html_e( 'Delete', 'all-in-one-video-gallery' ); ?>
					</button>
				</div>
			</td>
		</tr>		
	</template>
</div>