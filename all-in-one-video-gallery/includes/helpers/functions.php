<?php

/**
 * General helper functions.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

// If your PHP version is < 8
if ( ! function_exists( 'str_contains' ) ) {
    /**
     * Based on original work from the PHP Laravel framework.
     *
     * @since  3.5.0
     * @param  string  $haystack The string to search in.
     * @param  string  $needle   The substring to search for in the haystack.
     * @return boolean           Returns true if needle is in haystack, false otherwise.
     */
    function str_contains( $haystack, $needle ) {
        return $needle !== '' && mb_strpos( $haystack, $needle ) !== false;
    }
}

/**
 * Adds extra code to a registered script.
 * 
 * @since 3.0.0
 * @param string $handle   Name of the script to add the inline script to.
 * @param string $data     String containing the JavaScript to be added.
 * @param string $position Whether to add the inline script before the handle or after.
 */
function aiovg_add_inline_script( $handle, $data, $position = 'before' ) {
	// $is_script_added = wp_add_inline_script( $handle, $data, $position );

	add_action( 'wp_footer', function() use ( $data ) {
		echo '<script type="text/javascript">' . $data . '</script>';
	});
}

/**
 * Allow iframe & script tags in the "Third-Party Player Code" field.
 * 
 * @since  1.0.0
 * @param  array $allowed_tags Allowed HTML Tags.
 * @return array               Iframe & script tags included.
 */
function aiovg_allow_iframe_script_tags( $allowed_tags ) {
	// Only change for users who has "unfiltered_html" capability
	if ( ! current_user_can( 'unfiltered_html' ) ) return $allowed_tags;
	
	// Allow script and the following attributes
	$allowed_tags['script'] = array(
		'type'   => true,
		'src'    => true,
		'width'  => true,
		'height' => true
	);

	// Allow iframes and the following attributes
	$allowed_tags['iframe'] = array(
		'src'             => true,
		'title'           => true,		
		'width'           => true,
		'height'          => true,		
		'name'            => true,		
		'id'              => true,
		'class'           => true,
		'align'           => true,
		'style'           => true,
		'frameborder'     => true,
		'scrolling'       => true,
		'marginwidth'     => true,
		'marginheight'    => true,
		'allowfullscreen' => true
	);

	// Allow stream and the following attributes
	$allowed_tags['stream'] = array(
		'src'      => true,
		'controls' => true
	);
	
	return $allowed_tags;	
}

/**
 * Base64 decode a string.
 * 
 * @since  3.8.4
 * @param  string $string The string to be decoded.
 * @return string         Decoded string.
 */
function aiovg_base64_decode( $string ) {
	return base64_decode( str_replace( array( '-', '_', '.' ), array( '+', '/', '=' ), $string ) );
}

/**
 * Base64 encode a string.
 * 
 * @since  3.8.4
 * @param  string $string The string to be encoded.
 * @return string         Encoded string.
 */
function aiovg_base64_encode( $string ) {
	return str_replace( array( '+', '/', '=' ), array(  '-', '_', '.' ), base64_encode( $string ) );
}

/**
 * Combine video attributes as a string.
 * 
 * @since 2.0.0
 * @param array  $atts Array of video attributes.
 * @param string       Combined attributes string.
 */
function aiovg_combine_video_attributes( $atts ) {
	$attributes = array();
	
	foreach ( $atts as $key => $value ) {
		if ( '' === $value ) {
			$attributes[] = $key;
		} else {
			$attributes[] = sprintf( '%s="%s"', $key, $value );
		}
	}
	
	return implode( ' ', $attributes );
}

/**
 * Converts a timestamp string into seconds.
 *
 * This function takes a time string formatted as HH:MM:SS or MM:SS
 * and converts it into the total number of seconds.
 *
 * @since  3.9.7
 * @param  string $time The timestamp string (HH:MM:SS or MM:SS).
 * @return int          The total time in seconds.
 */
function aiovg_convert_time_to_seconds( $time ) {
    $parts = explode( ':', $time );
    $parts = array_map( 'intval', $parts ); // Convert to integers

    if ( count( $parts ) === 3 ) {
        return $parts[0] * 3600 + $parts[1] * 60 + $parts[2]; // HH:MM:SS
    } elseif ( count( $parts ) === 2 ) {
        return $parts[0] * 60 + $parts[1]; // MM:SS
    }

    return (int) $time;
}

/**
 * Create an attachment from the external image URL and return the attachment ID.
 * 
 * @since  2.6.3
 * @param  string $image_url Image URL from external server.
 * @param  int    $post_id   Post ID.
 * @return int               WordPress attachment ID.
 */
function aiovg_create_attachment_from_external_image_url( $image_url, $post_id ) {
    if ( empty( $image_url ) ) {
        return 0;
    }

    $image_url_hash = md5( $image_url );
    $attachment_id  = get_post_meta( $post_id, $image_url_hash, true );

    if ( ! empty( $attachment_id ) ) {
        if ( wp_attachment_is( 'image', $attachment_id ) ) {
            return $attachment_id;
        } else {
            delete_post_meta( $post_id, $image_url_hash );
        }
    }

    // Validate file type
    $allowed_mimes = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'gif'          => 'image/gif',
        'png'          => 'image/png'
    );

    $file_info = wp_check_filetype( basename( $image_url ), $allowed_mimes );

    if ( $file_info['type'] == false ) {
        $parsed_url = parse_url( $image_url );

        // URLs from Vimeo/Dailymotion don't have a file extension. So, manually set the file info.
        if ( strpos( $parsed_url['host'], '.vimeocdn.com' ) !== false || strpos( $parsed_url['host'], '.dmcdn.net' ) !== false ) {
            $file_info = array(
                'ext'  => 'jpg',
                'type' => 'image/jpeg'
            );
        }

        // Hook for developers to set the file info for image URLs that don't have a file extension.
        $file_info = apply_filters( 'aiovg_check_filetype', $file_info, $image_url, $post_id );
    }

    if ( $file_info['ext'] == false ) {
        return 0;
    }

    $file_extension = strtolower( $file_info['ext'] );
    if ( ! in_array( $file_extension, array( 'jpg', 'jpeg', 'png', 'gif' ) ) ) {
        return 0;
    }

    // Validate remote URL accessibility using wp_remote_head
    $response = wp_remote_head( $image_url, array(
        'timeout' => 5,
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (compatible; WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) . ')'
        )
    ) );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return 0;
    }

    // Check MIME type from the header
    $mime_type = wp_get_image_mime( $image_url );

    if ( $mime_type == false ) {
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );

        if ( empty( $content_type ) || ! in_array( $content_type, array_values( $allowed_mimes ) ) ) {
            return 0;
        }

        $mime_type = $content_type; // Use MIME type from the header
    }

    if ( ! in_array( $mime_type, array_values( $allowed_mimes ) ) ) {
        return 0;
    }

    // Set upload folder
    $wp_upload_dir = wp_upload_dir(); 
    $upload_dir = $wp_upload_dir['basedir'];
    if ( wp_mkdir_p( $wp_upload_dir['path'] ) ) {
        $upload_dir = $wp_upload_dir['path'];
    }

    // Set file path & name
    $unique_id = str_replace( '.', '-', uniqid() );
    $unique_file_name = wp_unique_filename( $upload_dir, $unique_id . '.' . $file_extension ); // Generate unique name
    $file_name = sanitize_file_name( basename( $unique_file_name ) ); // Create image file name

    $file_path = $upload_dir . '/' . $file_name;

    // Get image data using file_get_contents
    $image_data = @file_get_contents( $image_url );

    if ( $image_data === false ) {
        return 0;
    }

    // Create the image file on the server
    file_put_contents( $file_path, $image_data );

    // Create the attachment
    require_once( ABSPATH . 'wp-admin/includes/image.php' );

    $attachment = array(
        'post_mime_type' => $mime_type,
        'post_title'     => $file_name,
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    $attachment_id = wp_insert_attachment( $attachment, $file_path, $post_id );

    // Define attachment metadata
    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );

    // Assign metadata to attachment
    wp_update_attachment_metadata( $attachment_id, $attachment_data );

    // And finally, store a reference to the attachment in the post
    update_post_meta( $post_id, $image_url_hash, $attachment_id );

    return $attachment_id;
}

