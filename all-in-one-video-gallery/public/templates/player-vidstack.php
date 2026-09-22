<?php

/**
 * Vidstack Player.
 *
 * @link     https://plugins360.com
 * @since    3.3.1
 *
 * @package All_In_One_Video_Gallery
 */

$settings = array(
	'uid'        => isset( $_GET['uid'] ) ? sanitize_text_field( $_GET['uid'] ) : 0,
	'post_id'    => $post_id,
	'post_type'  => $post_type,
	'is_trailer' => $is_trailer,
	'statistics' => isset( $general_settings['statistics'] ) ? (int) $general_settings['statistics'] : 1,	
	'player'     => array(
		'iconUrl' => AIOVG_PLUGIN_URL . 'vendor/vidstack/plyr.svg',
		'volume'  => 0.5
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
	$type = isset( $post_meta['type'] ) ? $post_meta['type'][0] : 'default';

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

$sources = apply_filters( 'aiovg_iframe_vidstack_player_sources', $sources, $settings ); 

// Video Captions
$tracks = array();
$cc_load_policy = isset( $_GET['cc_load_policy'] ) ? (int) $_GET['cc_load_policy'] : (int) $player_settings['cc_load_policy'];

if ( ! empty( $post_meta['track'] ) ) {
	foreach ( $post_meta['track'] as $track ) {
		$tracks[] = maybe_unserialize( $track );
	}	

	if ( ! empty( $cc_load_policy ) ) {
		$settings['player']['captions'] = array(
			'active'   => true,
			'language' => 'auto',
			'update'   => false
		); 
	}
}

$tracks = apply_filters( 'aiovg_iframe_vidstack_player_tracks', $tracks );

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
		$settings['player']['markers'] = array(
			'enabled' => true,
			'points'  => array_values( $chapters )
		); 
	}
}

// Video Attributes
$player_theme = ( isset( $player_settings['theme'] ) && 'custom' == $player_settings['theme'] ) ? 'custom' : 'default';

$attributes = array(
	'id'       => 'player',
	'style'    => 'width: 100%; height: 100%; --plyr-color-main: ' . esc_attr( $primary_color ) . ';',
	'controls' => '',
	'preload'  => esc_attr( $player_settings['preload'] )
);

$autoplay = isset( $_GET['autoplay'] ) ? (int) $_GET['autoplay'] : (int) $player_settings['autoplay'];
if ( $autoplay ) {
	$attributes['autoplay'] = '';
	$settings['player']['autoplay'] = true;
}

$loop = isset( $_GET['loop'] ) ? (int) $_GET['loop'] : (int) $player_settings['loop'];
if ( $loop ) {
	$attributes['loop'] = '';
	$settings['player']['loop'] = array( 'active' => true );
}

$muted = isset( $_GET['muted'] ) ? (int) $_GET['muted'] : (int) $player_settings['muted'];
if ( $muted ) {
	$attributes['muted'] = '';
	$settings['player']['muted'] = true;
}

$playsinline = ! empty( $player_settings['playsinline'] ) ? 1 : 0;
if ( $playsinline ) {
	$attributes['playsinline'] = '';
	$settings['player']['playsinline'] = true;
} else {
	$settings['player']['playsinline'] = false;
}

$poster = '';
if ( isset( $_GET['poster'] ) ) {
	$poster = aiovg_base64_decode( $_GET['poster'] );
} elseif ( ! empty( $post_meta ) ) {
	$image_data = aiovg_get_image( $post_id, 'large' );
	$poster = $image_data['src'];
}

if ( ! empty( $poster ) ) {
	$attributes['data-poster'] = aiovg_sanitize_url( aiovg_make_url_absolute( $poster ) );
}

$attributes = apply_filters( 'aiovg_iframe_vidstack_player_attributes', $attributes );

// Player Controls
$has_play = isset( $_GET['playpause'] ) ? (int) $_GET['playpause'] : isset( $player_settings['controls']['playpause'] );
$has_current_time = isset( $_GET['current'] ) ? (int) $_GET['current'] : isset( $player_settings['controls']['current'] );
$has_progress = isset( $_GET['progress'] ) ? (int) $_GET['progress'] : isset( $player_settings['controls']['progress'] );
$has_duration = isset( $_GET['duration'] ) ? (int) $_GET['duration'] : isset( $player_settings['controls']['duration'] );
$has_volume = isset( $_GET['volume'] ) ? (int) $_GET['volume'] : isset( $player_settings['controls']['volume'] );
$has_quality_selector = isset( $_GET['quality'] ) ? (int) $_GET['quality'] : isset( $player_settings['controls']['quality'] );
$has_captions = isset( $_GET['tracks'] ) ? (int) $_GET['tracks'] : isset( $player_settings['controls']['tracks'] );
$has_speed_control = isset( $_GET['speed'] ) ? (int) $_GET['speed'] : isset( $player_settings['controls']['speed'] );
$has_pip = isset( $_GET['pip'] ) ? (int) $_GET['pip'] : isset( $player_settings['controls']['pip'] );
$has_fullscreen = isset( $_GET['fullscreen'] ) ? (int) $_GET['fullscreen'] : isset( $player_settings['controls']['fullscreen'] );

