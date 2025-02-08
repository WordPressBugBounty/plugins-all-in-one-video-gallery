<?php

/**
 * Video
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

/**
 * AIOVG_Public_Video class.
 *
 * @since 1.0.0
 */
class AIOVG_Public_Video {
	
	/**
	 * Get things started.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Register shortcode(s)
		add_shortcode( "aiovg_video", array( $this, "run_shortcode_video" ) );
	}
	
	/**
	 * Always keep using our custom template for AIOVG player page.
	 *
	 * @since  1.0.0
	 * @param  string $template The path of the template to include.
	 * @return string $template Filtered template path.
	 */
	public function template_include( $template ) {	
		$page_settings = get_option( 'aiovg_page_settings' );

		$player_page_id = apply_filters( 'wpml_object_id', (int) $page_settings['player'], 'page' );		
		if ( ! empty( $player_page_id ) && is_page( $player_page_id ) ) {
			$template = AIOVG_PLUGIN_DIR . 'public/templates/player.php';
		}
		
		return $template;		
	}	
	
	/**
	 * Remove admin bar from the AIOVG player page.
	 *
	 * @since  3.8.0
	 * @param  bool  $show_admin_bar Whether the admin bar should be shown.
	 * @return bool  $show_admin_bar Filtered value.
	 */
	public function remove_admin_bar( $show_admin_bar ) {
		$page_settings = get_option( 'aiovg_page_settings' );

		$player_page_id = apply_filters( 'wpml_object_id', (int) $page_settings['player'], 'page' );
		if ( ! empty( $player_page_id ) && is_page( $player_page_id ) ) {
			return false;
		}

		return $show_admin_bar;
	}
	
	/**
	 * Add support for HLS & MPEG-DASH.
	 *
	 * @since  3.0.0
	 * @param  array $mimes Array of allowed mime types.
	 * @return array        Filtered mime types array.
	 */
	public function add_mime_types( $mimes ) {			
		$mimes['m3u8'] = 'application/x-mpegurl';
		$mimes['mpd']  = 'application/dash+xml';

		return $mimes;		
	}
	