/**
 * Whether the current user has a specific capability.
 *
 * @since  1.0.0
 * @param  string $capability Capability name.
 * @param  int    $post_id    Optional. ID of the specific object to check against if
 *							  `$capability` is a "meta" cap.
 * @return bool               True if the current user has the capability, false if not.
 */
function aiovg_current_user_can( $capability, $post_id = 0 ) {
	$user_id = get_current_user_id();
	
	// If playing a video
	if ( 'play_aiovg_video' == $capability ) {
		$has_access = aiovg_current_user_has_video_access( $post_id );
		return apply_filters( 'aiovg_current_user_can', $has_access, $capability, $post_id );
	}

	// If editing, deleting, or reading a video, get the post and post type object
	if ( 'edit_aiovg_video' == $capability || 'delete_aiovg_video' == $capability || 'read_aiovg_video' == $capability ) {
		$post = get_post( $post_id );
		$post_type = get_post_type_object( $post->post_type );

		// If editing a video, assign the required capability
		if ( 'edit_aiovg_video' == $capability ) {
			if ( $user_id == $post->post_author ) {
				$capability = 'edit_aiovg_videos';
			} else {
				$capability = 'edit_others_aiovg_videos';
			}
		}
		
		// If deleting a video, assign the required capability
		elseif ( 'delete_aiovg_video' == $capability ) {
			if ( $user_id == $post->post_author ) {
				$capability = 'delete_aiovg_videos';
			} else {
				$capability = 'delete_others_aiovg_videos';
			}
		}
		
		// If reading a private video, assign the required capability
		elseif ( 'read_aiovg_video' == $capability ) {
			if ( 'private' != $post->post_status ) {
				$capability = 'read';
			} elseif ( $user_id == $post->post_author ) {
				$capability = 'read';
			} else {
				$capability = 'read_private_aiovg_videos';
			}
		}		
	}
		
	return current_user_can( $capability );	
}

/**
 * Checks if the current user has access to watch a specific video.
 *
 * This function verifies whether the current user has permission to view the 
 * given video based on role-based restrictions.
 * 
 * @since  3.9.6
 * @param  int   $post_id The ID of the video post.
 * @return bool           True if the current user has access, false otherwise.
 */
