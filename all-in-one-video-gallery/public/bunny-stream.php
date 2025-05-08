<?php

/**
 * Bunny Stream.
 *
 * @link    https://plugins360.com
 * @since   4.2.0
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Public_Bunny_Stream class.
 *
 * @since 4.2.0
 */
class AIOVG_Public_Bunny_Stream {	

	/**
	 * Save post.
	 *
	 * @since  4.2.0
	 * @param  int     $post_id Post ID.
	 * @param  WP_Post $post    The post object.
	 */
	public function save_post( $post_id, $post ) {	
		if ( ! isset( $_POST['post_type'] ) ) {
        	return $post_id;
    	}
	
		// Check this is the "aiovg_videos" custom post type
    	if ( 'aiovg_videos' != $post->post_type ) {
        	return $post_id;
    	}
		
		// If this is an autosave, our form has not been submitted, so we don't want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        	return $post_id;
		}
		
		// Check the logged in user has permission to edit this post
    	if ( ! aiovg_current_user_can( 'edit_aiovg_video', $post_id ) ) {
        	return $post_id;
    	}
		
		// Check if "aiovg_video_metabox_nonce" nonce is set
    	if ( isset( $_POST['aiovg_video_metabox_nonce'] ) ) {		
			// Verify that the nonce is valid
    		if ( wp_verify_nonce( $_POST['aiovg_video_metabox_nonce'], 'aiovg_save_video_metabox' ) ) {			
				// OK to save meta data
				$this->save_bunny_stream_data( $post_id );		
			}
		}
	}

	/**
	 * Update bunny stream data in the post.
	 *
	 * @since  4.2.0
	 * @param  int   $post_id Post ID.
	 */
	public function save_bunny_stream_data( $post_id ) {
		$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'default';
		$mp4  = isset( $_POST['mp4'] ) ? aiovg_sanitize_url( $_POST['mp4'] ) : '';
		$bunny_stream_video_id = isset( $_POST['bunny_stream_video_id'] ) ? sanitize_text_field( $_POST['bunny_stream_video_id'] ) : 0;

		if ( ! empty( $bunny_stream_video_id ) ) {
			if ( 'default' != $type ) {
				$this->delete_bunny_stream_video( $bunny_stream_video_id );
				$bunny_stream_video_id = 0;
			} elseif ( ! empty( $mp4 ) ) {
				if ( strpos( $mp4, '/' . $bunny_stream_video_id . '/' ) === false ) {
					$this->delete_bunny_stream_video( $bunny_stream_video_id );
					$bunny_stream_video_id = 0;
				}
			}			
		}
		
		update_post_meta( $post_id, 'bunny_stream_video_id', $bunny_stream_video_id );

		if ( ! empty( $_POST['deletable_bunny_stream_video_ids'] ) ) {
			$deletable_bunny_stream_video_ids = explode( ',', $_POST['deletable_bunny_stream_video_ids'] );
			$deletable_bunny_stream_video_ids = array_filter( array_map( 'sanitize_text_field', $deletable_bunny_stream_video_ids ) );

			if ( ! empty( $deletable_bunny_stream_video_ids ) ) {
				foreach ( $deletable_bunny_stream_video_ids as $bunny_stream_video_id ) {
					$this->delete_bunny_stream_video( $bunny_stream_video_id );
				}
			}
 		}
	}

	/**
	 * Delete bunny stream video.
	 *
	 * @since 4.2.0
	 * @param int   $post_id Video Post ID.
	 */
	public function before_delete_post( $post_id ) {		
		if ( 'aiovg_videos' != get_post_type( $post_id ) ) {
			return false;
		}

		// Get Bunny Stream video ID stored in post meta
		$video_id = get_post_meta( $post_id, 'bunny_stream_video_id', true );

		if ( empty( $video_id ) ) {
			return false;
		}

		// Confirm the video URL contains the expected video ID
		$video_url = get_post_meta( $post_id, 'mp4', true );
		
		if ( strpos( $video_url, '/' . $video_id . '/' ) === false ) {
			return false;
		}
		  
		$this->delete_bunny_stream_video( $video_id );	
	}

	/**
	 * Create a Bunny Stream video entry via AJAX.
	 *
	 * @since 4.2.0
	 */
	public function ajax_callback_create_bunny_stream_video() {
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		$settings = aiovg_has_bunny_stream_enabled(); // Fetch Bunny Stream settings
		$response = array();

		if ( empty( $settings ) ) {
			$response['error'] = __( 'Invalid API Credentials.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		// Sanitize and assign the necessary parameters from settings and request
		$api_key       = sanitize_text_field( $settings['api_key'] );
		$library_id    = intval( $settings['library_id'] );
		$cdn_hostname  = sanitize_text_field( $settings['cdn_hostname'] );
		$collection_id = ! empty( $settings['collection_id'] ) ? sanitize_text_field( $settings['collection_id'] ) : '';
		$title         = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
		$expires       = time() + 3600; // Token expiration time (1 hour from now)

		if ( empty( $title ) ) {
			$response['error'] = __( 'Invalid Video Title.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		// Build the request body for Bunny Stream API
		$body = array(
			'title'         => $title,
			'thumbnailTime' => 5 // Set thumbnail time in seconds
		);

		if ( ! empty( $collection_id ) ) {
			$body['collectionId'] = $collection_id;
		}

		// Send request to Bunny Stream API to create a video entry
		$api_response = wp_remote_post( "https://video.bunnycdn.com/library/{$library_id}/videos", array(
			'headers' => array(
				'AccessKey'    => $api_key,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json'
			),
			'body' => wp_json_encode( $body )
		));

		// Handle errors from the remote request
		if ( is_wp_error( $api_response ) ) {
			$response['error'] = $api_response->get_error_message();
			wp_send_json_error( $response );
		}

		// Decode the response and extract video metadata
		$body = wp_remote_retrieve_body( $api_response );
		$json = json_decode( $body, true );

		if ( ! is_array( $json ) ) {
			$response['error'] = __( 'Sorry, transcoding failed. Please contact the site administrator.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		$video_id = $json['guid'];

		// Build the final response with token and metadata		
		$response = array(
			'token'         => hash( 'sha256', $library_id . $api_key . $expires . $video_id ), // Upload token for Bunny's direct-to-cloud
			'expires'       => $expires,
			'video_id'      => $video_id,
			'library_id'    => $library_id,
			'collection_id' => $collection_id,
			'title'         => $title,
			'video_url'     => 'https://' . $cdn_hostname . '/' . $video_id . '/playlist.m3u8',
			'thumbnail_url' => 'https://' . $cdn_hostname . '/' . $video_id . '/' . $json['thumbnailFileName']
		);

		wp_send_json_success( $response ); // Return successful JSON response
	}

	/**
	 * Get a Bunny Stream video entry and return its status and metadata.
	 *
	 * @since 4.2.0
	 */
	public function ajax_callback_get_bunny_stream_video() {
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		$settings = aiovg_has_bunny_stream_enabled(); // Fetch Bunny Stream API settings
		$response = array();

		if ( empty( $settings ) ) {
			$response['error'] = __( 'Invalid API Credentials.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		// Sanitize and extract parameters
		$api_key    = sanitize_text_field( $settings['api_key'] );
		$library_id = intval( $settings['library_id'] );
		$video_id   = isset( $_POST['video_id'] ) ? sanitize_text_field( $_POST['video_id'] ) : '';

		if ( empty( $video_id ) ) {
			$response['error'] = __( 'Invalid Video ID.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		// Request video data from Bunny Stream API
		$api_response = wp_remote_get( "https://video.bunnycdn.com/library/{$library_id}/videos/{$video_id}", array(
			'headers' => array(
				'AccessKey' => $api_key,
				'Accept'    => 'application/json'
			)
		));

		// Handle API errors
		if ( is_wp_error( $api_response ) ) {
			$response['error'] = $api_response->get_error_message();
			wp_send_json_error( $response );
		}

		// Decode JSON response
		$body = wp_remote_retrieve_body( $api_response );
		$json = json_decode( $body, true );

		if ( ! is_array( $json ) ) {
			$response['error'] = __( 'Sorry, transcoding failed. Please contact the site administrator.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		$status_message = __( '<strong>Processing:</strong> Your video is being processed. This usually happens quickly, but during busy times, it may take a little longer. You can safely continue and save the form — no need to wait. The video will automatically become playable once processing is complete.', 'all-in-one-video-gallery' );
		
		if ( 3 == $json['status'] ) {
			$status_message = __( '<strong>Transcoding:</strong> Your video is being transcoded to optimize playback across all devices. This usually completes shortly. You can safely save your changes in the meantime — the video will appear on the front-end once transcoding finishes.', 'all-in-one-video-gallery' );
		}

		if ( 4 == $json['status'] ) {
			$status_message = __( '<strong>Congrats!</strong> Your video is ready and available for streaming.', 'all-in-one-video-gallery' );
		}

		$duration = aiovg_convert_seconds_to_human_time( $json['length'] );

		// Build the final response
		$response = array(
			'video_id'   => $video_id,
			'library_id' => $library_id,			
			'status'     => $json['status'],
			'message'    => $status_message,
			'duration'   => $duration
		);

		wp_send_json_success( $response ); // Send successful JSON response
	}
	
	/**
	 * Delete a Bunny Stream video entry.
	 *
	 * @since 4.2.2
	 */
	public function ajax_callback_delete_bunny_stream_video() {
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' ); // Verify the nonce for security

		$response = array();
		$video_id = isset( $_POST['video_id'] ) ? sanitize_text_field( $_POST['video_id'] ) : '';

		if ( empty( $video_id ) ) {
			$response['error'] = __( 'Invalid Video ID.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		$this->delete_bunny_stream_video( $video_id );
		
		wp_send_json_success(); // Send successful JSON response
	}

	/**
	 * Deletes a video from Bunny Stream CDN.
	 *
	 * @since  4.2.0
	 * @param  string $video_id Bunny Stream Video Library ID.
	 * @return bool             True on success, false on failure.
	 */
	public function delete_bunny_stream_video( $video_id ) {
		$settings = (array) get_option( 'aiovg_bunny_stream_settings' );

		if ( empty( $settings['api_key'] ) || empty( $settings['library_id'] ) ) {
			return false;
		}

		// Sanitize and prepare API credentials
		$api_key    = sanitize_text_field( $settings['api_key'] );
		$video_id   = sanitize_text_field( $video_id );
		$library_id = intval( $settings['library_id'] );

		// Build the Bunny Stream API URL for deleting the video
		$url = "https://video.bunnycdn.com/library/{$library_id}/videos/{$video_id}";

		// Prepare the request headers and options
		$args = array(
			'method'  => 'DELETE',
			'headers' => array(
				'Accept'    => 'application/json',
				'AccessKey' => $api_key,
			),
			'timeout' => 15,
		);

		// Execute the DELETE request
		$response = wp_remote_request( $url, $args );

		// Handle any request errors
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// Check for successful HTTP response code
		return wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Filters the video sources to use Bunny Stream HLS when available.
	 *
	 * @since  4.2.0
	 * @param  array $sources  The original video sources.
	 * @param  array $settings Player settings including post ID and post type.
	 * @return array           Modified video sources with Bunny Stream HLS if applicable.
	 */
	public function filter_player_sources( $sources, $settings = array() ) {
		// Ensure the MP4 source is present and has a valid URL
		if ( ! isset( $sources['mp4'] ) || empty( $sources['mp4']['src'] ) ) {
			return $sources;
		}

		// Ensure a valid post ID is provided
		if ( ! isset( $settings['post_id'] ) || empty( $settings['post_id'] ) ) {
			return $sources;
		}

		$post_id = $settings['post_id'];

		// Only proceed if the post type is set and matches 'aiovg_videos'
		if ( ! isset( $settings['post_type'] ) || 'aiovg_videos' != $settings['post_type'] ) {
			return $sources;
		}

		// Retrieve the Bunny Stream video ID
		$video_id = get_post_meta( $post_id, 'bunny_stream_video_id', true );

		if ( empty( $video_id ) ) {
			return $sources;
		}

		// Ensure the video URL belongs to the Bunny video before signing
		if ( strpos( $sources['mp4']['src'], '/' . $video_id . '/' ) === false ) {
			return $sources;
		}

		// Replace sources with HLS URL from Bunny Stream
		$hls_url = aiovg_get_bunny_stream_signed_url( $sources['mp4']['src'], $video_id );

		$sources = array(
			'hls' => array(
				'type' => 'application/x-mpegurl',
				'src'  => $hls_url
			)			
		);

		return $sources;
	}

	/**
	 * Filters the image URL to return a signed Bunny Stream URL if applicable.
	 *
	 * @since 4.2.0
	 * @param array  $image_data  The image data array with 'src'.
	 * @param int    $post_id     The ID of the post the image is related to.
	 * @param string $size        The requested image size.
	 * @param string $object_type The type of object, either "post" or "term".
	 * @return array              The filtered image data.
	 */
	function filter_image_url( $image_data, $post_id, $size, $object_type ) {
		// If image src is missing or empty, return as-is
		if ( ! isset( $image_data['src'] ) || empty( $image_data['src'] ) ) {
			return $image_data;
		}

		// Only proceed for 'post' object type
		if ( 'post' !== $object_type ) {
			return $image_data;
		}

		// Get Bunny Stream video ID from post meta
		$video_id = get_post_meta( $post_id, 'bunny_stream_video_id', true );

		if ( empty( $video_id ) ) {
			return $image_data;
		}

		// Ensure the image URL belongs to the Bunny video before signing
		if ( strpos( $image_data['src'], '/' . $video_id . '/' ) === false ) {
			return $image_data;
		}

		// Generate a signed Bunny Stream image URL
		$signed_url = aiovg_get_bunny_stream_signed_url( $image_data['src'], $video_id );
		$image_data['src'] = $signed_url;

		return $image_data;
	}

}