	/**
	 * Run the shortcode [aiovg_video].
	 *
	 * @since  1.0.0
	 * @param  array  $attributes An associative array of attributes.
	 * @param  string $content    Enclosing content.
	 * @return string             Shortcode output.
	 */
	public function run_shortcode_video( $attributes, $content = null ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		$attributes['shortcode'] = 'aiovg_video';
		$post_id = 0;
		
		if ( isset( $attributes['id'] ) && ! empty( $attributes['id'] ) ) {
			$post_id = (int) $attributes['id'];
			$post_status = get_post_status( $post_id );

			$has_permission = true;

			if ( 'publish' != $post_status ) {
				$has_permission = false;

				if ( 'private' == $post_status ) {
					if ( current_user_can( 'read_private_aiovg_videos' ) ) {
						$has_permission = true;
					}
				}			
			}

			$has_permission = apply_filters( 'aiovg_read_private_videos', $has_permission, $attributes, $content );

			if ( ! $has_permission ) {
				return '';
			}
		} else {			
			if ( isset( $attributes['src'] ) && ! empty( $attributes['src'] ) ) {
				if ( false !== strpos( $attributes['src'], 'youtube.com' ) || false !== strpos( $attributes['src'], 'youtu.be' ) ) {
					$attributes['youtube'] = aiovg_resolve_youtube_url( $attributes['src'] );
				} elseif ( false !== strpos( $attributes['src'], 'vimeo.com' ) ) {
					$attributes['vimeo'] = $attributes['src'];
				} elseif ( false !== strpos( $attributes['src'], 'dailymotion.com' ) ) {
					$attributes['dailymotion'] = $attributes['src'];
				} elseif ( false !== strpos( $attributes['src'], 'rumble.com' ) ) {
					$attributes['rumble'] = $attributes['src'];
				} elseif ( false !== strpos( $attributes['src'], 'facebook.com' ) ) {
					$attributes['facebook'] = $attributes['src'];
				} else {
					$filetype = wp_check_filetype( $attributes['src'] );
		
					if ( 'webm' == $filetype['ext'] ) {
						$attributes['webm'] = $attributes['src'];
					} elseif ( 'ogv' == $filetype['ext'] ) {
						$attributes['ogv'] = $attributes['src'];
					} elseif ( 'm3u8' == $filetype['ext'] ) {
						$attributes['hls'] = $attributes['src'];
					} elseif ( 'mpd' == $filetype['ext'] ) {
						$attributes['dash'] = $attributes['src'];
					} else {
						$attributes['mp4'] = $attributes['src'];
					}
				}

				unset( $attributes['src'] );
			} 
			
			$supported_formats = array( 'mp4', 'webm', 'ogv', 'hls', 'dash', 'youtube', 'vimeo', 'dailymotion', 'rumble', 'facebook' );
			$is_video_available = 0;

			foreach ( $supported_formats as $format ) {			
				if ( isset( $attributes[ $format ] ) ) {
					$attributes[ $format ] = aiovg_sanitize_url( $attributes[ $format ] );
					$is_video_available = 1;
					break;
				}
			}

			if ( isset( $attributes['poster'] ) ) {
				$attributes['poster'] = aiovg_sanitize_url( $attributes['poster'] );
			}
			
			if ( 0 == $is_video_available ) {
				$is_singular   = is_singular( 'aiovg_videos' );
				$in_the_loop   = apply_filters( 'aiovg_the_content_in_the_loop', in_the_loop() );
				$is_main_query = apply_filters( 'aiovg_the_content_is_main_query', is_main_query() );

				$category = isset( $attributes['category'] ) ? $attributes['category'] : '';
				$tag      = isset( $attributes['tag'] ) ? $attributes['tag'] : '';
				$featured = isset( $attributes['featured'] ) ? (int) $attributes['featured'] : 0;
				$orderby  = isset( $attributes['orderby'] ) ? sanitize_text_field( $attributes['orderby'] ) : '';
				$order    = isset( $attributes['order'] ) ? sanitize_text_field( $attributes['order'] ) : 'desc';

				if ( ! empty( $category ) || ! empty( $tag ) || ! empty( $featured ) || ! empty( $orderby ) ) {
					$is_singular = false;
				}

				if ( $is_singular && $in_the_loop && $is_main_query ) {
					$post_id = get_the_ID();
					$attributes['id'] = $post_id;
				} else {
					$args = array(				
						'post_type' => 'aiovg_videos',			
						'post_status' => 'publish',
						'posts_per_page' => 1,
						'fields' => 'ids',
						'no_found_rows' => true,
						'update_post_term_cache' => false,
						'update_post_meta_cache' => false
					);
			
					// Taxonomy Parameters
					$tax_queries = array();		

					if ( ! empty( $category ) ) { // Category
						$tax_queries[] = array(
							'taxonomy'         => 'aiovg_categories',
							'field'            => 'term_id',
							'terms'            => is_array( $category ) ? array_map( 'intval', $category ) : array_map( 'intval', explode( ',', $category ) ),
							'include_children' => false
						);
					}

					if ( ! empty( $tag ) ) { // Tag
						$tax_queries[] = array(
							'taxonomy'         => 'aiovg_tags',
							'field'            => 'term_id',
							'terms'            => is_array( $tag ) ? array_map( 'intval', $tag ) : array_map( 'intval', explode( ',', $tag ) ),
							'include_children' => false
						);
					}
					
					$count_tax_queries = count( $tax_queries );
					if ( $count_tax_queries ) {
						$args['tax_query'] = ( $count_tax_queries > 1 ) ? array_merge( array( 'relation' => 'AND' ), $tax_queries ) : $tax_queries;
					}

					// Custom Field (post meta) Parameters
					$meta_queries = array();

					if ( 'likes' == $orderby ) { // Likes			
						$meta_queries['likes'] = array(
							'relation' => 'OR',
							array(
								'key'     => 'likes',
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => 'likes',
								'type'    => 'NUMERIC',
								'compare' => 'EXISTS'
							)
						);				
					}

					if ( 'dislikes' == $orderby ) { // Dislikes			
						$meta_queries['dislikes'] = array(
							'relation' => 'OR',
							array(
								'key'     => 'dislikes',
								'compare' => 'NOT EXISTS'
							),
							array(
								'key'     => 'dislikes',
								'type'    => 'NUMERIC',
								'compare' => 'EXISTS'
							)
						);				
					}

					if ( ! empty( $featured ) ) { // Featured			
						$meta_queries['featured'] = array(
							'key'     => 'featured',
							'value'   => 1,
							'compare' => '='
						);				
					}		

					$count_meta_queries = count( $meta_queries );
					if ( $count_meta_queries ) {
						$args['meta_query'] = ( $count_meta_queries > 1 ) ? array_merge( array( 'relation' => 'AND' ), $meta_queries ) : $meta_queries;
					}

					// Order & Orderby Parameters
					switch ( $orderby ) {
						case 'likes':
						case 'dislikes':
							$args['orderby'] = array(
								$orderby => $order,
								'date'   => 'DESC'
							);			
							break;

						case 'views':
							$args['meta_key'] = $orderby;
							$args['orderby']  = 'meta_value_num';
						
							$args['order']    = $order;
							break;

						case 'rand':
							$args['orderby']  = 'rand';
							break;

						default:
							if ( ! empty( $orderby ) ) {
								$args['orderby'] = $orderby;
								$args['order']   = $order;
							}
					}
			
					$args = apply_filters( 'aiovg_query_args', $args, $attributes );
					$aiovg_query = new WP_Query( $args );
					
					if ( $aiovg_query->have_posts() ) {
						$posts = $aiovg_query->posts;
						$post_id = (int) $posts[0];

						$attributes['id'] = $post_id;
					}
				}			
			}			
		}
			
		// Output
		$show_title = isset( $attributes['show_title'] ) ? (int) $attributes['show_title'] : 0;
		if ( $show_title && $post_id > 0 ) {
			$attributes['title'] = get_the_title( $post_id );
		}

		$attributes = apply_filters( 'shortcode_atts_aiovg_video', $attributes, $content );
		
		$player_html = aiovg_get_player_html( $post_id, $attributes );

		$html = $player_html;

		if ( isset( $attributes['title'] ) && ! empty( $attributes['title'] ) ) {
			$title_position = isset( $attributes['title_position'] ) ? sanitize_text_field( $attributes['title_position'] ) : '';

			$allowed_title_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p' );
			$title_tag = isset( $attributes['title_tag'] ) ? sanitize_key( $attributes['title_tag'] ) : 'h2';
			if ( ! in_array( $title_tag, $allowed_title_tags) ) {
				$title_tag = 'h2';
			}			

			$html = '<div class="aiovg aiovg-video-shortcode">';
			
			switch ( $title_position ) {
				case 'bottom':
					$html .= sprintf( 
						'%1$s<%2$s class="aiovg-player-title">%3$s</%2$s>', 
						$player_html,
						$title_tag,
						esc_html( $attributes['title'] )						 
					);
					break;

				default: // top
					$html .= sprintf( 
						'<%1$s class="aiovg-player-title">%2$s</%1$s>%3$s',
						$title_tag,
						esc_html( $attributes['title'] ), 
						$player_html
					);
					
			}

			$html .= '</div>';			
		}

		return $html;
	}

