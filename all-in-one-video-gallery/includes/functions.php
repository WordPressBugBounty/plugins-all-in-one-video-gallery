<?php

/**
 * Helper Functions.
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

		// URLs from Vimeo/Dailymotion don't have a file extension. So, we manually set the file info.
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

	// Validate mime type
	$mime_type = wp_get_image_mime( $image_url );

	if ( $mime_type == false ) {
		return 0;
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

	// Get image data
	$image_data = file_get_contents( $image_url );

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
		if ( count( $tracks ) ) {
			foreach ( $tracks as $key => $track ) {
				if ( 'src_id' == $key ) wp_delete_attachment( (int) $track['src_id'], true );
			}
		}
	}
}

/**
 * Displays or retrieves the HTML dropdown list of terms.
 * 
 * @since  3.8.4
 * @param  array  $args Array of arguments to generate a terms drop-down element. 
 * @return string       HTML dropdown list of terms.
 */
function aiovg_dropdown_terms( $args ) {
	$multiple = isset( $args['multiple'] ) ? (bool) $args['multiple'] : true;
	$echo     = isset( $args['echo'] ) ? (bool) $args['echo'] : true;	

	if ( ! $multiple ) {
		if ( $echo ) {
			wp_dropdown_categories( $args );
			return false;
		} else {
			return wp_dropdown_categories( $args );
		}
	} 

	$input_placeholder = isset( $args['show_option_none'] ) ? $args['show_option_none'] : '';
	
	unset( $args['show_option_none'], $args['option_none_value'] );

	$args['walker'] = new AIOVG_Walker_Terms_MultiSelect();
	$args['echo']   = false;

	$dropdown = wp_dropdown_categories( $args );
	$dropdown = preg_replace( '/<select(.*?)>(.*?)<\/select>/s', '<div class="aiovg-dropdown-list" style="display:none;">$2</div>', $dropdown );

	// Output
	$html  = '<div class="aiovg-dropdown-terms">';
	$html .= sprintf( '<input type="text" class="aiovg-dropdown-input aiovg-form-control" placeholder="%s" readonly />', esc_attr( $input_placeholder )	);	
	$html .= $dropdown;
	$html .= '</div>';

	if ( $echo ) {
		echo $html;
	} else {
		return $html;
	}
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
 * Get the category breadcrumbs.
 *
 * @since  3.8.4
 * @param  object $term The current term object.
 * @return string       Category breadcrumbs.
 */
function aiovg_get_category_breadcrumbs( $term = null ) {
	$page_settings = get_option( 'aiovg_page_settings' );

	$id = $page_settings['category'];
	if ( empty( $id ) )	return '';

	$crumbs = array();

	// Home Page
	$crumbs[] = array(
		'text' => __( 'Home', 'all-in-one-video-gallery' ),
		'url'  => home_url()
	);	

	// Single Category Page
	if ( $term ) {
		// Include the main categories page
		$post = get_post( $id );

		$crumbs[] = array(
			'text' => $post->post_title,
			'url'  => get_permalink( $id )
		);

		// Include the parent categories if available
		if ( $ancestors = get_ancestors( $term->term_id, 'aiovg_categories' ) ) {
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $term_id ) {
				if ( $parent_term = get_term_by( 'term_id', $term_id, 'aiovg_categories' ) ) {
					$crumbs[] = array(
						'text' => $parent_term->name,
						'url'  => aiovg_get_category_page_url( $parent_term )
					);
				}
			}
		}

		// Include the current category page
		$crumbs[] = array(
			'text' => $term->name
		);
	} else {
		// Include the main categories page
		$post = get_post( $id );

		$crumbs[] = array(
			'text' => $post->post_title
		);
	}

	// Output
	$html = '';

	$crumbs = apply_filters( 'aiovg_breadcrumb_links', $crumbs, $term, 'aiovg_categories' );
	if ( ! empty( $crumbs ) ) {
		$links = array();

		foreach ( $crumbs as $crumb ) {
			if ( isset( $crumb['url'] ) ) {
				$links[] = sprintf(
					'<span><a href="%s">%s</a></span>',
					esc_url( $crumb['url'] ),
					wp_kses_post( $crumb['text'] )
				);
			} else {
				$links[] = sprintf(
					'<span>%s</span>',
					wp_kses_post( $crumb['text'] )
				);
			}
		}

		$separator = apply_filters( 'aiovg_breadcrumb_separator', ' » ' );
		$html = '<p class="aiovg-breadcrumbs">' . implode( $separator, $links ) . '</p>';
	}

	return $html;
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
		'aiovg_privacy_settings' => array(
			'show_consent'         => 0,
			'consent_message'      => __( '<strong>Please accept cookies to play this video</strong>. By accepting you will be accessing content from a service provided by an external third party.', 'all-in-one-video-gallery' ),
			'consent_button_label' => __( 'I Agree', 'all-in-one-video-gallery' ),
			'disable_cookies'      => array()
		),
		'aiovg_general_settings' => array(
			'custom_css'                => '',
			'lazyloading'               => 0,
			'datetime_format'           => '',
			'maybe_flush_rewrite_rules' => 1,
			'delete_plugin_data'        => 1,
			'delete_media_files'        => 1
		),
		'aiovg_api_settings' => array(
			'youtube_api_key'    => '',
			'vimeo_access_token' => '',
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
 * Grabs the $wp_query global in a safe way with some fallbacks.
 *
 * @since  2.6.3
 * @return mixed
 */
function aiovg_get_global_query_object() {
    global $wp_query;
    global $wp_the_query;
 
    if ( ! empty( $wp_query ) ) {
        return $wp_query;
    }
 
    if ( ! empty( $wp_the_query ) ) {
        return $wp_the_query;
    }
 
    return null;
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
		$image_data['src'] = AIOVG_PLUGIN_URL . 'public/assets/images/placeholder-image.png';
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
			$default = AIOVG_PLUGIN_URL . 'public/assets/images/placeholder-image.png';
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
 * Get the like / dislike button.
 *
 * @since 3.6.1
 * @param int     $post_id Video post ID.
 * @return string          HTML string.
 */
function aiovg_get_like_button( $post_id ) {
	$attributes = array();
	
	$attributes[] = sprintf( 'post_id="%d"', $post_id );

	$likes = (int) get_post_meta( $post_id, 'likes', true );
	$attributes[] = sprintf( 'likes="%d"', $likes );

	$dislikes = (int) get_post_meta( $post_id, 'dislikes', true );
	$attributes[] = sprintf( 'dislikes="%d"', $dislikes );
	
	$user_id = get_current_user_id();	

	if ( $user_id > 0 ) {
		$liked    = (array) get_user_meta( $user_id, 'aiovg_videos_likes' );	
		$disliked = (array) get_user_meta( $user_id, 'aiovg_videos_dislikes' );		
	} else {
		$likes_settings = get_option( 'aiovg_likes_settings' );

		$liked    = array();		
		$disliked = array();

		if ( empty( $likes_settings['login_required_to_vote'] ) ) {
			if ( isset( $_COOKIE['aiovg_videos_likes'] ) ) {
				$liked = explode( '|', $_COOKIE['aiovg_videos_likes'] );
				$liked = array_map( 'intval', $liked );
			}

			if ( isset( $_COOKIE['aiovg_videos_dislikes'] ) ) {
				$disliked = explode( '|', $_COOKIE['aiovg_videos_dislikes'] );
				$disliked = array_map( 'intval', $disliked );
			}
		}
	}

	if ( in_array( $post_id, $liked ) ) {
		$attributes[] = 'liked';
	} elseif ( in_array( $post_id, $disliked ) ) {
		$attributes[] = 'disliked';
	}

	$attributes[] = 'loaded';

	// Return
	return sprintf(
		'<aiovg-like-button %s></aiovg-like-button>',
		implode( ' ', $attributes )
	);
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
 * Get player HTML.
 * 
 * @since  1.0.0
 * @param  int    $post_id Post ID.
 * @param  array  $atts    Player configuration data.
 * @return string $html    Player HTML.
 */
function aiovg_get_player_html( $post_id = 0, $atts = array() ) {
	$player = AIOVG_Player::get_instance();
	return $player->create( $post_id, $atts );	
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
	$video_templates     = aiovg_get_video_templates();
	
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
							'options'     => $video_templates,
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
						),
					)
				)
			)
		)
	);

	return apply_filters( 'aiovg_shortcode_fields', $fields );
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

