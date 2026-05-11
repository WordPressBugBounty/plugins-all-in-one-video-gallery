<?php

/**
 * Video.js Player.
 *
 * @link     https://plugins360.com
 * @since    3.3.1
 *
 * @package All_In_One_Video_Gallery
 */

$settings = array(	
	'uid'            => isset( $_GET['uid'] ) ? sanitize_text_field( $_GET['uid'] ) : 0,
	'post_id'        => $post_id,
	'post_type'      => $post_type,
	'is_trailer'     => $is_trailer,
	'cc_load_policy' => isset( $_GET['cc_load_policy'] ) ? (int) $_GET['cc_load_policy'] : (int) $player_settings['cc_load_policy'],
	'hotkeys'        => isset( $player_settings['hotkeys'] ) && ! empty( $player_settings['hotkeys'] ) ? 1 : 0,	
	'statistics'     => isset( $general_settings['statistics'] ) ? (int) $general_settings['statistics'] : 1,
	'player'         => array(
		'controlBar'                => array(),
		'liveui'                    => true,
		'textTrackSettings'         => false,
		'playbackRates'             => array( 0.5, 0.75, 1, 1.5, 2 ),
		'techCanOverridePoster'     => true,
		'suppressNotSupportedError' => true
	),
	'i18n'           => array(
		'stream_not_found' => __( 'This stream is currently not live. Please check back or refresh your page.', 'all-in-one-video-gallery' )
	)
);

$autoadvance = isset( $_GET['autoadvance'] ) ? (int) $_GET['autoadvance'] : 0;
if ( $autoadvance ) {
	$settings['autoadvance'] = 1;
}

// Video Sources
$sources = array();
$allowed_types = array( 'mp4', 'webm', 'ogv', 'hls', 'dash', 'youtube', 'vimeo' );

if ( ! empty( $post_meta ) ) {
	$type = $post_meta['type'][0];

	switch ( $type ) {
		case 'default':
			$types = array( 'mp4', 'webm', 'ogv' );

			foreach ( $types as $type ) {
				if ( ! empty( $post_meta[ $type ][0] ) ) {
					$ext   = $type;
					$label = '';

					if ( 'mp4' == $type ) {
						$ext = aiovg_get_file_ext( $post_meta[ $type ][0] );
						if ( ! in_array( $ext, array( 'webm', 'ogv' ) ) ) {
							$ext = 'mp4';
						}

						if ( ! empty( $post_meta['quality_level'][0] ) ) {
							$label = $post_meta['quality_level'][0];
						}
					}

					$sources[ $type ] = array(
						'type' => "video/{$ext}",
						'src'  => $post_meta[ $type ][0]
					);

					if ( ! empty( $label ) ) {
						$sources[ $type ]['label'] = $label;
					}
				}
			}

			if ( ! empty( $post_meta['sources'][0] ) ) {
				$_sources = maybe_unserialize( $post_meta['sources'][0] );

				foreach ( $_sources as $source ) {
					if ( ! empty( $source['quality'] ) && ! empty( $source['src'] ) ) {	
						$ext = aiovg_get_file_ext( $source['src'] );
						if ( ! in_array( $ext, array( 'webm', 'ogv' ) ) ) {
							$ext = 'mp4';
						}

						$label = $source['quality'];

						$sources[ $label ] = array(
							'type'  => "video/{$ext}",
							'src'   => $source['src'],
							'label' => $label
						);
					}
				}
			}
			break;
		case 'adaptive':
			$hls = isset( $post_meta['hls'] ) ? $post_meta['hls'][0] : '';
			if ( ! empty( $hls ) ) {
				$sources['hls'] = array(
					'type' => 'application/x-mpegurl',
					'src'  => $hls
				);
			}

			$dash = isset( $post_meta['dash'] ) ? $post_meta['dash'][0] : '';
			if ( ! empty( $dash ) ) {
				$sources['dash'] = array(
					'type' => 'application/dash+xml',
					'src'  => $dash
				);
			}
			break;
		default:
			if ( in_array( $type, $allowed_types ) && ! empty( $post_meta[ $type ][0] ) ) {
				$src = $post_meta[ $type ][0];

				$sources[ $type ] = array(
					'type' => "video/{$type}",
					'src'  => $src
				);
			}
	}
} else {
	foreach ( $allowed_types as $type ) {		
		if ( isset( $_GET[ $type ] ) && ! empty( $_GET[ $type ] ) ) {
			switch ( $type ) {
				case 'hls': 
					$mime_type = 'application/x-mpegurl'; 
					break;
				case 'dash': 
					$mime_type = 'application/dash+xml'; 
					break;
				default: 
					$mime_type = "video/{$type}";
			}

			$src = aiovg_base64_decode( $_GET[ $type ] );
			$src = aiovg_sanitize_url( aiovg_make_url_absolute( $src ) );

			$sources[ $type ] = array(
				'type' => $mime_type,
				'src'  => $src
			);
		}	
	}
}

$sources = apply_filters( 'aiovg_video_sources', $sources, $settings ); // Backward compatibility to 3.3.0
$sources = apply_filters( 'aiovg_iframe_videojs_player_sources', $sources, $settings );

// Video Tracks
if ( 1 == $settings['cc_load_policy'] ) {
	$has_tracks = 1;
} else {
	$has_tracks = isset( $_GET['tracks'] ) ? (int) $_GET['tracks'] : isset( $player_settings['controls']['tracks'] );
}

$tracks = array();

if ( $has_tracks && ! empty( $post_meta['track'] ) ) {
	foreach ( $post_meta['track'] as $track ) {
		$tracks[] = maybe_unserialize( $track );
	}
	
	$has_srt_found = 0;

	foreach ( $tracks as $index => $track ) {
        $ext = pathinfo( $track['src'], PATHINFO_EXTENSION );
        if ( 'srt' == strtolower( $ext ) ) {
            $has_srt_found = 1;			
			break;
        }
    }

	if ( $has_srt_found ) {
		$settings['tracks'] = $tracks;
		$tracks = array();
	}
}

$tracks = apply_filters( 'aiovg_video_tracks', $tracks ); // Backward compatibility to 3.3.0
$tracks = apply_filters( 'aiovg_iframe_videojs_player_tracks', $tracks );

// Video Chapters
$has_chapters = isset( $_GET['chapters'] ) ? (int) $_GET['chapters'] : isset( $player_settings['controls']['chapters'] );

if ( $has_chapters && 'aiovg_videos' == $post_type ) {	
	$post     = get_post( $post_id );
	$chapters = aiovg_extract_chapters_from_string( $post->post_content );

	if ( ! empty( $post_meta['chapter'] ) ) {	
		foreach ( $post_meta['chapter'] as $chapter ) {
			$chapter = maybe_unserialize( $chapter );
			$seconds = aiovg_convert_time_to_seconds( $chapter['time'] );

			$chapters[ $seconds ] = array(
				'time'  => $seconds,
				'label' => sanitize_text_field( $chapter['label'] )				
			);
		}
	}

	if ( ! empty( $chapters ) ) {
		$settings['chapters'] = array_values( $chapters );
	}
}

// Video Attributes
$attributes = array( 
	'id'       => 'player',
	'class'    => 'video-js vjs-fill',
	'style'    => 'width: 100%; height: 100%;',
	'controls' => '',
	'preload'  =>  esc_attr( $player_settings['preload'] )
);

$theme = ( isset( $player_settings['theme'] ) && 'custom' == $player_settings['theme'] ) ? 'custom' : 'default';
$attributes['class'] .= ' vjs-theme-' . $theme;

$autoplay = isset( $_GET['autoplay'] ) ? (int) $_GET['autoplay'] : (int) $player_settings['autoplay'];
if ( $autoplay ) {
	$settings['player']['autoplay'] = true;
}

$loop = isset( $_GET['loop'] ) ? (int) $_GET['loop'] : (int) $player_settings['loop'];
if ( $loop ) {
	$attributes['loop'] = '';
}

$muted = isset( $_GET['muted'] ) ? (int) $_GET['muted'] : (int) $player_settings['muted'];
if ( $muted ) {
	$attributes['muted'] = '';
}

$playsinline = ! empty( $player_settings['playsinline'] ) ? 1 : 0;
if ( $playsinline ) {
	$attributes['playsinline'] = '';
}

$poster = '';
if ( isset( $_GET['poster'] ) ) {
	$poster = aiovg_base64_decode( $_GET['poster'] );
} elseif ( ! empty( $post_meta ) ) {
	$image_data = aiovg_get_image( $post_id, 'large' );
	$poster = $image_data['src'];
}

if ( ! empty( $poster) ) {
	$attributes['poster'] = esc_url( aiovg_make_url_absolute( $poster ) );
}

if ( ! empty( $brand_settings ) && ! empty( $brand_settings['copyright_text'] ) ) {
	$attributes['controlsList']  = 'nodownload';
	$attributes['oncontextmenu'] = 'return false;';
}

// Player Settings
$controls = array( 
	'playpause'  => 'PlayToggle', 
	'current'    => 'CurrentTimeDisplay', 
	'progress'   => 'ProgressControl', 
	'duration'   => 'DurationDisplay',	
	'spacer'     => 'CustomControlSpacer', 
	'tracks'     => 'CaptionsButton',
	'speed'      => 'PlaybackRateMenuButton', 
	'quality'    => 'QualitySelector',
	'volume'     => 'VolumePanel',
	'pip'        => 'PictureInPictureToggle', 
	'fullscreen' => 'FullscreenToggle'
);

foreach ( $controls as $index => $control ) {
	$enabled = isset( $_GET[ $index ] ) ? (int) $_GET[ $index ] : isset( $player_settings['controls'][ $index ] );

	if ( 'spacer' == $index ) {
		$enabled = 1;
	}

	if ( $enabled && 'pip' == $index ) {
		if ( isset( $sources['youtube'] ) || isset( $sources['vimeo'] ) ) {
			$enabled = 0;
		}
	}

	if ( ! $enabled ) {	
		unset( $controls[ $index ] );	
	}	
}

if ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) {
	if ( isset( $controls['progress'] ) ) {
		$controls = aiovg_insert_array_after( 'progress', $controls, array( 
			'liveui' => 'SeekToLive'
		));
	}
}