$controls = array();
$controls[] = 'play-large';

if ( $has_play ) {
	$controls[] = 'play';
}

if ( 'custom' === $player_theme ) {
	if ( $has_progress ) {
		$controls[] = 'progress';
	}

	if ( $has_current_time ) {
		$controls[] = 'current-time';
	}
} else {
	if ( $has_current_time ) {
		$controls[] = 'current-time';
	}

	if ( $has_progress ) {
		$controls[] = 'progress';
	}	
}

if ( $has_duration ) {
	$controls[] = 'duration';
}

if ( 'custom' === $player_theme ) {
	$controls[] = 'restart';
}

if ( $has_volume ) {
	$controls[] = 'mute';

	if ( ! wp_is_mobile() ) {
		$controls[] = 'volume';
	}
}

if ( $has_captions ) {
	if ( ! wp_is_mobile() ) {
		$controls[] = 'captions';
	}	
} else {
	if ( empty( $cc_load_policy ) ) {
		$tracks = array();
	}
}

if ( $has_quality_selector || $has_captions || $has_speed_control ) {
	$controls[] = 'settings';

	$settings['player']['settings'] = array();

	if ( $has_quality_selector ) {
		$settings['player']['settings'][] = 'quality';
	}

	if ( $has_captions ) {
		$settings['player']['settings'][] = 'captions';
	}

	if ( $has_speed_control ) {
		$settings['player']['settings'][] = 'speed';

		$settings['player']['speed'] = array(
			'selected' => 1,
			'options'  => array( 0.5, 0.75, 1, 1.5, 2 )
		);
	}
}

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
		$controls[] = 'download';

		if ( empty( $download_url ) ) {
			$download_url = home_url( '?vdl=' . aiovg_get_temporary_file_download_id( $sources['mp4']['src'] ) );
		}

		$settings['player']['urls'] = array(
			'download' => esc_url( $download_url )
		);
	}
}

if ( $has_pip ) {
	$controls[] = 'pip';
}

if ( $has_fullscreen ) {
	$controls[] = 'fullscreen';

	$settings['player']['fullscreen'] = array(
        'enabled'   => true,
        'iosNative' => true
    );
}

$settings['player']['controls'] = $controls;

// Keyboard Hotkeys
if ( isset( $player_settings['hotkeys'] ) && ! empty( $player_settings['hotkeys'] ) ) {
    $settings['player']['keyboard'] = array(
        'focused' => true,
        'global'  => true
    );
}

// YouTube
if ( isset( $sources['youtube'] ) ) {
	$settings['player']['youtube'] = array(
		'noCookie'       => false,
		'rel'            => 0,
		'showinfo'       => 0,
		'iv_load_policy' => 3,
		'modestbranding' => 1
	);

	parse_str( $sources['youtube']['src'], $queries );

	if ( isset( $queries['start'] ) ) {
		$settings['player']['youtube']['start'] = (int) $queries['start'];
	}

	if ( isset( $queries['t'] ) ) {
		$settings['player']['youtube']['start'] = (int) $queries['t'];
	}

	if ( isset( $queries['end'] ) ) {
		$settings['player']['youtube']['end'] = (int) $queries['end'];
	}
}

// Vimeo
if ( isset( $sources['vimeo'] ) ) {
	$settings['player']['vimeo'] = array(
		'byline'      => false,
		'portrait'    => false,
		'title'       => false,
		'speed'       => true,
		'transparent' => false
	);
}

// HLS
if ( isset( $sources['hls'] ) ) {
	$settings['hls'] = $sources['hls']['src'];

    $settings['player']['captions'] = array(
        'active'   => ! empty( $cc_load_policy ) ? true : false,
        'language' => 'auto',
        'update'   => true
    ); 
}

// Dash
if ( isset( $sources['dash'] ) ) {
	$settings['dash'] = $sources['dash']['src'];

    $settings['player']['captions'] = array(
        'active'   => ! empty( $cc_load_policy ) ? true : false,
        'language' => 'auto',
        'update'   => true
    ); 
}

// Share
$has_share = isset( $_GET['share'] ) ? (int) $_GET['share'] : isset( $player_settings['controls']['share'] );
if ( $has_share ) {
	$socialshare_settings = aiovg_get_option( 'aiovg_socialshare_settings' );

	$share_url = $post_url;
	$share_url_encoded = rawurlencode( $share_url );

	$share_title = rawurlencode( $post_title );

	$share_image = isset( $attributes['data-poster'] ) ? $attributes['data-poster'] : '';

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
	// Only a video with no post of its own needs a URL to be identified by. Picked the
	// same way includes/player/vidstack.php does, so both integration modes derive the
	// same capture key for the same video.
	$email_capture_video_url = '';

	if ( 'aiovg_videos' != $post_type && ! empty( $sources ) ) {
		$email_capture_source    = reset( $sources );
		$email_capture_video_url = isset( $email_capture_source['src'] ) ? $email_capture_source['src'] : '';
	}

	$email_capture_config = aiovg_get_email_capture_config( array(
		'post_id'   => $post_id,
		'post_type' => $post_type,
		'post_meta' => $post_meta,
		'override'  => isset( $_GET['email_capture'] ) ? $_GET['email_capture'] : null,
		'rolls'     => 'midpost',
		'video_url' => $email_capture_video_url
	) );

	// The memory test needs the capture key, so it follows resolution rather than
	// wrapping it.
	if ( false !== $email_capture_config && aiovg_should_display_email_capture( $email_capture_config['capture_key'] ) ) {
		$settings['email_capture'] = $email_capture_config;
	}
}

