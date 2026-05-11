<?php

/**
 * Video Player - GDPR Consent.
 *
 * @link     https://plugins360.com
 * @since    1.6.0
 *
 * @package All_In_One_Video_Gallery
 */

$image = '';

if ( isset( $_GET['poster'] ) ) {
	$image = aiovg_base64_decode( $_GET['poster'] );
} elseif ( ! empty( $post_meta ) ) {
    $image_data = aiovg_get_image( $post_id, 'large' );
	$image = $image_data['src'];
}

if ( ! empty( $image ) ) {
    $image = aiovg_make_url_absolute( $image );
} else {
    // YouTube
    if ( isset( $_GET['youtube'] ) ) {
        $src = aiovg_base64_decode( $_GET['youtube'] );
        $image = aiovg_get_youtube_image_url( $src );	
    }

    // Vimeo
    if ( isset( $_GET['vimeo'] ) ) {
        $src = aiovg_base64_decode( $_GET['vimeo'] );
        $image = aiovg_get_vimeo_image_url( $src );
    }

    // Dailymotion
    if ( isset( $_GET['dailymotion'] ) ) {
        $src = aiovg_base64_decode( $_GET['dailymotion'] );
        $image = aiovg_get_dailymotion_image_url( $src );
    }

    // Rumble
    if ( isset( $_GET['rumble'] ) ) {
        $src = aiovg_base64_decode( $_GET['rumble'] );
        $image = aiovg_get_rumble_image_url( $src );
    }
}

$consent_message      = apply_filters( 'aiovg_translate_strings', $privacy_settings['consent_message'], 'consent_message' );
$consent_button_label = apply_filters( 'aiovg_translate_strings', $privacy_settings['consent_button_label'], 'consent_button_label' );
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
        
    <?php if ( $post_id > 0 ) : ?>    
        <title><?php echo wp_kses_post( get_the_title( $post_id ) ); ?></title>    
        <link rel="canonical" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" />
    <?php endif; ?>

	<style type="text/css">
        html,
        body {
            margin: 0 !important;
			padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden;
            line-height: 1.5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			font-size: 14px;
        }

        #aiovg-privacy-wrapper {
            position: relative;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #222;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        #aiovg-privacy-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background-color: rgba( 0, 0, 0, 0.85 );
            overflow-y: auto;
        }

        #aiovg-privacy-consent-block {
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: auto;
            padding: 16px 24px;
            width: 100%;
            max-width: 480px;
            text-align: center;
            color: #fff;
        }

        #aiovg-privacy-consent-button,
        #aiovg-privacy-consent-button:hover,
        #aiovg-privacy-consent-button:focus {
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

        #aiovg-privacy-consent-button:hover {
            opacity: 0.85;
        }

        @keyframes aiovg-spin {
            to { transform: rotate( 360deg ); }
        }

        .aiovg-is-loading #aiovg-privacy-consent-block * {
            display: none !important;
        }

        .aiovg-is-loading #aiovg-privacy-consent-block {
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
    </style>

    <?php if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) : ?>
        <style type="text/css">
		    <?php echo esc_html( $general_settings['custom_css'] ); ?>
        </style>
	<?php endif; ?>
</head>
<body>    
	<div id="aiovg-privacy-wrapper"<?php if ( ! empty( $image ) ) : ?> style="background-image: url('<?php echo esc_url( $image ); ?>')"<?php endif; ?>>
		<div id="aiovg-privacy-overlay">
			<div id="aiovg-privacy-consent-block">
				<div id="aiovg-privacy-consent-message"><?php echo wp_kses_post( trim( $consent_message ) ); ?></div>
				<div>
					<button type="button" id="aiovg-privacy-consent-button"><?php echo esc_html( $consent_button_label ); ?></button>
				</div>
			</div>
		</div>
	</div>
				
	<script type="text/javascript">
        /**
         * Reload the page with query parameters to bypass cookies and prevent showing the consent message again.
         *
         * @param {number} autoplay 1 to enable autoplay after reload, 0 to keep it unchanged.
         */
        function reloadPlayer( autoplay ) {
            const url          = new URL( window.location.href );
            const searchParams = url.searchParams;

            searchParams.set( 'autoplay', autoplay );
            searchParams.set( 'nocookie', 1 );

            url.search           = searchParams.toString();
            window.location.href = url.toString();
        }
        
		/**
		 * Set cookie for accepting the privacy consent.
		 */
		function ajaxSubmit() {
            document.getElementById( 'aiovg-privacy-wrapper' ).classList.add( 'aiovg-is-loading' );

			var xmlhttp;

			if ( window.XMLHttpRequest ) {
				xmlhttp = new XMLHttpRequest();
			} else {
				xmlhttp = new ActiveXObject( 'Microsoft.XMLHTTP' );
			}
			
			xmlhttp.onreadystatechange = function() {				
				if ( xmlhttp.readyState == 4 && xmlhttp.status == 200 && xmlhttp.responseText ) {					
                    // Remove cookieconsent from other players 
                    window.parent.postMessage({                       
                        message: 'aiovg-cookie-consent',
                        context: 'iframe'
                    }, window.location.origin );

                    // Reload document
                    reloadPlayer(1);
				}					
			}

			xmlhttp.open( 'POST', '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>?action=aiovg_set_cookie&security=<?php echo esc_js( wp_create_nonce( 'aiovg_public_ajax_nonce' ) ); ?>', true );
			xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
			xmlhttp.send( 'action=aiovg_set_cookie' );							
		}
		
		document.getElementById( 'aiovg-privacy-consent-button' ).addEventListener( 'click', ajaxSubmit );

        // Api methods
        window.addEventListener( 'message', function( event ) {
            if ( event.origin !== window.location.origin ) {
                return false;
            }

            if ( ! event.data.hasOwnProperty( 'message' ) ) {
                return false;
            }

            switch ( event.data.message ) {
                case 'aiovg-cookie-consent':
                    reloadPlayer(0);
                    break;

                case 'aiovg-email-captured':
                    reloadPlayer(0);
                    break;
            }
        });
	</script>
</body>
</html>