if ( isset( $controls['current'] ) && isset( $controls['duration'] ) ) {
	if ( 'custom' == $theme || ! isset( $controls['progress'] ) ) {
		$controls = aiovg_insert_array_after( 'current', $controls, array( 
			'timedivider' => 'TimeDivider'
		));
	}
}

if ( isset( $controls['tracks'] ) ) {
	$controls = aiovg_insert_array_after( 'tracks', $controls, array( 
		'audio' => 'AudioTrackButton'
	));
}

$settings['player']['controlBar']['children'] = array_values( $controls );

if ( ! isset( $controls['progress'] ) ) {
	$attributes['class'] .= ' vjs-no-progress-control';
}

if ( empty( $controls ) || ( 1 == count( $controls ) && isset( $controls['spacer'] ) ) ) {
	$attributes['class'] .= ' vjs-no-control-bar';
}

$attributes = apply_filters( 'aiovg_video_attributes', $attributes );  // Backward compatibility to 3.3.0
$attributes = apply_filters( 'aiovg_iframe_videojs_player_attributes', $attributes ); 

// YouTube
if ( isset( $sources['youtube'] ) ) {
	$settings['player']['techOrder'] = array( 'youtube' );
	$settings['player']['youtube']   = array( 
		'iv_load_policy' => 3,
		'playsinline'    => $playsinline
	);

	parse_str( $sources['youtube']['src'], $queries );

	if ( isset( $queries['start'] ) ) {
		$settings['start'] = (int) $queries['start'];
	}

	if ( isset( $queries['t'] ) ) {
		$settings['start'] = (int) $queries['t'];
	}

	if ( isset( $queries['end'] ) ) {
		$settings['end'] = (int) $queries['end'];
	}

	// Hide YouTube Logo
	if ( ! empty( $player_settings['hide_youtube_logo'] ) ) {
		$attributes['class'] .= ' vjs-hide-youtube-logo';
	}
}

// Vimeo
if ( isset( $sources['vimeo'] ) ) {
	$settings['player']['techOrder'] = array( 'vimeo2' );
	$settings['player']['vimeo2'] = array( 
		'playsinline' => $playsinline
	);

	if ( strpos( $sources['vimeo']['src'], 'player.vimeo.com' ) !== false ) {
		$video_id = aiovg_get_vimeo_id_from_url( $sources['vimeo']['src'] );
		$sources['vimeo']['src'] = 'https://vimeo.com/' . $video_id;
	}
}

// Share
$has_share = isset( $_GET['share'] ) ? (int) $_GET['share'] : isset( $player_settings['controls']['share'] );
if ( $has_share ) {
	$socialshare_settings = aiovg_get_option( 'aiovg_socialshare_settings' );

	$share_url = $post_url;
	$share_url_encoded = rawurlencode( $share_url );

	$share_title = rawurlencode( $post_title );

	$share_image = isset( $attributes['poster'] ) ? $attributes['poster'] : '';

	$share_buttons = array();
		
	if ( isset( $socialshare_settings['services']['facebook'] ) ) {
		$share_buttons[] = array(
			'service' => 'facebook',
			'url'     => "https://www.facebook.com/sharer/sharer.php?u={$share_url_encoded}",
			'icon'    => 'aiovg-icon-facebook',
			'text'    => __( 'Facebook', 'all-in-one-video-gallery' )				
		);
	}

	if ( isset( $socialshare_settings['services']['twitter'] ) ) {
		$share_buttons[] = array(
			'service' => 'twitter',			
			'url'     => "https://twitter.com/intent/tweet?text={$share_title}&amp;url={$share_url_encoded}",
			'icon'    => 'aiovg-icon-twitter',
			'text'    => __( 'Twitter', 'all-in-one-video-gallery' )
		);
	}		

	if ( isset( $socialshare_settings['services']['linkedin'] ) ) {
		$share_buttons[] = array(	
			'service' => 'linkedin',		
			'url'     => "https://www.linkedin.com/shareArticle?url={$share_url_encoded}&amp;title={$share_title}",
			'icon'    => 'aiovg-icon-linkedin',
			'text'    => __( 'Linkedin', 'all-in-one-video-gallery' )
		);
	}

	if ( isset( $socialshare_settings['services']['pinterest'] ) ) {
		$pinterest_url = "https://pinterest.com/pin/create/button/?url={$share_url_encoded}&amp;description={$share_title}";

		if ( ! empty( $share_image ) ) {
			$pinterest_url .= "&amp;media={$share_image}";
		}

		$share_buttons[] = array(
			'service' => 'pinterest',			
			'url'     => $pinterest_url,
			'icon'    => 'aiovg-icon-pinterest',
			'text'    => __( 'Pinterest', 'all-in-one-video-gallery' )
		);
	}

	if ( isset( $socialshare_settings['services']['tumblr'] ) ) {
		$tumblr_url = "https://www.tumblr.com/share/link?url={$share_url_encoded}&amp;name={$share_title}";

		$share_description = aiovg_get_excerpt( $post_id, 160, '', false ); 
		if ( ! empty( $share_description ) ) {
			$share_description = rawurlencode( $share_description );

			$tumblr_url .= "&amp;description={$share_description}";
		}

		$share_buttons[] = array(
			'service' => 'tumblr',			
			'url'     => $tumblr_url,
			'icon'    => 'aiovg-icon-tumblr',
			'text'    => __( 'Tumblr', 'all-in-one-video-gallery' )
		);
	}

	if ( isset( $socialshare_settings['services']['whatsapp'] ) ) {
		if ( wp_is_mobile() ) {
			$whatsapp_url = "whatsapp://send?text={$share_title} {$share_url_encoded}";
		} else {
			$whatsapp_url = "https://api.whatsapp.com/send?text={$share_title}%20{$share_url_encoded}";
		}

		$share_buttons[] = array(	
			'service' => 'whatsapp',		
			'url'     => $whatsapp_url,
			'icon'    => 'aiovg-icon-whatsapp',
			'text'    => __( 'WhatsApp', 'all-in-one-video-gallery' )
		);
	}

	if ( isset( $socialshare_settings['services']['email'] ) ) {
		$email_subject = sprintf( __( 'Check out the "%s"', 'all-in-one-video-gallery' ), $share_title );
		$email_body    = sprintf( __( 'Check out the "%s" at %s', 'all-in-one-video-gallery' ), $share_title, $share_url );
		$email_url     = "mailto:?subject={$email_subject}&amp;body={$email_body}";

		$share_buttons[] = array(
			'service' => 'email',			
			'url'     => $email_url,
			'icon'    => 'aiovg-icon-email',
			'text'    => __( 'Email', 'all-in-one-video-gallery' )
		);
	}

	$share_buttons = apply_filters( 'aiovg_player_socialshare_buttons', $share_buttons );
	if ( ! empty( $share_buttons ) ) {
		$settings['share'] = 1;
	}
}