function aiovg_current_user_has_video_access( $post_id = 0 ) {
	if ( 0 == $post_id ) {
		return true;
	}

	if ( current_user_can( 'manage_aiovg_options' ) ) {
		return true;
	}

	if ( aiovg_current_user_can( 'edit_aiovg_video', $post_id ) ) {
		return true;
	}

	$restrictions_settings = get_option( 'aiovg_restrictions_settings' );
	if ( empty( $restrictions_settings['enable_restrictions'] ) ) {
		return true;
	}
	
	$access_control   = $restrictions_settings['access_control'];
	$restricted_roles = $restrictions_settings['restricted_roles'];

	if ( metadata_exists( 'post', $post_id, 'access_control' ) ) {
		$__access_control = (int) get_post_meta( $post_id, 'access_control', true );
		if ( $__access_control != -1 ) {
			$access_control = $__access_control;

			if ( $access_control == 2 ) {
				$__restricted_roles = get_post_meta( $post_id, 'restricted_roles', true );
				if ( ! empty( $__restricted_roles ) ) {		
					$restricted_roles = $__restricted_roles;
				}		
			}
		}		
	}

	// Everyone
	if ( $access_control == 0 ) {
		return true;
	}

	// Logged out users only
	if ( $access_control == 1 ) {
		if ( ! is_user_logged_in() ) {
			return true;
		}
	}

	// Logged in users only
	if ( $access_control == 2 ) {
		if ( is_user_logged_in() ) {
			$restricted_roles = (array) $restricted_roles;
			$restricted_roles = array_filter( $restricted_roles );
			if ( empty( $restricted_roles ) ) {
				return true;
			}

			$current_user  = wp_get_current_user();
			$roles_matched = array_intersect( $current_user->roles, $restricted_roles );
			if ( count( $roles_matched ) > 0 ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Delete category attachments.
 *
 * @since 1.0.0
 * @param int   $term_id Term ID.
 */
function aiovg_delete_category_attachments( $term_id ) {
	$general_settings = get_option( 'aiovg_general_settings' );
	
	if ( ! empty( $general_settings['delete_media_files'] ) ) {
		$image_id = get_term_meta( $term_id, 'image_id', true );
		if ( ! empty( $image_id ) ) wp_delete_attachment( $image_id, true );
	}
}

/**
 * Delete video attachments.
 *
 * @since 1.0.0
 * @param int   $post_id Post ID.
 */
function aiovg_delete_video_attachments( $post_id ) {	
	$general_settings = get_option( 'aiovg_general_settings' );
	
	if ( ! empty( $general_settings['delete_media_files'] ) ) {
		$mp4_id = get_post_meta( $post_id, 'mp4_id', true );
		if ( ! empty( $mp4_id ) ) wp_delete_attachment( $mp4_id, true );
		
		$webm_id = get_post_meta( $post_id, 'webm_id', true );
		if ( ! empty( $webm_id ) ) wp_delete_attachment( $webm_id, true );
		
		$ogv_id = get_post_meta( $post_id, 'ogv_id', true );
		if ( ! empty( $ogv_id ) ) wp_delete_attachment( $ogv_id, true );
		
		$image_id = get_post_meta( $post_id, 'image_id', true );
		if ( ! empty( $image_id ) ) wp_delete_attachment( $image_id, true );
		
		$tracks = get_post_meta( $post_id, 'track' );	
		if ( ! empty( $tracks ) ) {
			foreach ( $tracks as $track ) {
				if ( ! empty( $track['src_id'] ) ) {
					wp_delete_attachment( (int) $track['src_id'], true );
				}
			}
		}
	}
}

/**
 * Extracts chapters from a given string containing timestamps and titles.
 * 
 * This function parses a structured string input, identifying timestamps and 
 * their corresponding chapter titles, then returns them as an array.
 * 
 * @since  3.9.7
 * @param  string $string The input string containing timestamps and chapter titles.
 * @return array          An array of chapters, each containing 'title', and 'seconds'.
 */
function aiovg_extract_chapters_from_string( $string ) {
	// Regex to match timestamps and titles
	$pattern = '/((\d{1,2}:)?\d{1,2}:\d{2})\s+(.+)/';

	$chapters = array();

	if ( preg_match_all( $pattern, $string, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$seconds = aiovg_convert_time_to_seconds( trim( $match[1] ) );
			$label   = isset( $match[3] ) ? ltrim( $match[3], '-' ) : '';

			$chapters[ $seconds ] = array(				
				'time'  => $seconds,
				'label' => sanitize_text_field( trim( $label ) )
			);
		}
	}

	return $chapters;
}

/**
 * Extract player attributes.
 * 
 * @since  3.9.3
 * @param  array $attributes Array of attributes.
 * @return array             Filtered array of attributes.
 */
function aiovg_extract_player_attributes( $attributes = array() ) {
	$player_settings = get_option( 'aiovg_player_settings' );

	$player_attributes = array( 
		'autoplay', 
		'loop', 
		'muted', 
		'playpause', 
		'current', 
		'progress', 
		'duration',
		'tracks',
		'chapters', 
		'speed', 
		'quality', 
		'volume',
		'pip',
		'fullscreen',
		'share',
		'embed',
		'download'
	);
	
	$player_args = array();

	foreach ( $player_attributes as $key ) {  
		if ( ! isset( $attributes[ 'player_' . $key ] ) ) {
			continue;
		}

		$value = (int) $attributes[ 'player_' . $key ];
	
		if ( 'autoplay' == $key || 'loop' == $key || 'muted' == $key ) {
			$default = ! empty( $player_settings[ $key ] ) ? 1 : 0;
		} else {
			$default = isset( $player_settings['controls'][ $key ] ) ? 1 : 0;
		}
	
		if ( $value != $default ) {
			$player_args[ $key ] = $value;
		}
	}

	return $player_args;
}

/**
 * Get attachment ID of the given URL.
 * 
 * @since      1.0.0
 * @param      string $url   Media file URL.
 * @param      string $media "image" or "video". Type of the media. 
 * @return     int           Attachment ID on success, 0 on failure.
 * @deprecated               Replaced by the WordPress core "attachment_url_to_postid" function.
 */
function aiovg_get_attachment_id( $url, $media = 'image' ) {
	$attachment_id = 0;
	
	if ( empty( $url ) ) {
		return $attachment_id;
	}	
	
	if ( 'image' == $media ) {
		$dir = wp_upload_dir();
	
		if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?	
			$file = basename( $url );
	
			$query_args = array(
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'fields' => 'ids',
				'no_found_rows' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'meta_query' => array(
					array(
						'key' => '_wp_attachment_metadata',
						'value' => $file,
						'compare' => 'LIKE'						
					),
				)
			);
	
			$query = new WP_Query( $query_args );
	
			if ( $query->have_posts() ) {	
				foreach ( $query->posts as $post_id ) {	
					$meta = wp_get_attachment_metadata( $post_id );
	
					$original_file = basename( $meta['file'] );
					$cropped_image_files = wp_list_pluck( $meta['sizes'], 'file' );
	
					if ( $original_file === $file || in_array( $file, $cropped_image_files ) ) {
						$attachment_id = $post_id;
						break;
					}	
				}	
			}	
		}	
	} else {
		$url = wp_make_link_relative( $url );
		
		if ( ! empty( $url ) ) {
			global $wpdb;
			
			$query = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid RLIKE %s", $url );
			$attachment_id = $wpdb->get_var( $query );
		}		
	}

	return $attachment_id;	
}

/**
 * Retrieves the current page ID.
 *
 * @since  4.0.1
 * @return int   The current page ID if found, otherwise 0.
 */
function aiovg_get_current_page_id() {
    global $wp_the_query;

    if ( isset( $wp_the_query ) && is_object( $wp_the_query ) ) {
        $post_id = $wp_the_query->get_queried_object_id();

        // Ensure it's a valid post/page ID (avoid returning term IDs)
        if ( is_singular() || is_front_page() ) {
            return $post_id;
        }
    }

    return 0; // Default to 0 if no valid page ID is found
}


/**
 * Get the list of custom plugin pages.
 * 
 * @since  1.6.5
 * @return array $pages Array of pages.
 */
function aiovg_get_custom_pages_list() {
	$pages = array(
		'category' => array( 
			'title'   => __( 'Video Category', 'all-in-one-video-gallery' ), 
			'content' => '[aiovg_category]' 
		),
		'tag' => array( 
			'title'   => __( 'Video Tag', 'all-in-one-video-gallery' ), 
			'content' => '[aiovg_tag]' 
		),
		'search' => array( 
			'title'   => __( 'Search Videos', 'all-in-one-video-gallery' ), 
			'content' => '[aiovg_search]' 
		),
		'user_videos' => array( 
			'title'   => __( 'User Videos', 'all-in-one-video-gallery' ), 
			'content' => '[aiovg_user_videos]' 
		),
		'player' => array( 
			'title'   => __( 'Player Embed', 'all-in-one-video-gallery' ), 
			'content' => '' 
		)
	);

	return apply_filters( 'aiovg_custom_pages_list', $pages );
}

/**
 * Get default plugin settings.
 *
 * @since  1.5.3
 * @return array $defaults Array of plugin settings.
 */
function aiovg_get_default_settings() {
	$video_page_slug = 'aiovg_videos';
	$slugs = array( 'video', 'watch' );

	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );

		if ( ! $page ) {
			$video_page_slug = $slug;
			break;
		}
	}	

	$defaults = array(		
		'aiovg_player_settings' => array(
			'player'      => 'videojs',
			'theme'       => 'default',
			'theme_color' => '#00b2ff',
			'width'       => '',
			'ratio'       => 56.25,
			'autoplay'    => 0,
			'loop'        => 0,
			'muted'       => 0,
			'preload'     => 'auto',
			'playsinline' => 1,			
			'controls'    => array(
				'playpause'  => 'playpause',
				'current'    => 'current',
				'progress'   => 'progress', 
				'duration'   => 'duration',
				'tracks'     => 'tracks',
				'chapters'   => 'chapters',				
				'speed'      => 'speed',
				'quality'    => 'quality',
				'volume'     => 'volume', 
				'fullscreen' => 'fullscreen'					
			),
			'hotkeys'        => 0,
			'cc_load_policy' => 0,
			'quality_levels' => implode( "\n", array( '360p', '480p', '720p', '1080p' ) ),
			'use_native_controls'     => array(),
			'force_js_initialization' => 0			
		),
		'aiovg_socialshare_settings' => array(				
			'services' => array( 
				'facebook'  => 'facebook',
				'twitter'   => 'twitter',				
				'linkedin'  => 'linkedin',
				'pinterest' => 'pinterest',
				'tumblr'    => 'tumblr',
				'whatsapp'  => 'whatsapp',
				'email'     => 'email'
			),
			'open_graph_tags'  => 1,
			'twitter_username' => ''
		),
		'aiovg_videos_settings' => array(
			'template'        => 'classic',					
			'columns'         => 3,
			'limit'           => 10,
			'orderby'         => 'date',
			'order'           => 'desc',
			'thumbnail_style' => 'standard',
			'display'         => array(
				'count'    => 'count',
				'title'    => 'title',
				'category' => 'category',
				'tag'      => 'tag',
				'views'    => 'views',
				'duration' => 'duration'
			),
			'title_length'    => 0,
			'excerpt_length'  => 75
		),		
		'aiovg_categories_settings' => array(
			'template'         => 'grid',
			'columns'          => 3,
			'limit'            => 0,
			'orderby'          => 'name',
			'order'            => 'asc',
			'hierarchical'     => 1,
			'show_description' => 0,
			'show_count'       => 1,				
			'hide_empty'       => 0,
			'breadcrumbs'      => 0
		),	
		'aiovg_images_settings' => array(
			'width' => '',
			'ratio' => 56.25,
			'size'  => 'medium'	
		),
		'aiovg_featured_images_settings' => array(
			'enabled'                    => 0,
			'download_external_images'   => 1,
			'hide_on_single_video_pages' => 1
		),
		'aiovg_likes_settings' => array(
			'like_button'            => 0,
			'dislike_button'         => 0,
			'login_required_to_vote' => 0
		),
		'aiovg_pagination_settings' => array(
			'ajax'     => 0,
			'mid_size' => 2
		),		
		'aiovg_video_settings' => array(
			'display'      => array( 
				'category' => 'category',
				'tag'      => 'tag', 
				'views'    => 'views', 
				'related'  => 'related',
				'share'    => 'share'
			),
			'has_comments' => 1
		),			
		'aiovg_related_videos_settings' => array(
			'columns' => 3,
			'limit'   => 10,
			'orderby' => 'date',
			'order'   => 'desc',
			'display' => array(
				'pagination' => 'pagination'
			)
		),					
		'aiovg_permalink_settings' => array(
			'video' => $video_page_slug
		),
		'aiovg_restrictions_settings' => array(
			'enable_restrictions'         => 0,
			'access_control'              => 2,
			'restricted_roles'            => array(),
			'restricted_message'          => __( 'Sorry, but you do not have permission to view this video.', 'all-in-one-video-gallery' ),
			'show_restricted_label'       => 1,
			'restricted_label_text'       => __( 'restricted', 'all-in-one-video-gallery' ),
			'restricted_label_bg_color'   => '#999',
			'restricted_label_text_color' => '#fff'
		),
		'aiovg_privacy_settings' => array(
			'show_consent'         => 0,
			'consent_message'      => __( '<strong>Please accept cookies to play this video</strong>. By accepting you will be accessing content from a service provided by an external third party.', 'all-in-one-video-gallery' ),
			'consent_button_label' => __( 'I Agree', 'all-in-one-video-gallery' ),
			'disable_cookies'      => array()
		),
		'aiovg_general_settings' => array(
			'lazyloading'               => 0,
			'datetime_format'           => '',
			'maybe_flush_rewrite_rules' => 1,
			'delete_plugin_data'        => 1,
			'delete_media_files'        => 1,
			'custom_css'                => ''
		),
		'aiovg_api_settings' => array(
			'youtube_api_key'    => '',
			'vimeo_access_token' => '',
		),
		'aiovg_bunny_stream_settings' => array(
			'enable_bunny_stream'         => 0,
			'api_key'                     => '',
			'library_id'                  => '',
			'cdn_hostname'                => '',
			'collection_id'               => '',
			'enable_token_authentication' => 0,
			'token_authentication_key'    => '',
			'token_expiry'                => 3600
		),
		'aiovg_page_settings' => aiovg_insert_custom_pages()							
	);
		
	return $defaults;		
}

/**
 * Get the video excerpt.
 *
 * @since  1.0.0
 * @param  int    $post_id     Post ID.
 * @param  int    $char_length Excerpt length.
 * @param  string $append      String to append to the end of the excerpt.
 * @param  bool   $allow_html  Allow HTML in the excerpt.
 * @return string $content     Excerpt content.
 */
function aiovg_get_excerpt( $post_id = 0 , $char_length = 55, $append = '...', $allow_html = true ) {
	$content = '';

	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
	} else {
		global $post;
	}	

	if ( ! empty( $post->post_excerpt ) ) {
		$content = ( $allow_html == false ) ? wp_strip_all_tags( $post->post_excerpt, true ) : $post->post_excerpt;
	} elseif ( ! empty( $post->post_content ) ) {
		if ( 0 == $char_length ) {
			$content = ( $allow_html == false ) ? wp_strip_all_tags( $post->post_content, true ) : $post->post_content;
		} else {
			$excerpt = wp_strip_all_tags( $post->post_content, true );
			$char_length++;		

			if ( mb_strlen( $excerpt ) > $char_length ) {
				$subex = mb_substr( $excerpt, 0, $char_length - 5 );
				$exwords = explode( ' ', $subex );
				$excut = - ( mb_strlen( $exwords[ count( $exwords ) - 1 ] ) );
				if ( $excut < 0 ) {
					$content = mb_substr( $subex, 0, $excut );
				} else {
					$content = $subex;
				}
				$content .= $append;
			} else {
				$content = $excerpt;
			}
		}
	}

	$content = trim( $content );
	return apply_filters( 'aiovg_excerpt', $content, $post_id, $char_length, $append, $allow_html );	
}