	/**
	 * Filter video sources.
	 *
	 * @since  2.6.5
	 * @param  array $sources Video sources.
	 * @param  array $params  Player params.
	 * @return array $sources Filtered video sources.
	 */
	public function player_sources( $sources, $params = array() ) {	
		$post_id = 0;
		$type    = '';

		if ( ! empty( $params ) ) {
			$post_id = (int) $params['post_id'];
		} else {
			$post_id = (int) get_query_var( 'aiovg_video' );
		}		

		if ( $post_id > 0 ) {
			$type = get_post_meta( $post_id, 'type', true );
		}

		if ( isset( $sources['mp4'] ) && ! isset( $sources['hls'] ) ) {
			$mp4_src = $sources['mp4']['src'];

			if ( strpos( $mp4_src, 'videos.files.wordpress.com' ) !== false && strpos( $mp4_src, '.mp4' ) !== false ) {
				$hls_src = str_replace( '.mp4', '.master.m3u8', $mp4_src );
				$has_hls = 0;

				$query = parse_url( $mp4_src, PHP_URL_QUERY );
				parse_str( $query, $parsed_url );

				if ( isset( $parsed_url['isnew'] ) ) {
					$has_hls = (int) $parsed_url['isnew'];
				} else {					
					$hls_response = wp_remote_get( $hls_src );

					if ( 200 == wp_remote_retrieve_response_code( $hls_response ) ) {
						$has_hls = 1;
					}

					if ( $post_id > 0 && 'default' == $type ) {
						update_post_meta( $post_id, 'mp4', aiovg_sanitize_url( add_query_arg( 'isnew', $has_hls, $mp4_src ) ) );
					}
				}

				if ( $has_hls ) {
					$hls_source = array(
						'hls' => array(
							'type' => 'application/x-mpegurl',
							'src'  => $hls_src
						)
					);

					$sources = array_merge( $hls_source, $sources );
				}
			}
		}
		
		return $sources;	
	}	
	
