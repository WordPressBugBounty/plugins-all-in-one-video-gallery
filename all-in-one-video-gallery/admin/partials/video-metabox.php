<?php

/**
 * Video Metabox.
 *
 * @link    https://plugins360.com
 * @since   4.2.0
 *
 * @package All_In_One_Video_Gallery
 */

$restrictions_settings = aiovg_get_option( 'aiovg_restrictions_settings' );

$type = isset( $post_meta['type'] ) ? $post_meta['type'][0] : 'default';
?>

<div class="aiovg aiovg-metabox-ui">
	<!-- Tabs -->
	<div class="aiovg-tabs">
		<button type="button" class="aiovg-tab aiovg-active" data-target="#aiovg-tab-content-general">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
				<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
			</svg>
			<?php esc_html_e( 'General', 'all-in-one-video-gallery' ); ?>
		</button>
		<button type="button" class="aiovg-tab" data-target="#aiovg-tab-content-tracks">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
			</svg>
			<?php esc_html_e( 'Subtitles', 'all-in-one-video-gallery' ); ?>
		</button>
		<button type="button" class="aiovg-tab" data-target="#aiovg-tab-content-chapters">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="22" height="22" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
			</svg>
			<?php esc_html_e( 'Chapters', 'all-in-one-video-gallery' ); ?>
		</button>
		<?php if ( ! empty( $restrictions_settings['enable_restrictions'] ) ) : ?>
			<button type="button" class="aiovg-tab" data-target="#aiovg-tab-content-restrictions">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
				</svg>
				<?php esc_html_e( 'Restrictions', 'all-in-one-video-gallery' ); ?>
			</button>
		<?php endif; ?>
	</div>

	<!-- Tab Content: General -->
	<div id="aiovg-tab-content-general" class="aiovg-tab-content">
		<!-- Accordion: Source Type -->
		<div class="aiovg-accordion" data-collapsible="false">
			<div class="aiovg-accordion-body">
				<div id="aiovg-field-type" class="aiovg-form-control">
					<label for="aiovg-video-type" class="aiovg-form-label"><?php esc_html_e( 'Source Type', 'all-in-one-video-gallery' ); ?></label>
					<select name="type" id="aiovg-video-type" class="widefat">
						<?php 
						$options = aiovg_get_video_source_types( true );

						foreach ( $options as $key => $label ) {
							printf( 
								'<option value="%s"%s>%s</option>', 
								esc_attr( $key ), 
								selected( $key, $type, false ), 
								esc_html( $label )
							);
						}
						?>
					</select>
				</div>
			</div>
		</div>

		<!-- Accordion: Video Sources -->
		<div class="aiovg-accordion" data-collapsible="false">
			<div class="aiovg-accordion-body">
				<?php require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-sources.php'; ?>
			</div>
		</div>

		<!-- Accordion: Poster Image -->
		<div class="aiovg-accordion" data-collapsible="false">
			<div class="aiovg-accordion-body">
				<?php require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-image.php'; ?>
			</div>
		</div>

		<!-- Accordion: Additional Video Info -->
		<div class="aiovg-accordion" data-collapsible="true">
			<button type="button" class="aiovg-accordion-header">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z" />
				</svg>
				<?php esc_html_e( 'Additional Video Info', 'all-in-one-video-gallery' ); ?>
				<span class="dashicons dashicons-arrow-down-alt2"></span>
			</button>
			<div class="aiovg-accordion-body">
				<?php require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-additional-info.php'; ?>
			</div>
		</div>
	</div>

	<!-- Tab Content: Subtitles -->
	<div id="aiovg-tab-content-tracks" class="aiovg-tab-content" style="display: none;">
		<div class="aiovg-accordion" data-collapsible="false">
			<div class="aiovg-accordion-body">
				<?php require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-tracks.php'; ?>
			</div>
		</div>
	</div>

	<!-- Tab Content: Chapters -->
	<div id="aiovg-tab-content-chapters" class="aiovg-tab-content" style="display: none;">
		<div class="aiovg-accordion" data-collapsible="false">
			<div class="aiovg-accordion-body">
				<?php require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-chapters.php'; ?>
			</div>
		</div>
	</div>

	<?php if ( ! empty( $restrictions_settings['enable_restrictions'] ) ) : ?>
		<!-- Tab Content: Restrictions -->
		<div id="aiovg-tab-content-restrictions" class="aiovg-tab-content" style="display: none;">
			<div class="aiovg-accordion" data-collapsible="false">
				<div class="aiovg-accordion-body">
					<?php wp_nonce_field( 'aiovg_save_video_restrictions', 'aiovg_video_restrictions_nonce' ); // Nonce ?>
					<?php require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-restrictions.php'; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php wp_nonce_field( 'aiovg_save_video_metabox', 'aiovg_video_metabox_nonce' ); // Nonce ?>
</div>