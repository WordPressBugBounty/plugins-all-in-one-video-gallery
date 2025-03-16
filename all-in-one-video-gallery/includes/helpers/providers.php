<?php

/**
 * Helper functions for third-party services such as YouTube, Vimeo, Dailymotion, Facebook, etc.
 *
 * @link    https://plugins360.com
 * @since   3.8.4
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Create a HH:MM:SS from a timestamp.
 * Given a number of seconds, the function returns a readable duration formatted as HH:MM:SS
 *
 * @since  3.8.4
 * @param  int    $seconds Number of seconds.
 * @return string          The formatted time.
 */
function aiovg_convert_seconds_to_human_time( $seconds ) {
	$seconds = absint( $seconds );
	
	if ( $seconds <= 0 ) {
		return '';
	}

	$h = floor( $seconds / 3600 );
	$m = floor( $seconds % 3600 / 60 );
	$s = floor( $seconds %3600 % 60 );

	return ( ( $h > 0 ? $h . ":" : "" ) . ( ( $m < 10 ? "0" : "" ) . $m . ":" ) . ( $s < 10 ? "0" : "" ) . $s );
}

/**
 * Extract iframe src from the given HTML string.
 *
 * @since  1.0.0
 * @param  string $html HTML string.
 * @return string $src  Iframe URL.
 */
function aiovg_extract_iframe_src( $html ) {
	$src = '';

	if ( ! empty( $html ) && strpos( $html, '<iframe' ) !== false ) {
		preg_match( '/src="([^"]+)"/', $html, $matches );
		if ( $matches ) {
			$src = $matches[1];
		}
	}

	return $src;
}

/**
 * Get Dailymotion ID from URL.
 *
 * @since  1.5.0
 * @param  string $url Dailymotion video URL.
 * @return string $id  Dailymotion video ID.
 */
function aiovg_get_dailymotion_id_from_url( $url ) {	
	$id = '';
	
	if ( preg_match( '!^.+dailymotion\.com/(video|hub)/([^_]+)[^#]*(#video=([^_&]+))?|(dai\.ly/([^_]+))!', $url, $m ) ) {
        if ( isset( $m[6] ) ) {
            $id = $m[6];
        }
		
        if ( isset( $m[4] ) ) {
            $id = $m[4];
        }
		
        $id = $m[2];
    }

	return $id;	
}

/**
 * Get Dailymotion image from URL.
 *
 * @since  1.5.0
 * @param  string $url Dailymotion video URL.
 * @return string      Dailymotion image URL.
 */
function aiovg_get_dailymotion_image_url( $url ) {	
	$data = aiovg_get_dailymotion_oembed_data( $url );		
	return $data['thumbnail_url'];
}

/**
 * Get Dailymotion data using oEmbed.
 *
 * @since  3.8.4
 * @param  string $url      Dailymotion URL.
 * @return string $response Dailymotion oEmbed response data.
 */
function aiovg_get_dailymotion_oembed_data( $url ) {
	$response = array(		
		'thumbnail_url' => '',
		'duration'      => ''
	);

	$cache_key   = 'aiovg_' . md5( $url );
	$cache_value = wp_cache_get( $cache_key );

	if ( is_array( $cache_value ) ) {
		$response = array_merge( $response, $cache_value );
		return $response;
	}	

	$id = aiovg_get_dailymotion_id_from_url( $url );

	if ( ! empty( $id ) ) {
		$api_response = wp_remote_get( 'https://api.dailymotion.com/video/' . $id . '?fields=thumbnail_large_url,thumbnail_medium_url,duration', array( 'sslverify' => false ) );

		if ( ! is_wp_error( $api_response ) ) {
			$api_response = json_decode( $api_response['body'] );

			if ( isset( $api_response->thumbnail_large_url ) ) {
				$response['thumbnail_url'] = $api_response->thumbnail_large_url;
			} else {
				$response['thumbnail_url'] = $api_response->thumbnail_medium_url;
			}

			if ( isset( $api_response->duration ) ) {
				$response['duration'] = $api_response->duration;
			}
		} else {
			// error_log( $api_response->get_error_message() );
		}		
	}

	if ( ! empty( $response['thumbnail_url'] ) ) {	
		wp_cache_set( $cache_key, $response, '', HOUR_IN_SECONDS );
	}
	
	return $response;
}