/**
 * Category thumbnail HTML output.
 *
 * @since 1.5.7
 * @param WP_Term $term WP term object.
 * @param array   $atts Array of attributes.
 */
function the_aiovg_category_thumbnail( $term, $attributes ) {
	include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . 'public/templates/category-thumbnail.php', $attributes );
}

/**
 * Add content after player.
 *
 * @since 3.6.1
 * @param int   $post_id    Video post ID.
 * @param array $attributes Array of attributes.
 */
function the_aiovg_content_after_player( $post_id, $attributes ) {
	$post_id = (int) $post_id;	
	$content = '';	
	
	if ( ! empty( $attributes['show_player_like_button'] ) ) {
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-likes' );
		
		$content = aiovg_get_like_button( $post_id );
	}

	$content = apply_filters( 'aiovg_content_after_player', $content, $post_id, $attributes );

	if ( ! empty( $content ) ) {
		echo '<div class="aiovg-content-after-player aiovg-margin-top">';
		echo $content;
		echo '</div>';
	}
}

/**
 * Add content after thumbnail.
 *
 * @since 3.6.1
 * @param array $attributes Array of attributes.
 */
function the_aiovg_content_after_thumbnail( $attributes ) {
	$content = apply_filters( 'aiovg_content_after_thumbnail', '', $attributes );

	if ( ! empty( $content ) ) {
		echo '<div class="aiovg-content-after-thumbnail">';
		echo $content;
		echo '</div>';
	}
}

