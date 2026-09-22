<?php

/**
 * Video Player - Access Denied.
 *
 * @link     https://plugins360.com
 * @since    3.9.6
 *
 * @package All_In_One_Video_Gallery
 */

$restricted_message = aiovg_get_restricted_message( $post_id, 'iframe' );

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
		$src   = aiovg_base64_decode( $_GET['youtube'] );
		$image = aiovg_get_youtube_image_url( $src );
	}

	// Vimeo
	if ( isset( $_GET['vimeo'] ) ) {
		$src   = aiovg_base64_decode( $_GET['vimeo'] );
		$image = aiovg_get_vimeo_image_url( $src );
	}

	// Dailymotion
	if ( isset( $_GET['dailymotion'] ) ) {
		$src   = aiovg_base64_decode( $_GET['dailymotion'] );
		$image = aiovg_get_dailymotion_image_url( $src );
	}

	// Rumble
	if ( isset( $_GET['rumble'] ) ) {
		$src   = aiovg_base64_decode( $_GET['rumble'] );
		$image = aiovg_get_rumble_image_url( $src );
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<base target="_top">

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

		#aiovg-restricted-wrapper {
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

		#aiovg-restricted-overlay {
			position: absolute;
			inset: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			background-color: rgba( 0, 0, 0, 0.85 );
			overflow-y: auto;
		}

		#aiovg-restricted-message {
			box-sizing: border-box;
			margin: auto;
			padding: 16px 24px;
			width: 100%;
			max-width: 480px;
			text-align: center;
			color: #fff;
		}

		/* Paid Memberships Pro message, whose stylesheet is not loaded here */
		#aiovg-restricted-message .pmpro_card_title {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			margin: 0 0 8px;
			font-size: 18px;
			font-weight: 600;
			line-height: 1.3;
			color: #fff;
		}

		#aiovg-restricted-message .pmpro_card_title svg {
			flex-shrink: 0;
			width: 20px;
			height: 20px;
			stroke: currentColor;
		}

		#aiovg-restricted-message .pmpro_card_content p {
			margin: 0 0 12px;
		}

		#aiovg-restricted-message .pmpro_card_actions {
			font-size: 13px;
		}

		#aiovg-restricted-message .pmpro a {
			color: #fff;
			text-decoration: underline;
		}

		/* Same button as the cookie consent and email capture overlays */
		#aiovg-restricted-message .pmpro a.pmpro_btn,
		#aiovg-restricted-message .pmpro a.pmpro_btn:hover,
		#aiovg-restricted-message .pmpro a.pmpro_btn:focus {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border: 0;
			border-radius: 3px;
			box-shadow: none;
			background: none;
			background-color: <?php echo esc_attr( $primary_color ); ?>;
			background-image: none;
			padding: 0 20px;
			min-height: 44px;
			line-height: 1;
			color: #fff;
			font-family: inherit;
			font-size: 1em;
			font-weight: 600;
			text-decoration: none;
		}

		#aiovg-restricted-message .pmpro a.pmpro_btn:hover {
			opacity: 0.85;
		}
	</style>

	<?php if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) : ?>
		<style type="text/css">
			<?php echo esc_html( $general_settings['custom_css'] ); ?>
		</style>
	<?php endif; ?>
</head>
<body>
	<div id="aiovg-restricted-wrapper"<?php if ( ! empty( $image ) ) : ?> style="background-image: url('<?php echo esc_url( $image ); ?>')"<?php endif; ?>>
		<div id="aiovg-restricted-overlay">
			<div id="aiovg-restricted-message"><?php echo $restricted_message; ?></div>
		</div>
	</div>
</body>
</html>