/**
 * Get Dailymotion video duration from URL.
 *
 * @since  3.8.4
 * @param  string $url Dailymotion video URL.
 * @return string      Video duration.
 */
function aiovg_get_dailymotion_video_duration( $url ) {	
	$data = aiovg_get_dailymotion_oembed_data( $url );		
	return $data['duration'];
}

/**
 * Get video duration from the Third-Party Player Code.
 *
 * @since  3.8.4
 * @param  string $embedcode Player Code.
 * @return string $duration  Video duration.
 */
function aiovg_get_embedcode_video_duration( $embedcode ) {
	$duration = '';

	$iframe_src = aiovg_extract_iframe_src( $embedcode );
	if ( $iframe_src ) {
		// Vimeo
		if ( false !== strpos( $iframe_src, 'vimeo.com' ) ) {
			$duration = aiovg_get_vimeo_video_duration( $iframe_src );
		}

		// Dailymotion
		elseif ( false !== strpos( $iframe_src, 'dailymotion.com' ) ) {
			$duration = aiovg_get_dailymotion_video_duration( $iframe_src );
		}

		// Rumble
		elseif ( false !== strpos( $iframe_src, 'rumble.com' ) ) {
			$duration = aiovg_get_rumble_video_duration( $iframe_src );
		}
	}
    	
	// Return
	return $duration;	
}

/**
 * Get image from the Third-Party Player Code.
 *
 * @since  1.0.0
 * @param  string $embedcode Player Code.
 * @return string $url       Image URL.
 */
function aiovg_get_embedcode_image_url( $embedcode ) {
	$url = '';

	$iframe_src = aiovg_extract_iframe_src( $embedcode );
	if ( $iframe_src ) {
		// YouTube
		if ( false !== strpos( $iframe_src, 'youtube.com' ) || false !== strpos( $iframe_src, 'youtu.be' ) ) {
			$url = aiovg_get_youtube_image_url( $iframe_src );
		}

		// Vimeo
		elseif ( false !== strpos( $iframe_src, 'vimeo.com' ) ) {
			$url = aiovg_get_vimeo_image_url( $iframe_src );
		}

		// Dailymotion
		elseif ( false !== strpos( $iframe_src, 'dailymotion.com' ) ) {
			$url = aiovg_get_dailymotion_image_url( $iframe_src );
		}

		// Rumble
		elseif ( false !== strpos( $iframe_src, 'rumble.com' ) ) {
			$url = aiovg_get_rumble_image_url( $iframe_src );
		}
	}
    	
	// Return image url
	return $url;	
}

/**
 * Get Rumble image from URL.
 *
 * @since  3.8.4
 * @param  string $url Rumble video URL.
 * @return string      Rumble image URL.
 */
function aiovg_get_rumble_image_url( $url ) {	
	$data = aiovg_get_rumble_oembed_data( $url );		
	return $data['thumbnail_url'];
}

/**
 * Get Rumble data using oEmbed.
 *
 * @since  2.6.3
 * @param  string $url      Rumble URL.
 * @return string $response Rumble oEmbed response data.
 */
function aiovg_get_rumble_oembed_data( $url ) {
	$response = array(		
		'thumbnail_url' => '',
		'duration'      => '',
		'html'          => ''
	);

	$cache_key   = 'aiovg_' . md5( $url );
	$cache_value = wp_cache_get( $cache_key );

	if ( is_array( $cache_value ) ) {
		$response = array_merge( $response, $cache_value );
		return $response;
	}	

	$api_response = wp_remote_get( 'https://rumble.com/api/Media/oembed.json?url=' . urlencode( $url ) );

	if ( is_array( $api_response ) && ! is_wp_error( $api_response ) ) {
		$api_response = json_decode( $api_response['body'] );
		
		if ( isset( $api_response->thumbnail_url ) ) {
			$response['thumbnail_url'] = $api_response->thumbnail_url;
		}

		if ( isset( $api_response->duration ) ) {
			$response['duration'] = $api_response->duration;
		}

		if ( isset( $api_response->html ) ) {
			$response['html'] = $api_response->html;
		}		
	}
	
	if ( ! empty( $response['thumbnail_url'] ) ) {
		wp_cache_set( $cache_key, $response, '', HOUR_IN_SECONDS );
	}
	
	return $response;
}