$settings = apply_filters( 'aiovg_iframe_vidstack_player_settings', $settings );

// The inline scripts below reference this from outside the email capture markup
// block, which only renders when the form is actually being shown. Resolved once here
// so a video with no form does not index into a key that is not there. Already empty
// in the site-wide modes — see aiovg_get_email_capture_markup_key().
$email_capture_key       = isset( $settings['email_capture']['capture_key'] ) ? $settings['email_capture']['capture_key'] : '';

// Set only inside the "nocapture" guard above, and the submit handler below sits
// outside it — a reload carrying nocapture=1 would otherwise read an undefined var.
$email_capture_video_url = isset( $email_capture_video_url ) ? $email_capture_video_url : '';

// Trailers should not send statistics and should have limited controls to encourage users to watch the full video.
if ( ! empty( $is_trailer ) ) {
	$tracks = array();
	
	$settings['statistics'] = 0;

	unset( $settings['player']['captions'] );
	unset( $settings['player']['markers'] );
	if ( ! empty( $settings['player']['controls'] ) ) {
		$settings['player']['controls'] = array_values( array_diff( $settings['player']['controls'], array( 'download' ) ) );
	}
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

	<link rel="stylesheet" href="<?php echo AIOVG_PLUGIN_URL; ?>vendor/vidstack/plyr.css?v=3.7.8" />

	<style type="text/css">
		html,
        body {
            margin: 0 !important;
			padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden;
        }	
		
		body * {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
		}

		/* Icons */
		@font-face {
			font-family: 'aiovg-icons';
			src: url('<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.eot?tx9c7f');
			src: url('<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.eot?tx9c7f#iefix') format('embedded-opentype'),
				url('<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.ttf?tx9c7f') format('truetype'),
				url('<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.woff?tx9c7f') format('woff'),
				url('<?php echo AIOVG_PLUGIN_URL; ?>public/assets/fonts/aiovg-icons.svg?tx9c7f#aiovg-icons') format('svg');
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
			font-weight: normal;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			font-variant: normal;
			font-style: normal;	
		}

		.aiovg-icon-share:before {
			content: "\ea82";
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

		.aiovg-icon-close:before {
			content: "\ea0f";
		}		

		/* Common */
		.aiovg-player .plyr {
            width: 100%;
            height: 100%;
        }

		.aiovg-player .plyr a,
        .aiovg-player .plyr a:hover,
        .aiovg-player .plyr a:focus {
            text-decoration: none;
        }

		.aiovg-player .plyr__control--overlaid {
			--plyr-control-spacing: 15px;
		}

		.aiovg-player .plyr__control--overlaid svg {
			--plyr-control-icon-size: 27px;
		}	

		.aiovg-show-email-capture:not(.aiovg-ads-playing) .plyr__control--overlaid {
			display: none !important;
		}

		/* Custom Theme */
		.aiovg-player-theme-custom .plyr__controls {
			flex-wrap: wrap;
			justify-content: flex-start;
		}			

		.aiovg-player-theme-custom .plyr__controls .plyr__controls__item:first-child {
			margin-right: 0;
		}

		.aiovg-player-theme-custom .plyr__controls .plyr__controls__item.plyr__progress__container {
			position: relative;
			order: -1;
			flex: 1 0 100%;
			margin-left: 0;
			padding-left: 0;
		}	

		.aiovg-player-theme-custom .plyr__controls [data-plyr="restart"] {
			visibility: hidden;
		}

		.aiovg-player-theme-custom .plyr__controls .plyr__spacer {
			flex: 1 1 auto;
			height: 100%;
		}

		@media (max-width: 767px) {
			.aiovg-player-theme-custom .plyr__time+.plyr__time {
				display: flex;
			}
		}

		/* Tech: YouTube */
		.aiovg-hide-youtube-logo .plyr--youtube .plyr__video-wrapper iframe {
			pointer-events: none;
			top: -50% !important;
			height: 200% !important;			
		}

		/* Ads */
		.aiovg-player .plyr--playing .plyr__ads {
			display: none;
		}

		.aiovg-player .plyr__ads .plyr__control--overlaid {
			z-index: 999;
		}		

		/* Progressbar */
		.aiovg-player .plyr__progress .plyr__tooltip {
			max-width: fit-content;
		}

		.aiovg-player .plyr__progress .plyr__cues {
			visibility: hidden;
		}

		.aiovg-player .plyr__progress .plyr__progress__marker {	
			border: 2px solid var(--plyr-video-control-color, #ffffff);	
			border-radius: 50%;
			background: transparent;
			width: 10px;
			height: 10px;
		}

		/* Share & Embed */
        .aiovg-player .plyr__share-embed-button {
			position: absolute;  
			top: 15px;		
			right: 15px; 			
			-webkit-transition: opacity .5s;
               -moz-transition: opacity .5s;
                -ms-transition: opacity .5s;
                 -o-transition: opacity .5s;
                    transition: opacity .5s;
			opacity: 1;
			z-index: 1;	
			border-radius: 2px;
			background: rgba( 0, 0, 0, 0.5 );
			width: 35px;
			height: 35px;	
			text-align: center;  
			line-height: 1;
		} 

		.aiovg-player .plyr--hide-controls .plyr__share-embed-button {
			opacity: 0;
		}         

		.aiovg-player .plyr__share-embed-button:hover,
		.aiovg-player .plyr__share-embed-button:focus {        
			background: #00B3FF;	
		} 

		.aiovg-player .plyr__share-embed-modal {
			pointer-events: none;			
            display: flex;
			position: absolute;
			top: 0;
			left: 0;			
			flex-direction: column;            
            align-items: center;
			justify-content: center;
			-webkit-transition: opacity .5s;
               -moz-transition: opacity .5s;
                -ms-transition: opacity .5s;
                 -o-transition: opacity .5s;
                    transition: opacity .5s;   
			opacity: 0;	
			z-index: 9;         		
			background-color: #222;						
			width: 100%;
            height: 100%; 
        }

		.aiovg-player .plyr__share-embed-modal.fadein {			
			pointer-events: auto;
			opacity: 1;
		}

		.aiovg-player .plyr__share-embed-modal-content {
            width: 100%;
        }

		.aiovg-player .plyr__share-embed-modal-close-button {
			display: block;
			position: absolute;
			top: 15px;
			right: 15px;
			z-index: 9;
			border-radius: 2px;	
			cursor: pointer;
			width: 35px;
			height: 35px;
			text-align: center;
			line-height: 1;					
		}

		.aiovg-player .plyr__share-embed-modal-close-button:hover,
		.aiovg-player .plyr__share-embed-modal-close-button:focus {        
			background: #00B3FF;	
		} 

		.aiovg-player .plyr__share {
            text-align: center;
        }

		.aiovg-player .plyr__share-button {
            display: inline-block;
			margin: 2px;
			border-radius: 2px;
            width: 40px;
			height: 40px;            
			vertical-align: middle;
			line-height: 1;
        }       

		.aiovg-player .plyr__share-button:hover {
            opacity: 0.9;
        }

        .aiovg-player .plyr__share-button-facebook {
            background-color: #3B5996;
        }   
		
		.aiovg-player .plyr__share-button-twitter {
            background-color: #55ACEE;
        }

        .aiovg-player .plyr__share-button-linkedin {
            background-color: #006699;
        }

        .aiovg-player .plyr__share-button-pinterest {
            background-color: #C00117;
        }

        .aiovg-player .plyr__share-button-tumblr {
            background-color: #28364B;
        } 
		
		.aiovg-player .plyr__share-button-whatsapp {
            background-color: #25d366;
        }
		
		.aiovg-player .plyr__share-button-email {
            background-color: #6E6E6E;
        }

        .aiovg-player .plyr__share-button span {
           	line-height: 40px;
			color: #fff;
            font-size: 24px;
        }

        .aiovg-player .plyr__embed {	
		    margin: auto;
			padding: 20px;
			max-width: 720px;
        }

        .aiovg-player .plyr__embed label {
			display: block;
			margin: 0 0 7px 0;			
            text-align: center;
			text-transform: uppercase;
			color: #fff;
			font-size: 11px;
        }

		.aiovg-player .plyr__embed input { 
			box-sizing: border-box;           	
            border: 1px solid #fff;
			border-radius: 1px;			
			background: #fff;
			padding: 7px;
			width: 100%;	            
			line-height: 1;
			color: #000;
        }

        .aiovg-player .plyr__embed input:focus {
            border: 1px solid #fff;
        }

		/* Logo Overlay */
		.aiovg-player .plyr__logo {			
            -webkit-transition: opacity .5s;
               -moz-transition: opacity .5s;
                -ms-transition: opacity .5s;
                 -o-transition: opacity .5s;
                    transition: opacity .5s;
			opacity: 1;
		}

		.aiovg-player .plyr--hide-controls .plyr__logo {
			opacity: 0;
		}

		.aiovg-player .plyr__logo a {
			position: absolute;			
			transition: opacity 0.1s;
			opacity: 0.6;
			z-index: 3; 
			line-height: 1;			
		}

		.aiovg-player .plyr__logo a:hover {
			opacity: 1;
		}

		.aiovg-player .plyr__logo img {
			max-width: 150px;
		}

		/* Trailer Badge */
		.aiovg-player .plyr .aiovg-trailer-badge {
			display: inline-flex;
			position: absolute;
			top: 10px;
			left: 10px;
			align-items: center;
			z-index: 3;
			box-sizing: border-box;
			margin: 0;
			border: 0;
			border-radius: 3px;
			box-shadow: none;
			background-color: rgba( 0, 0, 0, 0.75 );
			padding: 4px 10px;
			max-width: calc( 100% - 20px );
			min-height: 24px;
			text-decoration: none;
			line-height: 1.3;
			color: #fff;
			font-size: 12px;
			font-weight: 600;
		}

		.aiovg-player .plyr a.aiovg-trailer-badge:hover {
			background-color: rgba( 0, 0, 0, 0.9 );
		}

		.aiovg-player .plyr a.aiovg-trailer-badge:focus-visible {
			outline: 2px solid #fff;
			outline-offset: 2px;
		}

		/* Clears the control bar, as a bottom-left logo does. */
		.aiovg-player .plyr .aiovg-trailer-badge-bottom {
			top: auto;
			bottom: 50px;
		}

		/* The custom theme puts the progress bar on its own row above the buttons. */
		.aiovg-player.aiovg-player-theme-custom .plyr .aiovg-trailer-badge-bottom {
			bottom: 70px;
		}

		.aiovg-ads-playing .aiovg-trailer-badge,
		.aiovg-show-email-capture .aiovg-trailer-badge {
			display: none !important;
		}

		/* Trailer ended: the 72px replay button, an 8px gap and the 44px button, centred as a group
		   in the space above the control bar, which stays visible. */
		.aiovg-player .plyr.aiovg-has-trailer-cta.aiovg-is-ended .plyr__control--overlaid {
			top: calc( 50% - 49px );
		}

		.aiovg-player .plyr.aiovg-has-trailer-cta.aiovg-is-ended a.aiovg-trailer-badge {
			top: calc( 50% - 5px );
			right: 0;
			bottom: auto;
			left: 0;
			justify-content: center;
			margin: 0 auto;
			background-color: var(--plyr-color-main, #3b82f6);
			padding: 8px 20px;
			width: fit-content;
			min-height: 44px;
			text-align: center;
			font-size: 19px;
			font-weight: 700;
		}

		.aiovg-player .plyr.aiovg-has-trailer-cta.aiovg-is-ended a.aiovg-trailer-badge:hover {
			background-color: var(--plyr-color-main, #3b82f6);
			opacity: 0.85;
		}

		/* The custom theme's control bar is taller (progress bar on its own row). */
		.aiovg-player.aiovg-player-theme-custom .plyr.aiovg-has-trailer-cta.aiovg-is-ended .plyr__control--overlaid {
			top: calc( 50% - 61px );
		}

		.aiovg-player.aiovg-player-theme-custom .plyr.aiovg-has-trailer-cta.aiovg-is-ended a.aiovg-trailer-badge {
			top: calc( 50% - 17px );
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
	
	<?php do_action( 'aiovg_iframe_vidstack_player_head', $settings, $attributes, $sources, $tracks ); ?>
</head>
<body class="aiovg-player aiovg-player-theme-<?php echo esc_attr( $player_theme ); ?><?php if ( ! empty( $player_settings['hide_youtube_logo'] ) ) : ?> aiovg-hide-youtube-logo<?php endif; ?><?php if ( ! empty( $is_trailer ) ) : ?> aiovg-is-trailer<?php endif; ?>">
	<?php
	// YouTube
	if ( isset( $sources['youtube'] ) ) {
		$video_id = aiovg_get_youtube_id_from_url( $sources['youtube']['src'] );		

		printf(
			'<div id="%s" style="%s" data-plyr-provider="youtube" data-plyr-embed-id="%s" data-poster="%s"></div>',
			esc_attr( $attributes['id'] ),
			esc_attr( $attributes['style'] ),
			esc_attr( $video_id ),
			( isset( $attributes['data-poster'] ) ? esc_url( $attributes['data-poster'] ) : '' )
		);
	}

	// Vimeo
	elseif ( isset( $sources['vimeo'] ) ) {
		$video_id = aiovg_get_vimeo_id_from_url( $sources['vimeo']['src'] );
	
		printf(
			'<div id="%s" style="%s" data-plyr-provider="vimeo" data-plyr-embed-id="%s" data-poster="%s"></div>',
			esc_attr( $attributes['id'] ),
			esc_attr( $attributes['style'] ),
			esc_attr( $video_id ),
			( isset( $attributes['data-poster'] ) ? esc_url( $attributes['data-poster'] ) : '' )
		);
	}

	// HLS or Dash
	elseif ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) {
		printf( '<video %s></video>', aiovg_combine_video_attributes( $attributes ) );
	}

	// HTML5 Video
	elseif ( ! empty( $sources ) ) {
		printf( '<video %s>', aiovg_combine_video_attributes( $attributes ) );

		// Video Sources
		foreach ( $sources as $source ) {
			printf( 
				'<source src="%s" type="%s" size="%d" />', 				
				esc_url( aiovg_make_url_absolute( $source['src'] ) ),
				esc_attr( $source['type'] ), 
				( isset( $source['label'] ) ? (int) $source['label'] : '' )
			);
		}

		// Video Tracks		
		foreach ( $tracks as $index => $track ) {
        	printf( 
				'<track kind="captions" src="%s" label="%s" srclang="%s" />', 
				esc_url( aiovg_make_url_absolute( $track['src'] ) ), 				
				esc_attr( $track['label'] ),
				esc_attr( $track['srclang'] )
			);
		}

		echo '</video>';
	}
	?>

	<?php if ( isset( $settings['share'] ) || isset( $settings['embed'] ) ) : ?>
		<div id="plyr__share-embed-modal" class="plyr__share-embed-modal" style="display: none;">
			<div class="plyr__share-embed-modal-content">
				<?php if ( isset( $settings['share'] ) ) : ?>
					<!-- Share Buttons -->
					<div class="plyr__share">
						<?php
						foreach ( $share_buttons as $button ) {
							printf( 
								'<a href="%s" class="plyr__share-button plyr__share-button-%s" target="_blank"><span class="%s"></span><span class="plyr__sr-only">%s</span></a>',							
								esc_attr( $button['url'] ), 
								esc_attr( $button['service'] ),
								esc_attr( $button['icon'] ),
								esc_attr( $button['text'] )
							);
						}
						?>
					</div>
				<?php endif; ?>

				<?php if ( isset( $settings['embed'] ) ) : ?>
					<!-- Embed Code -->
					<div class="plyr__embed">
						<label for="plyr__embed-code-input"><?php esc_html_e( 'Paste this code in your HTML page', 'all-in-one-video-gallery' ); ?></label>
						<input type="text" id="plyr__embed-code-input" value="<?php echo esc_attr( $embed_code ); ?>" readonly />
					</div>
				<?php endif; ?>

				<!-- Close Button -->
				<button type="button" id="plyr__share-embed-modal-close-button" class="plyr__controls__item plyr__control plyr__share-embed-modal-close-button aiovg-icon-close"><span class="plyr__sr-only">Close</span></button>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( isset( $settings['email_capture'] ) ) : ?>
		<div id="aiovg-email-capture" data-capture_key="<?php echo esc_attr( $settings['email_capture']['capture_key'] ); ?>" style="display: none;">
			<div id="aiovg-email-capture-block">
				<div id="aiovg-email-capture-header"><?php echo esc_html( $settings['email_capture']['header_text'] ); ?></div>

				<?php $email_capture_prefill = aiovg_get_email_capture_prefill(); ?>

				<div id="aiovg-email-capture-fields">
					<?php if ( ! empty( $settings['email_capture']['collect_name'] ) ) : ?>
						<input type="text" id="aiovg-email-capture-name" name="name" value="<?php echo esc_attr( $email_capture_prefill['name'] ); ?>" placeholder="<?php esc_attr_e( 'Your name', 'all-in-one-video-gallery' ); ?>" autocomplete="name" />
					<?php endif; ?>

					<input type="email" id="aiovg-email-capture-email" name="email" value="<?php echo esc_attr( $email_capture_prefill['email'] ); ?>" placeholder="<?php esc_attr_e( 'Your email address *', 'all-in-one-video-gallery' ); ?>" required autocomplete="email" aria-describedby="aiovg-email-capture-error" />

					<?php if ( ! empty( $settings['email_capture']['collect_phone'] ) ) : ?>
						<input type="tel" id="aiovg-email-capture-phone" name="phone" value="<?php echo esc_attr( $email_capture_prefill['phone'] ); ?>" placeholder="<?php esc_attr_e( 'Your phone number', 'all-in-one-video-gallery' ); ?>" autocomplete="tel" />
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

	<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/vidstack/plyr.polyfilled.js?v=3.7.8" type="text/javascript" defer></script>

	<?php if ( isset( $sources['hls'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/vidstack/hls.min.js?v=1.5.17" type="text/javascript" defer></script>
	<?php endif; ?>

	<?php if ( isset( $sources['dash'] ) ) : ?>
		<script src="<?php echo AIOVG_PLUGIN_URL; ?>vendor/vidstack/dash.all.min.js?v=4.7.4" type="text/javascript" defer></script>
	<?php endif; ?>

	<?php do_action( 'aiovg_iframe_vidstack_player_footer', $settings, $attributes, $sources, $tracks ); ?>

    <script type="text/javascript">
		'use strict';			
			
		/**
		 * Vars
		 */ 

		var settings = <?php echo json_encode( $settings ); ?>;	
		var emailCaptureEl = null;

		/**
		 * Email capture implementation.
		 * Mid-roll: Show when currentTime reaches the configured percentage.
		 */
		function initEmailCapture( player ) {
			emailCaptureEl = document.getElementById( 'aiovg-email-capture' );
			player.elements.container.appendChild( emailCaptureEl );

			let isModalOpen = false;
			let wasPlaying  = false;

			// Show the overlay and pause the player (mid-roll only).
			const showEmailCapture = () => {
				if ( ! emailCaptureEl || isModalOpen ) return false;
				isModalOpen = true;

				if ( ! player.ended ) {
					wasPlaying = ! player.paused || player.seeking;
					player.pause();
				}

				document.body.classList.add( 'aiovg-show-email-capture' );

				const firstInput = emailCaptureEl.querySelector( 'input' );
				if ( firstInput && ! firstInput.value ) {
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

			const emailCaptureVideoUrl = <?php echo wp_json_encode( $email_capture_video_url ); ?>;

			// Which video this is. The skip path sends it too: without it the server
			// cannot derive a capture key, and "Once per video" would never remember
			// the skip, asking again on every play.
			const videoParams = () => {
				if ( 'aiovg_videos' == settings.post_type && settings.post_id ) {
					return '&video_id=' + encodeURIComponent( settings.post_id );
				}

				// The URL PHP derived the capture key from, not the player's resolved
				// source, which can be a variant and would make the server recompute a
				// different key from the one in data-capture_key.
				return '&video_id=0&video_url=' + encodeURIComponent( emailCaptureVideoUrl );
			};

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

							// A skip the server did not remember clears only the consent gates. An empty
							// key means "once per visitor", where one capture clears every player.
							const remembered = ! ( response.data && false === response.data.remembered );

							window.parent.postMessage( remembered ? {
								message: 'aiovg-email-captured',
								context: 'iframe',
								capture_key: <?php echo wp_json_encode( $email_capture_key ); ?>
							} : {
								message: 'aiovg-cookie-consent',
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

				const onMidRollTimeUpdate = function() {
					if ( ! emailCaptureEl ) {
						player.off( 'timeupdate', onMidRollTimeUpdate );
						return false;
					}

					if ( midRollFired ) return false;

					const duration = player.duration;
					const current  = player.currentTime;

					if ( duration && current >= ( parseInt( settings.email_capture.mid_roll ) / 100 ) * duration ) {
						midRollFired = true;
						player.off( 'timeupdate', onMidRollTimeUpdate );
						showEmailCapture();
					}
				};

				player.on( 'timeupdate', onMidRollTimeUpdate );
			}				

			// Post-roll — show after the video ends.
			// Skipped when autoadvance is active to avoid conflicting with next-video navigation,
			// and when a trailer ends on its button instead.
			const hasTrailerButton = settings.hasOwnProperty( 'trailer' ) && settings.trailer.url;

			if ( settings.email_capture.post_roll && ! settings.hasOwnProperty( 'autoadvance' ) && ! hasTrailerButton ) {
				player.on( 'ended', function() {
					showEmailCapture();
				});
			}

			// Re-pause on any play event while the overlay is open.
			// Handles post-seek auto-resume (Plyr resumes after seek if it was playing).
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
				submitBtn.addEventListener( 'click', function() {
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

					params += videoParams();

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
						ajaxEmailCapture( 'skipped=1' + videoParams() );
					});

					skipLink.addEventListener( 'keydown', function( event ) {
						if ( 13 === event.keyCode || 32 === event.keyCode ) {
							event.preventDefault();
							ajaxEmailCapture( 'skipped=1' + videoParams() );
						}
					});
				}
			}
		}

		/**
		 * Update the views count.
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
					// console.log( xmlhttp.responseText );						
				}					
			}	

			var duration = player.duration || 0;

			xmlhttp.open( 'GET', '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=aiovg_update_views_count&post_id=<?php echo $post_id; ?>&duration=' + duration + '&security=<?php echo esc_js( wp_create_nonce( 'aiovg_public_ajax_nonce' ) ); ?>', true );
			xmlhttp.send();							
		}

		/**
		 * Init player.
		 */		
		function initPlayer() {
			var video  = document.getElementById( 'player' ); 
			var player = new Plyr( video, settings.player );

			var plyr = document.querySelector( '.plyr' );
			var lastEvent = null;			

			// Dispatch an event.
			var customEvent = document.createEvent( 'CustomEvent' );
			customEvent.initCustomEvent( 'player.init', false, false, { player: player, settings: settings } );
			window.dispatchEvent( customEvent );

			// On ready.
			player.on( 'ready', function() {
				lastEvent = 'ready';

				// Insert custom spacer
				const restartButton = player.elements.container.querySelector( '[data-plyr="restart"]' );

				if ( restartButton ) {
					const spacer = document.createElement( 'div' );
					spacer.className = 'plyr__controls__item plyr__spacer';
					spacer.setAttribute( 'aria-hidden', 'true' );

					// Replace the restart button with the spacer
					restartButton.parentNode.replaceChild( spacer, restartButton );
				}

				// Share / Embed.
				if ( settings.hasOwnProperty( 'share' ) || settings.hasOwnProperty( 'embed' ) ) {
					var shareButton = document.createElement( 'button' );
					shareButton.type = 'button';
					shareButton.className = 'plyr__controls__item plyr__control plyr__share-embed-button aiovg-icon-share';
					shareButton.innerHTML = '<span class="plyr__sr-only">Share</span>';

					plyr.appendChild( shareButton );					

					var closeButton = document.getElementById( 'plyr__share-embed-modal-close-button' );

					var modal = document.getElementById( 'plyr__share-embed-modal' );
					modal.style.display = 'flex';

					plyr.appendChild( modal );						

					// Show Modal.
					var wasPlaying = false;

					shareButton.addEventListener( 'click',  function() {
						if ( player.playing ) {
							wasPlaying = true;
							player.pause();
						} else {
							wasPlaying = false;
						}                    

						shareButton.style.display = 'none';						
						modal.className += ' fadein';
					});

					// Hide Modal.
					closeButton.addEventListener( 'click',  function() {
						if ( wasPlaying ) {
							player.play();
						}

						modal.className = modal.className.replace( ' fadein', '' );

						setTimeout(function() {
							shareButton.style.display = ''; 
						}, 500);				                           	
					});

					// Copy Embedcode.
					if ( settings.hasOwnProperty( 'embed' ) ) {
						document.getElementById( 'plyr__embed-code-input' ).addEventListener( 'focus', function() {
							this.select();
							
							if ( navigator.clipboard && navigator.clipboard.writeText ) {
								navigator.clipboard.writeText( this.value ).catch( () => {} );
							} else {
								document.execCommand( 'copy' );
							}
						});
					}
				}

				// Logo
				if ( settings.hasOwnProperty( 'logo' ) ) {
					var style = 'bottom:50px; left:' + settings.logo.margin + 'px;';

					switch ( settings.logo.position ) {
						case 'topleft':
							style = 'top:' + settings.logo.margin + 'px; left:' + settings.logo.margin + 'px;';
							break;

						case 'topright':
							style = 'top:' + settings.logo.margin + 'px; right:' + settings.logo.margin + 'px;';
							break;

						case 'bottomright':
							style = 'bottom:50px; right:' + settings.logo.margin + 'px;';
							break;
					}

					var logo = document.createElement( 'div' );
					logo.className = 'plyr__logo';
					logo.innerHTML = '<a href="' + settings.logo.link + '" target="_top" style="' + style + '"><img src="' + settings.logo.image + '" alt="" /><span class="plyr__sr-only">Logo</span></a>';

					plyr.appendChild( logo );
				}

				// Trailer Badge
				if ( settings.hasOwnProperty( 'trailer' ) ) {
					const badge = document.createElement( settings.trailer.url ? 'a' : 'span' );
					badge.className = 'aiovg-trailer-badge';
					badge.textContent = settings.trailer.label;

					if ( settings.trailer.url ) {
						badge.href = settings.trailer.url;
						badge.target = '_top';

						// Turns the badge into a button when the trailer ends, unless the next video autoplays.
						if ( ! settings.hasOwnProperty( 'autoadvance' ) ) {
							plyr.classList.add( 'aiovg-has-trailer-cta' );
						}
					}

					// Keep clear of a top-left logo.
					if ( settings.hasOwnProperty( 'logo' ) && 'topleft' == settings.logo.position ) {
						badge.classList.add( 'aiovg-trailer-badge-bottom' );
					}

					plyr.appendChild( badge );
				}

				// Email Capture.
				if ( settings.hasOwnProperty( 'email_capture' ) ) {
					initEmailCapture( player );
				}
			});

			// On playing.
			var hasVideoStarted = false;

			player.on( 'playing', function() {
				lastEvent = 'playing';

				if ( ! hasVideoStarted ) {
					hasVideoStarted = true;
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

			// On ended.
			player.on( 'ended', function() {
				if ( lastEvent == 'ended' ) {
					return false;
				}

				lastEvent = 'ended';
				plyr.className += ' plyr--stopped';

				// Plyr also sets plyr--stopped on the poster, so it can't mark the end.
				plyr.classList.add( 'aiovg-is-ended' );

				// Autoplay next video.
				if ( settings.hasOwnProperty( 'autoadvance' ) ) {
					window.parent.postMessage({ 				
						message: 'aiovg-video-ended',
						context: 'iframe'
					}, window.location.origin );
				}
			});

			player.on( 'play', function() {
				plyr.classList.remove( 'aiovg-is-ended' );
			});

			// HLS
			if ( settings.hasOwnProperty( 'hls' ) ) {
				const hls = new Hls();
				hls.loadSource( settings.hls );
				hls.attachMedia( video );

				// Handle changing captions.
				player.on( 'languagechange', () => {
					setTimeout( () => hls.subtitleTrack = player.currentTrack, 50 );
				});
			}

			// Dash
			if ( settings.hasOwnProperty( 'dash' ) ) {
				const dash = dashjs.MediaPlayer().create();
				dash.initialize( video, settings.dash, settings.player.autoplay || false );
			}		

			// Custom contextmenu.
			if ( settings.hasOwnProperty( 'contextmenu' ) ) {
				var contextmenu    = document.getElementById( 'aiovg-contextmenu' );
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
						// Per-video scoping. No key in the message means "once per visitor",
						// where any capture clears every player.
						if ( ! event.data.capture_key || event.data.capture_key === <?php echo wp_json_encode( $email_capture_key ); ?> ) {
							emailCaptureEl = null;
							document.body.classList.remove( 'aiovg-show-email-capture' );
						}
						break;

					case 'aiovg-video-play':
						player.play();
						break;

					case 'aiovg-video-pause':
						player.pause();
						break;

					case 'aiovg-video-seek':
						if ( event.data.hasOwnProperty( 'seconds' ) ) {							
							player.currentTime = event.data.seconds;
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