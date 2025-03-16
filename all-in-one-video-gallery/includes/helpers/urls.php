<?php

/**
 * Helper functions for generating and managing custom URLs and permalinks.
 *
 * @link    https://plugins360.com
 * @since   4.0.1
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Get the category page URL.
 *
 * @since  1.0.0
 * @param  object $term The term object.
 * @return string       Category page URL.
 */
function aiovg_get_category_page_url( $term ) {
	$page_settings = get_option( 'aiovg_page_settings' );
	
	$url = '/';
	
	if ( $page_settings['category'] > 0 ) {
		$url = get_permalink( $page_settings['category'] );
	
		if ( '' != get_option( 'permalink_structure' ) ) {
    		$url = user_trailingslashit( trailingslashit( $url ) . $term->slug );
  		} else {
    		$url = add_query_arg( 'aiovg_category', $term->slug, $url );
  		}
	}
  
	return apply_filters( 'aiovg_category_page_url', $url, $term );
}

/**
 * Get player page URL.
 * 
 * @since  1.0.0
 * @param  int    $post_id Post ID.
 * @param  array  $atts    Player configuration data.
 * @return string $url     Player page URL.
 */
function aiovg_get_player_page_url( $post_id = 0, $atts = array() ) {
	$page_settings = get_option( 'aiovg_page_settings' );

	$url = '';
	
	if ( $page_settings['player'] > 0 ) {
		$url = get_permalink( $page_settings['player'] );
	
		$id = $post_id;
		
		if ( empty( $id ) ) {
			global $post;
						
			if ( isset( $post ) ) {
				$id = $post->ID;
			}
		}
		
		if ( ! empty( $id ) ) {
			if ( '' != get_option( 'permalink_structure' ) ) {
				$url = user_trailingslashit( trailingslashit( $url ) . 'id/' . $id );
			} else {
				$url = add_query_arg( array( 'aiovg_type' => 'id', 'aiovg_video' => $id ), $url );
			}
		}
	}
	
	$query_args = array();
	
	foreach ( $atts as $key => $value ) {
		if ( '' !== $value ) {
			switch ( $key ) {
				case 'uid':
					$query_args[ $key ] = sanitize_text_field( $atts[ $key ] );
					break;
				case 'mp4':
				case 'webm':
				case 'ogv':
				case 'hls':
				case 'dash':
				case 'youtube':
				case 'vimeo':
				case 'dailymotion':
				case 'rumble':
				case 'facebook':
				case 'poster':
					$query_args[ $key ] = aiovg_base64_encode( $atts[ $key ] );
					break;
				case 'ratio':
					$query_args[ $key ] = (float) $atts[ $key ];
					break;
				case 'autoplay':
				case 'autoadvance':
				case 'loop':
				case 'muted':
				case 'playpause':
				case 'current':
				case 'progress':
				case 'duration':
				case 'tracks':
				case 'chapters':					
				case 'speed':
				case 'quality':
				case 'volume':
				case 'pip':
				case 'fullscreen':
				case 'share':
				case 'embed':
				case 'download':
				case 'cc_load_policy':
					$query_args[ $key ] = (int) $atts[ $key ];
					break;
			}
		}
	}
	
	if ( ! empty( $query_args ) ) {
		$url = add_query_arg( $query_args, $url );
	}
	
	// Return
	return apply_filters( 'aiovg_player_page_url', $url, $post_id, $atts );
}

/**
 * Generate the search results page URL.
 *
 * @since  1.0.0
 * @param  int    $page_id Search page ID.
 * @return string          Search results page URL.
 */
function aiovg_get_search_page_url( $page_id = 0 ) {
	if ( empty( $page_id ) ) {
		$page_settings = get_option( 'aiovg_page_settings' );
		$page_id = $page_settings['search'];
	}	
	
	$url = '/';
	
	if ( $page_id > 0 ) {
		$url = get_permalink( $page_id );
	}
	
	return apply_filters( 'aiovg_search_page_url', $url );	
}

/**
 * Get the tag page URL.
 *
 * @since  2.4.3
 * @param  object $term The term object.
 * @return string       Tag page URL.
 */
function aiovg_get_tag_page_url( $term ) {
	$page_settings = get_option( 'aiovg_page_settings' );
	
	$url = '/';
	
	if ( $page_settings['tag'] > 0 ) {
		$url = get_permalink( $page_settings['tag'] );
	
		if ( '' != get_option( 'permalink_structure' ) ) {
    		$url = user_trailingslashit( trailingslashit( $url ) . $term->slug );
  		} else {
    		$url = add_query_arg( 'aiovg_tag', $term->slug, $url );
  		}
	}
  
	return apply_filters( 'aiovg_tag_page_url', $url, $term );
}

/**
 * Get the user videos page URL.
 *
 * @since  1.0.0
 * @param  int    $user_id User ID.
 * @return string          User videos page URL.
 */
function aiovg_get_user_videos_page_url( $user_id ) {
	$page_settings = get_option( 'aiovg_page_settings' );
	
	$url = '/';

	if ( $page_settings['user_videos'] > 0 ) {	
		$url = get_permalink( $page_settings['user_videos'] );	
		$user_slug = get_the_author_meta( 'user_nicename', $user_id );
		
		if ( '' != get_option( 'permalink_structure' ) ) {
    		$url = user_trailingslashit( trailingslashit( $url ) . $user_slug );
  		} else {
    		$url = add_query_arg( 'aiovg_user', $user_slug, $url );
  		}		
	}
  
	return apply_filters( 'aiovg_author_page_url', $url, $user_id );
}