	/**
	 * Filters the video title and adds a restrictions label if applicable.
	 *
	 * @since  3.9.7
	 * @param  string $title   The original video title.
	 * @param  int    $post_id The video post ID.
	 * @return string          The modified title with the restrictions label if applicable.
	 */
	public function filter_the_title_with_restrictions_label( $title, $post_id ) {
		if ( ! aiovg_current_user_has_video_access( $post_id ) ) {
			$restrictions_settings = get_option( 'aiovg_restrictions_settings' );

			if ( ! empty( $restrictions_settings['show_restricted_label'] ) ) {
				$restricted_label_text = $restrictions_settings['restricted_label_text'];				
				if ( empty( $restricted_label_text ) ) {
					$restricted_label_text = __( 'restricted', 'all-in-one-video-gallery' );
				}

				$restricted_label_bg_color = $restrictions_settings['restricted_label_bg_color'];				
				if ( empty( $restricted_label_bg_color ) ) {
					$restricted_label_bg_color = '#aaa';
				}

				$restricted_label_text_color = $restrictions_settings['restricted_label_text_color'];				
				if ( empty( $restricted_label_text_color ) ) {
					$restricted_label_text_color = '#fff';
				}

				$title = sprintf( 
					'<span class="aiovg-restricted-label" style="background-color: %s; color: %s;">%s</span> %s',
					esc_attr( $restricted_label_bg_color ),
					esc_attr( $restricted_label_text_color ),
					esc_html( $restricted_label_text ),
					$title
				);
			}
		}
		
		return $title;
	}

	/**
	 * Filters timestamps in the video description and wraps them with links.
	 *
	 * @since  3.9.7
	 * @param  string $content The original video description.
	 * @return string          The modified description with timestamps wrapped in links.
	 */
	public function wrap_timestamps_with_links( $content ) {
		$player_settings = get_option( 'aiovg_player_settings' );

		if ( isset( $player_settings['controls']['chapters'] ) ) {
			$pattern = '/(\d{1,2}:)?\d{1,2}:\d{2}/';

			// Replace timestamps with anchor tags
			$content = preg_replace_callback( $pattern, function( $matches ) {
				$timestamp = $matches[0];
				return sprintf(
					'<a href="javascript:void(0);" class="aiovg-chapter-timestamp" data-time="%s">%s</a>',
					aiovg_convert_time_to_seconds( $timestamp ),
					$timestamp
				);
			}, $content );
		}

		return $content;
	}

