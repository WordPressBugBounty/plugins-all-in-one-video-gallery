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
 * Get the embed URL for a Bunny Stream video.
 *
 * @since  4.2.0 
 * @param  string       $url      The original Bunny Stream HLS video URL.
 * @param  int          $video_id Bunny Stream Video ID.
 * @return string|false           The signed Bunny Stream embed URL or false if not applicable.
 */
function aiovg_get_bunny_stream_embed_url( $url, $video_id ) {
	$settings = (array) get_option( 'aiovg_bunny_stream_settings' );

	if ( empty( $settings['library_id'] ) ) {
		return false;
	}

	// Sanitize video ID and retrieve library ID from settings
	$video_id   = sanitize_text_field( $video_id );
	$library_id = intval( $settings['library_id'] );

	// Construct the base embed URL
	$embed_url = sprintf(
		'https://iframe.mediadelivery.net/embed/%d/%s',
		$library_id,
		$video_id
	);

	// If token authentication is enabled, generate a signed token
	if ( ! empty( $settings['enable_token_authentication'] ) &&	! empty( $settings['token_authentication_key'] ) ) {
		// Generate the token using SHA256 hash of key + video_id + expiry
		$security_key    = sanitize_text_field( $settings['token_authentication_key'] );
		$expiration_time = ! empty( $settings['token_expiry'] ) ? absint( $settings['token_expiry'] ) : 3600;
		$expires         = time() + $expiration_time;
		$token           = hash( 'sha256', $security_key . $video_id . $expires );

		// Append token and expiry to the embed URL
		$embed_url = add_query_arg( 'token', $token, $embed_url );
		$embed_url = add_query_arg( 'expires', $expires, $embed_url );
	}

	// Allow developers to modify the embed URL via filter
	return apply_filters( 'aiovg_bunny_stream_embed_url', $embed_url, $url, $video_id );
}

/**
 * Get the signed file URL for a Bunny Stream video.
 *
 * @since  4.2.0
 * @param  string $url      The original Bunny Stream video / image URL.
 * @param  int    $video_id Bunny Stream video ID.
 * @return string           Signed Bunny Stream URL with token and restrictions appended.
 */
function aiovg_get_bunny_stream_signed_url( $url, $video_id ) {
	$settings = (array) get_option( 'aiovg_bunny_stream_settings' );

	if ( empty( $settings['enable_token_authentication'] ) || empty( $settings['token_authentication_key'] ) ) {
		return $url;
	}

	// Get the security key and expiry settings
	$security_key       = sanitize_text_field( $settings['token_authentication_key'] );
	$expiration_time    = ! empty( $settings['token_expiry'] ) ? absint( $settings['token_expiry'] ) : 3600;
	$expires            = time() + $expiration_time;
	$is_directory_token = true; // Indicates we're signing by directory path, not full file path
	$path_allowed       = '/' . sanitize_text_field( $video_id ) . '/';

	// Optional restrictions (not used currently, but placeholder for future config)
	$user_ip            = '';
	$countries_allowed  = '';
	$countries_blocked  = '';
	$referers_allowed   = '';

	// Append optional query parameters for geo and referrer restrictions
	if ( ! empty( $countries_allowed ) ) {
		$url = add_query_arg( 'token_countries', $countries_allowed, $url );
	}

	if ( ! empty( $countries_blocked ) ) {
		$url = add_query_arg( 'token_countries_blocked', $countries_blocked, $url );
	}

	if ( ! empty( $referers_allowed ) ) {
		$url = add_query_arg( 'token_referer', $referers_allowed, $url );		
	}

	// Parse the URL components
	$parsed = parse_url( $url );
	if ( ! is_array( $parsed ) ) {
		return $url;
	}

	$url_scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] : 'https';
	$url_host   = isset( $parsed['host'] ) ? $parsed['host'] : '';
	$url_path   = isset( $parsed['path'] ) ? $parsed['path'] : '';
	$url_query  = isset( $parsed['query'] ) ? $parsed['query'] : '';

	// Collect all query parameters for the signature
	$parameters = array();
	if ( ! empty( $url_query ) ) {
		parse_str( $url_query, $parameters );
	}

	// Adjust the token path if directory-based tokenization is used
	$signature_path = $url_path;
	if ( ! empty( $path_allowed ) ) {
		$signature_path = $path_allowed;
		$parameters['token_path'] = $signature_path;
	}

	// Sort parameters alphabetically for consistent hashing
	ksort( $parameters );

	// Build parameter strings for hashing and for final URL
	$parameter_data     = '';
	$parameter_data_url = '';

	foreach ( $parameters as $key => $value ) {
		if ( $parameter_data !== '' ) {
			$parameter_data .= '&';
		}
		$parameter_data_url .= '&';

		$parameter_data     .= $key . '=' . $value;
		$parameter_data_url .= $key . '=' . urlencode( $value );
	}

	// Create the base string for token generation
	$hashable_base = $security_key . $signature_path . $expires;
	
	if ( ! empty( $user_ip ) ) {
		$hashable_base .= $user_ip;
	}
	
	$hashable_base .= $parameter_data;

	// Generate the token using SHA-256 and encode it
	$token = hash( 'sha256', $hashable_base, true );
	$token = base64_encode( $token );
	$token = strtr( $token, '+/', '-_' );
	$token = str_replace( '=', '', $token );

	// Build the final signed URL based on token type
	if ( $is_directory_token ) {
		$signed_url = "{$url_scheme}://{$url_host}/bcdn_token={$token}&expires={$expires}{$parameter_data_url}{$url_path}";
	} else {
		$signed_url = "{$url_scheme}://{$url_host}{$url_path}?token={$token}{$parameter_data_url}&expires={$expires}";
	}

	// Allow customization via filter and return final signed URL
	return apply_filters( 'aiovg_bunny_stream_signed_url', $signed_url, $url, $video_id );
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
 * Check if Bunny Stream hosting is enabled and return settings.
 *
 * @since  4.2.0
 * @return array|false Returns the Bunny Stream settings array if all conditions are met; otherwise, false.
 */
function aiovg_has_bunny_stream_enabled() {
	$settings = (array) get_option( 'aiovg_bunny_stream_settings' );

	// Basic Bunny Stream setup check
	if (
		empty( $settings['enable_bunny_stream'] ) ||
		empty( $settings['api_key'] ) ||
		empty( $settings['library_id'] ) ||
		empty( $settings['cdn_hostname'] )
	) {
		return false;
	}

	return $settings;
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