// Embed
$has_embed = isset( $_GET['embed'] ) ? (int) $_GET['embed'] : isset( $player_settings['controls']['embed'] );
if ( $has_embed ) {
	$protocol = ( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' ) || $_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://';
    $current_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$current_url = aiovg_remove_query_arg( array( 'uid', 'autoadvance' ), $current_url );

	$embed_code = sprintf(
		'<div style="position:relative;padding-bottom:%s;height:0;overflow:hidden;"><iframe src="%s" title="%s" width="100%%" height="100%%" style="position:absolute;width:100%%;height:100%%;top:0px;left:0px;overflow:hidden" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>',
		( isset( $_GET['ratio'] ) ? (float) $_GET['ratio'] : (float) $player_settings['ratio'] ) . '%',
		esc_url( $current_url ),
		esc_attr( $post_title )
	);

	$settings['embed'] = 1;
}

// Download
if ( isset( $sources['mp4'] ) ) {
	$has_download = isset( $player_settings['controls']['download'] );
	$download_url = '';

	if ( ! empty( $post_meta ) ) {
		if ( isset( $post_meta['download'] ) && empty( $post_meta['download'][0] ) ) {
			$has_download = 0;
		}

		$download_url = home_url( '?vdl=' . $post_id );
	}

	if ( isset( $_GET['download'] ) ) {
		$has_download = (int) $_GET['download'];
	}

	if ( $has_download ) {
		if ( empty( $download_url ) ) {
			$download_url = home_url( '?vdl=' . aiovg_get_temporary_file_download_id( $sources['mp4']['src'] ) );
		}

		$settings['download'] = array(
			'url' => esc_url( $download_url )
		);
	}
}

// Logo
if ( ! empty( $brand_settings ) ) {
	$has_logo = ! empty( $brand_settings['logo_image'] ) ? (int) $brand_settings['show_logo'] : 0;
	if ( $has_logo ) {
		$settings['logo'] = array(
			'image'    => esc_url( aiovg_make_url_absolute( $brand_settings['logo_image'] ) ),
			'link'     => ! empty( $brand_settings['logo_link'] ) ? esc_url( $brand_settings['logo_link'] ) : 'javascript:void(0)',
			'position' => sanitize_text_field( $brand_settings['logo_position'] ),
			'margin'   => ! empty( $brand_settings['logo_margin'] ) ? (int) $brand_settings['logo_margin'] : 15
		);
	}

	$has_contextmenu = ! empty( $brand_settings['copyright_text'] ) ? 1 : 0;
	if ( $has_contextmenu ) {
		$settings['contextmenu'] = array(
			'content' => wp_strip_all_tags( apply_filters( 'aiovg_translate_strings', $brand_settings['copyright_text'], 'copyright_text' ) )
		);
	}
}

// Trailer
if ( ! empty( $is_trailer ) ) {
	$player_overlay_link = isset( $trailer_settings['player_overlay_link'] ) ? $trailer_settings['player_overlay_link'] : '';
	$player_overlay_link = apply_filters( 'aiovg_player_overlay_link', $player_overlay_link, $post_id );

	$player_overlay_text = isset( $trailer_settings['player_overlay_text'] ) ? $trailer_settings['player_overlay_text'] : '';
	if ( empty( $player_overlay_text ) ) {
		$player_overlay_text = ! empty( $player_overlay_link ) ? __( 'Watch Full Video', 'all-in-one-video-gallery' ) : __( 'Trailer', 'all-in-one-video-gallery' );
	}
	$player_overlay_text = apply_filters( 'aiovg_player_overlay_text', $player_overlay_text, $post_id );

	$settings['trailer'] = array(
		'label' => wp_strip_all_tags( $player_overlay_text ),
		'url'   => esc_url( $player_overlay_link )
	);
}

// Email Capture.
if ( ! isset( $_GET['nocapture'] ) ) {
	if ( ! empty( $email_capture_settings['enable_email_capture'] ) && ! is_user_logged_in() && ! isset( $_COOKIE['aiovg_email_captured'] ) ) {
		$show_email_capture = 1;

		if ( 'aiovg_videos' == $post_type && isset( $post_meta['email_capture'] ) ) {
			$show_email_capture = (int) $post_meta['email_capture'][0];
		}

		if ( isset( $_GET['email_capture'] ) ) {
			$show_email_capture = (int) $_GET['email_capture'];
		}

		if ( $show_email_capture === -1 ) { // Global
			$show_email_capture = 1;
		}

		if ( ! empty( $show_email_capture ) ) {
			$mid_roll     = ! empty( $email_capture_settings['mid_roll'] ) ? (int) $email_capture_settings['mid_roll'] : 0;
			$post_roll    = ! empty( $email_capture_settings['post_roll'] ) ? 1 : 0;
			$header_text  = ! empty( $email_capture_settings['header_text'] ) ? $email_capture_settings['header_text'] : __( 'Enter your email to continue watching', 'all-in-one-video-gallery' );
			$footer_text  = ! empty( $email_capture_settings['footer_text'] ) ? $email_capture_settings['footer_text'] : '';
			$button_label = ! empty( $email_capture_settings['button_label'] ) ? $email_capture_settings['button_label'] : __( 'Submit', 'all-in-one-video-gallery' );
			$skip_text    = ! empty( $email_capture_settings['skip_text'] ) ? $email_capture_settings['skip_text'] : __( 'Skip', 'all-in-one-video-gallery' );
			
			if ( ! empty( $mid_roll ) || ! empty( $post_roll ) ) {
				$settings['email_capture'] = array(
					'mid_roll'      => $mid_roll,
					'post_roll'     => $post_roll,
					'collect_name'  => ! empty( $email_capture_settings['collect_name'] ) ? 1 : 0,
					'collect_phone' => ! empty( $email_capture_settings['collect_phone'] ) ? 1 : 0,
					'allow_skip'    => ! empty( $email_capture_settings['allow_skip'] ) ? 1 : 0,
					'header_text'   => apply_filters( 'aiovg_translate_strings', $header_text, 'header_text' ),
					'footer_text'   => apply_filters( 'aiovg_translate_strings', $footer_text, 'footer_text' ),
					'button_label'  => apply_filters( 'aiovg_translate_strings', $button_label, 'button_label' ),
					'skip_text'     => apply_filters( 'aiovg_translate_strings', $skip_text, 'skip_text' )
				);
			}
		}
	}
}

$settings = apply_filters( 'aiovg_video_settings', $settings ); // Backward compatibility to 3.3.0
$settings = apply_filters( 'aiovg_iframe_videojs_player_settings', $settings );

// Trailers should not send statistics and should have limited controls to encourage users to watch the full video.
if ( ! empty( $is_trailer ) ) {
	$tracks = array();
	
	$settings['statistics'] = 0;

	unset( $settings['tracks'] );
	unset( $settings['chapters'] );
	unset( $settings['download'] );
}
?>
<!DOCTYPE html>
<html translate="no">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">

    <?php if ( $post_id > 0 ) : ?>    
        <title><?php echo wp_kses_post( $post_title ); ?></title>    
        <link rel="canonical" href="<?php echo esc_url( $post_url ); ?>" />
    <?php endif; ?>

	<link rel="stylesheet" href="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/video-js.min.css?v=8.18.1" />

	<?php if ( in_array( 'QualitySelector', $settings['player']['controlBar']['children'] ) ) : ?>
		<?php if ( isset( $sources['mp4'] ) || isset( $sources['webm'] ) || isset( $sources['ogv'] ) ) : ?>
			<link rel="stylesheet" href="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/quality-selector/quality-selector.min.css?v=1.3.1" />
		<?php endif; ?>

		<?php if ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) : ?>
			<link rel="stylesheet" href="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/contrib-quality-menu/videojs-contrib-quality-menu.min.css?v=1.0.3" />
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( isset( $settings['share'] ) || isset( $settings['embed'] ) || isset( $settings['download'] ) || isset( $settings['logo'] ) || isset( $settings['trailer'] ) ) : ?>
		<link rel="stylesheet" href="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/overlay/videojs-overlay.min.css?v=3.1.0" />
	<?php endif; ?>

	<style type="text/css">
		html,
        body {
            margin: 0 !important;
			padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden;      
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;      
        }

		/* Icons */
		@font-face {
			font-family: 'aiovg-icons';
			src: url( '<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.eot?tx9c7f' );
			src: url( '<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.eot?tx9c7f#iefix' ) format( 'embedded-opentype' ),
				url( '<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.ttf?tx9c7f' ) format( 'truetype' ),
				url( '<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.woff?tx9c7f' ) format( 'woff' ),
				url( '<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.svg?tx9c7f#aiovg-icons' ) format( 'svg' );
			font-weight: normal;
			font-style: normal;
			font-display: swap;
		}
		
		[class^="aiovg-icon-"],
		[class*=" aiovg-icon-"] {
			text-transform: none;
			line-height: 1;
			color: #fff;
			font-family: 'aiovg-icons' !important;
			speak: none;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			font-weight: normal;
			font-variant: normal;
			font-style: normal;
		}

		.aiovg-icon-facebook:before {
			content: "\ea90";
		}

		.aiovg-icon-twitter:before {
			content: "\e900";
		}

		.aiovg-icon-linkedin:before {
			content: "\eaca";
		}

		.aiovg-icon-pinterest:before {
			content: "\ead1";
		}

		.aiovg-icon-tumblr:before {
			content: "\eab9";
		}

		.aiovg-icon-whatsapp:before {
			content: "\ea93";
		}

		.aiovg-icon-email:before {
			content: "\e901";
		}

		/* Common */
		.aiovg-player .video-js {
            width: 100%;
            height: 100%;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

		.aiovg-player .video-js a,
        .aiovg-player .video-js a:hover,
        .aiovg-player .video-js a:focus {
            text-decoration: none;
        }

		.aiovg-player .video-js *:not(.vjs-input-embed-code):focus {
			outline: none;
		}

		/* Poster */
		.aiovg-player .video-js .vjs-poster {
			background-color: #000;
		}

		.aiovg-player .video-js .vjs-poster img {
			object-fit: cover;
		}

		.aiovg-player .video-js.vjs-ended .vjs-poster {
			display: inline-block;
		}

		/* Text Track */
		.aiovg-player .video-js:not(.vjs-has-started) .vjs-text-track-display {
			display: none;
		}

		.aiovg-player .video-js.vjs-ended .vjs-text-track-display {
			display: none;
		}

		/* Spinner */
		.aiovg-player .vjs-waiting.vjs-paused .vjs-loading-spinner {
			display: none;
		}

		.aiovg-player.vjs-waiting .vjs-loading-spinner {
			display: flex !important;
		}

		/* Big Play Button */
		.aiovg-player .video-js .vjs-big-play-button {
			transform: translate3d( -50%, -50%, 0 );
			margin: 0;
			border: 0;
			border-radius: 50%;
			background-color: rgba( 0, 0, 0, 0.5 );
			background-image: url( "data:image/svg+xml,%3Csvg viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M8.56047 5.09337C8.34001 4.9668 8.07015 4.96875 7.85254 5.10019C7.63398 5.23162 7.5 5.47113 7.5 5.73011L7.5 18.2698C7.5 18.5298 7.63398 18.7693 7.85254 18.9007C7.96372 18.9669 8.0882 19 8.21268 19C8.33241 19 8.45309 18.9688 8.56047 18.9075L18.1351 12.6377C18.3603 12.5082 18.5 12.2648 18.5 12C18.5 11.7361 18.3603 11.4917 18.1351 11.3632L8.56047 5.09337Z' fill='%23fff'%3E%3C/path%3E%3C/svg%3E" );
			background-position: center;
			background-size: 75%;
			width: 3em;
			height: 3em;
		}

		.aiovg-player .video-js:hover .vjs-big-play-button,
		.aiovg-player .video-js .vjs-big-play-button:focus {
			background-color: rgba( 0, 0, 0, 0.8 );			
		}		

		.aiovg-player .vjs-waiting.vjs-paused .vjs-big-play-button,
		.aiovg-player .video-js.vjs-ended .vjs-big-play-button {
			display: block;
		}

		.aiovg-player.vjs-waiting .vjs-big-play-button,
		.aiovg-show-email-capture:not(.aiovg-ads-playing) .vjs-big-play-button {
			display: none !important;
		}
		
		.aiovg-player .video-js .vjs-big-play-button .vjs-icon-placeholder:before {
			content: "";
		}

		/* Control Bar */
		.aiovg-player .video-js.vjs-no-control-bar .vjs-control-bar {
			display: none;
		}		

		.aiovg-player .video-js.vjs-ended .vjs-control-bar {
			display: none;
		}

		/* Menu */
		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu ul {
			background-color: transparent;	
			max-height: 20em;		
		}

		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li,
		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li:hover,
		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li:focus {
			margin-bottom: 1px;
			border-radius: 2px;			
			background-color: rgba( 43, 51, 63, 0.7 );
			padding: 0.5em;
			color: #fff;
			text-transform: initial;
		}	

		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li:hover,
		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li:focus {
			background-color: rgba( 0, 0, 0, 0.8 );
		}

		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li.vjs-selected,
		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li.vjs-selected:hover,
		.aiovg-player .video-js .vjs-menu-button-popup .vjs-menu li.vjs-selected:focus {			
			background-color: rgba( 255, 255, 255, 0.9 );
			color: #000;
		}

		/* Current Time & Duration */
		.aiovg-player .video-js .vjs-current-time {
			display: flex;
		}

		.aiovg-player .video-js:not(.vjs-live) .vjs-time-divider {
			display: flex;
			margin: 0;
			padding-right: 0;
			padding-left: 0;
			min-width: auto;
		}

		.aiovg-player .video-js:not(.vjs-live) .vjs-duration {
			display: flex;
		}

		/* Progress Control */
		.aiovg-player .video-js .vjs-progress-control {
			width: 100%;
			-webkit-tap-highlight-color: rgba( 0, 0, 0, 0 );
		}

		.aiovg-player .video-js .vjs-play-progress:before {
			top: 50%;
			transform: translateY( -50% );
		}		

		.aiovg-player .video-js .vjs-progress-control .vjs-time-tooltip {
			padding: 0.5em;
		}

		.aiovg-player .video-js .vjs-progress-control:hover .vjs-time-tooltip {
			display: flex;
			gap: 0.2em;
			transform: translateX( -50% );
			width: max-content;
		}

		/* Chapters */
		.aiovg-player .video-js .vjs-marker {
			position: absolute;
			top: 0;
			bottom: 0;
			opacity: 0.8;
			z-index: 1;
			background: #ffff00;
			width: 3px;	
		}

		/* Spacer */
		.aiovg-player .video-js .vjs-custom-control-spacer {
			display: flex;
			flex: 1 1 auto;
		}

		/* Playback Rate */
		.aiovg-player .video-js .vjs-playback-rate .vjs-menu {
			left: -3em;
			width: 10em;
		}

		.aiovg-player .video-js .vjs-playback-rate .vjs-playback-rate-value {
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 1.2em;
		}

		/* Quality Selector (Self Hosted / External URL) */
		.aiovg-player .video-js.vjs-4k .vjs-quality-selector:after, 
		.aiovg-player .video-js.vjs-hd .vjs-quality-selector:after {
			pointer-events: none; 
			display: flex;
			position: absolute;
			top: 0.5em;
			right: 0;
			align-items: center;
			justify-content: center;			
			border-radius: 2em;	
			background-color: #f00;
			padding: 0;
			width: 2.2em;
			height: 2.2em;
			text-align: center; 
			letter-spacing: 0.1em;
			line-height: 1;
			color: inherit;								 
			font-size: 0.7em;
			content: "";			
		}

		.aiovg-player .video-js.vjs-4k .vjs-quality-selector:after {
			content: "4K";
		}

		.aiovg-player .video-js.vjs-hd .vjs-quality-selector:after {
			content: "HD";
		}
		
		.aiovg-player .video-js .vjs-quality-selector .vjs-menu .vjs-quality-menu-item-sub-label {			
			position: absolute;
			right: 0;
			width: 4em;
			text-align: center;
			text-transform: none;	
			font-size: 75%;
			font-weight: bold;					
		}

		/* Quality Menu (HLS / MPEG-DASH) */
		.aiovg-player .video-js.vjs-quality-menu .vjs-quality-menu-button-4K-flag:after, 
		.aiovg-player .video-js.vjs-quality-menu .vjs-quality-menu-button-HD-flag:after {
			display: flex;
			align-items: center;
			justify-content: center;
			background-color: #f00;
			line-height: 1;
		}

		/* Volume Panel */
		.aiovg-player .video-js .vjs-slider-horizontal .vjs-volume-level:before {
			top: 50%;
			transform: translateY( -50% );
		}
		
		.aiovg-player .video-js .vjs-slider-horizontal .vjs-volume-tooltip {
			padding: 0.5em;
		}

		/* Share & Embed */
		.aiovg-player .video-js .vjs-share {
			display: none;
			margin: 0.5em;
		}
		
		.aiovg-player .video-js.vjs-has-started .vjs-share {
			display: block;			
			transition: visibility 0.1s, opacity 0.1s;
			visibility: visible;
			opacity: 1;
		}

		.aiovg-player .video-js.vjs-has-started.vjs-user-inactive.vjs-playing .vjs-share {			
			transition: visibility 1s, opacity 1s;
			visibility: visible;
			opacity: 0;
		}

		.aiovg-player .video-js .vjs-share button {
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0;
			border: 0;		
			border-radius: 2px;
			box-shadow: none;
			background: rgba( 0, 0, 0, 0.5 );
			cursor: pointer;	
			padding: 0;
			width: 4em;
			height: 4em;
			line-height: 1;
			color: #fff;
		}

		.aiovg-player .video-js .vjs-share:hover button {
			background-color: rgba( 0, 0, 0, 0.8 );
		}

		.aiovg-player .video-js .vjs-share .vjs-icon-share {
			line-height: 1;
			font-size: 1.8em;
		}				

		.aiovg-player .video-js .vjs-modal-dialog-share-embed {
            background: #222 !important;
        }

		.aiovg-player .video-js .vjs-modal-dialog-share-embed .vjs-modal-dialog-content {
			line-height: 1;
			font-size: 1em;
		}

		.aiovg-player .video-js .vjs-modal-dialog-share-embed .vjs-close-button {
            margin: 1em;
        }

		.aiovg-player .video-js .vjs-share-embed {
            display: flex !important;
            flex-direction: column; 
            align-items: center;
			justify-content: center;
			gap: 2.5em;
			width: 100%;
			height: 100%;
        }

		.aiovg-player .video-js .vjs-share-buttons {
            display: flex;
			flex-wrap: wrap;
			align-items: center;
			justify-content: center;
			gap: 0.5em;
        }

		.aiovg-player .video-js .vjs-share-button {
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 2px;
            width: 4em;
			height: 4em;            
			line-height: 1;
			color: #fff;
        }       

		.aiovg-player .video-js .vjs-share-button:hover {
            opacity: 0.9;
        }

        .aiovg-player .video-js .vjs-share-button-facebook {
            background-color: #3B5996;
        }   
		
		.aiovg-player .video-js .vjs-share-button-twitter {
            background-color: #55ACEE;
        }

        .aiovg-player .video-js .vjs-share-button-linkedin {
            background-color: #006699;
        }

        .aiovg-player .video-js .vjs-share-button-pinterest {
            background-color: #C00117;
        }

        .aiovg-player .video-js .vjs-share-button-tumblr {
            background-color: #28364B;
        } 
		
		.aiovg-player .video-js .vjs-share-button-whatsapp {
            background-color: #25d366;
        }
		
		.aiovg-player .video-js .vjs-share-button-email {
            background-color: #6E6E6E;
        }

        .aiovg-player .video-js .vjs-share-button span {            
			line-height: 1;
            font-size: 2em;
        }

        .aiovg-player .video-js .vjs-embed-code {
            display: flex;
			flex-direction: column;
			align-items: center;
			gap: 1em;
			width: 100%;
			max-width: 640px;
        }

        .aiovg-player .video-js .vjs-embed-code label {
			text-transform: uppercase;
			font-size: 11px;
        }

        .aiovg-player .video-js .vjs-embed-code input {            
            border: 1px solid #fff;
			border-radius: 2px;
			background: #fff;
			padding: 0.5em;
			width: 100%;            
			line-height: 1;
			color: #000;
        }

        .aiovg-player .video-js .vjs-embed-code input:focus {
            border: 1px solid #fff;
        }

		/* Download Button */
		.aiovg-player .video-js .vjs-download {
			display: none;
			margin: 0.5em;
			cursor: pointer;
		}

		.aiovg-player .video-js .vjs-has-share.vjs-download {
			margin-top: 5em;
		}

		.aiovg-player .video-js.vjs-has-started .vjs-download {
			display: block;			
			transition: visibility 0.1s, opacity 0.1s;
			visibility: visible;
			opacity: 1;
		}

		.aiovg-player .video-js.vjs-has-started.vjs-user-inactive.vjs-playing .vjs-download {
			transition: visibility 1s, opacity 1s;
			visibility: visible;
			opacity: 0;
		}

		.aiovg-player .video-js .vjs-download a {
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0;
			border-radius: 2px;
			background-color: rgba( 0, 0, 0, 0.5 );	
			padding: 0;
			width: 4em;
			height: 4em;
			line-height: 1;
			color: #fff;
		}	
		
		.aiovg-player .video-js .vjs-download:hover a {
			background-color: rgba( 0, 0, 0, 0.8 );	
		}

		.aiovg-player .video-js .vjs-download .vjs-icon-file-download {
			line-height: 1;
			font-size: 1.8em;
		}	

		/* Logo Overlay */
		.aiovg-player .video-js .vjs-logo {
			opacity: 0;
			max-width: inherit;
		}

		.aiovg-player .video-js.vjs-has-started .vjs-logo {
			transition: opacity 0.1s;
			opacity: 0.6;
		}

		.aiovg-player .video-js.vjs-has-started .vjs-logo:hover {
			opacity: 1;
		}

		.aiovg-player .video-js.vjs-has-started.vjs-user-inactive.vjs-playing .vjs-logo {
			transition: opacity 1s;
			opacity: 0;
		}

		.aiovg-player .video-js .vjs-logo a {
			display: inline-block;
			line-height: 1;
		}

		.aiovg-player .video-js .vjs-logo img {
			max-width: 100%;
		}

		.aiovg-player .video-js.vjs-ended .vjs-logo {
			display: none;
		}

		/* Trailer Badge */
		.aiovg-is-trailer .video-js .vjs-logo a {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			align-items: center;
			justify-content: center;
			gap: 6px;
			border-radius: 3px;
			background: rgba( 0, 0, 0, 0.5 );
			padding: 6px 10px;
			max-width: 160px;
		}

		.aiovg-is-trailer .video-js .vjs-logo img {
			flex-shrink: 0;
			border-radius: 3px;
			max-width: 40px;
			max-height: 24px;
			object-fit: contain;
		}

		.aiovg-is-trailer .video-js .vjs-logo .vjs-logo-label {
			text-align: center;			
			line-height: 1.3;
			white-space: normal;
			color: #fff;
			font-size: 11px;
		}

		/* Tech: YouTube */
		.aiovg-player .vjs-hide-youtube-logo iframe.vjs-tech {
			top: -50% !important;
			height: 200% !important;
		}
		
		/* Error Display */
		.aiovg-player .video-js .vjs-error-display {
			background: #222 !important;
		}

		/* Theme: Custom */
		.aiovg-player .video-js.vjs-theme-custom .vjs-text-track-display {
			bottom: 4.5em;
		}

		.aiovg-player .video-js.vjs-theme-custom.vjs-no-progress-control .vjs-text-track-display,
		.aiovg-player .video-js.vjs-theme-custom.vjs-live:not(.vjs-liveui) .vjs-text-track-display {
			bottom: 3.6em;
		}

		.aiovg-player .video-js.vjs-theme-custom .vjs-overlay-bottom-right,
		.aiovg-player .video-js.vjs-theme-custom .vjs-overlay-bottom-left {
			bottom: 4.5em;
		}

		.aiovg-player .video-js.vjs-theme-custom.vjs-no-progress-control .vjs-overlay-bottom-right,
		.aiovg-player .video-js.vjs-theme-custom.vjs-live:not(.vjs-liveui) .vjs-overlay-bottom-right,
		.aiovg-player .video-js.vjs-theme-custom.vjs-no-progress-control .vjs-overlay-bottom-left,
		.aiovg-player .video-js.vjs-theme-custom.vjs-live:not(.vjs-liveui) .vjs-overlay-bottom-left {
			bottom: 3.6em;
		}

		.aiovg-player .video-js.vjs-theme-custom .vjs-control-bar {
			background-color: transparent;
			background-image: linear-gradient( 180deg, rgba( 0, 0, 0, 0 ) 0, rgba( 0, 0, 0, 0 ) 100% );			
			font-size: 12px;
		}

		.aiovg-player .video-js.vjs-theme-custom.vjs-no-progress-control .vjs-control-bar,
		.aiovg-player .video-js.vjs-theme-custom.vjs-live:not(.vjs-liveui) .vjs-control-bar {
			background-color: rgba( 43, 51, 63, 0.7 );
			background-image: none;
		}
		
		.aiovg-player .video-js.vjs-theme-custom .vjs-menu-button-popup .vjs-menu {
			margin-bottom: 2.1em;			
			font-size: 10px;
		}

		.aiovg-player .video-js.vjs-theme-custom .vjs-menu-button-popup .vjs-menu ul {
			padding-bottom: 0.9em;
		}

		.aiovg-player .video-js.vjs-theme-custom.vjs-no-progress-control .vjs-menu-button-popup .vjs-menu ul,
		.aiovg-player .video-js.vjs-theme-custom.vjs-live:not(.vjs-liveui) .vjs-menu-button-popup .vjs-menu ul {
			padding-bottom: 0;
		}

		.aiovg-player .video-js.vjs-theme-custom .vjs-progress-control {
			position: absolute;
			top: -2em;
			right: 0;
			left: 0;
		}

		.aiovg-player .video-js.vjs-theme-custom .vjs-progress-control .vjs-time-tooltip {
			top: -3em;
		}

		.aiovg-player .video-js.vjs-theme-custom .vjs-volume-tooltip {
			top: -4.2em;
		}

		.aiovg-player .video-js.vjs-theme-custom.vjs-no-progress-control .vjs-volume-tooltip,
		.aiovg-player .video-js.vjs-theme-custom.vjs-live:not(.vjs-liveui) .vjs-volume-tooltip {
			top: -3.4em;
		}

		/* Email Capture */
        #aiovg-email-capture {
            position: absolute;
            inset: 0;
			z-index: 9999999999; /* match contextmenu — ensures visibility in fullscreen */
            display: none;
            align-items: flex-start;
            justify-content: center;
            background-color: rgba( 0, 0, 0, 0.85 );
            overflow-y: auto;
			line-height: 1.5;
			color: #fff;
			font-size: 14px;
        }

		.aiovg-show-email-capture:not(.aiovg-ads-playing) #aiovg-email-capture {
			display: flex !important;
		}

        #aiovg-email-capture-block {
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: auto;
            padding: 16px 24px;
            width: 100%;
            max-width: 480px;
        }

        #aiovg-email-capture-header {
			text-align: center;
            text-shadow: 0 1px 4px rgba( 0, 0, 0, 0.8 );
            font-size: 1.15em;
        }

        #aiovg-email-capture-fields {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        #aiovg-email-capture-fields input {
            box-sizing: border-box;
            display: block;
            border: 1px solid rgba( 255, 255, 255, 0.3 );
            border-radius: 3px;
            background: rgba( 255, 255, 255, 0.12 );
            padding: 0 12px;
            width: 100%;
            min-height: 44px;
            line-height: 1;
            color: #fff;
            font-family: inherit;
            font-size: 1em;
        }

        #aiovg-email-capture-fields input::placeholder {
            color: rgba( 255, 255, 255, 0.5 );
        }

        #aiovg-email-capture-fields input:focus {
            outline: none;
            border-color: rgba( 255, 255, 255, 0.6 );
            background: rgba( 255, 255, 255, 0.18 );
        }

        #aiovg-email-capture-email.aiovg-is-invalid {
            border-color: #ff7070;
        }

        #aiovg-email-capture-error {
            display: none;
            margin-top: -4px;
            color: #ff7070;
            font-size: 0.875em;
        }

		#aiovg-email-capture-error::before {
            content: "⚠";
            margin-right: 5px;
        }

        #aiovg-email-capture-footer {
            margin-top: -4px;
            line-height: 1.4;
            color: rgba( 255, 255, 255, 0.55 );
            font-size: 0.875em;            
        }

        #aiovg-email-capture-footer a {
            color: rgba( 255, 255, 255, 0.75 );
        }

        #aiovg-email-capture-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 12px;            
        }

        #aiovg-email-capture-submit,
        #aiovg-email-capture-submit:hover,
        #aiovg-email-capture-submit:focus {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 3px;
            box-shadow: none;
            background: none;
            background-color: <?php echo esc_attr( $primary_color ); ?>;
	        background-image: none;
            cursor: pointer;
            padding: 0 20px;
            min-height: 44px;
            line-height: 1;
            color: #fff;
            font-family: inherit;
            font-size: 1em;
            font-weight: 600;
        }

        #aiovg-email-capture-submit:hover {
            opacity: 0.85;
        }

        #aiovg-email-capture-skip {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            text-decoration: underline;
            color: rgba( 255, 255, 255, 0.6 );
            font-size: 0.875em;     
        }

        #aiovg-email-capture-skip:hover {
            color: #fff;
        }

		@keyframes aiovg-spin {
            to { transform: rotate( 360deg ); }
        }

        .aiovg-is-loading #aiovg-email-capture-block * {
            display: none !important;
        }

        .aiovg-is-loading #aiovg-email-capture-block {
            pointer-events: none;
            margin: auto;
            border: 3px solid rgba( 255, 255, 255, 0.25 );
            border-radius: 50%;
            border-top-color: #fff;    
            padding: 0;        
            width: 36px;
            height: 36px;
            animation: aiovg-spin 0.7s linear infinite;            
        }

		/* Custom ContextMenu */
		#aiovg-contextmenu {
            position: absolute;
            top: 0;
            left: 0;
			z-index: 9999999999; /* make sure it shows on fullscreen */
            margin: 0;
			border-radius: 2px;
  			background-color: rgba( 0, 0, 0, 0.5 );
            padding: 0;			
        }
        
        #aiovg-contextmenu .aiovg-contextmenu-content {
            margin: 0;
			cursor: pointer;
            padding: 8px 12px;
			white-space: nowrap;
			color: #fff;
            font-size: 11px;
        }
    </style>
	
	<?php if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) : ?>
        <style type="text/css">
		    <?php echo esc_html( $general_settings['custom_css'] ); ?>
        </style>
	<?php endif; ?>
	
	<?php do_action( 'aiovg_player_head', $settings, $attributes, $sources, $tracks ); // Backward compatibility to 3.3.0 ?>
	<?php do_action( 'aiovg_iframe_videojs_player_head', $settings, $attributes, $sources, $tracks ); ?>