	/**
	 * Filter the post content.
	 *
	 * @since  1.0.0
	 * @param  string $content Content of the current post.
	 * @return string $content Modified Content.
	 */
	public function the_content( $content ) {
		$in_the_loop   = apply_filters( 'aiovg_the_content_in_the_loop', in_the_loop() );
		$is_main_query = apply_filters( 'aiovg_the_content_is_main_query', is_main_query() );

		if ( is_singular( 'aiovg_videos' ) && $in_the_loop && $is_main_query ) {		
			global $post, $wp_query;
			
			if ( $post->ID != $wp_query->get_queried_object_id() ) {
				return $content;
			}
			
			if ( post_password_required( $post->ID ) ) {
				return $content;
			}

			if ( ! aiovg_current_user_has_video_access( $post->ID ) ) {
				$restrictions_settings = get_option( 'aiovg_restrictions_settings' );

				$restricted_message = $restrictions_settings['restricted_message'];
				if ( empty( $restricted_message ) ) {
					$restricted_message = __( 'Sorry, but you do not have permission to view this video.', 'all-in-one-video-gallery' );
				}

				$content = '<p>' . wp_kses_post( $restricted_message ) . '</p>';
				return $content;
			}
			
			// Vars
			$player_settings = get_option( 'aiovg_player_settings' );	
			$video_settings = get_option( 'aiovg_video_settings' );					
			$related_videos_settings = get_option( 'aiovg_related_videos_settings' );
			$categories_settings = get_option( 'aiovg_categories_settings' );
			$likes_settings = get_option( 'aiovg_likes_settings' );	
			
			$attributes = array(
				'id'              => $post->ID,				
				'show_category'   => isset( $video_settings['display']['category'] ),
				'show_tag'        => isset( $video_settings['display']['tag'] ),
				'show_date'       => isset( $video_settings['display']['date'] ),
				'show_user'       => isset( $video_settings['display']['user'] ),
				'show_views'      => isset( $video_settings['display']['views'] ),
				'related'         => isset( $video_settings['display']['related'] ),
				'share'           => isset( $video_settings['display']['share'] ),
				'columns'         => $related_videos_settings['columns'],
				'limit'           => $related_videos_settings['limit'],
				'orderby'         => $related_videos_settings['orderby'],
				'order'           => $related_videos_settings['order'],
				'show_pagination' => isset( $related_videos_settings['display']['pagination'] )
			);

			$show_like_button = 0;
			if ( ! empty( $likes_settings['like_button'] ) || ! empty( $likes_settings['dislike_button'] ) ) {
				$show_like_button = 1;
			}
			$attributes['show_player_like_button'] = $show_like_button;
			
			$attributes['categories'] = wp_get_object_terms( 
				get_the_ID(), 
				'aiovg_categories',
				array(
					'orderby' => sanitize_text_field( $categories_settings['orderby'] ),
					'order'   => sanitize_text_field( $categories_settings['order'] )
				) 
			);

			$attributes['tags'] = wp_get_object_terms( 
				get_the_ID(), 
				'aiovg_tags',
				array(
					'orderby' => sanitize_text_field( $categories_settings['orderby'] ),
					'order'   => sanitize_text_field( $categories_settings['order'] )
				) 
			);
			
			// Enqueue dependencies
			wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' );

			if ( isset( $player_settings['controls']['chapters'] ) ) {
				wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-public' );
			}
			
			// Process output
			ob_start();
			include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . 'public/templates/single-video.php' );
			$content = ob_get_clean();			
		}
		
