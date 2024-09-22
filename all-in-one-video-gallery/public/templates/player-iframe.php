<?php

/**
 * Video Player: Third-Party.
 *
 * @link     https://plugins360.com
 * @since    1.6.0
 *
 * @package All_In_One_Video_Gallery
 */
 
$player_html = '';
$embed_url   = '';
$maybe_shortcode = false;

if ( ! empty( $post_meta ) ) {
    if ( in_array( $current_video_provider, $thirdparty_providers_all ) ) {
        $embed_url = $post_meta[ $current_video_provider ][0];
    }

    if ( 'embedcode' == $current_video_provider ) { 
        $embedcode = $post_meta['embedcode'][0];

		if ( $iframe_src = aiovg_extract_iframe_src( $embedcode ) ) { 
            $embed_url = $iframe_src; 
        } else {
            $player_html     = $embedcode;
            $maybe_shortcode = true;
        }
	}
} else {
    if ( in_array( $current_video_provider, $thirdparty_providers_all ) ) {
        if ( isset( $_GET[ $current_video_provider ] ) ) {
            $embed_url = urldecode( $_GET[ $current_video_provider ] );
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
        parse_str( $embed_url, $queries );
                    
        $embed_url = 'https://www.youtube.com/embed/' . aiovg_get_youtube_id_from_url( $embed_url ) . '?iv_load_policy=3&modestbranding=1&rel=0&showinfo=0';	                    
        
        if ( isset( $queries['start'] ) ) {
            $embed_url = add_query_arg( 'start', (int) $queries['start'], $embed_url );
        }

        if ( isset( $queries['t'] ) ) {
            $embed_url = add_query_arg( 'start', (int) $queries['t'], $embed_url );
        }
        
        if ( isset( $queries['end'] ) ) {
            $embed_url = add_query_arg( 'end', (int) $queries['end'], $embed_url );
        } 

        $autoplay = isset( $_GET[ 'autoplay' ] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $embed_url = add_query_arg( 'cc_load_policy', (int) $player_settings['cc_load_policy'], $embed_url );        

        if ( empty( $controls ) ) {
            $embed_url = add_query_arg( 'controls', 0, $embed_url );
        }        

        if ( ! in_array( 'fullscreen', $controls ) ) {
            $embed_url = add_query_arg( 'fs', 0, $embed_url );
        } 

        $loop = isset( $_GET[ 'loop' ] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET[ 'muted' ] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'mute', (int) $muted, $embed_url ); 

        $playsinline = ! empty( $player_settings['playsinline'] ) ? 1 : 0;
        $embed_url = add_query_arg( 'playsinline', $playsinline, $embed_url );                
    }

    // Vimeo
    if ( 'vimeo' == $current_video_provider ) {
        $oembed = aiovg_get_vimeo_oembed_data( $embed_url );

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

        $autoplay = isset( $_GET[ 'autoplay' ] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $loop = isset( $_GET[ 'loop' ] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET[ 'muted' ] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'muted', (int) $muted, $embed_url );

        $playsinline = ! empty( $player_settings['playsinline'] ) ? 1 : 0;
        $embed_url = add_query_arg( 'playsinline', $playsinline, $embed_url );
    }

    // Dailymotion
    if ( 'dailymotion' == $current_video_provider ) {
        $embed_url = 'https://www.dailymotion.com/embed/video/' . aiovg_get_dailymotion_id_from_url( $embed_url ) . '?queue-autoplay-next=0&queue-enable=0&sharing-enable=0&ui-logo=0&ui-start-screen-info=0';

        $autoplay = isset( $_GET[ 'autoplay' ] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $loop = isset( $_GET[ 'loop' ] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET[ 'muted' ] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'mute', (int) $muted, $embed_url );
    }

    // Rumble
    if ( 'rumble' == $current_video_provider ) {
        $oembed = aiovg_get_rumble_oembed_data( $embed_url );

        if ( ! empty( $oembed['html'] ) ) {
            if ( $iframe_src = aiovg_extract_iframe_src( $oembed['html'] ) ) { 
                $embed_url = add_query_arg( 'rel', 0, $iframe_src );	
                        
                $autoplay = isset( $_GET[ 'autoplay' ] ) ? (int) $_GET['autoplay'] : (int) $player_settings['autoplay'];
                if ( ! empty( $autoplay ) ) {
                    $embed_url = add_query_arg( 'autoplay', 2, $embed_url );	
                }
            }
        }
    }

    // Facebook
    if ( 'facebook' == $current_video_provider ) {
        $embed_url = 'https://www.facebook.com/plugins/video.php?href=' . urlencode( $embed_url ) . '&width=560&height=315&show_text=false&appId';
    
        $autoplay = isset( $_GET[ 'autoplay' ] ) ? $_GET['autoplay'] : $player_settings['autoplay'];
        $embed_url = add_query_arg( 'autoplay', (int) $autoplay, $embed_url );

        $loop = isset( $_GET[ 'loop' ] ) ? $_GET['loop'] : $player_settings['loop'];
        $embed_url = add_query_arg( 'loop', (int) $loop, $embed_url );

        $muted = isset( $_GET[ 'muted' ] ) ? $_GET['muted'] : $player_settings['muted'];
        $embed_url = add_query_arg( 'muted', (int) $muted, $embed_url );
    }

    // Build iframe code
    $player_html = sprintf(
        '<iframe src="%s" title="%s" width="560" height="315" frameborder="0" scrolling="no" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>',
        esc_url( $embed_url ),
        esc_attr( $post_title )
    );
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

    <?php if ( $maybe_shortcode ) wp_head(); ?>
    <?php do_action( 'aiovg_player_iframe_head' ); ?>
</head>
<body>    
    <?php echo $maybe_shortcode ? do_shortcode( $player_html ) : $player_html; ?>

    <?php if ( 'aiovg_videos' == $post_type ) : ?>
        <script type="text/javascript">
            /**
             * Update video views count.
             */
            function ajaxSubmit() {
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

                xmlhttp.open( 'GET', '<?php echo admin_url( 'admin-ajax.php' ); ?>?action=aiovg_update_views_count&post_id=<?php echo $post_id; ?>&security=<?php echo wp_create_nonce( 'aiovg_ajax_nonce' ); ?>', true );
                xmlhttp.send();							
            }

            ajaxSubmit();		
        </script>
    <?php endif; ?>

    <?php if ( $maybe_shortcode ) wp_footer(); ?>
    <?php do_action( 'aiovg_player_iframe_footer' ); ?>
</body>
</html>