/**
 * Display the video excerpt.
 *
 * @since 1.0.0
 * @param int   $char_length Excerpt length.
 */
function the_aiovg_excerpt( $char_length ) {
	$excerpt = aiovg_get_excerpt( 0, $char_length );
	if ( ! empty( $excerpt ) ) {
		$excerpt = wp_kses_post( $excerpt );
		$excerpt = do_shortcode( $excerpt );
	}

	echo $excerpt;
}

/**
 * Display more button on gallery pages.
 *
 * @since 2.5.1
 * @param int   $numpages The total amount of pages.
 * @param array $atts     Array of attributes.
 */
function the_aiovg_more_button( $numpages = '', $atts = array() ) {
	if ( empty( $numpages ) ) {
    	$numpages = 1;
  	}

	$paged = 1;			
	if ( isset( $atts['paged'] ) ) {
		$paged = (int) $atts['paged'];
	}

	if ( empty( $atts['more_link'] ) ) { // Ajax	
		if ( $paged < $numpages ) {			
			wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-pagination' );

			echo sprintf( '<aiovg-pagination class="aiovg-more aiovg-more-ajax aiovg-text-center" data-params=\'%s\'>', wp_json_encode( $atts ) );
			echo sprintf( 
				'<button type="button" class="aiovg-link-more" data-numpages="%d" data-paged="%d">%s</button>', 
				$numpages,
				$paged,
				esc_html( $atts['more_label'] ) 
			);
			echo '</aiovg-pagination>';
		}
	} else {
		echo '<aiovg-pagination class="aiovg-more aiovg-text-center">';
		echo sprintf( 
			'<button type="button" onclick="location.href=\'%s\'" class="aiovg-link-more">%s</button>', 
			esc_url( $atts['more_link'] ), 
			esc_html( $atts['more_label'] ) 
		);
		echo '</aiovg-pagination>';
	}
}

/**
 * Display paginated links on gallery pages.
 *
 * @since 1.0.0
 * @param int   $numpages  The total amount of pages.
 * @param int   $pagerange How many numbers to either side of current page.
 * @param int   $paged     The current page number.
 * @param array $atts      Array of attributes.
 */