/**
 * Get Rumble video duration from URL.
 *
 * @since  3.8.4
 * @param  string $url Rumble video URL.
 * @return string      Video duration.
 */
function aiovg_get_rumble_video_duration( $url ) {	
	$data = aiovg_get_rumble_oembed_data( $url );		
	return $data['duration'];
}

/**
 * Get Vimeo ID from URL.
 *
 * @since  3.5.0
 * @param  string $url Vimeo video URL.
 * @return string $id  Vimeo video ID.
 */
function aiovg_get_vimeo_id_from_url( $url ) {
	$id = '';

	// Use regexp to programmatically parse the ID. So, we can avoid an oEmbed API request
	if ( strpos( $url, 'player.vimeo.com' ) !== false ) {
		if ( preg_match( '#(?:https?://)?(?:www.)?(?:player.)?vimeo.com/(?:[a-z]*/)*([0-9]{6,11})[?]?.*#', $url, $matches ) ) {
			$id = $matches[1];
		}
	}

	// Let us ask the Vimeo itself using their oEmbed API
	if ( empty( $id ) ) {
		$oembed = aiovg_get_vimeo_oembed_data( $url );
		$id = $oembed['video_id'];
	}

	return $id;
}

/**
 * Get Vimeo image from URL.
 *
 * @since  3.5.0
 * @param  string $url Vimeo video URL.
 * @return string      Vimeo image URL.
 */
function aiovg_get_vimeo_image_url( $url ) {
	$data = aiovg_get_vimeo_oembed_data( $url );	

	// Find large thumbnail using the Vimeo API v2
	if ( ! empty( $data['video_id'] ) ) {			
		$api_response = wp_remote_get( 'https://vimeo.com/api/v2/video/' . $data['video_id'] . '.php' );
		
		if ( ! is_wp_error( $api_response ) ) {
			$api_response = maybe_unserialize( $api_response['body'] );

			if ( is_array( $api_response ) && isset( $api_response[0]['thumbnail_large'] ) ) {
				$data['thumbnail_url'] = $api_response[0]['thumbnail_large'];
			}
		}
	}

	// Get images from private videos
	if ( ! empty( $data['video_id'] ) && empty( $data['thumbnail_url'] ) ) {
		$api_settings = get_option( 'aiovg_api_settings' );	

		if ( isset( $api_settings['vimeo_access_token'] ) && ! empty( $api_settings['vimeo_access_token'] ) ) {
			$args = array(
				'headers' => array(
					'Authorization' => 'Bearer ' . sanitize_text_field( $api_settings['vimeo_access_token'] )
				)
			);

			$api_response = wp_remote_get( 'https://api.vimeo.com/videos/' . $data['video_id'] . '/pictures', $args );
			
			if ( is_array( $api_response ) && ! is_wp_error( $api_response ) ) {
				$api_response = json_decode( $api_response['body'] );					

				if ( isset( $api_response->data ) ) {
					$bypass = false;
		
					foreach ( $api_response->data as $item ) {
						foreach ( $item->sizes as $picture ) {
							$data['thumbnail_url'] = $picture->link;
	
							if ( $picture->width >= 400 ) {
								$bypass = true;
								break;
							}
						}
	
						if ( $bypass ) break;
					}
				}
			}
		}
	}

	if ( ! empty( $data['thumbnail_url'] ) ) {
		$data['thumbnail_url'] = add_query_arg( 'isnew', 1, $data['thumbnail_url'] );
	}

	return $data['thumbnail_url'];
}

/**
 * Get Vimeo data using oEmbed.
 *
 * @since  1.6.6
 * @param  string $url      Vimeo URL.
 * @return string $response Vimeo oEmbed response data.
 */
