<?php

/**
 * Video Player - Email Capture.
 *
 * @link     https://plugins360.com
 * @since    1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

$video_id  = 0;
$video_url = '';
$image_url = '';

if ( ! empty( $_GET['poster'] ) ) {
    $image_url = aiovg_base64_decode( $_GET['poster'] );
    $image_url = aiovg_make_url_absolute( $image_url );
}

if ( 'aiovg_videos' == $post_type && ! empty( $post_id ) ) {
    $video_id = $post_id;

    if ( empty( $image_url ) ) {
        $image_data = aiovg_get_image( $video_id, 'large' );

        if ( $image_url = $image_data['src'] ) {
            $image_url = aiovg_make_url_absolute( $image_url );
        }
    }
} else {
    foreach ( array( 'mp4', 'webm', 'ogv', 'hls', 'dash', 'youtube', 'vimeo', 'dailymotion', 'rumble', 'facebook' ) as $provider ) {
		if ( ! isset( $_GET[ $provider ] ) ) {
            continue;
        }

        $video_url = aiovg_base64_decode( $_GET[ $provider ] );

        if ( ! empty( $image_url ) ) {
            continue;
        }

        switch ( $provider ) {
            case 'youtube':
                $image_url = aiovg_get_youtube_image_url( $video_url );	
                break;

            case 'vimeo':
                $image_url = aiovg_get_vimeo_image_url( $video_url );
                break;

            case 'dailymotion':
                $image_url = aiovg_get_dailymotion_image_url( $video_url );
                break;

            case 'rumble':
                $image_url = aiovg_get_rumble_image_url( $video_url );
                break;
        }
    }
}

$header_text   = ! empty( $email_capture_settings['header_text'] ) ? $email_capture_settings['header_text'] : __( 'Enter your email to continue watching', 'all-in-one-video-gallery' );
$footer_text   = ! empty( $email_capture_settings['footer_text'] ) ? $email_capture_settings['footer_text'] : '';
$button_label  = ! empty( $email_capture_settings['button_label'] ) ? $email_capture_settings['button_label'] : __( 'Submit', 'all-in-one-video-gallery' );
$skip_text     = ! empty( $email_capture_settings['skip_text'] ) ? $email_capture_settings['skip_text'] : __( 'Skip', 'all-in-one-video-gallery' );
			
$settings = array(
    'collect_name'      => ! empty( $email_capture_settings['collect_name'] ) ? 1 : 0,
    'collect_phone'     => ! empty( $email_capture_settings['collect_phone'] ) ? 1 : 0,
    'allow_skip'        => ! empty( $email_capture_settings['allow_skip'] ) ? 1 : 0,
    'header_text'       => apply_filters( 'aiovg_translate_strings', $header_text, 'header_text' ),
    'footer_text'       => apply_filters( 'aiovg_translate_strings', $footer_text, 'footer_text' ),
    'button_label'      => apply_filters( 'aiovg_translate_strings', $button_label, 'button_label' ),
    'skip_text'         => apply_filters( 'aiovg_translate_strings', $skip_text, 'skip_text' ),
    'video_id'          => (int) $video_id,
    'video_url'         => ! empty( $video_url ) ? esc_url_raw( $video_url ) : '',
    // Resolved here because player.php picks this template before the providers above
    // have been read. Same helper, so the rule is still defined once. Empty in the
    // site-wide modes, which is how the listener knows any capture clears this page.
    'capture_key'       => aiovg_get_email_capture_markup_key( $video_id, $post_type, $video_url )
);
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

        #aiovg-email-capture {
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

        #aiovg-email-capture-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            background-color: rgba( 0, 0, 0, 0.85 );
            overflow-y: auto;
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
            color: #fff;
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
    </style>

    <?php if ( ! empty( $general_settings['custom_css'] ) ) : ?>
        <style type="text/css">
		    <?php echo esc_html( $general_settings['custom_css'] ); ?>
        </style>
	<?php endif; ?>
</head>
<body>
	<div id="aiovg-email-capture" data-capture_key="<?php echo esc_attr( $settings['capture_key'] ); ?>"<?php if ( ! empty( $image_url ) ) : ?> style="background-image: url('<?php echo esc_url( $image_url ); ?>')"<?php endif; ?>>
		<div id="aiovg-email-capture-overlay">
			<div id="aiovg-email-capture-block">
				<div id="aiovg-email-capture-header"><?php echo esc_html( $settings['header_text'] ); ?></div>

				<?php $email_capture_prefill = aiovg_get_email_capture_prefill(); ?>

				<div id="aiovg-email-capture-fields">
					<?php if ( ! empty( $settings['collect_name'] ) ) : ?>
						<input type="text" id="aiovg-email-capture-name" name="name" value="<?php echo esc_attr( $email_capture_prefill['name'] ); ?>" placeholder="<?php esc_attr_e( 'Your name', 'all-in-one-video-gallery' ); ?>" autocomplete="name" />
					<?php endif; ?>

					<input type="email" id="aiovg-email-capture-email" name="email" value="<?php echo esc_attr( $email_capture_prefill['email'] ); ?>" placeholder="<?php esc_attr_e( 'Your email address *', 'all-in-one-video-gallery' ); ?>" required autocomplete="email" aria-describedby="aiovg-email-capture-error" />
					
                    <?php if ( ! empty( $settings['collect_phone'] ) ) : ?>
						<input type="tel" id="aiovg-email-capture-phone" name="phone" value="<?php echo esc_attr( $email_capture_prefill['phone'] ); ?>" placeholder="<?php esc_attr_e( 'Your phone number', 'all-in-one-video-gallery' ); ?>" autocomplete="tel" />
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $settings['footer_text'] ) ) : ?>
					<div id="aiovg-email-capture-footer"><?php echo wp_kses_post( $settings['footer_text'] ); ?></div>
				<?php endif; ?>

                <div id="aiovg-email-capture-error" role="alert" aria-live="assertive" tabindex="-1" data-invalid_email="<?php esc_attr_e( 'Please enter a valid email address.', 'all-in-one-video-gallery' ); ?>" data-error_generic="<?php esc_attr_e( 'Something went wrong. Please try again.', 'all-in-one-video-gallery' ); ?>"></div>

				<div id="aiovg-email-capture-actions">
					<button type="button" id="aiovg-email-capture-submit"><?php echo esc_html( $settings['button_label'] ); ?></button>

					<?php if ( ! empty( $settings['allow_skip'] ) ) : ?>
						<a id="aiovg-email-capture-skip" role="button" tabindex="0"><?php echo esc_html( $settings['skip_text'] ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		document.addEventListener( 'DOMContentLoaded', function() {
			'use strict';

			/**
			 * Vars.
			 */
			const settings       = <?php echo wp_json_encode( $settings ); ?>;
			const emailCaptureEl = document.getElementById( 'aiovg-email-capture' );
            const errorSpan      = document.getElementById( 'aiovg-email-capture-error' );

			/**
             * Reload the page with query parameters to bypass cookies and prevent showing the form again.
             *
             * @param {number} autoplay 1 to enable autoplay after reload, 0 to keep it unchanged.
             */
			function reloadPlayer( autoplay ) {
				const url          = new URL( window.location.href );
				const searchParams = url.searchParams;

				searchParams.set( 'autoplay', autoplay );
				searchParams.set( 'nocookie', 1 );
				searchParams.set( 'nocapture', 1 );

				url.search           = searchParams.toString();
				window.location.href = url.toString();
			}

			/**
			 * Which video this is. The skip path sends it too: without it the server
			 * cannot derive a capture key, and "Once per video" would never remember
			 * the skip, asking again on every play.
			 *
			 * @return {string} URL-encoded video identity parameters.
			 */
			function videoParams() {
				let params = '&video_id=' + encodeURIComponent( settings.video_id );

				if ( settings.video_url ) {
					params += '&video_url=' + encodeURIComponent( settings.video_url );
				}

				return params;
			}

			/**
			 * Send AJAX and reload on success.
			 *
			 * @param {string} data URL-encoded POST body, e.g. "email=foo%40bar.com&name=Foo".
			 */
			function ajaxSubmit( data ) {
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
							// A skip the server did not remember clears only the consent gates. An empty
							// key means "once per visitor", where one capture clears every player.
							const remembered = ! ( response.data && false === response.data.remembered );

							window.parent.postMessage( remembered ? {
								message: 'aiovg-email-captured',
								context: 'iframe',
								capture_key: <?php echo wp_json_encode( $settings['capture_key'] ); ?>
							} : {
								message: 'aiovg-cookie-consent',
								context: 'iframe'
							}, window.location.origin );

							// Reload document
							reloadPlayer(1);
						} else {
							// Show error message
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

			/**
			 * Submit button handler.
			 */
			document.getElementById( 'aiovg-email-capture-submit' ).addEventListener( 'click', function() {
				const emailField = document.getElementById( 'aiovg-email-capture-email' );

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

				if ( settings.collect_name ) {
					const nameField = document.getElementById( 'aiovg-email-capture-name' );
					if ( nameField && nameField.value.trim() ) {
						params += '&name=' + encodeURIComponent( nameField.value.trim() );
					}
				}

				if ( settings.collect_phone ) {
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

				ajaxSubmit( params );
			} );

			/**
			 * Skip link handler.
			 */
			if ( settings.allow_skip ) {
				const skipLink = document.getElementById( 'aiovg-email-capture-skip' );

				if ( skipLink ) {
					skipLink.addEventListener( 'click', function() {
						ajaxSubmit( 'skipped=1' + videoParams() );
					} );

					skipLink.addEventListener( 'keydown', function( event ) {
						if ( 13 === event.keyCode || 32 === event.keyCode ) {
							event.preventDefault();
							ajaxSubmit( 'skipped=1' + videoParams() );
						}
					} );
				}
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
						// where any capture clears every player. Otherwise only reload when
						// the capture was for this same video, shown twice on one page.
						if ( ! event.data.capture_key || event.data.capture_key === <?php echo wp_json_encode( $settings['capture_key'] ); ?> ) {
							reloadPlayer(0);
						}
						break;
				}
			});

            // Focus the first input for better accessibility
            const firstInput = document.querySelector( '#aiovg-email-capture input' );
            if ( firstInput && ! firstInput.value ) {
                firstInput.focus();
            }
		} );
	</script>
</body>
</html>
