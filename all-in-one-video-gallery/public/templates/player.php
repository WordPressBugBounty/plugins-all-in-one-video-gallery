<?php

/**
 * Video Player.
 *
 * @link     https://plugins360.com
 * @since    1.0.0
 *
 * @package All_In_One_Video_Gallery
 */
 
$player_settings  = get_option( 'aiovg_player_settings' );
$privacy_settings = get_option( 'aiovg_privacy_settings' );
$brand_settings   = get_option( 'aiovg_brand_settings', array() );

$post_id    = (int) get_query_var( 'aiovg_video', 0 );
$post_type  = 'page';
$post_title = '';
$post_url   = '';
$post_meta  = array();

$player_template = ( 'vidstack' == $player_settings['player'] ) ? 'vidstack' : 'videojs';

$thirdparty_providers_with_api    = array( 'youtube', 'vimeo' );
$thirdparty_providers_without_api = array( 'dailymotion', 'rumble', 'facebook' );
$thirdparty_providers_all         = array_merge( $thirdparty_providers_with_api, $thirdparty_providers_without_api );
$current_video_provider           = 'html5';

if ( $post_id > 0 ) {
	$post_type  = get_post_type( $post_id );
	$post_title = get_the_title( $post_id );
    $post_url   = get_permalink( $post_id );
		
	if ( 'aiovg_videos' == $post_type ) {
		$post_meta = get_post_meta( $post_id );		
	}
}

if ( ! empty( $post_meta ) ) {
	$current_video_provider = $post_meta['type'][0];

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
} else {
	foreach ( $thirdparty_providers_with_api as $provider ) {
		$use_native_controls = isset( $player_settings['use_native_controls'][ $provider ] );
		$use_native_controls = apply_filters( 'aiovg_use_native_controls', $use_native_controls, $provider );
	
		if ( $use_native_controls ) {
			if ( isset( $_GET[ $provider ] ) ) {
				$current_video_provider = $provider;
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

if ( ! isset( $_COOKIE['aiovg_gdpr_consent'] ) && ! empty( $privacy_settings['show_consent'] ) && ! empty( $privacy_settings['consent_message'] ) && ! empty( $privacy_settings['consent_button_label'] ) ) {		
	if ( in_array( $current_video_provider, $thirdparty_providers_all ) || 'iframe' == $player_template ) {
		$player_template = 'gdpr';
	}
}

include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . "public/templates/player-{$player_template}.php" );