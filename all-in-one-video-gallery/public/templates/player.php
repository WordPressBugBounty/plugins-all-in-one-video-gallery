<?php

/**
 * Video Player.
 *
 * @link     https://plugins360.com
 * @since    1.0.0
 *
 * @package All_In_One_Video_Gallery
 */
 
$player_settings        = aiovg_get_option( 'aiovg_player_settings' );
$brand_settings         = aiovg_get_option( 'aiovg_brand_settings' );
$trailer_settings       = aiovg_get_option( 'aiovg_trailer_settings' );
$privacy_settings       = aiovg_get_option( 'aiovg_privacy_settings' );
$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );
$general_settings       = aiovg_get_option( 'aiovg_general_settings' );

$post_id    = (int) get_query_var( 'aiovg_video', 0 );
$post_type  = 'page';
$post_title = '';
$post_url   = '';
$post_meta  = array();
$embed_url  = '';
$is_trailer = false;

$player_template = ( 'vidstack' == $player_settings['player'] ) ? 'vidstack' : 'videojs';
$primary_color   = ! empty( $general_settings['primary_color'] ) ? $general_settings['primary_color'] : '#3b82f6';

$thirdparty_providers_with_api    = array( 'youtube', 'vimeo' );
$thirdparty_providers_without_api = array( 'dailymotion', 'rumble', 'facebook' );
$thirdparty_providers_all         = array_merge( $thirdparty_providers_with_api, $thirdparty_providers_without_api );
$current_video_provider           = 'html5';

if ( $post_id > 0 ) {
	$post_type  = get_post_type( $post_id );
	$post_title = get_the_title( $post_id );
    $post_url   = get_permalink( $post_id );
		
	if ( 'aiovg_videos' == $post_type ) {
		// Same status rule as the [aiovg_video] shortcode, plus previews by users who can edit the video.
		$post_status    = get_post_status( $post_id );
		$has_permission = true;

		if ( 'publish' != $post_status ) {
			$has_permission = false;

			if ( 'private' == $post_status ) {
				if ( current_user_can( 'read_private_aiovg_videos' ) ) {
					$has_permission = true;
				}
			}
		}

		$has_permission = apply_filters( 'aiovg_read_private_videos', $has_permission, array( 'id' => $post_id ), null );

		if ( ! $has_permission && ! aiovg_current_user_can( 'edit_aiovg_video', $post_id ) ) {
			status_header( 404 );
			nocache_headers();

			esc_html_e( 'Sorry, but you do not have permission to view this video.', 'all-in-one-video-gallery' );
			return false;
		}

		$post_meta = get_post_meta( $post_id );		
		
		// Determine the video provider based on the post meta.
		$current_video_provider = isset( $post_meta['type'] ) ? $post_meta['type'][0] : 'default';

		// Check if the user has access to this video.
		$has_access = aiovg_current_user_can( 'play_aiovg_video', $post_id );

		// If the user doesn't have access, check if there's a trailer configured and use that 
		// for the player instead.
		if ( ! $has_access && ! empty( $trailer_settings['enable_trailers'] ) ) {
			$trailer_type = isset( $post_meta['trailer_type'] ) ? $post_meta['trailer_type'][0] : '';
			$trailer_src  = isset( $post_meta['trailer_src'] ) ? $post_meta['trailer_src'][0] : '';

			if ( ! empty( $trailer_src ) ) {
				$is_trailer = true;
				
				if ( 'embedcode' == $trailer_type ) {
					$post_meta['embedcode'] = array( $trailer_src );
					$current_video_provider = 'embedcode';
				} else {
					// URL auto-detect: mirror the run_shortcode_video() detection chain.
					if ( false !== strpos( $trailer_src, 'youtube.com' ) || false !== strpos( $trailer_src, 'youtu.be' ) ) {
						$post_meta['youtube'] = array( aiovg_resolve_youtube_url( $trailer_src ) );
						$current_video_provider = 'youtube';
					} elseif ( false !== strpos( $trailer_src, 'vimeo.com' ) ) {
						$post_meta['vimeo'] = array( $trailer_src );
						$current_video_provider = 'vimeo';
					} elseif ( false !== strpos( $trailer_src, 'dailymotion.com' ) || false !== strpos( $trailer_src, 'dai.ly' ) ) {
						$post_meta['dailymotion'] = array( $trailer_src );
						$current_video_provider = 'dailymotion';
					} elseif ( false !== strpos( $trailer_src, 'rumble.com' ) ) {
						$post_meta['rumble'] = array( $trailer_src );
						$current_video_provider = 'rumble';
					} elseif ( false !== strpos( $trailer_src, 'facebook.com' ) ) {
						$post_meta['facebook'] = array( $trailer_src );
						$current_video_provider = 'facebook';
					} else {
						$current_video_provider = 'default';

						$filetype = wp_check_filetype( $trailer_src );
						
						if ( 'webm' == $filetype['ext'] ) {
							$post_meta['webm'] = array( $trailer_src );
						} elseif ( 'ogv' == $filetype['ext'] ) {
							$post_meta['ogv'] = array( $trailer_src );
						} elseif ( 'm3u8' == $filetype['ext'] ) {
							$post_meta['hls'] = array( $trailer_src );
							$current_video_provider = 'adaptive';
						} elseif ( 'mpd' == $filetype['ext'] ) {							
							$post_meta['dash'] = array( $trailer_src );
							$current_video_provider = 'adaptive';
						} else {
							$post_meta['mp4'] = array( $trailer_src );
						}						

						// Additionally check for Bunny Stream: inject GUID and force URL into mp4 so
						// the native-controls detection block below activates correctly.
						$trailer_video_id = isset( $post_meta['trailer_bunny_stream_video_id'] ) ? $post_meta['trailer_bunny_stream_video_id'][0] : '';
						
						if ( ! empty( $trailer_video_id ) ) {
							// GUID stored when the trailer was uploaded via Bunny Stream.
							$post_meta['bunny_stream_video_id'] = array( $trailer_video_id );
							$post_meta['mp4'] = array( $trailer_src );
							
							$current_video_provider = 'default';
						}
					}
				}

				$post_meta['type'] = array( $current_video_provider );
				$post_meta['sources'] = array();
			}
		}

		// If the user doesn't have access and there's no trailer, load the restricted template and exit.
		if ( ! $has_access && ! $is_trailer ) {
			include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . 'public/templates/player-restricted.php' );
			return false;
		}
	}
}

