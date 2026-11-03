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
		$mp4  = isset( $_POST['mp4'] ) ? trim( $_POST['mp4'] ) : '';

		if ( ! empty( $mp4 ) ) {
			$private_base_url = aiovg_get_private_base_url();

			// Check if the URL is a masked uploaded file
			if ( 0 === strpos( $mp4, $private_base_url ) ) {
				// Extract the encoded portion
				$encoded = substr( $mp4, strlen( $private_base_url ) );

				// Decode the masked URL
				$decoded = aiovg_base64_decode( $encoded );

				// Sanitize the real file URL
				$mp4 = aiovg_sanitize_url( $decoded );
			} else {
				// Direct URL entered by the user
				$mp4 = aiovg_sanitize_url( $mp4 );
			}
		}

		// Read before the updates below, so the post's previous GUIDs still count as its own
		$stored_video_ids = array(
			get_post_meta( $post_id, 'bunny_stream_video_id', true ),
			get_post_meta( $post_id, 'trailer_bunny_stream_video_id', true )
		);

		$bunny_stream_video_id = isset( $_POST['bunny_stream_video_id'] ) ? sanitize_text_field( $_POST['bunny_stream_video_id'] ) : 0;

		if ( ! empty( $bunny_stream_video_id ) ) {
			if ( ! $this->user_can_manage_bunny_stream_video( $bunny_stream_video_id, $post_id, $stored_video_ids ) ) {
				$bunny_stream_video_id = 0; // Not this user's to store or delete
			} elseif ( 'default' != $type ) {
				$this->delete_unused_bunny_stream_video( $bunny_stream_video_id, $post_id );
				$bunny_stream_video_id = 0;
			} elseif ( ! empty( $mp4 ) ) {
				if ( strpos( $mp4, '/' . $bunny_stream_video_id . '/' ) === false ) {
					$this->delete_unused_bunny_stream_video( $bunny_stream_video_id, $post_id );
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
					if ( $this->user_can_manage_bunny_stream_video( $bunny_stream_video_id, $post_id, $stored_video_ids ) ) {
						$this->delete_unused_bunny_stream_video( $bunny_stream_video_id, $post_id );
					}
				}
			}
 		}

		// Trailer Bunny Stream
		if ( isset( $_POST['aiovg_video_trailer_nonce'] ) && wp_verify_nonce( $_POST['aiovg_video_trailer_nonce'], 'aiovg_save_video_trailer' ) ) {
			$trailer_type = isset( $_POST['trailer_type'] ) ? sanitize_text_field( $_POST['trailer_type'] ) : '';
			$trailer_src  = isset( $_POST['trailer_src'] ) ? aiovg_sanitize_url( $_POST['trailer_src'] ) : '';

			$trailer_bunny_stream_video_id = isset( $_POST['trailer_bunny_stream_video_id'] ) ? sanitize_text_field( $_POST['trailer_bunny_stream_video_id'] ) : '';

			if ( ! empty( $trailer_bunny_stream_video_id ) ) {
				if ( ! $this->user_can_manage_bunny_stream_video( $trailer_bunny_stream_video_id, $post_id, $stored_video_ids ) ) {
					$trailer_bunny_stream_video_id = ''; // Not this user's to store or delete
				} elseif ( 'embedcode' === $trailer_type ) {
					$this->delete_unused_bunny_stream_video( $trailer_bunny_stream_video_id, $post_id );
					$trailer_bunny_stream_video_id = '';
				} elseif ( ! empty( $trailer_src ) ) {
					if ( strpos( $trailer_src, '/' . $trailer_bunny_stream_video_id . '/' ) === false ) {
						$this->delete_unused_bunny_stream_video( $trailer_bunny_stream_video_id, $post_id );
						$trailer_bunny_stream_video_id = '';
					}
				}
			}

			update_post_meta( $post_id, 'trailer_bunny_stream_video_id', $trailer_bunny_stream_video_id );

			if ( ! empty( $_POST['deletable_trailer_bunny_stream_video_ids'] ) ) {
				$deletable_trailer_bunny_stream_video_ids = explode( ',', $_POST['deletable_trailer_bunny_stream_video_ids'] );
				$deletable_trailer_bunny_stream_video_ids = array_filter( array_map( 'sanitize_text_field', $deletable_trailer_bunny_stream_video_ids ) );

				if ( ! empty( $deletable_trailer_bunny_stream_video_ids ) ) {
					foreach ( $deletable_trailer_bunny_stream_video_ids as $deletable_trailer_id ) {
						if ( $this->user_can_manage_bunny_stream_video( $deletable_trailer_id, $post_id, $stored_video_ids ) ) {
							$this->delete_unused_bunny_stream_video( $deletable_trailer_id, $post_id );
						}
					}
				}
			}
		}
	}

	/**
	 * Delete Bunny Stream videos associated with a video post before it is deleted.
	 *
	 * @since 4.2.0
	 * @param int   $post_id Video Post ID.
	 */
	public function before_delete_post( $post_id ) {
		if ( 'aiovg_videos' != get_post_type( $post_id ) ) {
			return false;
		}

		// "Delete Media Files on Delete" covers the Bunny Stream copies too
		$general_settings = aiovg_get_option( 'aiovg_general_settings' );

		if ( empty( $general_settings['delete_media_files'] ) ) {
			return false;
		}

		// Main video: delete Bunny Stream video if one is stored
		$video_id = get_post_meta( $post_id, 'bunny_stream_video_id', true );

		if ( ! empty( $video_id ) ) {
			$video_url = get_post_meta( $post_id, 'mp4', true );

			if ( strpos( $video_url, '/' . $video_id . '/' ) !== false ) {
				$this->delete_unused_bunny_stream_video( $video_id, $post_id );
			}
		}

		// Trailer: delete Bunny Stream trailer video if one is stored
		$trailer_video_id = get_post_meta( $post_id, 'trailer_bunny_stream_video_id', true );

		if ( ! empty( $trailer_video_id ) ) {
			$trailer_src = get_post_meta( $post_id, 'trailer_src', true );

			if ( strpos( $trailer_src, '/' . $trailer_video_id . '/' ) !== false ) {
				$this->delete_unused_bunny_stream_video( $trailer_video_id, $post_id );
			}
		}
	}

	/**
	 * Create a Bunny Stream video entry via AJAX.
	 *
	 * @since 4.2.0
	 */
	public function ajax_callback_create_bunny_stream_video() {
		// Verify nonce
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' );

		// Ensure user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
		}

		// Verify user capability
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );

			if ( ! $post || 'aiovg_videos' !== $post->post_type ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}

			if ( ! aiovg_current_user_can( 'edit_aiovg_video', $post_id ) ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}
		} else {
			if ( ! aiovg_current_user_can( 'edit_aiovg_videos' ) ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}
		}

		// Vars
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

		// A wrong API key or library ID returns an error object
		if ( empty( $json['guid'] ) ) {
			$code = wp_remote_retrieve_response_code( $api_response );

			$response['error'] = ( 401 == $code || 403 == $code ) ? __( 'Invalid API Credentials.', 'all-in-one-video-gallery' ) : __( 'Sorry, transcoding failed. Please contact the site administrator.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		$video_id = $json['guid'];

		// Remember the uploader; outlives the 24-hour nonces that gate every cleanup route
		set_transient( $this->get_bunny_stream_upload_key( $video_id ), 1, 2 * DAY_IN_SECONDS );

		// Build the final response with token and metadata		
		$response = array(
			'token'         => hash( 'sha256', $library_id . $api_key . $expires . $video_id ), // Upload token for Bunny's direct-to-cloud
			'expires'       => $expires,
			'video_id'      => $video_id,
			'library_id'    => $library_id,
			'collection_id' => $collection_id,
			'title'         => $title,
			'video_url'     => 'https://' . $cdn_hostname . '/' . $video_id . '/playlist.m3u8',
			'thumbnail_url' => 'https://' . $cdn_hostname . '/' . $video_id . '/' . ( isset( $json['thumbnailFileName'] ) ? $json['thumbnailFileName'] : 'thumbnail.jpg' )
		);

		wp_send_json_success( $response ); // Return successful JSON response
	}

	/**
	 * Get a Bunny Stream video entry and return its status and metadata.
	 *
	 * @since 4.2.0
	 */
	public function ajax_callback_get_bunny_stream_video() {
		// Verify nonce
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' );

		// Ensure user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
		}

		// Verify user capability
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );

			if ( ! $post || 'aiovg_videos' !== $post->post_type ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}

			if ( ! aiovg_current_user_can( 'edit_aiovg_video', $post_id ) ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}
		} else {
			if ( ! aiovg_current_user_can( 'edit_aiovg_videos' ) ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}
		}

		// Vars
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

		if ( ! $this->user_can_manage_bunny_stream_video( $video_id, $post_id ) ) {
			$response['error'] = __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' );
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

		if ( ! is_array( $json ) || ! isset( $json['status'] ) ) {
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
		// Verify nonce
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' );

		// Ensure user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
		}

		// Verify user capability
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
			
		if ( $post_id > 0 ) {
			$post = get_post( $post_id );

			if ( ! $post || 'aiovg_videos' !== $post->post_type ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}

			if ( ! aiovg_current_user_can( 'delete_aiovg_video', $post_id ) ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}
		} else {
			// If no post exists yet, this is a cancel-upload cleanup
			if ( ! aiovg_current_user_can( 'edit_aiovg_videos' ) ) {
				wp_send_json_error( __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' ) );
			}
		}

		// Vars
		$response = array();
		$video_id = isset( $_POST['video_id'] ) ? sanitize_text_field( $_POST['video_id'] ) : '';

		if ( empty( $video_id ) ) {
			$response['error'] = __( 'Invalid Video ID.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		if ( ! $this->user_can_manage_bunny_stream_video( $video_id, $post_id ) ) {
			$response['error'] = __( 'You do not have sufficient permissions to do this action.', 'all-in-one-video-gallery' );
			wp_send_json_error( $response );
		}

		$this->delete_unused_bunny_stream_video( $video_id, $post_id );
		delete_transient( $this->get_bunny_stream_upload_key( $video_id ) );
		
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
		$settings = aiovg_get_option( 'aiovg_bunny_stream_settings' );

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
	 * Delete a Bunny Stream video unless another video still uses it.
	 *
	 * @since  4.9.6
	 * @access private
	 * @param  string  $video_id Bunny Stream video ID.
	 * @param  int     $post_id  The video post letting go of it, or 0 when there is none yet.
	 */
	private function delete_unused_bunny_stream_video( $video_id, $post_id ) {
		// Translations share the Bunny Stream video, so keep it while another video uses it
		if ( ! aiovg_is_media_in_use( $video_id, 'bunny_stream', $post_id ) ) {
			$this->delete_bunny_stream_video( $video_id );
		}
	}

	/**
	 * Whether the current user may act on a Bunny Stream video.
	 *
	 * @since  5.1.0
	 * @access private
	 * @param  string $video_id         Bunny Stream video ID.
	 * @param  int    $post_id          The video post being edited, or 0 when there is none yet.
	 * @param  array  $stored_video_ids Optional. The post's GUIDs as stored before a save. Read from the post when omitted.
	 * @return bool                     True if the user uploaded it, it is stored on the post, or the user manages the plugin.
	 */
	private function user_can_manage_bunny_stream_video( $video_id, $post_id, $stored_video_ids = null ) {
		if ( empty( $video_id ) ) {
			return false;
		}

		if ( current_user_can( 'manage_aiovg_options' ) ) {
			return true;
		}

		if ( get_transient( $this->get_bunny_stream_upload_key( $video_id ) ) ) {
			return true;
		}

		// The callers have already checked the user's permission on this post
		if ( $post_id > 0 ) {
			if ( null === $stored_video_ids ) {
				$stored_video_ids = array(
					get_post_meta( $post_id, 'bunny_stream_video_id', true ),
					get_post_meta( $post_id, 'trailer_bunny_stream_video_id', true )
				);
			}

			if ( in_array( $video_id, $stored_video_ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the transient name that records who uploaded a Bunny Stream video.
	 *
	 * @since  5.1.0
	 * @access private
	 * @param  string $video_id Bunny Stream video ID.
	 * @return string           Transient name, unique to the current user and video.
	 */
	private function get_bunny_stream_upload_key( $video_id ) {
		return 'aiovg_bunny_' . md5( get_current_user_id() . '|' . $video_id );
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

		// When playing a trailer, check against the trailer's Bunny Stream GUID instead.
		$meta_key = ! empty( $settings['is_trailer'] ) ? 'trailer_bunny_stream_video_id' : 'bunny_stream_video_id';
		$video_id = get_post_meta( $post_id, $meta_key, true );

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
