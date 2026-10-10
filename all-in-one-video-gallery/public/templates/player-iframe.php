<?php

/**
 * Video Player: Third-Party.
 *
 * @link     https://plugins360.com
 * @since    1.6.0
 *
 * @package All_In_One_Video_Gallery
 */
 
$player_html            = '';
$maybe_shortcode        = false;
$is_youtube             = false;
$is_vimeo               = false;
$enable_referrer_policy = false;
$dailymotion_watch_url  = '';

if ( ! empty( $post_meta ) ) {
    if ( in_array( $current_video_provider, $thirdparty_providers_all ) ) {
        $embed_url = $post_meta[ $current_video_provider ][0];
    }

    if ( 'embedcode' == $current_video_provider ) { 
        $embedcode = $post_meta['embedcode'][0];

		if ( $iframe_src = aiovg_extract_iframe_src( $embedcode ) ) { 
            $embed_url = $iframe_src; 

            if ( false !== strpos( $embed_url, 'youtube.com' ) || false !== strpos( $embed_url, 'youtu.be' ) ) {
                $is_youtube             = true;
                $enable_referrer_policy = true;

                $embed_url = add_query_arg( 'enablejsapi', 1, $embed_url );
            }

            if ( false !== strpos( $embed_url, 'vimeo.com' ) ) {
                $is_vimeo = true;
            }

            // Dailymotion embed codes without their own Player ID play through ours, like a Dailymotion URL.
            if ( false !== strpos( $embed_url, 'dailymotion.com' ) && false === strpos( $embed_url, 'dailymotion.com/player/' ) && '' != aiovg_get_dailymotion_id_from_url( $embed_url ) ) {
                $current_video_provider = 'dailymotion';
            }
        } else {
            $player_html     = $embedcode;
            $maybe_shortcode = true;
        }
	}
} else {
    if ( in_array( $current_video_provider, $thirdparty_providers_all ) ) {
        if ( isset( $_GET[ $current_video_provider ] ) ) {
            $embed_url = aiovg_base64_decode( $_GET[ $current_video_provider ] );
        }
    }
}

