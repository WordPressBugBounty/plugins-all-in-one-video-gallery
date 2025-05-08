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

<div class="aiovg-flex aiovg-flex-col aiovg-gap-4">
	<p class="description">
		<?php printf( __( 'The chapters can also be included in the video description. Kindly <a href="%s" target="_blank" rel="noopener noreferrer">follow this link</a>.', 'all-in-one-video-gallery' ), 'https://plugins360.com/all-in-one-video-gallery/adding-chapters/' ); ?>
	</p>

	<table id="aiovg-chapters" class="aiovg-table form-table striped">
		<tbody>
			<?php foreach ( $chapters as $key => $chapter ) : ?>
				<tr class="aiovg-chapters-row">
					<td class="aiovg-handle">
						<span class="aiovg-text-muted dashicons dashicons-move"></span>
					</td>
					<td>
						<div class="aiovg-chapter">
							<div class="aiovg-chapter-time">
								<label class="aiovg-text-small"><?php esc_html_e( 'Time', 'all-in-one-video-gallery' ); ?></label>				
								<input type="text" name="chapter_time[]" class="widefat" placeholder="<?php esc_attr_e( 'HH:MM:SS', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $chapter['time'] ); ?>" />
							</div>	

							<div class="aiovg-chapter-label">
								<label class="aiovg-text-small"><?php esc_html_e( 'Label', 'all-in-one-video-gallery' ); ?></label>				
								<input type="text" name="chapter_label[]" class="widefat" placeholder="<?php esc_attr_e( 'Chapter Title', 'all-in-one-video-gallery' ); ?>" value="<?php echo esc_attr( $chapter['label'] ); ?>" />
							</div>													
					
							<div class="aiovg-chapter-buttons">
								<button type="button" class="aiovg-delete-chapter button">
									<?php esc_html_e( 'Delete', 'all-in-one-video-gallery' ); ?>
								</button>
							</div>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<a href="javascript:;" id="aiovg-add-new-chapter" class="aiovg-font-bold">
		<?php esc_html_e( '[+] Add New Chapter', 'all-in-one-video-gallery' ); ?>
	</a>

	<template id="aiovg-template-chapter">
		<tr class="aiovg-chapters-row">
			<td class="aiovg-handle">
				<span class="aiovg-text-muted dashicons dashicons-move"></span>
			</td>
			<td>
				<div class="aiovg-chapter">
					<div class="aiovg-chapter-time">
						<label class="aiovg-text-small"><?php esc_html_e( 'Time', 'all-in-one-video-gallery' ); ?></label>				
						<input type="text" name="chapter_time[]" class="widefat" placeholder="<?php esc_attr_e( 'HH:MM:SS', 'all-in-one-video-gallery' ); ?>" />
					</div>

					<div class="aiovg-chapter-label">
						<label class="aiovg-text-small"><?php esc_html_e( 'Label', 'all-in-one-video-gallery' ); ?></label>				
						<input type="text" name="chapter_label[]" class="widefat" placeholder="<?php esc_attr_e( 'Chapter Title', 'all-in-one-video-gallery' ); ?>" />
					</div>
			
					<div class="aiovg-chapter-buttons">
						<button type="button" class="aiovg-delete-chapter button">
							<?php esc_html_e( 'Delete', 'all-in-one-video-gallery' ); ?>
						</button>
					</div>
				</div>
			</td>
		</tr>		
	</template>
</div>