/**
 * Get the file extension.
 *
 * @since  2.4.4
 * @param  string $url     File URL.
 * @param  string $default Default file extension.
 * @return string $ext     File extension.
 */
function aiovg_get_file_ext( $url, $default = 'mp4' ) {
	if ( $ext = pathinfo( $url, PATHINFO_EXTENSION ) ) {
		return $ext;
	}

	return $default;
}

/**
 * Get image data for the given object ID.
 *
 * @since  2.6.3
 * @param  int    $object_id         The ID of the object image is for.
 * @param  string $size              Size of the image.
 * @param  string $object_type       "post" or "term".
 * @param  bool   $placeholder_image When the object has no image, returns a placeholder image if set to true.
 * @return array                     The Image data.
 */
function aiovg_get_image( $object_id, $size = "large", $object_type = "post", $placeholder_image = false ) {
	$image_data = array(
		'src' => '',
		'alt' => ''
	);

	$object_id = (int) $object_id;
	$attach_id = 0;
	
	if ( 'term' == $object_type ) { 
		// Get image from terms
		if ( $attach_id = (int) get_term_meta( $object_id, 'image_id', true ) ) {
			$attributes = wp_get_attachment_image_src( $attach_id, $size );	

			if ( ! empty( $attributes ) ) {
				$image_data['src'] = $attributes[0];
				$image_data['alt'] = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );
			} else {
				$attach_id = 0;
			}	
		}

		if ( empty( $attach_id ) ) {
			$image_url = get_term_meta( $object_id, 'image', true );
			
			if ( ! empty( $image_url ) ) {
				$image_data['src'] = $image_url;
			}
		}
	} else { 
		// Get image from posts
		if ( $attach_id = (int) get_post_meta( $object_id, 'image_id', true ) ) {
			$attributes = wp_get_attachment_image_src( $attach_id, $size );	

			if ( ! empty( $attributes ) ) {
				$image_data['src'] = $attributes[0];
				$image_data['alt'] = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );
			} else {
				$attach_id = 0;
			}			
		}

		if ( empty( $attach_id ) ) {
			$image_url = get_post_meta( $object_id, 'image', true );

			if ( ! empty( $image_url ) ) {
				$image_data['src'] = $image_url;
			} else {
				if ( $attach_id = (int) get_post_meta( $object_id, '_thumbnail_id', true ) ) {
					$attributes = wp_get_attachment_image_src( $attach_id, $size );

					if ( ! empty( $attributes ) ) {
						$image_data['src'] = $attributes[0];
						$image_data['alt'] = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );
					}		
				}
			}
		}

		// Set custom alt text if available
		$image_alt = get_post_meta( $object_id, 'image_alt', true );
		if ( ! empty( $image_alt ) ) {
			$image_data['alt'] = $image_alt;
		}
	}
	
	// Set default image
	if ( empty( $image_data['src'] ) && ! empty( $placeholder_image ) ) {
		$image_data['src'] = AIOVG_PLUGIN_PLACEHOLDER_IMAGE_URL;
	}
	
	// Return
	return apply_filters( 'aiovg_get_image', $image_data, $object_id, $size, $object_type, $placeholder_image );
}

/**
 * Get image URL using the attachment ID.
 *
 * @since  1.0.0
 * @param  int    $id      Attachment ID.
 * @param  string $size    Size of the image.
 * @param  string $default Default image URL.
 * @param  string $type    "gallery" or "player".
 * @return string $url     Image URL.
 */
function aiovg_get_image_url( $id, $size = "large", $default = '', $type = 'gallery' ) {
	$url = '';
	
	// Get image from attachment
	if ( $id ) {
		$attributes = wp_get_attachment_image_src( (int) $id, $size );
		if ( ! empty( $attributes ) ) {
			$url = $attributes[0];
		}
	}
	
	// Set default image
	if ( ! empty( $default ) ) {
		$default = aiovg_make_url_absolute( $default );
	} else {
		if ( 'gallery' == $type ) {
			$default = AIOVG_PLUGIN_PLACEHOLDER_IMAGE_URL;
		}
	}	
	
	if ( empty( $url ) ) {
		$url = $default;
	}
	
	// Return image url
	return apply_filters( 'aiovg_image_url', $url, $id, $size, $default, $type );
}

/**
 * Get the client IP Address.
 *
 * @since  2.0.0
 * @return string $ip_address The client IP Address.
 */
function aiovg_get_ip_address() {
	// Whether ip is from share internet
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip_address = $_SERVER['HTTP_CLIENT_IP'];
	}
	
	// Whether ip is from proxy
	elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	
	// Whether ip is from remote address
	else {
		$ip_address = $_SERVER['REMOTE_ADDR'];
	}
	
	return $ip_address;		
}

/**
 * Get message to display based on the $type input.
 *
 * @since  1.0.0
 * @param  string $msg_id  Message Identifier.
 * @return string $message Message to display.
 */
function aiovg_get_message( $msg_id ) {
	$message = '';
	
	switch ( $msg_id ) {
		case 'videos_empty':
			$message = __( 'No videos found', 'all-in-one-video-gallery' );
			break;
		case 'categories_empty':
			$message = __( 'No categories found', 'all-in-one-video-gallery' );
			break;
		case 'tags_empty':
			$message = __( 'No tags found', 'all-in-one-video-gallery' );
			break;
		case 'login_required':
			$message = __( 'Sorry, you need to login to view this content.', 'all-in-one-video-gallery' );
			break;
	}
	
	return $message;	
}

/**
 * Get MySQL's RAND function seed value.
 * 
 * @since  1.6.5
 * @return string $seed Seed value.
 */
function aiovg_get_orderby_rand_seed( $paged = 'no_longer_required' ) {
	$seed = '';
	
	if ( isset( $_COOKIE['aiovg_rand_seed'] ) ) {
		$seed = (int) $_COOKIE['aiovg_rand_seed'];	
	}

	return $seed;
}

/**
 * Get current page number.
 *
 * @since  1.0.0
 * @return int   $paged The current page number.
 */