		return $content;	
	}

	/**
     * Filters whether the current video post is open for comments.
     *
     * @since 2.5.6
     *
     * @param  bool $open    Whether the current post is open for comments.
     * @param  int  $post_id The post ID.
	 * @return bool $open    True if the comments are open, false if not.
     */
	public function comments_open( $open, $post_id ) {
		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
				
			if ( 'aiovg_videos' == $post_type ) {
				$video_settings = get_option( 'aiovg_video_settings' );

				$has_comments = (int) $video_settings['has_comments'];

				if ( $has_comments == 2 ) { // Forcefully enable comments on all the video pages
					$open = true;
				}

				if ( $has_comments == -2 ) { // Forcefully disable comments on all the video pages
					$open = false;
				}
			}
		}

		return $open;
	}

	/**
	 * Update video views count.
	 *
	 * @since 1.0.0
	 */
	public function ajax_callback_update_views_count() {
		if ( isset( $_REQUEST['post_id'] ) ) {		
			$post_id = (int) $_REQUEST['post_id'];
						
			if ( $post_id > 0 ) {
				check_ajax_referer( 'aiovg_ajax_nonce', 'security' );
				aiovg_update_views_count( $post_id );

				// Update video duration if applicable
				if ( isset( $_REQUEST['duration'] ) ) {		
					$duration = (float) $_REQUEST['duration'];
								
					if ( $duration > 0 ) {
						$current_duration = get_post_meta( $post_id, 'duration', true );
						if ( empty( $current_duration ) ) {
							$duration = aiovg_convert_seconds_to_human_time( $duration );
							update_post_meta( $post_id, 'duration', $duration );
						}
					}		
				}
			}		
		}
		
		wp_send_json_success();	
	}	

	/**
	 * Force download the video file.
	 *
	 * @since 2.5.8
	 */
	public function download_video() {
		if ( ! isset( $_GET['vdl'] ) ) {
			return;
		}	
		
		if ( is_numeric( $_GET['vdl'] ) ) {
			$file = get_post_meta( (int) $_GET['vdl'], 'mp4', true );
			$file = aiovg_make_url_absolute( $file );
		} else {
			$file = get_transient( sanitize_text_field( $_GET['vdl'] ) );
		}

		$file = remove_query_arg( 'isnew', $file );

		if ( empty( $file ) ) {
			die( esc_html__( 'File is not readable or not found.', 'all-in-one-video-gallery' ) );
           	exit;
        }

		// Vars
		$is_remote_file = true;
        $formatted_path = 'url';        	
		$mime_type      = 'video/mp4'; 
		$file_size      = '';		

		// Removing spaces and replacing with %20 ascii code
        $file = preg_replace( '/\s+/', '%20', trim( $file ) );  
	  	$file = str_replace( '         ', '%20', $file );
	  	$file = str_replace( '        ', '%20', $file );
	  	$file = str_replace( '       ', '%20', $file );
	  	$file = str_replace( '      ', '%20', $file );
	  	$file = str_replace( '     ', '%20', $file );
	  	$file = str_replace( '    ', '%20', $file );
	  	$file = str_replace( '   ', '%20', $file );
	  	$file = str_replace( '  ', '%20', $file );
	  	$file = str_replace( ' ', '%20', $file );

		// Detect the file type	
		if ( strpos( $file, home_url() ) !== false ) {
			$is_remote_file = false;
		}		        		
          
        if ( preg_match( '#http://#', $file ) || preg_match( '#https://#', $file ) ) {
          	$formatted_path = 'url';
        } else {
          	$formatted_path = 'filepath';
        }
        
        if ( $formatted_path == 'url' ) {
          	$file_headers = @get_headers( $file );
  
          	if ( is_array( $file_headers ) && $file_headers[0] == 'HTTP/1.1 404 Not Found' ) {
           		die( esc_html__( 'File is not readable or not found.', 'all-in-one-video-gallery' ) );
           		exit;
          	}          
        } elseif ( $formatted_path == 'filepath' ) {		
          	if ( ! @is_readable( $file ) ) {
				die( esc_html__( 'File is not readable or not found.', 'all-in-one-video-gallery' ) );
               	exit;
          	}
        }
        
       	// Fetching File Size
       	if ( $is_remote_file || $formatted_path == 'url' ) {         
          	$data = @get_headers( $file, true );
          
          	if ( ! empty( $data['Content-Length'] ) ) {
          		$file_size = (int) $data[ 'Content-Length' ];          
          	} else {               
               	// If get_headers fails then try to fetch fileSize with curl
               	$ch = @curl_init();

               	if ( ! @curl_setopt( $ch, CURLOPT_URL, $file ) ) {
                 	@curl_close( $ch );
                 	@exit;
               	}
               
               	@curl_setopt( $ch, CURLOPT_NOBODY, true );
               	@curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
               	@curl_setopt( $ch, CURLOPT_HEADER, true );
               	@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
               	@curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
               	@curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
               	@curl_exec( $ch );
               
               	if ( ! @curl_errno( $ch ) ) {
                	$http_status = (int) @curl_getinfo( $ch, CURLINFO_HTTP_CODE );
                    if ( $http_status >= 200 && $http_status <= 300 )
                    	$file_size = (int) @curl_getinfo( $ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD );
               	}

               	@curl_close( $ch );               
          	}          
		} elseif ( $formatted_path == 'filepath' ) {		   
		    $file_size = (int) @filesize( $file );			   			   
       	}
          
		// Get the extension of the file
		$path = @parse_url( $file, PHP_URL_PATH ); 
		$ext  = @pathinfo( $path, PATHINFO_EXTENSION );

		switch ( $ext ) {          
			case 'mp3':
				$mime_type = "audio/mpeg";
				break;
			case 'wav':
				$mime_type = "audio/x-wav";
				break;
			case 'au':
				$mime_type = "audio/basic";
				break;
			case 'snd':
				$mime_type = "audio/basic";
				break;
			case 'm3u':
				$mime_type = "audio/x-mpegurl";
				break;
			case 'ra':
				$mime_type = "audio/x-pn-realaudio";
				break;
			case 'mp2':
				$mime_type = "video/mpeg";
				break;
			case 'mov':
				$mime_type = "video/quicktime";
				break;
			case 'qt':
				$mime_type = "video/quicktime";
				break;
			case 'mp4':
				$mime_type = "video/mp4";
				break;
			case 'webm':
				$mime_type = "video/webm";
				break;
			case 'ogv':
				$mime_type = "video/ogg";
				break;
			case 'm4v':
				$mime_type = "video/x-m4v";
				break;
			case 'm4a':
				$mime_type = "audio/mp4";
				break;
			case 'mp4a':
				$mime_type = "audio/mp4";
				break;
			case 'm4p':
				$mime_type = "audio/mp4";
				break;
			case 'm3a':
				$mime_type = "audio/mpeg";
				break;
			case 'm2a':
				$mime_type = "audio/mpeg";
				break;
			case 'mp2a':
				$mime_type = "audio/mpeg";
				break;
			case 'mp2':
				$mime_type = "audio/mpeg";
				break;
			case 'mpga':
				$mime_type = "audio/mpeg";
				break;
			case '3gp':
				$mime_type = "video/3gpp";
				break;
			case '3g2':
				$mime_type = "video/3gpp2";
				break;
			case 'mp4v':
				$mime_type = "video/mp4";
				break;
			case 'mpg4':
				$mime_type = "video/mp4";
				break;
			case 'm2v':
				$mime_type = "video/mpeg";
				break;
			case 'm1v':
				$mime_type = "video/mpeg";
				break;
			case 'mpe':
				$mime_type = "video/mpeg";
				break;
			case 'avi':
				$mime_type = "video/x-msvideo";
				break;
			case 'midi':
				$mime_type = "audio/midi";
				break;
			case 'mid':
				$mime_type = "audio/mid";
				break;
			case 'amr':
				$mime_type = "audio/amr";
				break;            
		
			default:
				$mime_type = "application/octet-stream";
		}

		if ( 'application/octet-stream' == $mime_type ) {
			die( esc_html__( 'Invalid file format.', 'all-in-one-video-gallery' ) );
			exit;
		}
        
        // Off output buffering to decrease Server usage
        @ob_end_clean();
        
        if ( ini_get( 'zlib.output_compression' ) ) {
        	ini_set( 'zlib.output_compression', 'Off' );
        }
        
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: '. $mime_type );
        header( 'Content-Disposition: attachment; filename=' . (string) @basename( $file ) );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Expires: Wed, 07 May 2013 09:09:09 GMT' );
	    header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
	    header( 'Cache-Control: post-check=0, pre-check=0', false );
	    header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	    header( 'Pragma: no-cache' );
        header( 'Content-Length: '. $file_size);        
        
        // Will Download 1 MB in chunkwise
        $chunk = 1 * ( 1024 * 1024 );

        if ( $nfile = @fopen( $file, 'rb' ) ) {
			while ( ! feof( $nfile ) ) {                 
				print( @fread( $nfile, $chunk ) );
				@ob_flush();
				@flush();
			}
			@fclose( $nfile );
		}		
	}
	
}