function aiovg_get_vimeo_oembed_data( $url ) {
	$response = array(		
		'video_id'      => '',
		'thumbnail_url' => '',
		'duration'      => '',
		'html'          => ''
	);

	$cache_key   = 'aiovg_' . md5( $url );
	$cache_value = wp_cache_get( $cache_key );

	if ( is_array( $cache_value ) ) {
		$response = array_merge( $response, $cache_value );
		return $response;
	}

	$api_response = wp_remote_get( 'https://vimeo.com/api/oembed.json?url=' . urlencode( $url ) );

	if ( is_array( $api_response ) && ! is_wp_error( $api_response ) ) {
		$api_response = json_decode( $api_response['body'] );

		if ( isset( $api_response->video_id ) ) {
			$response['video_id'] = $api_response->video_id;
		}	
		
		if ( isset( $api_response->thumbnail_url ) ) {
			$response['thumbnail_url'] = $api_response->thumbnail_url;
		}

		if ( isset( $api_response->duration ) ) {
			$response['duration'] = $api_response->duration;
		}

		if ( isset( $api_response->html ) ) {
			$response['html'] = $api_response->html;
		}
	}

	// Fallback to our old method to get the Vimeo ID
	if ( empty( $response['video_id'] ) ) {			
		$is_vimeo = preg_match( '/vimeo\.com/i', $url );  
		if ( $is_vimeo ) {
			$response['video_id'] = preg_replace( '/[^\/]+[^0-9]|(\/)/', '', rtrim( $url, '/' ) );
		}
	}

	if ( ! empty( $response['video_id'] ) ) {	
		wp_cache_set( $cache_key, $response, '', HOUR_IN_SECONDS );
	}
	
	return $response;
}

/**
 * Get Vimeo video duration from URL.
 *
 * @since  3.8.4
 * @param  string $url Vimeo video URL.
 * @return string      Video duration.
 */
function aiovg_get_vimeo_video_duration( $url ) {	
	$data = aiovg_get_vimeo_oembed_data( $url );		
	return $data['duration'];
}

/**
 * Get YouTube ID from URL.
 *
 * @since  1.0.0
 * @param  string $url YouTube video URL.
 * @return string $id  YouTube video ID.
 */
function aiovg_get_youtube_id_from_url( $url ) {	
	$id  = '';
    $url = parse_url( $url );
		
    if ( 0 === strcasecmp( $url['host'], 'youtu.be' ) ) {
       	$id = substr( $url['path'], 1 );
    } elseif ( 0 === strcasecmp( $url['host'], 'www.youtube.com' ) || 0 === strcasecmp( $url['host'], 'youtube.com' ) ) {
       	if ( isset( $url['query'] ) ) {
       		parse_str( $url['query'], $url['query'] );
           	if ( isset( $url['query']['v'] ) ) {
           		$id = $url['query']['v'];
           	}
       	}
			
       	if ( empty( $id ) ) {
           	$url['path'] = explode( '/', substr( $url['path'], 1 ) );
           	if ( in_array( $url['path'][0], array( 'e', 'embed', 'v', 'shorts', 'live' ) ) ) {
               	$id = $url['path'][1];
           	}
       	}
    }
    	
	return $id;	
}

/**
 * Get YouTube image from URL.
 *
 * @since  1.0.0
 * @param  string $url YouTube video URL.
 * @return string $url YouTube image URL.
 */
function aiovg_get_youtube_image_url( $url ) {	
	$id  = aiovg_get_youtube_id_from_url( $url );
	$url = '';

	if ( ! empty( $id ) ) {
		$url = "https://img.youtube.com/vi/$id/maxresdefault.jpg";
		$response = wp_remote_get( $url );

		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$url = "https://img.youtube.com/vi/$id/mqdefault.jpg"; 
		}
	}
	   	
	return $url;	
}

/**
 * Resolve YouTube URLs.
 * 
 * @since  2.5.6
 * @param  string $url YouTube URL.
 * @return string $url Resolved YouTube URL.
 */
function aiovg_resolve_youtube_url( $url ) {
	if ( false !== strpos( $url, '/shorts/' ) || false !== strpos( $url, '/live/' ) ) {
		$id = aiovg_get_youtube_id_from_url( $url );

		if ( ! empty( $id ) ) {
			$url = 'https://www.youtube.com/watch?v=' . $id; 
		}
	}

	return $url;
}