// Determine the player template to use based on the video provider and settings.
if ( ! empty( $post_meta ) ) {
	if ( in_array( $current_video_provider, $thirdparty_providers_with_api ) ) {
		$use_native_controls = isset( $player_settings['use_native_controls'][ $current_video_provider ] );
		$use_native_controls = apply_filters( 'aiovg_use_native_controls', $use_native_controls, $current_video_provider );

		if ( $use_native_controls ) {
			$player_template = 'iframe';
		}
	}

	if ( in_array( $current_video_provider, $thirdparty_providers_without_api ) ) {
		$player_template = 'iframe';
	}

	if ( 'embedcode' == $current_video_provider ) {
		$player_template = 'iframe';			
	}

	if ( 'default' == $current_video_provider ) {
		$mp4 = isset( $post_meta['mp4'] ) ? $post_meta['mp4'][0] : '';
		if ( ! empty( $mp4 ) ) {
			$use_native_controls = apply_filters( 'aiovg_use_native_controls', isset( $player_settings['use_native_controls']['bunny_stream'] ), 'bunny_stream' );
			if ( $use_native_controls ) {
				$video_id = isset( $post_meta['bunny_stream_video_id'] ) ? $post_meta['bunny_stream_video_id'][0] : '';
				if ( ! empty( $video_id ) && strpos( $mp4, '/' . $video_id . '/' ) !== false ) {
					$embed_url = aiovg_get_bunny_stream_embed_url( $mp4, $video_id );
					if ( ! empty( $embed_url ) ) {
						$current_video_provider = 'bunny_stream';
						$player_template = 'iframe';
					}
				}
			}
		}
	}
} else {
	foreach ( $thirdparty_providers_with_api as $provider ) {
		$use_native_controls = isset( $player_settings['use_native_controls'][ $provider ] );
		$use_native_controls = apply_filters( 'aiovg_use_native_controls', $use_native_controls, $provider );
	
		if ( isset( $_GET[ $provider ] ) ) {
			$current_video_provider = $provider;
			if ( $use_native_controls ) {
				$player_template = 'iframe';
			}		
		}
	}

	foreach ( $thirdparty_providers_without_api as $provider ) {
		if ( isset( $_GET[ $provider ] ) ) {
			$current_video_provider = $provider;
			$player_template = 'iframe';
		}
	}
}

// GDPR consent handling
if ( ! isset( $_GET['nocookie'] ) ) {
	if ( ! isset( $_COOKIE['aiovg_gdpr_consent'] ) && ! isset( $_COOKIE['aiovg_email_captured'] ) && ! empty( $privacy_settings['show_consent'] ) && ! empty( $privacy_settings['consent_message'] ) && ! empty( $privacy_settings['consent_button_label'] ) ) {
		if ( in_array( $current_video_provider, $thirdparty_providers_all ) || 'iframe' == $player_template ) {
			$player_template = 'gdpr';
		}
	}
}

// Email capture handling
if ( ! isset( $_GET['nocapture'] ) ) {
	// A video played from a bare URL is identified by that URL. Same provider order
	// player-email-capture.php uses, so both derive the same capture key.
	$email_capture_video_url = '';

	if ( 'aiovg_videos' != $post_type || empty( $post_id ) ) {
		foreach ( array( 'mp4', 'webm', 'ogv', 'hls', 'dash', 'youtube', 'vimeo', 'dailymotion', 'rumble', 'facebook' ) as $provider ) {
			if ( isset( $_GET[ $provider ] ) ) {
				$email_capture_video_url = aiovg_base64_decode( $_GET[ $provider ] );
			}
		}
	}

	$email_capture_config = aiovg_get_email_capture_config( array(
		'post_id'   => $post_id,
		'post_type' => $post_type,
		'post_meta' => $post_meta,
		'override'  => isset( $_GET['email_capture'] ) ? $_GET['email_capture'] : null,
		'rolls'     => 'pre',
		'video_url' => $email_capture_video_url
	) );

	// The memory test needs the capture key, so it follows resolution rather than
	// wrapping it.
	if ( false !== $email_capture_config && aiovg_should_display_email_capture( $email_capture_config['capture_key'] ) ) {
		$player_template = 'email-capture';
	}
}

include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . "public/templates/player-{$player_template}.php" );