</head>
<body id="body" class="aiovg-player<?php if ( ! empty( $is_trailer ) ) : ?> aiovg-is-trailer<?php endif; ?> vjs-waiting">
    <video-js <?php the_aiovg_video_attributes( $attributes ); ?>>
        <?php 
		// Video Sources
		foreach ( $sources as $source ) {
			printf( 
				'<source type="%s" src="%s" label="%s" />', 
				esc_attr( $source['type'] ), 
				esc_url( aiovg_make_url_absolute( $source['src'] ) ),
				( isset( $source['label'] ) ? esc_attr( $source['label'] ) : '' ) 
			);
		}
		
		// Video Tracks
		foreach ( $tracks as $index => $track ) {
        	printf( 
				'<track kind="captions" src="%s" label="%s" srclang="%s" %s/>', 
				esc_url( aiovg_make_url_absolute( $track['src'] ) ), 				
				esc_attr( $track['label'] ),
				esc_attr( $track['srclang'] ), 
				( 0 == $index && 1 == $settings['cc_load_policy'] ? 'default' : '' )
			);
		}
       ?>       
	</video-js>

	<?php if ( ! empty( $settings['share'] ) || ! empty( $settings['embed'] ) ) : ?>
		<div id="vjs-share-embed" class="vjs-share-embed" style="display: none;">
			<?php if ( isset( $settings['share'] ) ) : ?>
				<!-- Share Buttons -->
				<div class="vjs-share-buttons">
					<?php
					foreach ( $share_buttons as $button ) {
						printf( 
							'<a href="%1$s" class="vjs-share-button vjs-share-button-%2$s" title="%3$s" target="_blank"><span class="%4$s" aria-hidden="true"></span><span class="vjs-control-text" aria-live="polite">%3$s</span></a>',							
							esc_attr( $button['url'] ), 
							esc_attr( $button['service'] ),
							esc_attr( $button['text'] ),
							esc_attr( $button['icon'] )
						);
					}
					?>
				</div>
			<?php endif; ?>

			<?php if ( isset( $settings['embed'] ) ) : ?>
				<!-- Embed Code -->
				<div class="vjs-embed-code">
					<label for="vjs-input-embed-code"><?php esc_html_e( 'Paste this code in your HTML page', 'all-in-one-video-gallery' ); ?></label>
					<input type="text" id="vjs-input-embed-code" class="vjs-input-embed-code" value="<?php echo esc_attr( $embed_code ); ?>" readonly />
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( isset( $settings['email_capture'] ) ) : ?>
		<div id="aiovg-email-capture" style="display: none;">
			<div id="aiovg-email-capture-block">
				<div id="aiovg-email-capture-header"><?php echo esc_html( $settings['email_capture']['header_text'] ); ?></div>

				<div id="aiovg-email-capture-fields">
					<?php if ( ! empty( $settings['email_capture']['collect_name'] ) ) : ?>
						<input type="text" id="aiovg-email-capture-name" name="name" placeholder="<?php esc_attr_e( 'Your name', 'all-in-one-video-gallery' ); ?>" autocomplete="name" />
					<?php endif; ?>

					<input type="email" id="aiovg-email-capture-email" name="email" placeholder="<?php esc_attr_e( 'Your email address *', 'all-in-one-video-gallery' ); ?>" required autocomplete="email" aria-describedby="aiovg-email-capture-error" />

					<?php if ( ! empty( $settings['email_capture']['collect_phone'] ) ) : ?>
						<input type="tel" id="aiovg-email-capture-phone" name="phone" placeholder="<?php esc_attr_e( 'Your phone number', 'all-in-one-video-gallery' ); ?>" autocomplete="tel" />
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $settings['email_capture']['footer_text'] ) ) : ?>
					<div id="aiovg-email-capture-footer"><?php echo wp_kses_post( $settings['email_capture']['footer_text'] ); ?></div>
				<?php endif; ?>

				<div id="aiovg-email-capture-error" role="alert" aria-live="assertive" tabindex="-1" data-invalid_email="<?php esc_attr_e( 'Please enter a valid email address.', 'all-in-one-video-gallery' ); ?>" data-error_generic="<?php esc_attr_e( 'Something went wrong. Please try again.', 'all-in-one-video-gallery' ); ?>"></div>

				<div id="aiovg-email-capture-actions">
					<button type="button" id="aiovg-email-capture-submit"><?php echo esc_html( $settings['email_capture']['button_label'] ); ?></button>

					<?php if ( ! empty( $settings['email_capture']['allow_skip'] ) ) : ?>
						<a id="aiovg-email-capture-skip" role="button" tabindex="0"><?php echo esc_html( $settings['email_capture']['skip_text'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( isset( $settings['contextmenu'] ) ) : ?>
		<div id="aiovg-contextmenu" style="display: none;">
            <div class="aiovg-contextmenu-content"><?php echo esc_html( $settings['contextmenu']['content'] ); ?></div>
        </div>
	<?php endif; ?>
    
	<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/video.min.js?v=8.18.1" type="text/javascript" defer></script>

	<?php if ( in_array( 'QualitySelector', $settings['player']['controlBar']['children'] ) ) : ?>
		<?php if ( isset( $sources['mp4'] ) || isset( $sources['webm'] ) || isset( $sources['ogv'] ) ) : ?>
			<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/quality-selector/silvermine-videojs-quality-selector.min.js?v=1.3.1" type="text/javascript" defer></script>
		<?php endif; ?>

		<?php if ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) : ?>
			<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/contrib-quality-menu/videojs-contrib-quality-menu.min.js?v=1.0.3" type="text/javascript" defer></script>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( isset( $sources['youtube'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/youtube/Youtube.min.js?v=3.1.0" type="text/javascript" defer></script>
	<?php endif; ?>

	<?php if ( isset( $settings['start'] ) || isset( $settings['end'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/offset/videojs-offset.min.js?v=2.1.3" type="text/javascript" defer></script>
	<?php endif; ?>

	<?php if ( isset( $sources['vimeo'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/vimeo/Vimeo.min.js?v=3.1.0" type="text/javascript" defer></script>
	<?php endif; ?>

	<?php if ( isset( $settings['share'] ) || isset( $settings['embed'] ) || isset( $settings['download'] ) || isset( $settings['logo'] ) || isset( $settings['trailer'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/overlay/videojs-overlay.min.js?v=3.1.0" type="text/javascript" defer></script>
	<?php endif; ?>

	<?php if ( ! empty( $settings['hotkeys'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/videojs/plugins/hotkeys/videojs.hotkeys.min.js?v=0.2.30" type="text/javascript" defer></script>
	<?php endif; ?> 

	<?php do_action( 'aiovg_player_footer', $settings, $attributes, $sources, $tracks ); // Backward compatibility to 3.3.0 ?>
	<?php do_action( 'aiovg_iframe_videojs_player_footer', $settings, $attributes, $sources, $tracks ); ?>

    <script type="text/javascript">
		'use strict';			
			
		// Vars
		var settings = <?php echo json_encode( $settings ); ?>;		
		
		var playButton = null;
		var emailCaptureEl = null;
		var overlays = [];		
		var hasVideoStarted = false;
		var lastEvent = null;				

		/**
		 * Email capture implementation.
		 * Mid-roll: Show when currentTime reaches the configured percentage.
		 */
		function initEmailCapture( player ) {
			emailCaptureEl = document.getElementById( 'aiovg-email-capture' );
			player.el().appendChild( emailCaptureEl );

			let isModalOpen = false;
			let wasPlaying  = false;

			// Show the overlay and pause the player (mid-roll only).
			const showEmailCapture = () => {
				if ( ! emailCaptureEl || isModalOpen ) return false; // Prevent multiple mid-roll triggers
				isModalOpen = true;

				if ( ! player.ended() ) {
					wasPlaying = ! player.paused();
					player.pause();
				}

				document.body.classList.add( 'aiovg-show-email-capture' );

				const firstInput = emailCaptureEl.querySelector( 'input' );
				if ( firstInput ) {
					firstInput.focus();
				}
			}

			// Hide the overlay and resume playback for mid-roll.
			const hideEmailCapture = () => {
				if ( ! emailCaptureEl || ! isModalOpen ) return false;
				isModalOpen = false; // Clear before player.play() so the guard above does not block it.

				document.body.classList.remove( 'aiovg-show-email-capture' );
				
				emailCaptureEl.remove();
				emailCaptureEl = null;

				if ( wasPlaying ) {
					player.play();
				}
			}

			// Hide the overlay; POST to the AJAX handler.
			const ajaxEmailCapture = ( data ) => {
				if ( ! emailCaptureEl ) return false;

				if ( emailCaptureEl.classList.contains( 'aiovg-is-loading' ) ) return false; // Prevent multiple submissions
                emailCaptureEl.classList.add( 'aiovg-is-loading' );

				const xmlhttp = new XMLHttpRequest();

				xmlhttp.onreadystatechange = function() {
					if ( 4 == xmlhttp.readyState ) {
						let response = null;

						if ( 200 == xmlhttp.status && xmlhttp.responseText ) {
							try {
								response = JSON.parse( xmlhttp.responseText );
							} catch ( e ) {}
						}

						if ( response && response.success ) {
							// Hide the form and resume playback if mid-roll.
							hideEmailCapture();

							// Remove email capture form from other players
							window.parent.postMessage( {
								message: 'aiovg-email-captured',
								context: 'iframe'
							}, window.location.origin );
						} else {
							// Show error message
                            const errorSpan = document.getElementById( 'aiovg-email-capture-error' );
							errorSpan.textContent   = ( response && response.data && typeof response.data === 'string' ) ? response.data : errorSpan.getAttribute( 'data-error_generic' );
							errorSpan.style.display = 'block';

							// Restore the form so the user can retry.
							emailCaptureEl.classList.remove( 'aiovg-is-loading' );
						}
					}
				};

				xmlhttp.open( 'POST', '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=aiovg_save_email_lead&security=<?php echo esc_js( wp_create_nonce( 'aiovg_public_ajax_nonce' ) ); ?>', true );
				xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
				xmlhttp.send( data );
			}

			// Mid-roll — show when currentTime reaches the configured percentage.
			if ( settings.email_capture.mid_roll ) {
				let midRollFired = false;

				const onMidRollTimeUpdate = () => {
					if ( ! emailCaptureEl ) {
						player.off( 'timeupdate', onMidRollTimeUpdate );
						return false;
					}

					if ( midRollFired ) return false;

					// Skip during ad playback — currentTime/duration reflect the ad, not the content.
					if ( settings.hasOwnProperty( 'ads' ) && player.ads && player.ads.isInAdMode() ) {
						return false;
					}

					const duration = player.duration();
					const current  = player.currentTime();

					if ( duration && current >= ( parseInt( settings.email_capture.mid_roll ) / 100 ) * duration ) {
						midRollFired = true;
						player.off( 'timeupdate', onMidRollTimeUpdate );
						showEmailCapture();
					}
				};

				player.on( 'timeupdate', onMidRollTimeUpdate );
			}

			// Post-roll — show after the video ends.
			// Skipped when autoadvance is active to avoid conflicting with next-video navigation.
			if ( settings.email_capture.post_roll && ! settings.hasOwnProperty( 'autoadvance' ) ) {
				player.on( 'ended', function() {
					showEmailCapture();
				});
			}

			// Re-pause on any play event while the overlay is open.
			player.on( 'play', function() {
				if ( ! emailCaptureEl || ! isModalOpen ) return false;

				wasPlaying = true;
				player.pause();
			});

			player.on( 'playing', function() {
				if ( ! emailCaptureEl || ! isModalOpen ) return false;

				wasPlaying = true;
				player.pause();
			});
			
			// Submit handler.
			const submitBtn = document.getElementById( 'aiovg-email-capture-submit' );

			if ( submitBtn ) {
				submitBtn.addEventListener( 'click', () => {
					const emailField = document.getElementById( 'aiovg-email-capture-email' );
					const errorSpan  = document.getElementById( 'aiovg-email-capture-error' );

					const email = emailField.value.trim();
					const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

					if ( ! email || ! regex.test( email ) ) {
						const errorMessage = errorSpan.getAttribute( 'data-invalid_email' ) || 'Please enter a valid email address.';
						
						errorSpan.textContent = errorMessage;
						errorSpan.style.display = 'block';

						emailField.classList.add( 'aiovg-is-invalid' );
						emailField.setAttribute( 'aria-invalid', 'true' );					
						return false;
					}

					errorSpan.style.display = 'none';

					emailField.classList.remove( 'aiovg-is-invalid' );
					emailField.removeAttribute( 'aria-invalid' );

					let params = 'email=' + encodeURIComponent( email );

					if ( settings.email_capture.collect_name ) {
						const nameField = document.getElementById( 'aiovg-email-capture-name' );							
						if ( nameField && nameField.value.trim() ) {
							params += '&name=' + encodeURIComponent( nameField.value.trim() );
						}
					}

					if ( settings.email_capture.collect_phone ) {
						const phoneField = document.getElementById( 'aiovg-email-capture-phone' );
						if ( phoneField && phoneField.value.trim() ) {
							params += '&phone=' + encodeURIComponent( phoneField.value.trim() );
						}
					}

					if ( 'aiovg_videos' == settings.post_type && settings.post_id ) {
						params += '&video_id=' + encodeURIComponent( settings.post_id );
					} else {
						params += '&video_id=0';
						params += '&video_url=' + encodeURIComponent( player.currentSrc() );
					}

					let pageUrl = document.referrer;
					if ( ! pageUrl ) {
						try {
							pageUrl = window.parent.location.href;
						} catch ( e ) {}
					}
					params += '&page_url=' + encodeURIComponent( pageUrl );

					ajaxEmailCapture( params );
				});
			}

			// Skip handler.
			if ( settings.email_capture.allow_skip ) {
				const skipLink = document.getElementById( 'aiovg-email-capture-skip' );

				if ( skipLink ) {
					skipLink.addEventListener( 'click', function() {
						ajaxEmailCapture( 'skipped=1' );
					});

					skipLink.addEventListener( 'keydown', function( event ) {
						if ( 13 === event.keyCode || 32 === event.keyCode ) {
							event.preventDefault();
							ajaxEmailCapture( 'skipped=1' );
						}
					});
				}
			}
		}

		/**
		 * Called when the big play button in the player is clicked.
		 */
		function onPlayClicked() {
			if ( ! hasVideoStarted ) {
				document.body.classList.add( 'vjs-waiting' );
			}

			playButton.removeEventListener( 'click', onPlayClicked );
		}

		/**
		 * Add SRT Text Track.
		 */
		function addSrtTextTrack( player, track, mode ) {
			var xmlhttp;

			if ( window.XMLHttpRequest ) {
				xmlhttp = new XMLHttpRequest();
			} else {
				xmlhttp = new ActiveXObject( 'Microsoft.XMLHTTP' );
			}
			
			xmlhttp.onreadystatechange = function() {				
				if ( xmlhttp.readyState == 4 && xmlhttp.status == 200 && xmlhttp.responseText ) {					
					var text = srtToWebVTT( xmlhttp.responseText );

					if ( text ) {
						var blob = new Blob([ text ], { type : 'text/vtt' });
						var src  = URL.createObjectURL( blob );

						var obj = {
							kind: 'captions',
							src: src,
							label: track.label,
							srclang: track.srclang													
						};

						if ( mode ) {
							obj.mode = mode;
						}

						player.addRemoteTextTrack( obj, true );
					} 
				}					
			};	

			xmlhttp.open( 'GET', track.src, true );
			xmlhttp.send();							
		}

		/**
		 * Convert SRT to WebVTT.
		 */
		function srtToWebVTT( data ) {
          	// Remove dos newlines.
          	var srt = data.replace( /\r+/g, '' );

          	// Trim white space start and end.
          	srt = srt.replace( /^\s+|\s+$/g, '' );

          	// Get cues.
          	var cuelist = srt.split( '\n\n' );
          	var result  = '';

          	if ( cuelist.length > 0 ) {
            	result += "WEBVTT\n\n";

            	for ( var i = 0; i < cuelist.length; i++ ) {
            		result += convertSrtCue( cuelist[ i ] );
            	}
          	}

          	return result;
        }

		function convertSrtCue( caption ) {
          	// Remove all html tags for security reasons.
          	// srt = srt.replace( /<[a-zA-Z\/][^>]*>/g, '' );

          	var cue = '';
          	var s   = caption.split( /\n/ );

          	// Concatenate muilt-line string separated in array into one.
          	while ( s.length > 3 ) {
              	for ( var i = 3; i < s.length; i++ ) {
                  	s[2] += "\n" + s[ i ];
              	}

              	s.splice( 3, s.length - 3 );
          	}

          	var line = 0;

          	// Detect identifier.
          	if ( ! s[0].match( /\d+:\d+:\d+/ ) && s[1].match( /\d+:\d+:\d+/ ) ) {
            	cue  += s[0].match( /\w+/ ) + "\n";
            	line += 1;
          	}

          	// Get time strings.
          	if ( s[ line ].match( /\d+:\d+:\d+/ ) ) {
            	// Convert time string.
            	var m = s[1].match( /(\d+):(\d+):(\d+)(?:,(\d+))?\s*--?>\s*(\d+):(\d+):(\d+)(?:,(\d+))?/ );

            	if ( m ) {
              		cue  += m[1] + ":" + m[2] + ":" + m[3] + "." + m[4] + " --> " + m[5] + ":" + m[6] + ":" + m[7] + "." + m[8] + "\n";
              		line += 1;
            	} else {
              		// Unrecognized timestring.
              		return '';
            	}
          	} else {
            	// File format error or comment lines.
            	return '';
          	}

          	// Get cue text.
          	if ( s[ line ] ) {
            	cue += s[ line ] + "\n\n";
          	}

          	return cue;
        }	
		
		/**
		 * Helper functions for chapters.
		 */
		function addMarkers( player, markers ) {
			var total   = player.duration();
			var seekBar = document.querySelector( '.vjs-progress-control .vjs-progress-holder' );

			if ( seekBar !== null ) {
				for ( var i = 0; i < markers.length; i++ ) {
					var elem = document.createElement( 'div' );
					elem.className = 'vjs-marker';
					elem.style.left = ( markers[ i ].time / total ) * 100 + '%';

					seekBar.appendChild( elem );
				}
			}
		}

		function formatedTimeToSeconds( time ) {
			var timeSplit = time.split( ':' );
			var seconds   = +timeSplit.pop();
	
			return timeSplit.reduce(( acc, curr, i, arr ) => {
				if ( arr.length === 2 && i === 1 ) return acc + +curr * 60 ** 2;
				else return acc + +curr * 60;
			}, seconds);
		}

		function timeEl( time ) {
			return videojs.dom.createEl( 'span', undefined, undefined, '(' + time + ')' );
		}

		function labelEl( label ) {
			return videojs.dom.createEl( 'strong', undefined, undefined, label );
		}

		/**
		 * Update video views count.
		 */
		function updateViewsCount( player ) {
			var xmlhttp;

			if ( window.XMLHttpRequest ) {
				xmlhttp = new XMLHttpRequest();
			} else {
				xmlhttp = new ActiveXObject( 'Microsoft.XMLHTTP' );
			}
			
			xmlhttp.onreadystatechange = function() {				
				if ( xmlhttp.readyState == 4 && xmlhttp.status == 200 && xmlhttp.responseText ) {					
					/** console.log( xmlhttp.responseText ); */				
				}					
			};

			var duration = player.duration() || 0;

			xmlhttp.open( 'GET', '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=aiovg_update_views_count&post_id=<?php echo $post_id; ?>&duration=' + duration + '&security=<?php echo esc_js( wp_create_nonce( 'aiovg_public_ajax_nonce' ) ); ?>', true );
			xmlhttp.send();							
		}		

		/**
		 * Merge attributes.
		 */
		function combineAttributes( attributes ) {
			var str = '';

			for ( var key in attributes ) {
				str += ( key + '="' + attributes[ key ] + '" ' );
			}

			return str;
		}

		/**
		 * Init player.
		 */		
		function initPlayer() {
			settings.html5 = {
				vhs: {
					overrideNative: ! videojs.browser.IS_ANY_SAFARI,
				}
			};

			var player = videojs( 'player', settings.player );			

			// Maintained for backward compatibility.
			if ( typeof window['onPlayerInitialized'] === 'function' ) {
				window.onPlayerInitialized( player );
			}

			// Dispatch an event.
			var evt = document.createEvent( 'CustomEvent' );
			evt.initCustomEvent( 'player.init', false, false, { player: player, settings: settings } );
			window.dispatchEvent( evt );

			// On player ready.
			player.ready(function() {
				lastEvent = 'ready';
				document.body.classList.remove( 'vjs-waiting' );
				
				// Big play button.
				playButton = document.querySelector( '.vjs-big-play-button' );
				if ( playButton !== null ) {
					playButton.addEventListener( 'click', onPlayClicked );
				}

				// Email Capture.
				if ( settings.hasOwnProperty( 'email_capture' ) ) {
					initEmailCapture( player );
				}
			});

			// On metadata loaded.
			player.one( 'loadedmetadata', function() {
				lastEvent = 'loadedmetadata';

				// Standard quality selector.
				var qualitySelector = document.querySelector( '.vjs-quality-selector' );

				if ( qualitySelector !== null ) {
					var nodes = qualitySelector.querySelectorAll( '.vjs-menu-item' );

					for ( var i = 0; i < nodes.length; i++ ) {
						var node = nodes[ i ];

						var textNode   = node.querySelector( '.vjs-menu-item-text' );
						var resolution = textNode.innerHTML.replace( /\D/g, '' );

						if ( resolution >= 2160 ) {
							node.innerHTML += '<span class="vjs-quality-menu-item-sub-label">4K</span>';
						} else if ( resolution >= 720 ) {
							node.innerHTML += '<span class="vjs-quality-menu-item-sub-label">HD</span>';
						}
					}
				}

				// Add support for SRT.
				if ( settings.hasOwnProperty( 'tracks' ) ) {
					for ( var i = 0, max = settings.tracks.length; i < max; i++ ) {
						var track = settings.tracks[ i ];

						var mode = '';
						if ( i == 0 && settings.cc_load_policy == 1 ) {
							mode = 'showing';
						}

						if ( /srt/.test( track.src.toLowerCase() ) ) {
							addSrtTextTrack( player, track, mode );
						} else {
							var obj = {
								kind: 'captions',
								src: track.src,								
								label: track.label,
								srclang: track.srclang
							};

							if ( mode ) {
								obj.mode = mode;
							}

							player.addRemoteTextTrack( obj, true ); 
						}					               
					}  
				}  
				
				// Chapters
				if ( settings.hasOwnProperty( 'chapters' ) ) {
					addMarkers( player, settings.chapters );
				}
			});

			// Chapters
			if ( settings.hasOwnProperty( 'chapters' ) ) {
				try {
					player.getDescendant([
						'ControlBar',
						'ProgressControl',
						'SeekBar',
						'MouseTimeDisplay',
						'TimeTooltip',
					]).update = function( seekBarRect, seekBarPoint, time ) {
						var markers = settings.chapters;
						var markerIndex = markers.findIndex(({ time: markerTime }) => markerTime == formatedTimeToSeconds( time ));
				
						if ( markerIndex > -1 ) {
							var label = markers[ markerIndex ].label;
					
							videojs.dom.emptyEl( this.el() );
							videojs.dom.appendContent( this.el(), [labelEl( label ), timeEl( time )] );
					
							return false;
						}
				
						this.write( time );
					};
				} catch ( error ) { 
					/** console.log( error ); */	
				}
			}

			player.on( 'play', function() {
				lastEvent = 'play';

				if ( ! hasVideoStarted ) {
					hasVideoStarted = true;
					document.body.classList.remove( 'vjs-waiting' );

					if ( settings.statistics && settings.post_type == 'aiovg_videos' ) {
						updateViewsCount( player );
					}
				}

				// Pause other players.
				window.parent.postMessage({ 				
					message: 'aiovg-video-playing',
					context: 'iframe'
				}, window.location.origin );
			});

			player.on( 'playing', function() {
				lastEvent = 'playing';
				player.trigger( 'controlsshown' );
			});

			player.on( 'ended', function() {
				if ( lastEvent == 'ended' ) {
					return false;
				}

				lastEvent = 'ended';
				player.trigger( 'controlshidden' );

				// Autoplay next video.
				if ( settings.hasOwnProperty( 'autoadvance' ) ) {
					window.parent.postMessage({ 
						message: 'aiovg-video-ended',
						context: 'iframe'						
					}, window.location.origin ); 
				}
			});

			// Standard quality selector.
			player.on( 'qualitySelected', function( event, source ) {
				var resolution = source.label.replace( /\D/g, '' );

				player.removeClass( 'vjs-4k' );
				player.removeClass( 'vjs-hd' );

				if ( resolution >= 2160 ) {
					player.addClass( 'vjs-4k' );
				} else if ( resolution >= 720 ) {
					player.addClass( 'vjs-hd' );
				}
			});

			// HLS quality selector.
			var src = player.src();

			if ( /.m3u8/.test( src ) || /.mpd/.test( src ) ) {
				if ( settings.player.controlBar.children.indexOf( 'QualitySelector' ) !== -1 ) {
					player.qualityMenu();
				}
			}

			// Offset
			var offset = {};

			if ( settings.hasOwnProperty( 'start' ) ) {
				offset.start = settings.start;
			}

			if ( settings.hasOwnProperty( 'end' ) ) {
				offset.end = settings.end;
			}
			
			if ( Object.keys( offset ).length > 1 ) {
				offset.restart_beginning = false;
				player.offset( offset );
			}			

			// Share / Embed.
			if ( settings.hasOwnProperty( 'share' ) || settings.hasOwnProperty( 'embed' ) ) {
				overlays.push({
					content: '<button type="button" id="vjs-share-embed-button" class="vjs-share-embed-button" title="Share"><span class="vjs-icon-share" aria-hidden="true"></span><span class="vjs-control-text" aria-live="polite">Share</span></button>',
					class: 'vjs-share',
					align: 'top-right',
					start: 'controlsshown',
					end: 'controlshidden',
					showBackground: false					
				});					
			}

			// Download
			if ( settings.hasOwnProperty( 'download' ) ) {
				var className = 'vjs-download';

				if ( settings.hasOwnProperty( 'share' ) || settings.hasOwnProperty( 'embed' ) ) {
					className += ' vjs-has-share';
				}

				overlays.push({
					content: '<a href="' + settings.download.url + '" id="vjs-download-button" class="vjs-download-button" title="Download" target="_blank"><span class="vjs-icon-file-download" aria-hidden="true"></span><span class="vjs-control-text" aria-live="polite">Download</span></a>',
					class: className,
					align: 'top-right',
					start: 'controlsshown',
					end: 'controlshidden',
					showBackground: false					
				});
			}

			// Logo / Trailer Badge
			var hasLogo    = settings.hasOwnProperty( 'logo' );
			var hasTrailer = settings.hasOwnProperty( 'trailer' );

			if ( hasLogo || hasTrailer ) {
				var align = 'bottom-left';
				var style = 'margin: 5px;';

				if ( hasLogo ) {
					if ( settings.logo.margin ) {
						settings.logo.margin = settings.logo.margin - 5;
					}

					style = 'margin: ' + settings.logo.margin + 'px;';

					switch ( settings.logo.position ) {
						case 'topleft':
							align = 'top-left';
							break;

						case 'topright':
							align = 'top-right';
							break;

						case 'bottomright':
							align = 'bottom-right';
							break;
					}
				}

				// Determine the href: trailer URL takes precedence when active.
				var logoHref = hasLogo ? settings.logo.link : 'javascript:void(0)';
				
				if ( hasTrailer && settings.trailer.url ) {
					logoHref = settings.trailer.url;
				}

				// Icon: logo image > external-link SVG (only when URL set) > nothing.
				var iconHtml = '';

				if ( hasLogo ) {
					iconHtml = '<img src="' + settings.logo.image + '" alt="" />';
				} else if ( hasTrailer && settings.trailer.url ) {
					iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>';
				}

				// Label: visible trailer label or sr-only text for logo-only mode.
				var labelHtml   = hasTrailer ? '<span class="vjs-logo-label">' + settings.trailer.label + '</span>' : '<span class="vjs-control-text" aria-live="polite">Logo</span>';
				var logoContent = '<a href="' + logoHref + '" target="_top" style="' + style + '">'	+ iconHtml + labelHtml	+ '</a>';

				overlays.push({
					content: logoContent,
					class: 'vjs-logo',
					align: align,
					start: 'controlsshown',
					end: 'controlshidden',
					showBackground: false
				});
			}

			// Overlay
			if ( overlays.length > 0 ) {
				player.overlay({
					content: '',
					overlays: overlays
				});

				if ( settings.hasOwnProperty( 'share' ) || settings.hasOwnProperty( 'embed' ) ) {
					var options = {};
					options.content = document.getElementById( 'vjs-share-embed' );
					options.temporary = false;

					var ModalDialog = videojs.getComponent( 'ModalDialog' );
					
					var modal = new ModalDialog( player, options );
					modal.addClass( 'vjs-modal-dialog-share-embed' );

					player.addChild( modal );

					var wasPlaying = true;
					document.getElementById( 'vjs-share-embed-button' ).addEventListener( 'click', function() {
						wasPlaying = ! player.paused();
						modal.open();						
					});

					modal.on( 'modalclose', function() {
						if ( wasPlaying ) {
							player.play();
						}						
					});
				}

				if ( settings.hasOwnProperty( 'embed' ) ) {
					document.getElementById( 'vjs-input-embed-code' ).addEventListener( 'focus', function() {
						this.select();
						
						if ( navigator.clipboard && navigator.clipboard.writeText ) {
							navigator.clipboard.writeText( this.value ).catch( () => {} );
						} else {
							document.execCommand( 'copy' );
						}
					});
				}
			}

			// Keyboard hotkeys.
			if ( settings.hotkeys ) {
				player.hotkeys();
			}

			// Custom contextmenu.
			if ( settings.hasOwnProperty( 'contextmenu' ) ) {
				var contextmenu = document.getElementById( 'aiovg-contextmenu' );
				var timeoutHandler = '';
				
				document.addEventListener( 'contextmenu', function( event ) {						
					if ( event.keyCode == 3 || event.which == 3 ) {
						event.preventDefault();
						event.stopPropagation();
						
						var width = contextmenu.offsetWidth,
							height = contextmenu.offsetHeight,
							x = event.pageX,
							y = event.pageY,
							doc = document.documentElement,
							scrollLeft = ( window.pageXOffset || doc.scrollLeft ) - ( doc.clientLeft || 0 ),
							scrollTop = ( window.pageYOffset || doc.scrollTop ) - ( doc.clientTop || 0 ),
							left = x + width > window.innerWidth + scrollLeft ? x - width : x,
							top = y + height > window.innerHeight + scrollTop ? y - height : y;
				
						contextmenu.style.display = '';
						contextmenu.style.left = left + 'px';
						contextmenu.style.top = top + 'px';
						
						clearTimeout( timeoutHandler );

						timeoutHandler = setTimeout(function() {
							contextmenu.style.display = 'none';
						}, 1500);				
					}														 
				});
				
				document.addEventListener( 'click', function() {
					contextmenu.style.display = 'none';								 
				});	
			}

			// Custom error.
			videojs.hook( 'beforeerror', function( player, error ) {
				// Prevent current error from being cleared out.
				if ( error == null ) {
					return player.error();
				}

				// But allow changing to a new error.
				if ( error.code == 2 || error.code == 4 ) {
					var src = player.src();

					if ( /.m3u8/.test( src ) || /.mpd/.test( src ) ) {
						return {
							code: error.code,
							message: settings.i18n.stream_not_found
						}
					}
				}
				
				return error;
			});

			// Api methods
			window.addEventListener( 'message', function( event ) {
				if ( event.origin !== window.location.origin ) {
					return false;
				}

				if ( ! event.data.hasOwnProperty( 'message' ) ) {
					return false;
				}

				switch ( event.data.message ) {
					case 'aiovg-email-captured':
						emailCaptureEl = null;
						document.body.classList.remove( 'aiovg-show-email-capture' );						
						break;

					case 'aiovg-video-play':
						player.play();
						break;

					case 'aiovg-video-pause':
						player.pause();
						break;

					case 'aiovg-video-seek':
						if ( event.data.hasOwnProperty( 'seconds' ) ) {
							player.currentTime( event.data.seconds );
							if ( ! hasVideoStarted ) {
								player.play();
							}
						}
						break;
				}
			});
		}

		document.addEventListener( 'DOMContentLoaded', function() {
			initPlayer();
		});
    </script>	
</body>
</html>