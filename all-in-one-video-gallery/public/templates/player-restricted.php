<?php

/**
 * Video Player - Access Denied.
 *
 * @link     https://plugins360.com
 * @since    3.9.6
 *
 * @package All_In_One_Video_Gallery
 */

$restrictions_settings = aiovg_get_option( 'aiovg_restrictions_settings' );

$restricted_message = $restrictions_settings['restricted_message'];
if ( empty( $restricted_message ) ) {
    $restricted_message = __( 'Sorry, but you do not have permission to view this video.', 'all-in-one-video-gallery' );
}
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
            font-family: Verdana, Geneva, sans-serif;
			font-size: 14px;
        }

        #restrictions-wrapper { 
            display: flex;
            align-items: center; 
            justify-content: center;        
            margin: 0;
            background-color: #222;            
            padding: 0;
            width: 100%;
            height: 100%;
            text-align: center;
            line-height: 1.5;           
        }

        #restrictions-wrapper * {
            color: #eee;
        }

        #restrictions-message {
            width: 90%;
            max-width: 640px;
        }      
    </style>

    <?php if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) : ?>
        <style type="text/css">
		    <?php echo esc_html( $general_settings['custom_css'] ); ?>
        </style>
	<?php endif; ?>
</head>
<body>    
	<div id="restrictions-wrapper">
		<div id="restrictions-message"><?php echo wp_kses_post( trim( $restricted_message ) ); ?></div>
	</div>	
</body>
</html>
