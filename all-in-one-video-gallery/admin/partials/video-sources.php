<?php

/**
 * Video Metabox: "General" tab.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$player_settings = get_option( 'aiovg_player_settings' );

$quality_levels = explode( "\n", $player_settings['quality_levels'] );
$quality_levels = array_filter( $quality_levels );
$quality_levels = array_map( 'sanitize_text_field', $quality_levels );

$mp4           = isset( $post_meta['mp4'] ) ? $post_meta['mp4'][0] : '';
$has_webm      = isset( $post_meta['has_webm'] ) ? $post_meta['has_webm'][0] : 0;
$webm          = isset( $post_meta['webm'] ) ? $post_meta['webm'][0] : '';
$has_ogv       = isset( $post_meta['has_ogv'] ) ? $post_meta['has_ogv'][0] : 0;
$ogv           = isset( $post_meta['ogv'] ) ? $post_meta['ogv'][0] : '';
$quality_level = isset( $post_meta['quality_level'] ) ? $post_meta['quality_level'][0] : '';
$sources       = isset( $post_meta['sources'] ) ? maybe_unserialize( $post_meta['sources'][0] ) : array();
$hls           = isset( $post_meta['hls'] ) ? $post_meta['hls'][0] : '';
$dash          = isset( $post_meta['dash'] ) ? $post_meta['dash'][0] : '';
$youtube       = isset( $post_meta['youtube'] ) ? $post_meta['youtube'][0] : '';
$vimeo         = isset( $post_meta['vimeo'] ) ? $post_meta['vimeo'][0] : '';
$dailymotion   = isset( $post_meta['dailymotion'] ) ? $post_meta['dailymotion'][0] : '';
$rumble        = isset( $post_meta['rumble'] ) ? $post_meta['rumble'][0] : '';
$facebook      = isset( $post_meta['facebook'] ) ? $post_meta['facebook'][0] : '';
$embedcode     = isset( $post_meta['embedcode'] ) ? $post_meta['embedcode'][0] : '';

$can_upload_to_bunny_stream = false;
if ( aiovg_current_user_can( 'edit_aiovg_video', $post->ID ) ) {
	$can_upload_to_bunny_stream = aiovg_has_bunny_stream_enabled();
}

$bunny_stream_video_id = isset( $post_meta['bunny_stream_video_id'] ) ? $post_meta['bunny_stream_video_id'][0] : '';
?>

<div class="aiovg-flex aiovg-flex-col aiovg-gap-6">
	<div id="aiovg-field-mp4" class="aiovg-form-control aiovg-toggle-fields aiovg-type-default<?php if ( ! empty( $bunny_stream_video_id ) ) echo ' aiovg-is-bunny-stream'; ?>">
		<div class="aiovg-flex aiovg-items-center aiovg-gap-2">
			<label for="aiovg-mp4" class="aiovg-form-label"><?php esc_html_e( 'Video File', 'all-in-one-video-gallery' ); ?></label>
			<span class="description">(mp4, webm, ogv, m4v, mov)</span>
		</div>
		<div class="aiovg-sources aiovg-flex aiovg-flex-col aiovg-gap-6">
			<div class="aiovg-source aiovg-flex aiovg-flex-col aiovg-gap-2">
				<?php
				if ( ! empty( $quality_levels ) ) {
					echo sprintf( 
						'<div class="aiovg-quality-selector aiovg-flex aiovg-flex-col aiovg-gap-2 aiovg-margin-top"%s>', 
						( empty( $sources ) ? ' style="display: none;"' : '' ) 
					);

					echo sprintf(
						'<div class="aiovg-flex aiovg-items-center aiovg-gap-1 aiovg-text-muted"><span class="dashicons dashicons-format-video"></span> %s (%s)</div>',
						esc_html__( 'Select a Quality Level', 'all-in-one-video-gallery' ),
						esc_html__( 'Default quality', 'all-in-one-video-gallery' )
					);

					echo '<div class="aiovg-flex aiovg-flex-wrap aiovg-items-center aiovg-gap-3">';

					echo sprintf( 
						'<label><input type="radio" name="quality_level" value=""%s/>%s</label>',
						checked( $quality_level, '', false ),
						esc_html__( 'None', 'all-in-one-video-gallery' )
					);

					foreach ( $quality_levels as $quality ) {
						echo sprintf( 
							'<label><input type="radio" name="quality_level" value="%s"%s/>%s</label>',
							esc_attr( $quality ),
							checked( $quality_level, $quality, false ),
							esc_html( $quality )
						);
					}

					echo '</div>';
					echo '</div>';
				}
				?>       
				<div class="aiovg-media-uploader">                                         
					<input type="text" name="mp4" id="aiovg-mp4" class="widefat" placeholder="<?php esc_attr_e( 'Enter your direct file URL (OR) upload your file using the button here', 'all-in-one-video-gallery' ); ?> &rarr;" value="<?php echo esc_attr( $mp4 ); ?>" />
					<button type="button" class="aiovg-upload-media button" data-format="mp4">
						<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
					</button>
					<?php if ( $can_upload_to_bunny_stream ) : ?>
						<input type="hidden" name="bunny_stream_video_id" id="aiovg-bunny_stream_video_id" value="<?php echo esc_attr( $bunny_stream_video_id ); ?>" />
						<input type="hidden" name="deletable_bunny_stream_video_ids" id="aiovg-deletable_bunny_stream_video_ids" value="" />								
						<input type="file" accept="video/*" style="display: none;" />
						<button type="button" id="aiovg-bunny-stream-upload-button" class="button">
							<span class="dashicons dashicons-cloud-upload"></span>
							<?php esc_html_e( 'Bunny Stream', 'all-in-one-video-gallery' ); ?>
						</button>
					<?php endif; ?>
				</div>
				<div class="aiovg-upload-status"></div>
			</div>

			<?php if ( ! empty( $quality_levels ) ) : ?>
				<?php if ( ! empty( $sources ) ) : 
					foreach ( $sources as $index => $source ) :	?>
						<div class="aiovg-source aiovg-flex aiovg-flex-col aiovg-gap-2">
							<?php
							echo '<div class="aiovg-quality-selector aiovg-flex aiovg-flex-col aiovg-gap-2">';

							echo sprintf(
								'<div class="aiovg-flex aiovg-items-center aiovg-gap-1 aiovg-text-muted"><span class="dashicons dashicons-format-video"></span> %s</div>',
								esc_html__( 'Select a Quality Level', 'all-in-one-video-gallery' )
							);

							echo '<div class="aiovg-flex aiovg-flex-wrap aiovg-items-center aiovg-gap-3">';

							echo sprintf( 
								'<label><input type="radio" name="quality_levels[%d]" value=""%s/>%s</label>',
								$index,
								checked( $source['quality'], '', false ),
								esc_html__( 'None', 'all-in-one-video-gallery' )
							);

							foreach ( $quality_levels as $quality ) {
								echo sprintf( 
									'<label><input type="radio" name="quality_levels[%d]" value="%s"%s/>%s</label>',
									$index,
									esc_attr( $quality ),
									checked( $source['quality'], $quality, false ),
									esc_html( $quality )
								);
							}
							
							echo '</div>';
							echo '</div>';
							?>
							<div class="aiovg-media-uploader">
								<input type="text" name="sources[<?php echo $index; ?>]" class="widefat" placeholder="<?php esc_attr_e( 'Enter your direct file URL (OR) upload your file using the button here', 'all-in-one-video-gallery' ); ?> &rarr;" value="<?php echo esc_attr( $source['src'] ); ?>" />
								<button type="button" class="aiovg-upload-media button" data-format="mp4">
									<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( count( $sources ) < ( count( $quality_levels ) - 1 ) ) : ?>
					<a href="javascript:;" id="aiovg-add-new-source" class="aiovg-font-bold" data-limit="<?php echo count( $quality_levels ); ?>">
						<?php esc_html_e( '[+] Add More Quality Levels', 'all-in-one-video-gallery' ); ?>
					</a>
				<?php endif; ?>
			<?php endif; ?>  
		</div>
	</div>

	<?php if ( ! empty( $webm ) ) : ?>
		<div id="aiovg-field-webm" class="aiovg-form-control aiovg-toggle-fields aiovg-type-default">
			<div class="aiovg-flex aiovg-items-center aiovg-gap-2">
				<label for="aiovg-webm" class="aiovg-form-label"><?php esc_html_e( 'WebM', 'all-in-one-video-gallery' ); ?></label>
				<span class="description">(<?php esc_html_e( 'deprecated', 'all-in-one-video-gallery' ); ?>)</span>
			</div>
			<div class="aiovg-media-uploader">                                                
				<input type="text" name="webm" id="aiovg-webm" class="widefat" placeholder="<?php esc_attr_e( 'Enter your direct file URL (OR) upload your file using the button here', 'all-in-one-video-gallery' ); ?> &rarr;" value="<?php echo esc_attr( $webm ); ?>" />
				<button type="button" class="aiovg-upload-media button" data-format="webm">
					<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
				</button>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $ogv ) ) : ?>
		<div id="aiovg-field-ogv" class="aiovg-form-control aiovg-toggle-fields aiovg-type-default">
			<div class="aiovg-flex aiovg-items-center aiovg-gap-2">
				<label for="aiovg-ogv" class="aiovg-form-label"><?php esc_html_e( 'OGV', 'all-in-one-video-gallery' ); ?></label>
				<span class="description">(<?php esc_html_e( 'deprecated', 'all-in-one-video-gallery' ); ?>)</span>
			</div>
			<div class="aiovg-media-uploader">                                                
				<input type="text" name="ogv" id="aiovg-ogv" class="widefat" placeholder="<?php esc_attr_e( 'Enter your direct file URL (OR) upload your file using the button here', 'all-in-one-video-gallery' ); ?> &rarr;" value="<?php echo esc_attr( $ogv ); ?>" />
				<button type="button" class="aiovg-upload-media button" data-format="ogv">
					<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
				</button>
			</div> 
		</div> 
	<?php endif; ?> 

	<div class="aiovg-form-control aiovg-toggle-fields aiovg-type-adaptive">
		<label for="aiovg-hls" class="aiovg-form-label"><?php esc_html_e( 'HLS URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="hls" id="aiovg-hls" class="widefat" placeholder="<?php printf( '%s: https://www.mysite.com/stream.m3u8', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $hls ); ?>" />
	</div>

	<div class="aiovg-form-control aiovg-toggle-fields aiovg-type-adaptive">
		<label for="aiovg-dash" class="aiovg-form-label"><?php esc_html_e( 'MPEG-DASH URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="dash" id="aiovg-dash" class="widefat" placeholder="<?php printf( '%s: https://www.mysite.com/stream.mpd', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $dash ); ?>" />
	</div>

	<div id="aiovg-field-youtube" class="aiovg-form-control aiovg-toggle-fields aiovg-type-youtube">
		<label for="aiovg-youtube" class="aiovg-form-label"><?php esc_html_e( 'YouTube URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="youtube" id="aiovg-youtube" class="widefat" placeholder="<?php printf( '%s: https://www.youtube.com/watch?v=twYp6W6vt2U', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $youtube ); ?>" />
	</div>

	<div id="aiovg-field-vimeo" class="aiovg-form-control aiovg-toggle-fields aiovg-type-vimeo">
		<label for="aiovg-vimeo" class="aiovg-form-label"><?php esc_html_e( 'Vimeo URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="vimeo" id="aiovg-vimeo" class="widefat" placeholder="<?php printf( '%s: https://vimeo.com/108018156', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $vimeo ); ?>" />
	</div>

	<div id="aiovg-field-dailymotion" class="aiovg-form-control aiovg-toggle-fields aiovg-type-dailymotion">
		<label for="aiovg-dailymotion" class="aiovg-form-label"><?php esc_html_e( 'Dailymotion URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="dailymotion" id="aiovg-dailymotion" class="widefat" placeholder="<?php printf( '%s: https://www.dailymotion.com/video/x11prnt', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $dailymotion ); ?>" />
	</div>
			
	<div id="aiovg-field-rumble" class="aiovg-form-control aiovg-toggle-fields aiovg-type-rumble">
		<label for="aiovg-rumble" class="aiovg-form-label"><?php esc_html_e( 'Rumble URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="rumble" id="aiovg-rumble" class="widefat" placeholder="<?php printf( '%s: https://rumble.com/val8vm-how-to-use-rumble.html', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $rumble ); ?>" />
	</div>

	<div id="aiovg-field-facebook" class="aiovg-form-control aiovg-toggle-fields aiovg-type-facebook">
		<label for="aiovg-facebook" class="aiovg-form-label"><?php esc_html_e( 'Facebook URL', 'all-in-one-video-gallery' ); ?></label>
		<input type="text" name="facebook" id="aiovg-facebook" class="widefat" placeholder="<?php printf( '%s: https://www.facebook.com/facebook/videos/10155278547321729', esc_attr__( 'Example', 'all-in-one-video-gallery' ) ); ?>" value="<?php echo esc_url( $facebook ); ?>" />
	</div>

	<div id="aiovg-field-embedcode" class="aiovg-form-control aiovg-toggle-fields aiovg-type-embedcode">
		<label for="aiovg-embedcode" class="aiovg-form-label"><?php esc_html_e( 'Player Code', 'all-in-one-video-gallery' ); ?></label>
		<textarea name="embedcode" id="aiovg-embedcode" class="widefat" rows="6" placeholder="<?php esc_attr_e( 'Enter your Player Code', 'all-in-one-video-gallery' ); ?>"><?php echo esc_textarea( $embedcode ); ?></textarea>
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

	<?php do_action( 'aiovg_admin_add_video_source_fields', $post->ID ); ?>

	<template id="aiovg-template-source">
		<div class="aiovg-source aiovg-flex aiovg-flex-col aiovg-gap-2">
			<?php
			echo '<div class="aiovg-quality-selector aiovg-flex aiovg-flex-col aiovg-gap-2">';

			echo sprintf(
				'<div class="aiovg-flex aiovg-items-center aiovg-gap-1 aiovg-text-muted"><span class="dashicons dashicons-format-video"></span> %s</div>',
				esc_html__( 'Select a Quality Level', 'all-in-one-video-gallery' )
			);

			echo '<div class="aiovg-flex aiovg-flex-wrap aiovg-items-center aiovg-gap-3">';

			echo sprintf( 
				'<label><input type="radio" value=""/>%s</label>',
				esc_html__( 'None', 'all-in-one-video-gallery' )
			);

			foreach ( $quality_levels as $quality ) {
				echo sprintf( 
					'<label><input type="radio" value="%s"/>%s</label>',
					esc_attr( $quality ),
					esc_html( $quality )
				);
			}

			echo '</div>';
			echo '</div>';
			?>
			<div class="aiovg-media-uploader">
				<input type="text" class="widefat" placeholder="<?php esc_attr_e( 'Enter your direct file URL (OR) upload your file using the button here', 'all-in-one-video-gallery' ); ?> &rarr;" value="" />
				<button type="button" class="aiovg-upload-media button" data-format="mp4">
					<?php esc_html_e( 'Upload File', 'all-in-one-video-gallery' ); ?>
				</button>
			</div>
		</div>
	</template>
</div>