function the_aiovg_pagination( $numpages = '', $pagerange = '', $paged = '', $atts = array() ) {
	if ( empty( $numpages ) ) {
    	$numpages = 1;
  	}
	
	if ( empty( $pagerange ) ) {
		$pagination_settings = get_option( 'aiovg_pagination_settings', array() );
	
		$pagerange = isset( $pagination_settings['mid_size'] ) ? (int) $pagination_settings['mid_size'] : 2;
					
		if ( empty( $pagerange ) ) {
			$pagerange = 2;
		}
  	}

  	if ( empty( $paged ) ) {
    	$paged = aiovg_get_page_number();
  	}

	$is_ajax = isset( $atts['pagination_ajax'] ) && ! empty( $atts['pagination_ajax'] );
	if ( $is_ajax ) {
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-pagination' );
	}

  	// Construct the pagination arguments to enter into our paginate_links function
	$arr_params = array();

	parse_str( $_SERVER['QUERY_STRING'], $queries );
	if ( ! empty( $queries ) ) {
		$arr_params = array_keys( $queries );
	}
	 
	$base = aiovg_remove_query_arg( $arr_params, get_pagenum_link( 1 ) );	
	
	if ( ! get_option( 'permalink_structure' ) || isset( $_GET['aiovg'] ) ) {
		$prefix = strpos( $base, '?' ) ? '&' : '?';
    	$format = $prefix . 'paged=%#%';
    } else {
		$prefix = ( '/' == substr( $base, -1 ) ) ? '' : '/';
    	$format = $prefix . 'page/%#%';
	} 
	
  	$pagination_args = array(
    	'base'         => $base . '%_%',
    	'format'       => $format,
    	'total'        => $numpages,
    	'current'      => $paged,
    	'show_all'     => false,
    	'end_size'     => 1,
    	'mid_size'     => $pagerange,
    	'prev_next'    => true,
    	'prev_text'    => __( '&laquo;', 'all-in-one-video-gallery' ),
    	'next_text'    => __( '&raquo;', 'all-in-one-video-gallery' ),
    	'type'         => 'array',
    	'add_args'     => false,
    	'add_fragment' => ''
  	);

	$pagination_args = apply_filters( 'aiovg_pagination_args', $pagination_args, $numpages, $pagerange, $paged, $atts );
  	$paginate_links = paginate_links( $pagination_args );

  	if ( is_array( $paginate_links ) ) {
		if ( $is_ajax ) {
			printf(
				'<aiovg-pagination class="aiovg-pagination aiovg-pagination-ajax aiovg-text-center" data-params=\'%s\' data-current="%d">',			
				wp_json_encode( $atts ),
				$paged
			);
		} else {
			echo '<aiovg-pagination class="aiovg-pagination aiovg-text-center">';			
		}

		echo '<div class="aiovg-pagination-links">'; 		   	
		foreach ( $paginate_links as $key => $page_link ) {		
			echo $page_link;
		}
		echo '</div>';	

		echo '<div class="aiovg-pagination-info aiovg-text-muted aiovg-text-small">';
		echo sprintf( __( 'Page %d of %d', 'all-in-one-video-gallery' ), $paged, $numpages );
		echo '</div>';		
		
		echo '</aiovg-pagination>';
  	}
}

/**
 * Display a video player.
 * 
 * @since 1.0.0
 * @param int   $post_id Post ID.
 * @param array $atts    Player configuration data.
 */
function the_aiovg_player( $post_id = 0, $atts = array() ) {
	echo aiovg_get_player_html( $post_id, $atts );	
}

/**
 * Display social sharing buttons.
 *
 * @since 1.0.0
 */