if ( ! empty( $embed_url ) ) {
    $options  = array( 'playpause', 'current', 'progress', 'duration', 'volume', 'fullscreen' );
    $controls = array();
    
    foreach ( $options as $option ) {	
        if ( isset( $_GET[ $option ] ) ) {	
            if ( 1 == (int) $_GET[ $option ] ) {
                $controls[] = $option;
            }		
        } else {	
            if ( isset( $player_settings['controls'][ $option ] ) ) {
                $controls[] = $option;
            }		
        }	
    }

    // YouTube
    if ( 'youtube' == $current_video_provider ) {
        $is_youtube             = true;
        $enable_referrer_policy = true;

        parse_str( $embed_url, $queries );
                    
        $embed_url = 'https://www.youtube.com/embed/' . aiovg_get_youtube_id_from_url( $embed_url ) . '?iv_load_policy=3&modestbranding=1&rel=0&showinfo=0&enablejsapi=1';	                    
        
        if ( isset( $queries['start'] ) ) {
            $embed_url = add_query_arg( 'start', (int) $queries['start'], $embed_url );
        }

        if ( isset( $queries['t'] ) ) {
            $embed_url = add_query_arg( 'start', (int) $queries['t'], $embed_url );
        }
        
        if ( isset( $queries['end'] ) ) {
            $embed_url = add_query_arg( 'end', (int) $queries['end'], $embed_url );
        } 

        $autoplay = isset( $_GET['autoplay'] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $embed_url = add_query_arg( 'cc_load_policy', (int) $player_settings['cc_load_policy'], $embed_url );        

        if ( empty( $controls ) ) {
            $embed_url = add_query_arg( 'controls', 0, $embed_url );
        }        

        if ( ! in_array( 'fullscreen', $controls ) ) {
            $embed_url = add_query_arg( 'fs', 0, $embed_url );
        } 

        $loop = isset( $_GET['loop'] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET['muted'] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'mute', (int) $muted, $embed_url ); 

        $playsinline = ! empty( $player_settings['playsinline'] ) ? 1 : 0;
        $embed_url = add_query_arg( 'playsinline', $playsinline, $embed_url );
        
        $embed_url = apply_filters( 'aiovg_youtube_embed_url', $embed_url, $post_id );
    }

    // Vimeo
    if ( 'vimeo' == $current_video_provider ) {
        $is_vimeo = true;
        $oembed   = aiovg_get_vimeo_oembed_data( $embed_url );

        $embed_url = 'https://player.vimeo.com/video/' . $oembed['video_id'] . '?byline=0&portrait=0&title=0&vimeo_logo=0';

        if ( ! empty( $oembed['html'] ) ) {
            if ( $iframe_src = aiovg_extract_iframe_src( $oembed['html'] ) ) {
                $parsed_url = parse_url( $iframe_src, PHP_URL_QUERY );
                parse_str( $parsed_url, $queries );

                if ( isset( $queries['app_id'] ) ) {
                    $embed_url = add_query_arg( 'app_id', $queries['app_id'], $embed_url );
                }

                if ( isset( $queries['h'] ) ) {
                    $embed_url = add_query_arg( 'h', $queries['h'], $embed_url );
                }                
            }
        }

        $autoplay = isset( $_GET['autoplay'] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $loop = isset( $_GET['loop'] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET['muted'] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'muted', (int) $muted, $embed_url );

        $playsinline = ! empty( $player_settings['playsinline'] ) ? 1 : 0;
        $embed_url = add_query_arg( 'playsinline', $playsinline, $embed_url );

        $tracks = isset( $_GET['tracks'] ) ? (int) $_GET['tracks'] : isset( $player_settings['controls']['tracks'] );
        $cc_load_policy = isset( $_GET['cc_load_policy'] ) ? (int) $_GET['cc_load_policy'] : (int) $player_settings['cc_load_policy'];
        if ( ! empty( $tracks ) || ! empty( $cc_load_policy ) ) {
			$embed_url = add_query_arg( 'texttrack', 'en-x-autogen', $embed_url );
		}

        $embed_url = apply_filters( 'aiovg_vimeo_embed_url', $embed_url, $post_id );
    }

    // Dailymotion
    if ( 'dailymotion' == $current_video_provider ) {
        $dailymotion_player_id = aiovg_get_dailymotion_player_id();

        if ( ! empty( $dailymotion_player_id ) ) {
            // The iframe player only reads "loop"; the rest is set on the player in Dailymotion Studio.
            $embed_url = 'https://geo.dailymotion.com/player/' . $dailymotion_player_id . '.html?video=' . aiovg_get_dailymotion_id_from_url( $embed_url );

            $loop = isset( $_GET['loop'] ) ? $_GET['loop'] : $player_settings['loop'];
            if ( ! empty( $loop ) ) {
                $embed_url = add_query_arg( 'loop', 'true', $embed_url );
            }

            $embed_url = apply_filters( 'aiovg_dailymotion_embed_url', $embed_url, $post_id );
        } else {
            // Dailymotion refuses embeds without a Player ID, so link to the video instead.
            $dailymotion_watch_url = aiovg_get_dailymotion_watch_url( $embed_url );

            $dailymotion_image = '';

            if ( isset( $_GET['poster'] ) ) {
                $dailymotion_image = aiovg_base64_decode( $_GET['poster'] );
            } elseif ( ! empty( $post_meta ) ) {
                $image_data = aiovg_get_image( $post_id, 'large' );
                $dailymotion_image = $image_data['src'];
            }

            if ( ! empty( $dailymotion_image ) ) {
                $dailymotion_image = aiovg_make_url_absolute( $dailymotion_image );
            } else {
                $dailymotion_image = aiovg_get_dailymotion_image_url( $embed_url );
            }
        }
    }

    // Rumble
    if ( 'rumble' == $current_video_provider ) {
        $oembed = aiovg_get_rumble_oembed_data( $embed_url );

        if ( ! empty( $oembed['html'] ) ) {
            if ( $iframe_src = aiovg_extract_iframe_src( $oembed['html'] ) ) { 
                $embed_url = add_query_arg( 'rel', 0, $iframe_src );	
                        
                $autoplay = isset( $_GET['autoplay'] ) ? (int) $_GET['autoplay'] : (int) $player_settings['autoplay'];
                if ( ! empty( $autoplay ) ) {
                    $embed_url = add_query_arg( 'autoplay', 2, $embed_url );	
                }
            }
        }

        $embed_url = apply_filters( 'aiovg_rumble_embed_url', $embed_url, $post_id );
    }

    // Facebook
    if ( 'facebook' == $current_video_provider ) {
        $embed_url = 'https://www.facebook.com/plugins/video.php?href=' . urlencode( $embed_url ) . '&width=560&height=315&show_text=false&appId';
    
        $autoplay = isset( $_GET['autoplay'] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $loop = isset( $_GET['loop'] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET['muted'] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'muted', (int) $muted, $embed_url );

        $embed_url = apply_filters( 'aiovg_facebook_embed_url', $embed_url, $post_id );
    }

    // Bunny Stream
    if ( 'bunny_stream' == $current_video_provider ) {
        $autoplay = isset( $_GET['autoplay'] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $autoplay = ! empty( $autoplay ) ? 'true' : 'false';
        $embed_url = add_query_arg( 'autoplay', $autoplay, $embed_url );

        $preload = isset( $_GET['preload'] ) ? $_GET['preload'] : $player_settings['preload'];
        $preload = ( 'none' == $preload ) ? 'false' : 'true';
        $embed_url = add_query_arg( 'preload', $preload, $embed_url );

        $muted = isset( $_GET['muted'] ) ? $_GET['muted'] : $player_settings['muted'];
        $muted = ! empty( $muted ) ? 'true' : 'false';
        $embed_url = add_query_arg( 'muted', $muted, $embed_url );

        $loop = isset( $_GET['loop'] ) ? $_GET['loop'] : $player_settings['loop'];
        $loop = ! empty( $loop ) ? 'true' : 'false';
        $embed_url = add_query_arg( 'loop', $loop, $embed_url );

        $playsinline = ! empty( $player_settings['playsinline'] ) ? 'true' : 'false';
        $embed_url = add_query_arg( 'playsinline', $playsinline, $embed_url );

        $speed = isset( $_GET['speed'] ) ? $_GET['speed'] : isset( $player_settings['controls']['speed'] );
        $speed = ! empty( $speed ) ? 'true' : 'false';
        $embed_url = add_query_arg( 'showSpeed', $speed, $embed_url );
    }

    if ( ! empty( $dailymotion_watch_url ) ) {
        // "Watch on Dailymotion" link over the poster
        $player_html = sprintf(
            '<a id="aiovg-dailymotion-link" href="%s" target="_blank" rel="noopener noreferrer"%s><span id="aiovg-dailymotion-button">%s</span></a>',
            esc_url( $dailymotion_watch_url ),
            ( ! empty( $dailymotion_image ) ? sprintf( ' style="background-image: url(\'%s\')"', esc_url( $dailymotion_image ) ) : '' ),
            esc_html__( 'Watch on Dailymotion', 'all-in-one-video-gallery' )
        );
    } else {
        // Build iframe code
        $player_html = sprintf(
            '<iframe id="aiovg-player-iframe" src="%s" title="%s" width="560" height="315" frameborder="0" scrolling="no" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen%s></iframe>',
            esc_url( $embed_url ),
            esc_attr( $post_title ),
            ( $enable_referrer_policy ? ' referrerpolicy="strict-origin-when-cross-origin"' : '' )
        );
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
          
    <?php if ( $post_id > 0 ) : ?>    
        <title><?php echo wp_kses_post( $post_title ); ?></title>    
        <link rel="canonical" href="<?php echo esc_url( $post_url ); ?>" />
    <?php endif; ?>
    
	<style type="text/css">
        html, 
        body, 
        iframe {            
            margin: 0 !important; 
            padding: 0 !important; 
            width: 100% !important;
            height: 100% !important;
            overflow: hidden;
        }
    </style>

    <?php if ( ! empty( $dailymotion_watch_url ) ) : ?>
        <style type="text/css">
            #aiovg-dailymotion-link {
                display: flex;
                position: relative;
                align-items: center;
                justify-content: center;
                background-color: #222;
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
                width: 100%;
                height: 100%;
                text-decoration: none;
            }

            #aiovg-dailymotion-link:before {
                position: absolute;
                inset: 0;
                background-color: rgba( 0, 0, 0, 0.5 );
                content: "";
            }

            /* Same button as the cookie consent and email capture overlays */
            #aiovg-dailymotion-button {
                display: inline-flex;
                position: relative;
                align-items: center;
                justify-content: center;
                border-radius: 3px;
                background-color: <?php echo esc_attr( $primary_color ); ?>;
                padding: 0 20px;
                min-height: 44px;
                line-height: 1;
                color: #fff;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 14px;
                font-weight: 600;
            }

            #aiovg-dailymotion-link:hover #aiovg-dailymotion-button,
            #aiovg-dailymotion-link:focus #aiovg-dailymotion-button {
                opacity: 0.85;
            }
        </style>
    <?php endif; ?>

    <?php if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) : ?>
        <style type="text/css">
		    <?php echo esc_html( $general_settings['custom_css'] ); ?>
        </style>
	<?php endif; ?>
    
    <?php if ( $maybe_shortcode ) wp_head(); ?>
    <?php do_action( 'aiovg_player_iframe_head' ); ?>
</head>
<body>    
    <?php echo $maybe_shortcode ? do_shortcode( $player_html ) : $player_html; ?>
    <?php if ( $maybe_shortcode ) wp_footer(); ?>
    <?php do_action( 'aiovg_player_iframe_footer' ); ?>

    <?php if ( $is_youtube ) : ?>
        <script src="https://www.youtube.com/iframe_api"></script>
        <script type="text/javascript">
            let player     = null;
            let isApiReady = false;

            function onYouTubeIframeAPIReady() {
                player = new YT.Player( 'aiovg-player-iframe', {
                    events: {
                        'onReady': function() {
                            isApiReady = true;
                        },
                        'onStateChange': ( event ) => {
                            if ( 1 == event.data ) { // Playing
                                // Pause other players
                                window.parent.postMessage({ 				
                                    message: 'aiovg-video-playing',
                                    context: 'iframe'
                                }, window.location.origin );
                            } 
                        }
                    }
                });            
            } 

            window.addEventListener( 'message', function( event ) {
                if ( ! player || ! isApiReady ) {
                    return false;
                }

                if ( event.origin !== window.location.origin ) {
                    return false;
                }

                if ( typeof event.data !== 'object' ) {
                    return false;
                }
                
                if ( ! event.data.hasOwnProperty( 'message' ) ) {
                    return false;
                }

                switch ( event.data.message ) {
                    case 'aiovg-video-play':
                        if ( player.playVideo ) {
                            player.playVideo();
                        }
                        break;

                    case 'aiovg-video-pause':
                        if ( player.pauseVideo ) {
                            player.pauseVideo();
                        }
                        break;

                    case 'aiovg-video-seek':
                        if ( event.data.hasOwnProperty( 'seconds' ) ) {
                            if ( player.seekTo ) {
                                player.seekTo( event.data.seconds, true );
                            }
                        }
                        break;
                }
            });          
        </script>
    <?php endif; ?>

    <?php if ( $is_vimeo ) : ?>
        <script src="https://player.vimeo.com/api/player.js"></script>
        <script type="text/javascript">
            let iframe     = document.querySelector( '#aiovg-player-iframe' );
            let player     = new Vimeo.Player( iframe );
            let isApiReady = false;

            player.ready().then(function() {
                isApiReady = true;
            });

            player.on( 'play', ( data ) => {
                // Pause other players
                window.parent.postMessage({ 				
                    message: 'aiovg-video-playing',
                    context: 'iframe'
                }, window.location.origin );
            });

            window.addEventListener( 'message', function( event ) {
                if ( ! player || ! isApiReady ) {
                    return false;
                }

                if ( event.origin !== window.location.origin ) {
                    return false;
                }

                if ( typeof event.data !== 'object' ) {
                    return false;
                }

                if ( ! event.data.hasOwnProperty( 'message' ) ) {
                    return false;
                }

                switch ( event.data.message ) {
                    case 'aiovg-video-play':
                        if ( player.play ) {
                            player.play();
                        }
                        break;

                    case 'aiovg-video-pause':
                        if ( player.pause ) {
                            player.pause();
                        }
                        break;

                    case 'aiovg-video-seek':
                        if ( event.data.hasOwnProperty( 'seconds' ) ) {
                            if ( player.setCurrentTime ) {
                                player.setCurrentTime( event.data.seconds );
                            }
                        }
                        break;
                }
            });          
        </script>
    <?php endif; ?>  
    
    <?php
    // Update views count.
    $update_views = isset( $general_settings['statistics'] ) ? (int) $general_settings['statistics'] : 1;
    if ( ! empty( $update_views ) && empty( $is_trailer ) && 'aiovg_videos' == $post_type ) {
        aiovg_update_views_count( $post_id );
    }
    ?>
</body>
</html>