function aiovg_get_page_number() {
	global $paged;
	
	if ( get_query_var( 'paged' ) ) {
    	$paged = get_query_var( 'paged' );
	} elseif ( get_query_var( 'page' ) ) {
    	$paged = get_query_var( 'page' );
	} else {
		$paged = 1;
	}
    	
	return absint( $paged );		
}

/**
 * Get the sorting options for the search form.
 * 
 * @since  3.8.4
 * @return array $options Array of options.
 */
function aiovg_get_search_form_sort_options() {
	$options = array(
		'title-asc'  => __( 'Title - Ascending', 'all-in-one-video-gallery' ),
		'title-desc' => __( 'Title - Descending', 'all-in-one-video-gallery' ),		
		'date-desc'  => __( 'Newest First', 'all-in-one-video-gallery' ), 
		'date-asc'   => __( 'Oldest First', 'all-in-one-video-gallery' ),                       
		'views-desc' => __( 'Most Viewed', 'all-in-one-video-gallery' )
	);

	$likes_settings = get_option( 'aiovg_likes_settings' );
	if ( ! empty( $likes_settings['like_button'] ) ) {
		$options['likes-desc'] = __( 'Most Liked', 'all-in-one-video-gallery' );
	}

	return apply_filters( 'aiovg_search_form_sort_options', $options );
}

/**
 * Get shortcode builder form fields.
 *
 * @since 1.5.7
 */