function the_aiovg_socialshare_buttons() {
	if ( ! is_singular( 'aiovg_videos' ) ) { 
		return false;
	}

	global $post;
	
	$socialshare_settings = get_option( 'aiovg_socialshare_settings' );
	
	// Get current page url
	$url = get_permalink();
	
	// Get current page title
	$title = get_the_title();
	$title = str_replace( ' ', '%20', $title );
	$title = str_replace( '|', '%7C', $title );
	$title = str_replace( '@', '%40', $title );

	// Get image
	$image_data = aiovg_get_image( $post->ID, 'large' );
	$image = $image_data['src'];

	// Build sharing buttons
	$buttons = array();

	if ( isset( $socialshare_settings['services']['facebook'] ) ) {
		$buttons['facebook'] = array(
			'icon' => 'aiovg-icon-facebook',
			'text' => __( 'Facebook', 'all-in-one-video-gallery' ),
			'url'  => "https://www.facebook.com/sharer/sharer.php?u={$url}"				
		);
	}

	if ( isset( $socialshare_settings['services']['twitter'] ) ) {
		$buttons['twitter'] = array(
			'icon' => 'aiovg-icon-twitter',
			'text' => __( 'Twitter', 'all-in-one-video-gallery' ),
			'url'  => "https://twitter.com/intent/tweet?text={$title}&amp;url={$url}"				
		);
	}		

	if ( isset( $socialshare_settings['services']['linkedin'] ) ) {
		$buttons['linkedin'] = array(	
			'icon' => 'aiovg-icon-linkedin',			
			'text' => __( 'Linkedin', 'all-in-one-video-gallery' ),
			'url'  => "https://www.linkedin.com/shareArticle?url={$url}&amp;title={$title}"
		);
	}

	if ( isset( $socialshare_settings['services']['pinterest'] ) ) {
		$buttons['pinterest'] = array(
			'icon' => 'aiovg-icon-pinterest',		
			'text' => __( 'Pin It', 'all-in-one-video-gallery' ),
			'url'  => "https://pinterest.com/pin/create/button/?url={$url}&amp;media={$image}&amp;description={$title}"
		);
	}

	if ( isset( $socialshare_settings['services']['tumblr'] ) ) {
		$tumblr_url = "https://www.tumblr.com/share/link?url={$url}&amp;name={$title}";

		$description = sanitize_text_field( aiovg_get_excerpt( $post->ID, 160, '', false ) ); 
		if ( ! empty( $description ) ) {
			$description = str_replace( ' ', '%20', $description );
			$description = str_replace( '|', '%7C', $description );
			$description = str_replace( '@', '%40', $description );	

			$tumblr_url .= "&amp;description={$description}";
		}

		$buttons['tumblr'] = array(
			'icon' => 'aiovg-icon-tumblr',				
			'text' => __( 'Tumblr', 'all-in-one-video-gallery' ),
			'url'  => $tumblr_url
		);
	}

	if ( isset( $socialshare_settings['services']['whatsapp'] ) ) {
		if ( wp_is_mobile() ) {
			$whatsapp_url = "whatsapp://send?text={$title} " . rawurlencode( $url );
		} else {
			$whatsapp_url = "https://api.whatsapp.com/send?text={$title}&nbsp;{$url}";
		}

		$buttons['whatsapp'] = array(	
			'icon' => 'aiovg-icon-whatsapp',				
			'text' => __( 'WhatsApp', 'all-in-one-video-gallery' ),
			'url'  => $whatsapp_url
		);
	}

	if ( isset( $socialshare_settings['services']['email'] ) ) {
		$email_subject = sprintf( __( 'Check out the "%s"', 'all-in-one-video-gallery' ), $title );
		$email_body    = sprintf( __( 'Check out the "%s" at %s', 'all-in-one-video-gallery' ), $title, $url );
		$email_url     = "mailto:?subject={$email_subject}&amp;body={$email_body}";

		$buttons['email'] = array(		
			'icon' => 'aiovg-icon-email',
			'text' => __( 'Email', 'all-in-one-video-gallery' ),
			'url'  => $email_url
		);
	}

	$buttons = apply_filters( 'aiovg_socialshare_buttons', $buttons );

	if ( count( $buttons ) ) {
		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-icons' );
		
		echo '<div class="aiovg-social">';

		foreach ( $buttons as $label => $button ) {
			printf( 
				'<a class="aiovg-social-%s aiovg-link-social" href="%s" title="%s" target="_blank"><span class="%s"></span></a>', 
				esc_attr( $label ),
				esc_attr( $button['url'] ), 
				esc_attr( $button['text'] ),
				( isset( $button['icon'] ) ? esc_attr( $button['icon'] ) : '' )
			);
		}

		echo '</div>';
	}	
}

/**
 * Build & display attributes using the $atts array.
 * 
 * @since 1.0.0
 * @param array $atts Array of attributes.
 */
function the_aiovg_video_attributes( $atts ) {
	echo aiovg_combine_video_attributes( $atts );
}

/**
 * Video thumbnail HTML output.
 *
 * @since 1.5.7
 * @param WP_Post $post WP post object.
 * @param array   $atts Array of attributes.
 */
function the_aiovg_video_thumbnail( $post, $attributes ) {
	$template = 'video-thumbnail.php';
	
	if ( isset( $attributes['thumbnail_style'] ) && 'image-left' == $attributes['thumbnail_style'] ) {
		$template = 'video-thumbnail-image-left.php';
	}

	include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . "public/templates/{$template}", $attributes );
}