function aiovg_get_shortcode_fields() {
	$defaults            = aiovg_get_default_settings();
	$categories_settings = array_merge( $defaults['aiovg_categories_settings'], get_option( 'aiovg_categories_settings', array() ) );
	$videos_settings     = array_merge( $defaults['aiovg_videos_settings'], get_option( 'aiovg_videos_settings', array() ) );
	$player_settings     = array_merge( $defaults['aiovg_player_settings'], get_option( 'aiovg_player_settings', array() ) );
	$images_settings     = array_merge( $defaults['aiovg_images_settings'], (array) get_option( 'aiovg_images_settings', array() ) );
	
	// Fields	
	$fields = array(
		'video' => array(
			'title'    => __( 'Single Video', 'all-in-one-video-gallery' ),
			'sections' => array(
				'general' => array(
					'title'  => __( 'General', 'all-in-one-video-gallery' ),
					'fields' => array(
						array(
							'name'        => 'id',
							'label'       => __( 'Select Video', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'video',
							'value'       => 0
						),
						array(
							'name'        => 'type',
							'label'       => __( 'Source Type', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => aiovg_get_video_source_types(),
							'value'       => 'default'
						),
						array(
							'name'        => 'mp4',
							'label'       => __( 'Video', 'all-in-one-video-gallery' ),
							'description' => __( 'Enter your direct file URL in the textbox above (OR) upload your file using the "Upload File" link.', 'all-in-one-video-gallery' ),
							'type'        => 'media',
							'value'       => ''
						),
						array(
							'name'        => 'hls',
							'label'       => __( 'HLS', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://www.mysite.com/stream.m3u8', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'dash',
							'label'       => __( 'MPEG-DASH', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://www.mysite.com/stream.mpd', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'youtube',
							'label'       => __( 'YouTube', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://www.youtube.com/watch?v=twYp6W6vt2U', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'vimeo',
							'label'       => __( 'Vimeo', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://vimeo.com/108018156', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'dailymotion',
							'label'       => __( 'Dailymotion', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://www.dailymotion.com/video/x11prnt', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'rumble',
							'label'       => __( 'Rumble', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://rumble.com/val8vm-how-to-use-rumble.html', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'facebook',
							'label'       => __( 'Facebook', 'all-in-one-video-gallery' ),
							'description' => sprintf( '%s: https://www.facebook.com/facebook/videos/10155278547321729', __( 'Example', 'all-in-one-video-gallery' ) ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'poster',
							'label'       => __( 'Image', 'all-in-one-video-gallery' ),
							'description' => __( 'Enter your direct file URL in the textbox above (OR) upload your file using the "Upload File" link.', 'all-in-one-video-gallery' ),
							'type'        => 'media',
							'value'       => ''
						),
						array(
							'name'        => 'width',
							'label'       => __( 'Width', 'all-in-one-video-gallery' ),
							'description' => __( 'In pixels. Maximum width of the player. Leave this field empty to scale 100% of its enclosing container/html element.', 'all-in-one-video-gallery' ),
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'ratio',
							'label'       => __( 'Height (Ratio)', 'all-in-one-video-gallery' ),
							'description' => __( "In percentage. 1 to 100. Calculate player's height using the ratio value entered.", 'all-in-one-video-gallery' ),
							'type'        => 'text',
							'value'       => $player_settings['ratio']
						),
						array(
							'name'        => 'autoplay',
							'label'       => __( 'Autoplay', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $player_settings['autoplay']
						),
						array(
							'name'        => 'loop',
							'label'       => __( 'Loop', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $player_settings['loop']
						),
						array(
							'name'        => 'muted',
							'label'       => __( 'Muted', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $player_settings['muted']
						)					
					)
				),
				'controls' => array(
					'title'  => __( 'Player Controls', 'all-in-one-video-gallery' ),
					'fields' => array(
						array(
							'name'        => 'playpause',
							'label'       => __( 'Play / Pause', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['playpause'] )
						),
						array(
							'name'        => 'current',
							'label'       => __( 'Current Time', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['current'] )
						),
						array(
							'name'        => 'progress',
							'label'       => __( 'Progressbar', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['progress'] )
						),
						array(
							'name'        => 'duration',
							'label'       => __( 'Duration', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['duration'] )
						),
						array(
							'name'        => 'tracks',
							'label'       => __( 'Subtitles', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['tracks'] )
						),
						array(
							'name'        => 'chapters',
							'label'       => __( 'Chapters', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['chapters'] )
						),						
						array(
							'name'        => 'speed',
							'label'       => __( 'Speed Control', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['speed'] )
						),
						array(
							'name'        => 'quality',
							'label'       => __( 'Quality Selector', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['quality'] )
						),
						array(
							'name'        => 'volume',
							'label'       => __( 'Volume Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['volume'] )
						),
						array(
							'name'        => 'pip',
							'label'       => __( 'Picture-in-Picture Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['pip'] )
						),
						array(
							'name'        => 'fullscreen',
							'label'       => __( 'Fullscreen Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['fullscreen'] )
						),
						array(
							'name'        => 'share',
							'label'       => __( 'Share Buttons', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['share'] )
						),
						array(
							'name'        => 'embed',
							'label'       => __( 'Embed Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['embed'] )
						),
						array(
							'name'        => 'download',
							'label'       => __( 'Download Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $player_settings['controls']['download'] )
						)
					)
				)
			)
		),		
		'videos' => array(
			'title'    => __( 'Video Gallery', 'all-in-one-video-gallery' ),
			'sections' => array(
				'general' => array(
					'title'  => __( 'General', 'all-in-one-video-gallery' ),
					'fields' => array(
						array(
							'name'        => 'title',
							'label'       => __( 'Title', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'template',
							'label'       => __( 'Select Template', 'all-in-one-video-gallery' ),
							'description' => ( aiovg_fs()->is_not_paying() ? sprintf( __( '<a href="%s" target="_blank">Upgrade Pro</a> for more templates (Popup, Inline, Slider, Playlist, Compact, etc.)', 'all-in-one-video-gallery' ), esc_url( aiovg_fs()->get_upgrade_url() ) ) : '' ),
							'type'        => 'select',
							'options'     => aiovg_get_video_templates(),
							'value'       => $videos_settings['template']
						),
						array(
							'name'        => 'category',
							'label'       => __( 'Select Categories', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'categories',
							'value'       => array()
						),
						array(
							'name'        => 'tag',
							'label'       => __( 'Select Tags', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'tags',
							'value'       => array()
						),
						array(
							'name'        => 'include',
							'label'       => __( 'Include Video ID(s)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => ''
						),	
						array(
							'name'        => 'exclude',
							'label'       => __( 'Exclude Video ID(s)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => ''
						),							
						array(
							'name'        => 'orderby',
							'label'       => __( 'Order By', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'title'      => __( 'Title', 'all-in-one-video-gallery' ),
								'date'       => __( 'Date Added', 'all-in-one-video-gallery' ),								
								'views'      => __( 'Views Count', 'all-in-one-video-gallery' ),
								'likes'      => __( 'Likes Count', 'all-in-one-video-gallery' ),
                        		'dislikes'   => __( 'Dislikes Count', 'all-in-one-video-gallery' ),
								'rand'       => __( 'Random', 'all-in-one-video-gallery' ),
								'menu_order' => __( 'Menu Order', 'all-in-one-video-gallery' )
							),
							'value'       => $videos_settings['orderby']
						),
						array(
							'name'        => 'order',
							'label'       => __( 'Order', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'asc'  => __( 'ASC', 'all-in-one-video-gallery' ),
								'desc' => __( 'DESC', 'all-in-one-video-gallery' )
							),
							'value'       => $videos_settings['order']
						),
						array(
							'name'        => 'featured',
							'label'       => __( 'Featured Only', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'related',
							'label'       => __( 'Follow URL', 'all-in-one-video-gallery' ) . ' (' . __( 'Related Videos', 'all-in-one-video-gallery' ) . ')',
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 0
						),					
					)
				),
				'gallery' => array(
					'title'  => __( 'Gallery', 'all-in-one-video-gallery' ),
					'fields' => array(										
						array(
							'name'        => 'ratio',
							'label'       => __( 'Height (Ratio)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => $images_settings['ratio']
						),	
						array(
							'name'        => 'columns',
							'label'       => __( 'Columns', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'number',
							'min'         => 1,
							'max'         => 12,
							'step'        => 1,
							'value'       => $videos_settings['columns']
						),
						array(
							'name'        => 'limit',
							'label'       => __( 'Limit (per page)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'number',
							'min'         => 0,
							'max'         => 500,
							'step'        => 1,
							'value'       => $videos_settings['limit']
						),
						array(
							'name'        => 'thumbnail_style',
							'label'       => __( 'Image Position', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'standard'   => __( 'Top', 'all-in-one-video-gallery' ),
								'image-left' => __( 'Left', 'all-in-one-video-gallery' )
							),
							'value'       => $videos_settings['thumbnail_style']
						),
						array(
							'name'        => 'display',
							'label'       => __( 'Show / Hide (Thumbnails)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'header',
							'value'       => 0
						),
						array(
							'name'        => 'show_count',
							'label'       => __( 'Videos Count', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['count'] )
						),
						array(
							'name'        => 'show_title',
							'label'       => __( 'Video Title', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['title'] )
						),
						array(
							'name'        => 'show_category',
							'label'       => __( 'Category Name(s)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['category'] )
						),
						array(
							'name'        => 'show_tag',
							'label'       => __( 'Tag Name(s)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['tag'] )
						),				
						array(
							'name'        => 'show_date',
							'label'       => __( 'Date Added', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['date'] )
						),
						array(
							'name'        => 'show_user',
							'label'       => __( 'Author Name', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['user'] )
						),						
						array(
							'name'        => 'show_views',
							'label'       => __( 'Views Count', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['views'] )
						),
						array(
							'name'        => 'show_likes',
							'label'       => __( 'Likes Count', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['likes'] )
						),
						array(
							'name'        => 'show_dislikes',
							'label'       => __( 'Dislikes Count', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['dislikes'] )
						),		
						array(
							'name'        => 'show_duration',
							'label'       => __( 'Video Duration', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['duration'] )
						),
						array(
							'name'        => 'show_excerpt',
							'label'       => __( 'Video Excerpt (Short Description)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => isset( $videos_settings['display']['excerpt'] )
						),
						array(
							'name'        => 'title_length',
							'label'       => __( 'Title Length', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'number',
							'value'       => isset( $videos_settings['title_length'] ) ? $videos_settings['title_length'] : 0
						),
						array(
							'name'        => 'excerpt_length',
							'label'       => __( 'Excerpt Length', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'number',
							'value'       => $videos_settings['excerpt_length']
						),
						array(
							'name'        => 'show_pagination',
							'label'       => __( 'Pagination', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 1
						),
						array(
							'name'        => 'show_more',
							'label'       => __( 'More Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'more_label',
							'label'       => __( 'More Button Label', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => __( 'Show More', 'all-in-one-video-gallery' )
						),
						array(
							'name'        => 'more_link',
							'label'       => __( 'More Button Link', 'all-in-one-video-gallery' ),
							'description' => __( 'Leave this field blank to use Ajax', 'all-in-one-video-gallery' ),
							'type'        => 'url',
							'value'       => ''
						),
					)
				),
				'filters' => array(
					'title'  => __( 'Filters & Search Form', 'all-in-one-video-gallery' ),
					'fields' => array(						
						array(
							'name'        => 'filters_keyword',
							'label'       => __( 'Filter By Video Title, Description', 'all-in-one-video-gallery' ),
							'description' => __( 'Enable keyword search that allows visitors to filter videos by matching words in the title or description.', 'all-in-one-video-gallery' ),
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'filters_category',
							'label'       => __( 'Filter By Categories', 'all-in-one-video-gallery' ),
							'description' => __( 'Allow visitors to filter videos based on assigned categories.', 'all-in-one-video-gallery' ),
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'filters_tag',
							'label'       => __( 'Filter By Tags', 'all-in-one-video-gallery' ),
							'description' => __( 'Allow visitors to filter videos based on assigned tags.', 'all-in-one-video-gallery' ),
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'filters_sort',
							'label'       => __( 'Sort By Dropdown', 'all-in-one-video-gallery' ),
							'description' => __( 'Enable a dropdown to let visitors sort videos by options like date, title, or popularity.', 'all-in-one-video-gallery' ),
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'filters_template',
							'label'       => __( 'Filters Template', 'all-in-one-video-gallery' ),
							'description' => __( 'Choose how the filters will be displayed — vertically (stacked) or horizontally (inline).', 'all-in-one-video-gallery' ),
							'type'        => 'select',
							'options'     => array(
								'vertical'   => __( 'Vertical', 'all-in-one-video-gallery' ),
								'horizontal' => __( 'Horizontal', 'all-in-one-video-gallery' )
							),
							'value'       => 'horizontal'
						),						
						array(
							'name'        => 'filters_mode',
							'label'       => __( 'Filters Mode', 'all-in-one-video-gallery' ),
							'description' => __( 'How should the filter form behave when users interact with it?', 'all-in-one-video-gallery' ),
							'type'        => 'select',
							'options'     => array(
								'live'   => __( 'Live - Update instantly', 'all-in-one-video-gallery' ),								
								'ajax'   => __( 'Ajax - Update instantly without page reload', 'all-in-one-video-gallery' ),
								'search' => __( 'Search - Update on button click', 'all-in-one-video-gallery' )
							),
							'value'       => 'live'
						),
						array(
							'name'        => 'filters_position',
							'label'       => __( 'Filters Position', 'all-in-one-video-gallery' ),
							'description' => __( 'Decide where the filters should appear — above the gallery (Top), on the left, or on the right.', 'all-in-one-video-gallery' ),
							'type'        => 'select',
							'options'     => array(
								'top'   => __( 'Top', 'all-in-one-video-gallery' ),
								'left'  => __( 'Left', 'all-in-one-video-gallery' ),
								'right' => __( 'Right', 'all-in-one-video-gallery' )
							),
							'value'       => 'top'
						)
					)
				)
			)
		),
		'categories' => array(
			'title'    => __( 'Categories', 'all-in-one-video-gallery' ),
			'sections' => array(
				'general' => array(
					'title'  => __( 'General', 'all-in-one-video-gallery' ),
					'fields' => array(
						array(
							'name'        => 'title',
							'label'       => __( 'Title', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'template',
							'label'       => __( 'Select Template', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'grid'     => __( 'Grid', 'all-in-one-video-gallery' ),
								'list'     => __( 'List', 'all-in-one-video-gallery' ),
								'dropdown' => __( 'Dropdown', 'all-in-one-video-gallery' )
							),
							'value'       => $categories_settings['template']
						),
						array(
							'name'        => 'id',
							'label'       => __( 'Select Parent', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'parent',
							'options'     => array(),
							'value'       => 0
						),
						array(
							'name'        => 'include',
							'label'       => __( 'Include Category ID(s)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => ''
						),
						array(
							'name'        => 'exclude',
							'label'       => __( 'Exclude Category ID(s)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => ''
						),					
						array(
							'name'        => 'ratio',
							'label'       => __( 'Height (Ratio)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'text',
							'value'       => $images_settings['ratio']
						),
						array(
							'name'        => 'columns',
							'label'       => __( 'Columns', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'number',
							'min'         => 1,
							'max'         => 12,
							'step'        => 1,
							'value'       => $categories_settings['columns']
						),
						array(
							'name'        => 'limit',
							'label'       => __( 'Limit (per page)', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'number',
							'min'         => 0,
							'max'         => 500,
							'step'        => 1,
							'value'       => $categories_settings['limit']
						),
						array(
							'name'        => 'orderby',
							'label'       => __( 'Order By', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'id'         => __( 'ID', 'all-in-one-video-gallery' ),
								'count'      => __( 'Count', 'all-in-one-video-gallery' ),
								'name'       => __( 'Name', 'all-in-one-video-gallery' ),
								'slug'       => __( 'Slug', 'all-in-one-video-gallery' ),
								'menu_order' => __( 'Menu Order', 'all-in-one-video-gallery' )
							),
							'value'       => $categories_settings['orderby']
						),
						array(
							'name'        => 'order',
							'label'       => __( 'Order', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'asc'  => __( 'ASC', 'all-in-one-video-gallery' ),
								'desc' => __( 'DESC', 'all-in-one-video-gallery' )
							),
							'value'       => $categories_settings['order']
						),
						array(
							'name'        => 'hierarchical',
							'label'       => __( 'Show Hierarchy', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $categories_settings['hierarchical']
						),
						array(
							'name'        => 'show_description',
							'label'       => __( 'Show Description', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $categories_settings['show_description']
						),
						array(
							'name'        => 'show_count',
							'label'       => __( 'Show Videos Count', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $categories_settings['show_count']
						),
						array(
							'name'        => 'hide_empty',
							'label'       => __( 'Hide Empty Categories', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => $categories_settings['hide_empty']
						),
						array(
							'name'        => 'show_pagination',
							'label'       => __( 'Show Pagination', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 1
						)
					)
				)
			)
		),		
		'search_form' => array(
			'title'    => __( 'Search Form', 'all-in-one-video-gallery' ),
			'sections' => array(
				'general' => array(
					'title'  => __( 'General', 'all-in-one-video-gallery' ),
					'fields' => array(
						array(
							'name'        => 'template',
							'label'       => __( 'Select Template', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'select',
							'options'     => array(
								'vertical'   => __( 'Vertical', 'all-in-one-video-gallery' ),
								'horizontal' => __( 'Horizontal', 'all-in-one-video-gallery' )
							),
							'value'       => 'horizontal'
						),
						array(
							'name'        => 'keyword',
							'label'       => __( 'Search By Video Title, Description', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 1
						),
						array(
							'name'        => 'category',
							'label'       => __( 'Search By Categories', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'tag',
							'label'       => __( 'Search By Tags', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 0
						),
						array(
							'name'        => 'sort',
							'label'       => __( 'Sort By Dropdown', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 0
						),						
						array(
							'name'        => 'search_button',
							'label'       => __( 'Search Button', 'all-in-one-video-gallery' ),
							'description' => '',
							'type'        => 'checkbox',
							'value'       => 1
						),
						array(
							'name'        => 'target',
							'label'       => __( 'Search Results Page', 'all-in-one-video-gallery' ),
							'description' => __( 'The selected "Search Results Page" must include the [aiovg_search] shortcode, which will be replaced by the search results.', 'all-in-one-video-gallery' ),
							'type'        => 'select',
							'options'     => array(
								'default' => __( "Use Plugin's Default Search Results Page", 'all-in-one-video-gallery' ),
								'current' => __( 'Display Results on Current Page', 'all-in-one-video-gallery' )
							),
							'value'       => 'default'
						)
					)
				)
			)
		)
	);

	return apply_filters( 'aiovg_shortcode_fields', $fields );
}

/**
 * Get temporary file download ID.
 * 
 * @since  2.6.1
 * @param  string $path File path.
 * @return string       Download ID. 
 */
function aiovg_get_temporary_file_download_id( $path ) {
	$transient_key = md5( $path );
	
	delete_transient( $transient_key );
	set_transient( $transient_key, $path, 1 * HOUR_IN_SECONDS );
	
	return $transient_key;
}

/**
 * Retrieves the date on which the video was added.
 * 
 * @since  3.6.3
 * @return string Date the current video was added. 
 */
function aiovg_get_the_date() {
	$general_settings = get_option( 'aiovg_general_settings' );

	if ( isset( $general_settings['datetime_format'] ) && ! empty( $general_settings['datetime_format'] ) ) {
		$date = get_the_date( $general_settings['datetime_format'] );
	} else {
		$date = sprintf( 
			__( '%s ago', 'all-in-one-video-gallery' ), 
			human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) 
		);
	}

	return apply_filters( 'aiovg_get_the_date', $date );
}

/**
 * Get unique ID.
 *
 * @since  1.5.7
 * @return string Unique ID.
 */
function aiovg_get_uniqid() {
	global $aiovg;

	if ( ! isset( $aiovg['uniqid'] ) ) {
		$aiovg['uniqid'] = 0;
	}

	return uniqid() . ++$aiovg['uniqid'];
}

/**
 * Get video source types.
 * 
 * @since  1.0.0
 * @param  bool  $is_admin True if admin, false if not
 * @return array Array of source types.
 */
function aiovg_get_video_source_types( $is_admin = false ) {
	$types = array(
		'default'     => __( 'Self Hosted', 'all-in-one-video-gallery' ) . ' / ' . __( 'External URL', 'all-in-one-video-gallery' ),
		'adaptive'    => __( 'Adaptive / Live Streaming', 'all-in-one-video-gallery' ),
		'youtube'     => __( 'YouTube', 'all-in-one-video-gallery' ),
		'vimeo'       => __( 'Vimeo', 'all-in-one-video-gallery' ),
		'dailymotion' => __( 'Dailymotion', 'all-in-one-video-gallery' ),
		'rumble'      => __( 'Rumble', 'all-in-one-video-gallery' ),
		'facebook'    => __( 'Facebook', 'all-in-one-video-gallery' )
	);

	if ( $is_admin && current_user_can( 'unfiltered_html' ) ) {
		$types['embedcode'] = __( 'Third-Party Player Code', 'all-in-one-video-gallery' );
	}
	
	return apply_filters( 'aiovg_video_source_types', $types );
}

/**
 * Get video templates.
 *
 * @since 1.5.7
 * @return array Array of video templates.
 */
function aiovg_get_video_templates() {
	$templates = array(
		'classic' => __( 'Classic', 'all-in-one-video-gallery' )
	);
	
	return apply_filters( 'aiovg_video_templates', $templates );
}

/**
 * Get a list of user roles.
 *
 * @since  3.9.6
 * @return array Array of user roles.
 */
function aiovg_get_user_roles() {
	$roles = wp_roles()->get_names();
	asort( $roles );
	
	return $roles;
}

/**
 * Inserts a new key/value after the key in the array.
 *
 * @since  1.0.0
 * @param  string $key       The key to insert after.
 * @param  array  $array     An array to insert in to.
 * @param  array  $new_array An array to insert.
 * @return                   The new array if the key exists, FALSE otherwise.
 */
function aiovg_insert_array_after( $key, $array, $new_array ) {
	if ( array_key_exists( $key, $array ) ) {
    	$new = array();
    	foreach ( $array as $k => $value ) {
      		$new[ $k ] = $value;
      		if ( $k === $key ) {
				foreach ( $new_array as $new_key => $new_value ) {
        			$new[ $new_key ] = $new_value;
				}
      		}
    	}
    	return $new;
  	}
		
  	return $array;  
}

/**
 * Insert required custom pages and return their IDs as array.
 * 
 * @since  1.0.0
 * @return array Array of created page IDs.
 */
function aiovg_insert_custom_pages() {
	// Vars
	if ( false == get_option( 'aiovg_page_settings' ) ) {		
		$pages = array();
		$page_definitions = aiovg_get_custom_pages_list();
		
		foreach ( $page_definitions as $slug => $page ) {
			$page_check = get_page_by_title( $page['title'] );

			if ( ! isset( $page_check->ID ) ) {
				$id = wp_insert_post(
					array(
						'post_title'     => $page['title'],
						'post_content'   => $page['content'],
						'post_status'    => 'publish',
						'post_author'    => 1,
						'post_type'      => 'page',
						'comment_status' => 'closed'
					)
				);
					
				$pages[ $slug ] = $id;	
			} else {
				$pages[ $slug ] = $page_check->ID;	
			}		
		}
	} else {
		$pages = get_option( 'aiovg_page_settings' );
	}

	return $pages;
}

/**
 * Insert missing custom pages.
 * 
 * @since 2.4.3
 */
function aiovg_insert_missing_pages() {
	$pages = get_option( 'aiovg_page_settings' );
	$page_definitions = aiovg_get_custom_pages_list();		

	foreach ( $page_definitions as $slug => $page ) {
		if ( ! array_key_exists( $slug, $pages ) ) {
			$page_check = get_page_by_title( $page['title'] );

			if ( ! isset( $page_check->ID ) ) {
				$id = wp_insert_post(
					array(
						'post_title'     => $page['title'],
						'post_content'   => $page['content'],
						'post_status'    => 'publish',
						'post_author'    => 1,
						'post_type'      => 'page',
						'comment_status' => 'closed'
					)
				);
					
				$pages[ $slug ] = $id;	
			} else {
				$pages[ $slug ] = $page_check->ID;	
			}	
		}	
	}

	update_option( 'aiovg_page_settings', $pages );
}

/**
 * Check whether the current post/page uses Gutenberg editor.
 *
 * @since  1.6.2
 * @return bool  True if the post/page uses Gutenberg, false if not.
 */
function aiovg_is_gutenberg_page() {
    if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
        // The Gutenberg plugin is on
        return true;
    }
	
    $current_screen = get_current_screen();
    if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
        // Gutenberg page on 5+
        return true;
    }
	
    return false;
}

/**
 * Check if Yoast or the Rank Math SEO plugin is active.
 *
 * @since  3.9.5
 * @return bool  True if active, false if not.
 */
function aiovg_is_yoast_or_rank_math_active() {	
	$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

	// Check if Yoast SEO plugin is active
	if ( in_array( 'wordpress-seo/wp-seo.php', $active_plugins ) || in_array( 'wordpress-seo-premium/wp-seo-premium.php', $active_plugins ) ) {
		return true;
	}

	// Check if Rank Math SEO plugin is active
	if ( in_array( 'seo-by-rank-math/rank-math.php', $active_plugins ) ) {
		return true;
	}

	return false;
}

/**
 * Convert relative file paths into absolute URLs.
 * 
 * @since  3.8.4
 * @param  string $url Input file URL.
 * @return string $url Absolute file URL.
 */
function aiovg_make_url_absolute( $url ) {
    if ( empty( $url ) ) {
        return $url;
    }

    // Trim any unnecessary whitespaces
    $url = trim( $url );

    // If there's no host, it's likely a relative URL
	$host = parse_url( $url, PHP_URL_HOST );

	if ( empty( $host ) ) {
		// Prepend the site URL to make it absolute
		$url = get_site_url( null, $url );
	}

    return $url;
}

/**
 * Prepares shortcode attributes for AJAX requests.
 *
 * This function removes unwanted attributes from the shortcode attributes array
 * before passing it as JSON parameters in AJAX requests.
 *
 * @since  4.0.1
 * @param  array $attributes Shortcode attributes.
 * @return array             Filtered attributes suitable for AJAX requests.
 */
function aiovg_prepare_attributes_for_ajax( $attributes ) {    
    $exclude_attributes = array(
		'display',
    	'filters', 
		'filters_keyword',
		'filters_category',
		'filters_tag',
		'filters_sort',
		'filters_template', 
		'filters_mode', 
		'filters_position'
    );

    foreach ( $exclude_attributes as $attribute ) {
        unset( $attributes[ $attribute ] );
    }

    return $attributes;
}


/**
  * Removes an item or list from the query string.
  *
  * @since  1.0.0
  * @param  string|array $key   Query key or keys to remove.
  * @param  bool|string  $query When false uses the $_SERVER value. Default false.
  * @return string              New URL query string.
  */
function aiovg_remove_query_arg( $key, $query = false ) {
	if ( is_array( $key ) ) { // removing multiple keys
		foreach ( $key as $k ) {
			$query = str_replace( '#038;', '&', $query );
			$query = add_query_arg( $k, false, $query );
		}
		
		return $query;
	}
		
	return add_query_arg( $key, false, $query );	
}

/**
 * Convert relative file paths into absolute URLs.
 * 
 * @since      2.4.0
 * @deprecated 3.8.4  Use aiovg_make_url_absolute() instead.
 * @param      string $url Input file URL.
 * @return     string      Absolute file URL.
 */
function aiovg_resolve_url( $url ) {
	return aiovg_make_url_absolute( $url );
}

/**
 * Sanitize the array inputs.
 *
 * @since  1.0.0
 * @param  array $value Input array.
 * @return array        Sanitized array.
 */
function aiovg_sanitize_array( $value ) {
	return ! empty( $value ) ? array_map( 'sanitize_text_field', $value ) : array();
}

/**
 * Sanitize the integer inputs, accepts empty values.
 *
 * @since  1.0.0
 * @param  string|int $value Input value.
 * @return string|int        Sanitized value.
 */
function aiovg_sanitize_int( $value ) {
	$value = intval( $value );
	return ( 0 == $value ) ? '' : $value;	
}

/**
 * Sanitize the URLs. Accepts relative file paths, spaces.
 *
 * @since  2.4.0
 * @param  string $value Input value.
 * @return string        Sanitized value.
 */
function aiovg_sanitize_url( $value ) {
	if ( ! empty( $value ) ) {
		$value = trim( $value );
	}
	
	return sanitize_url( $value );
}

/**
 * Trims text to a certain number of characters.
 *
 * @since  2.5.8
 * @param  string $text        Text to be trimmed.
 * @param  int    $char_length Character length.
 * @param  string $append      String to append to the end of the trimmed content.
 * @return string $text        Trimmed text.
 */
function aiovg_truncate( $text, $char_length = 55, $append = '...' ) {
	if ( empty( $char_length ) )	{
		return $text;
	}

	$text = strip_tags( $text );
	
	if ( $char_length > 0 && strlen( $text ) > $char_length ) {
		$tmp = substr( $text, 0, $char_length );
		$tmp = substr( $tmp, 0, strrpos( $tmp, ' ' ) );

		if ( strlen( $tmp ) >= $char_length - 3 ) {
			$tmp = substr( $tmp, 0, strrpos( $tmp, ' ' ) );
		}

		$text = $tmp . '...';
	}

	return $text;
}

/**
 * Update video views count.
 *
 * @since 1.0.0
 * @param int   $post_id Post ID
 */
function aiovg_update_views_count( $post_id ) {	
	$privacy_settings = get_option( 'aiovg_privacy_settings' );
	
	if ( isset( $privacy_settings['disable_cookies'] ) && isset( $privacy_settings['disable_cookies']['aiovg_videos_views'] ) ) {
		$count = (int) get_post_meta( $post_id, 'views', true );
		update_post_meta( $post_id, 'views', ++$count );
	} else {			
		$visited = array();

		if ( isset( $_COOKIE['aiovg_videos_views'] ) ) {
			$visited = explode( '|', $_COOKIE['aiovg_videos_views'] );
			$visited = array_map( 'intval', $visited );
		}

		if ( ! in_array( $post_id, $visited ) ) {
			$count = (int) get_post_meta( $post_id, 'views', true );
			update_post_meta( $post_id, 'views', ++$count );

			// SetCookie
			$visited[] = $post_id;
			setcookie( 'aiovg_videos_views', implode( '|', $visited ), time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		}
	}
}
