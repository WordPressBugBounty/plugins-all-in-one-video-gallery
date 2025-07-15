<?php

/**
 * Videos
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
 * AIOVG_Admin_Videos class.
 *
 * @since 1.0.0
 */
class AIOVG_Admin_Videos {

	/**
	 * Add "All Videos" menu.
	 *
	 * @since 1.6.5
	 */
	public function admin_menu() {	
		add_submenu_page(
			'all-in-one-video-gallery',
			__( 'All-in-One Video Gallery - Videos', 'all-in-one-video-gallery' ),
			__( 'All Videos', 'all-in-one-video-gallery' ),
			'manage_aiovg_options',
			'edit.php?post_type=aiovg_videos'
		);
		
		add_submenu_page(
			'all-in-one-video-gallery',
			__( 'Add New Video', 'all-in-one-video-gallery' ),
			__( 'Add New', 'all-in-one-video-gallery' ),
			'manage_aiovg_options',
			'post-new.php?post_type=aiovg_videos'
		);
	}

	/**
	 * Move "All Videos" submenu under our plugin's main menu.
	 *
	 * @since  1.6.5
	 * @param  string $parent_file The parent file.
	 * @return string $parent_file The parent file.
	 */
	public function parent_file( $parent_file ) {	
		global $submenu_file, $current_screen;

		if ( 'aiovg_videos' == $current_screen->post_type ) {
			$submenu_file = 'edit.php?post_type=aiovg_videos';
			$parent_file  = 'all-in-one-video-gallery';
		}

		return $parent_file;
	}

	/**
	 * Register the custom post type "aiovg_videos".
	 *
	 * @since 1.0.0
	 */
	public function register_post_type() {			
		$featured_images_settings = get_option( 'aiovg_featured_images_settings', array() );
		$permalink_settings = get_option( 'aiovg_permalink_settings' );
		
		$labels = array(
			'name'                  => _x( 'Videos', 'Post Type General Name', 'all-in-one-video-gallery' ),
			'singular_name'         => _x( 'Video', 'Post Type Singular Name', 'all-in-one-video-gallery' ),
			'menu_name'             => __( 'Video Gallery', 'all-in-one-video-gallery' ),
			'name_admin_bar'        => __( 'Video', 'all-in-one-video-gallery' ),
			'archives'              => __( 'Video Archives', 'all-in-one-video-gallery' ),
			'attributes'            => __( 'Video Attributes', 'all-in-one-video-gallery' ),
			'parent_item_colon'     => __( 'Parent Video:', 'all-in-one-video-gallery' ),
			'all_items'             => __( 'All Videos', 'all-in-one-video-gallery' ),
			'add_new_item'          => __( 'Add New Video', 'all-in-one-video-gallery' ),
			'add_new'               => __( 'Add New', 'all-in-one-video-gallery' ),
			'new_item'              => __( 'New Video', 'all-in-one-video-gallery' ),
			'edit_item'             => __( 'Edit Video', 'all-in-one-video-gallery' ),
			'update_item'           => __( 'Update Video', 'all-in-one-video-gallery' ),
			'view_item'             => __( 'View Video', 'all-in-one-video-gallery' ),
			'view_items'            => __( 'View Videos', 'all-in-one-video-gallery' ),
			'search_items'          => __( 'Search Video', 'all-in-one-video-gallery' ),
			'not_found'             => __( 'No videos found', 'all-in-one-video-gallery' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'all-in-one-video-gallery' ),
			'featured_image'        => __( 'Featured Image', 'all-in-one-video-gallery' ),
			'set_featured_image'    => __( 'Set featured image', 'all-in-one-video-gallery' ),
			'remove_featured_image' => __( 'Remove featured image', 'all-in-one-video-gallery' ),
			'use_featured_image'    => __( 'Use as featured image', 'all-in-one-video-gallery' ),
			'insert_into_item'      => __( 'Insert into video', 'all-in-one-video-gallery' ),
			'uploaded_to_this_item' => __( 'Uploaded to this video', 'all-in-one-video-gallery' ),
			'items_list'            => __( 'Videos list', 'all-in-one-video-gallery' ),
			'items_list_navigation' => __( 'Videos list navigation', 'all-in-one-video-gallery' ),
			'filter_items_list'     => __( 'Filter videos list', 'all-in-one-video-gallery' ),
		);
		
		$supports = array( 'title', 'editor', 'author', 'excerpt', 'comments' );			

		$has_thumbnail = isset( $featured_images_settings['enabled'] ) ? (int) $featured_images_settings['enabled'] : 0;
		if ( $has_thumbnail == 1 ) {
			$supports[] = 'thumbnail';
		}
		
		$args = array(
			'label'                 => __( 'Video', 'all-in-one-video-gallery' ),
			'description'           => __( 'Video Description', 'all-in-one-video-gallery' ),
			'labels'                => $labels,
			'supports'              => $supports,
			'taxonomies'            => array(),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'show_in_nav_menus'     => true,
			'show_in_admin_bar'     => true,
			'show_in_rest'          => true,
			'can_export'            => true,
			'has_archive'           => true,		
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'aiovg_video',
			'map_meta_cap'          => true,
		);

		if ( is_array( $permalink_settings ) ) {
			if ( ! empty( $permalink_settings['video'] ) ) {
				$args['rewrite'] = array(
					'slug' => sanitize_title( $permalink_settings['video'] )
				);
			}

			if ( ! empty( $permalink_settings['video_archive_page'] ) ) {
				$page_id = (int) $permalink_settings['video_archive_page'];
				$post    = get_post( $page_id );

				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $page_id );

					if ( $permalink ) {
						$site_url = trailingslashit( home_url() );

						$slug = str_replace( $site_url, '', $permalink );            
						$slug = trim( $slug, '/' );
						$slug = urldecode( $slug );

						$args['rewrite'] = array(
							'slug' => $slug
						);

						$args['has_archive'] = false;
					}
				}
			}
		}
		
		register_post_type( 'aiovg_videos', $args );	
	}
	
	/**
	 * Adds custom meta fields in the "Publish" meta box.
	 *
	 * @since 1.0.0
	 */
	public function post_submitbox_misc_actions() {	
		global $post, $post_type;
		
		if ( 'aiovg_videos' == $post_type ) {
			$post_id  = $post->ID;
			$featured = get_post_meta( $post_id, 'featured', true );

			require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-submitbox.php';
		}		
	}
	
	/**
	 * Register meta boxes.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box( 
			'aiovg-video-metabox', 
			__( 'Video', 'all-in-one-video-gallery' ), 
			array( $this, 'display_meta_box_video' ), 
			'aiovg_videos', 
			'normal', 
			'high' 
		);
	}

	/**
	 * Display "Video" meta box.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post WordPress Post object.
	 */
	public function display_meta_box_video( $post ) {
		$post_meta = get_post_meta( $post->ID );
		$post_meta = apply_filters( 'aiovg_get_post_meta', $post_meta, $post->ID, '', false, 'aiovg_videos' );

		require_once AIOVG_PLUGIN_DIR . 'admin/partials/video-metabox.php';
	}
	
	/**
	 * Save meta data.
	 *
	 * @since  1.0.0
	 * @param  int     $post_id Post ID.
	 * @param  WP_Post $post    The post object.
	 * @return int     $post_id If the save was successful or not.
	 */
	public function save_meta_data( $post_id, $post ) {	
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
		
		// Check if "aiovg_video_submitbox_nonce" nonce is set
    	if ( isset( $_POST['aiovg_video_submitbox_nonce'] ) ) {		
			// Verify that the nonce is valid
    		if ( wp_verify_nonce( $_POST['aiovg_video_submitbox_nonce'], 'aiovg_save_video_submitbox' ) ) {			
				// OK to save meta data.
				$featured = isset( $_POST['featured'] ) ? 1 : 0;
    			update_post_meta( $post_id, 'featured', $featured );				
			}			
		} else {
			$featured = (int) get_post_meta( $post_id, 'featured', true );
			update_post_meta( $post_id, 'featured', $featured );
		}
		
		// Check if "aiovg_video_metabox_nonce" nonce is set
    	if ( isset( $_POST['aiovg_video_metabox_nonce'] ) ) {		
			// Verify that the nonce is valid
    		if ( wp_verify_nonce( $_POST['aiovg_video_metabox_nonce'], 'aiovg_save_video_metabox' ) ) {			
				// OK to save meta data		
				$featured_images_settings = get_option( 'aiovg_featured_images_settings' );

				$type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'default';
				update_post_meta( $post_id, 'type', $type );
				
				$mp4 = isset( $_POST['mp4'] ) ? aiovg_sanitize_url( $_POST['mp4'] ) : '';
				update_post_meta( $post_id, 'mp4', $mp4 );
				update_post_meta( $post_id, 'mp4_id', attachment_url_to_postid( $mp4, 'video' ) );
				
				$has_webm = isset( $_POST['has_webm'] ) ? 1 : 0;
				update_post_meta( $post_id, 'has_webm', $has_webm );
				
				$webm = isset( $_POST['webm'] ) ? aiovg_sanitize_url( $_POST['webm'] ) : '';
				update_post_meta( $post_id, 'webm', $webm );
				update_post_meta( $post_id, 'webm_id', attachment_url_to_postid( $webm, 'video' ) );
				
				$has_ogv = isset( $_POST['has_ogv'] ) ? 1 : 0;
				update_post_meta( $post_id, 'has_ogv', $has_ogv );
				
				$ogv = isset( $_POST['ogv'] ) ? aiovg_sanitize_url( $_POST['ogv'] ) : '';
				update_post_meta( $post_id, 'ogv', $ogv );
				update_post_meta( $post_id, 'ogv_id', attachment_url_to_postid( $ogv, 'video' ) );

				$quality_level = isset( $_POST['quality_level'] ) ? sanitize_text_field( $_POST['quality_level'] ) : '';
				update_post_meta( $post_id, 'quality_level', $quality_level );

				if ( ! empty( $_POST['sources'] ) && ! empty( $_POST['quality_levels'] ) ) {					
					$values = array();

					$quality_levels = array_map( 'sanitize_text_field', $_POST['quality_levels'] );
					$sources = array_map( 'aiovg_sanitize_url', $_POST['sources'] );

					foreach ( $sources as $index => $source ) {
						if ( ! empty( $source ) && ! empty( $quality_levels[ $index ] ) ) {
							$values[] = array(
								'quality' => $quality_levels[ $index ],
								'src'     => $source
							);
						}
					}

					update_post_meta( $post_id, 'sources', $values );
				}

				$hls = isset( $_POST['hls'] ) ? aiovg_sanitize_url( $_POST['hls'] ) : '';
				update_post_meta( $post_id, 'hls', $hls );
				
				$dash = isset( $_POST['dash'] ) ? aiovg_sanitize_url( $_POST['dash'] ) : '';
				update_post_meta( $post_id, 'dash', $dash );
				
				$youtube = isset( $_POST['youtube'] ) ? aiovg_sanitize_url( aiovg_resolve_youtube_url( $_POST['youtube'] ) ) : '';
				update_post_meta( $post_id, 'youtube', $youtube );
				
				$vimeo = isset( $_POST['vimeo'] ) ? aiovg_sanitize_url( $_POST['vimeo'] ) : '';
				update_post_meta( $post_id, 'vimeo', $vimeo );
				
				$dailymotion = isset( $_POST['dailymotion'] ) ? aiovg_sanitize_url( $_POST['dailymotion'] ) : '';
				update_post_meta( $post_id, 'dailymotion', $dailymotion );

				$rumble = isset( $_POST['rumble'] ) ? aiovg_sanitize_url( $_POST['rumble'] ) : '';
				update_post_meta( $post_id, 'rumble', $rumble );
				
				$facebook = isset( $_POST['facebook'] ) ? aiovg_sanitize_url( $_POST['facebook'] ) : '';
				update_post_meta( $post_id, 'facebook', $facebook );
				
				add_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
				$embedcode = isset( $_POST['embedcode'] ) ? wp_kses_post( str_replace( "'", '"', $_POST['embedcode'] ) ) : '';
				update_post_meta( $post_id, 'embedcode', $embedcode );
				remove_filter( 'wp_kses_allowed_html', 'aiovg_allow_iframe_script_tags' );
				
				$duration = '';

				if ( ! empty( $_POST['duration'] ) ) {
					$duration = sanitize_text_field( $_POST['duration'] );
				} else {
					if ( 'vimeo' == $type && ! empty( $vimeo ) ) {
						$duration = aiovg_get_vimeo_video_duration( $vimeo );
					} elseif ( 'dailymotion' == $type && ! empty( $dailymotion ) ) {
						$duration = aiovg_get_dailymotion_video_duration( $dailymotion );
					} elseif ( 'rumble' == $type && ! empty( $rumble ) ) {
						$duration = aiovg_get_rumble_video_duration( $rumble );
					} elseif ( 'embedcode' == $type && ! empty( $embedcode ) ) {
						$duration = aiovg_get_embedcode_video_duration( $embedcode );
					}

					$duration = aiovg_convert_seconds_to_human_time( $duration );
				}

				update_post_meta( $post_id, 'duration', $duration );
				
				$views = isset( $_POST['views'] ) ? (int) $_POST['views'] : 0;
				update_post_meta( $post_id, 'views', $views );

				$likes = isset( $_POST['likes'] ) ? (int) $_POST['likes'] : 0;
				update_post_meta( $post_id, 'likes', $likes );

				$dislikes = isset( $_POST['dislikes'] ) ? (int) $_POST['dislikes'] : 0;
				update_post_meta( $post_id, 'dislikes', $dislikes );
				
				$download = isset( $_POST['download'] ) ? (int) $_POST['download'] : 0;
				update_post_meta( $post_id, 'download', $download );
	
				// Poster Image	
				$image    = '';
				$image_id = 0;

				if ( ! empty( $_POST['image'] ) ) {
					$image    = aiovg_sanitize_url( $_POST['image'] );
					$image_id = attachment_url_to_postid( $image, 'image' );
				} else {
					if ( 'youtube' == $type && ! empty( $youtube ) ) {
						$image = aiovg_get_youtube_image_url( $youtube );
					} elseif ( 'vimeo' == $type && ! empty( $vimeo ) ) {
						$image = aiovg_get_vimeo_image_url( $vimeo );
					} elseif ( 'dailymotion' == $type && ! empty( $dailymotion ) ) {
						$image = aiovg_get_dailymotion_image_url( $dailymotion );
					} elseif ( 'rumble' == $type && ! empty( $rumble ) ) {
						$image = aiovg_get_rumble_image_url( $rumble );
					} elseif ( 'embedcode' == $type && ! empty( $embedcode ) ) {
						$image = aiovg_get_embedcode_image_url( $embedcode );
					}
				}

				if ( ! empty( $featured_images_settings['enabled'] ) ) { // Set featured image
					$set_featured_image = isset( $_POST['set_featured_image'] ) ? (int) $_POST['set_featured_image'] : 0;
					update_post_meta( $post_id, 'set_featured_image', $set_featured_image );
					
					if ( empty( $image ) ) {
						$set_featured_image = 0;
					} else {
						if ( isset( $_POST['images'] ) ) { // Has images from thumbnail generator?
							$images = array_map( 'aiovg_sanitize_url', $_POST['images'] );
	
							foreach ( $images as $__image ) {		
								if ( $__image == $image ) {
									$set_featured_image = 0;
									break;
								}
							}
						}
					}					

					if ( ! empty( $set_featured_image ) ) {
						if ( empty( $image_id ) && ! empty( $featured_images_settings['download_external_images'] ) ) {
							$image_id = aiovg_create_attachment_from_external_image_url( $image, $post_id );
						}

						if ( ! empty( $image_id ) ) {
							set_post_thumbnail( $post_id, $image_id ); 
						}
					}
				}
				
				update_post_meta( $post_id, 'image', $image );
				update_post_meta( $post_id, 'image_id', $image_id );

				$image_alt = isset( $_POST['image_alt'] ) ? sanitize_text_field( $_POST['image_alt'] ) : '';
				update_post_meta( $post_id, 'image_alt', $image_alt );

				// Subtitles
				delete_post_meta( $post_id, 'track' );
				
				if ( ! empty( $_POST['track_src'] ) ) {				
					$sources = $_POST['track_src'];
					$sources = array_map( 'trim', $sources );	
					$sources = array_filter( $sources );
					
					foreach ( $sources as $key => $source ) {
						$track = array(
							'src'     => aiovg_sanitize_url( $source ),
							'src_id'  => attachment_url_to_postid( $source, 'track' ),  
							'label'   => sanitize_text_field( $_POST['track_label'][ $key ] ),
							'srclang' => sanitize_text_field( $_POST['track_srclang'][ $key ] )
						);
						
						add_post_meta( $post_id, 'track', $track );
					}					
				}

				// Chapters
				delete_post_meta( $post_id, 'chapter' );
				
				if ( ! empty( $_POST['chapter_time'] ) ) {				
					foreach ( $_POST['chapter_time'] as $key => $value ) {						
						$time  = sanitize_text_field( $_POST['chapter_time'][ $key ] );
						$label = sanitize_text_field( $_POST['chapter_label'][ $key ] );

						if ( empty( $time ) || empty( $label ) ) continue;

						$chapter = array(
							'time'  => $time,
							'label' => $label
						);
						
						add_post_meta( $post_id, 'chapter', $chapter );
					}
				}

				// Restrictions: Check if "aiovg_video_restrictions_nonce" nonce is set
				if ( isset( $_POST['aiovg_video_restrictions_nonce'] ) ) {
					// Verify that the nonce is valid
					if ( wp_verify_nonce( $_POST['aiovg_video_restrictions_nonce'], 'aiovg_save_video_restrictions' ) ) {			
						// OK to save meta data
						$access_control = isset( $_POST['access_control'] ) ? (int) $_POST['access_control'] : -1;
						update_post_meta( $post_id, 'access_control', $access_control );
		
						$restricted_roles = isset( $_POST['restricted_roles'] ) ? array_map( 'sanitize_text_field', $_POST['restricted_roles'] ) : array();
						update_post_meta( $post_id, 'restricted_roles', $restricted_roles );
					}
				}
			}
		}
		
		return $post_id;	
	}
	
	/**
	 * Print footer scripts.
	 *
	 * @since 4.0.1
	 */
	public function print_footer_scripts() {
		if ( defined( 'AIOVG_DISABLE_TOUR' ) && AIOVG_DISABLE_TOUR ) {
			return false;
		}

		if ( ! current_user_can( 'manage_aiovg_options' ) ) {
			return false;
		}

		global $pagenow, $typenow;		
		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) || 'aiovg_videos' != $typenow ) {
			return false;
		}

		$current_user_id = get_current_user_id();
		$video_form_tour = get_user_meta( $current_user_id, 'aiovg_video_form_tour', true );
		$automatic_tour_enabled = 0;

		if ( 'completed' == $video_form_tour ) {
			return false;
		}		

		if ( '' == $video_form_tour ) {
			$automatic_tour_enabled = 1;

			$args = array(				
				'post_type' => 'aiovg_videos',			
				'posts_per_page' => 1,
				'fields' => 'ids',
				'no_found_rows' => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false
			);
			
			$aiovg_query = new WP_Query( $args );
			
			if ( $aiovg_query->have_posts() ) {
				$automatic_tour_enabled = 0;
			}
		}

		$l10n = array(
			'take_a_guided_tour' => __( 'Take a Guided Tour', 'all-in-one-video-gallery' ),
			'progress_text'      => sprintf( __( '%s of %s', 'all-in-one-video-gallery' ), '{{current}}', '{{total}}' ),
			'next_btn_text'      => __( 'Next →', 'all-in-one-video-gallery' ),
			'prev_btn_text'      => __( '← Previous', 'all-in-one-video-gallery' ),
			'done_btn_text'      => __( 'Done', 'all-in-one-video-gallery' ),
		);

		$steps = array(
			array(
				'popover' => array(
					'title'       => __( 'Welcome to the Quick Tour', 'all-in-one-video-gallery' ),
					'description' => __( 'This form lets you add or edit videos for your gallery.<br><br>Let\'s walk through the key steps to get your first video published.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#title',
				'popover' => array(
					'title'       => __( 'Video Title', 'all-in-one-video-gallery' ),
					'description' => __( 'Start by entering a <strong>clear, descriptive title</strong> for your video.<br><br>This helps visitors (and search engines) understand what your video is about.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#postdivrich',
				'popover' => array(
					'title'       => __( 'Video Description', 'all-in-one-video-gallery' ),
					'description' => __( 'Next, add a <strong>description</strong> for your video.<br><br>This is optional, but highly recommended, as it helps with <strong>SEO</strong>, provides context to your audience, and even shows up when sharing videos on social media.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#aiovg-field-type',
				'popover' => array(
					'title'       => __( 'Source Type', 'all-in-one-video-gallery' ),
					'description' => __( 'Choose where your video is hosted — <strong>YouTube</strong>, <strong>Vimeo</strong>, <strong>Self-Hosted</strong>, or another source.<br><br>This helps the plugin know how to handle your video.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '%%video-field%%',
				'popover' => array(
					'title'       => __( 'Video File Input', 'all-in-one-video-gallery' ),
					'description' => __( 'Depending on the source you selected, either <strong>upload a video file</strong> or <strong>paste the video URL</strong>.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#aiovg-field-image',
				'popover' => array(
					'title'       => __( 'Poster Image', 'all-in-one-video-gallery' ),
					'description' => sprintf(
						__( 'Upload a <strong>poster image</strong> for your video.<br><br>This image appears in the gallery and before the video plays.<br><br>Leave this field empty for <strong>YouTube, Vimeo, Dailymotion, and Rumble</strong> to automatically fetch thumbnails.<br><br>Want automatic thumbnails for <strong>self-hosted videos</strong>? Check out our <a href="%s" target="_blank" rel="noopener noreferrer">Premium Add-on</a>.', 'all-in-one-video-gallery' ),
						'https://plugins360.com/all-in-one-video-gallery/auto-thumbnail-generator/'
					)
				)
			),
			array(
				'element' => '#aiovg_categoriesdiv',
				'popover' => array(
					'title'       => __( 'Video Categories', 'all-in-one-video-gallery' ),
					'description' => __( 'Assign your video to <strong>categories</strong> to help visitors find related content.<br><br><strong>Need a new category?</strong> Just click "+ Add New Category" to create one.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#tagsdiv-aiovg_tags',
				'popover' => array(
					'title'       => __( 'Video Tags', 'all-in-one-video-gallery' ),
					'description' => __( 'Add <strong>tags</strong> to describe your video in more detail.<br><br>Tags work like keywords and help visitors find similar videos.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#publish',
				'popover' => array(
					'title'       => __( 'Save Your Video', 'all-in-one-video-gallery' ),
					'description' => __( 'All set? Great!<br><br>Click <strong>"Publish"</strong> to save your video.<br><br>Repeat these steps to add as many videos as you want.', 'all-in-one-video-gallery' )
				)
			),
			array(
				'element' => '#toplevel_page_all-in-one-video-gallery',
				'popover' => array(
					'title'       => __( 'Next Steps', 'all-in-one-video-gallery' ),
					'description' => sprintf(
						__( 'That\'s it — you\'re all set!<br><br>Use the menu on the left to manage your <strong>Videos</strong>, <strong>Categories</strong>, and <strong>Tags</strong>. You can also customize the plugin in <strong>Settings</strong>.<br><br><strong>To display your videos on a page:</strong><br>Simply add this shortcode to any page or post: <code>[aiovg_videos filters="all"]</code><br><br>Want to customize how your gallery looks? <a href="%s" target="_blank" rel="noopener noreferrer">Check out this guide</a> to explore all the display options.', 'all-in-one-video-gallery' ),
						'https://plugins360.com/all-in-one-video-gallery/displaying-video-gallery/'
					),
					'align'       => 'center'
				)
			)
		);						
		?>
		<script type="text/javascript">
			(function( $ ) {
				'use strict';

				function initTour() {
					const type = $( '#aiovg-video-type' ).val();

					const driver = window.driver.js.driver;

					let steps = <?php echo json_encode( $steps ); ?>;
					steps.forEach(step => {
						if ( step.element == '%%video-field%%' ) {							
							step.element = '.aiovg-type-' + type;
						}
					});

					const driverObj = driver({
						steps: steps,
						disableActiveInteraction: true,
						popoverClass: 'driverjs-theme',
						showProgress: true,
						progressText: "<?php echo esc_html( $l10n['progress_text'] ); ?>",
						nextBtnText: "<?php echo esc_html( $l10n['next_btn_text'] ); ?>",
						prevBtnText: "<?php echo esc_html( $l10n['prev_btn_text'] ); ?>",
						doneBtnText: "<?php echo esc_html( $l10n['done_btn_text'] ); ?>",												
						onDestroyStarted: () => {
							const data = {
								'action': 'aiovg_store_user_meta',
								'key': 'aiovg_video_form_tour',				
								'value': ( driverObj.isLastStep() ? 'completed' : driverObj.getActiveIndex() ),
								'security': aiovg_admin.ajax_nonce
							};

							$.post( ajaxurl, data, function( response ) {
								// Do Nothing
							});

							driverObj.destroy();
						}
					});

					driverObj.drive();
				}
				
				$(function() {
					// Insert the "Take a Guided Tour" button
					const button = '<button type="button" id="aiovg-button-tour" class="page-title-action">' + 
						'<span class="dashicons dashicons-controls-repeat"></span> ' +
						'<?php echo esc_html( $l10n['take_a_guided_tour'] ); ?>' + 
						'</button>';

					$( button ).insertBefore( '.wp-header-end' );

					// Init Driver
					$( '#aiovg-button-tour' ).on( 'click', function( event ) {
						event.preventDefault();
						initTour();
					});	
					
					const automaticTourEnabled = <?php echo (int) $automatic_tour_enabled; ?>;
					if ( automaticTourEnabled == 1 ) {
						initTour();
					}
				});
			})( jQuery );
		</script>
		<?php
	}

	/**
	 * Add custom filter options.
	 *
	 * @since 1.0.0
	 */
	public function restrict_manage_posts() {	
		global $typenow, $wp_query;
		
		if ( 'aiovg_videos' == $typenow ) {			
			// Restrict by category
        	wp_dropdown_categories(array(
            	'show_option_none'  => __( "All Categories", 'all-in-one-video-gallery' ),
				'option_none_value' => 0,
            	'taxonomy'          => 'aiovg_categories',
            	'name'              => 'aiovg_categories',
            	'orderby'           => 'name',
            	'selected'          => isset( $wp_query->query['aiovg_categories'] ) ? $wp_query->query['aiovg_categories'] : '',
            	'hierarchical'      => true,
            	'depth'             => 3,
            	'show_count'        => false,
            	'hide_empty'        => false,
        	));			
			
			// Restrict by custom filtering options	
			if ( current_user_can( 'manage_aiovg_options' ) ) {
				$selected = isset( $_GET['aiovg_filter'] ) ? sanitize_text_field( $_GET['aiovg_filter'] ) : '';

				$options  = array(
					''         => __( 'All Videos', 'all-in-one-video-gallery' ),
					'featured' => __( 'Featured Only', 'all-in-one-video-gallery' )
				);

				$options = apply_filters( 'aiovg_admin_videos_custom_filters', $options );

				echo '<select name="aiovg_filter">';
				foreach ( $options as $value => $label ) {
					printf( '<option value="%s"%s>%s</option>', $value, selected( $value, $selected, false ), $label );
				}
				echo '</select>';
			}
    	}	
	}
	
	/**
	 * Parse a query string and filter listings accordingly.
	 *
	 * @since 1.0.0
	 * @param WP_Query $query WordPress Query object.
	 */
	public function parse_query( $query ) {	
		global $pagenow, $post_type;
		
    	if ( 'edit.php' == $pagenow && 'aiovg_videos' == $post_type ) {			
			// Convert category id to taxonomy term in query
			if ( isset( $query->query_vars['aiovg_categories'] ) && ctype_digit( $query->query_vars['aiovg_categories'] ) && 0 != $query->query_vars['aiovg_categories'] ) {		
        		$term = get_term_by( 'id', $query->query_vars['aiovg_categories'], 'aiovg_categories' );
        		$query->query_vars['aiovg_categories'] = $term->slug;			
			}
			
			// Convert tag id to taxonomy term in query
			if ( isset( $query->query_vars['aiovg_tags'] ) && ctype_digit( $query->query_vars['aiovg_tags'] ) && 0 != $query->query_vars['aiovg_tags'] ) {		
        		$term = get_term_by( 'id', $query->query_vars['aiovg_tags'], 'aiovg_tags' );
        		$query->query_vars['aiovg_tags'] = $term->slug;			
    		}

			// Set featured meta in query
			$query->query_vars['meta_query'] = array(
				'relation' => 'AND'
			);

			if ( isset( $_GET['aiovg_filter'] ) && 'featured' == $_GET['aiovg_filter'] ) {		
        		$query->query_vars['meta_query']['featured'] = array(
					'key'   => 'featured',
					'value' => 1
				);			
    		}
			
			// Sort by views
			if ( isset( $_GET['orderby'] ) && 'views' == $_GET['orderby'] ) {
				$query->query_vars['meta_query']['views'] = array(
					'key'     => 'views',
					'compare' => 'EXISTS'
				);

				$query->query_vars['orderby'] = 'views';
			}
		}	
	}

	/**
	 * Filters the array of row action links.
	 *
	 * @since  2.5.1
	 * @param  array   $actions An array of row action links.
	 * @param  WP_Post $post    The post object.
	 * @return array            Filtered array of row action links.
	 */
	public function row_actions( $actions, $post ) {
		if ( $post->post_type == 'aiovg_videos' ) {
			// Copy URL
			$copy_shortcode = sprintf( 
				'<a class="aiovg-copy-url" href="javascript: void(0);" data-url="%s">%s</a>',
				get_permalink( $post->ID ),
				esc_html__( 'Copy URL', 'all-in-one-video-gallery' )
			);

			$actions['copy-url'] = $copy_shortcode;

			// Copy Shortcode
			$copy_shortcode = sprintf( 
				'<a class="aiovg-copy-shortcode" href="javascript: void(0);" data-id="%d">%s</a>',
				$post->ID,
				esc_html__( 'Copy Shortcode', 'all-in-one-video-gallery' )
			);

			$actions['copy-shortcode'] = $copy_shortcode;
		}

		return $actions;
	}
	
	/**
	 * Retrieve the table columns.
	 *
	 * @since  1.0.0
	 * @param  array $columns Array of default table columns.
	 * @return array          Filtered columns array.
	 */
	public function get_columns( $columns ) {
		$columns = aiovg_insert_array_after( 'cb', $columns, array( 
			'image' => ''
		));

		$columns = aiovg_insert_array_after( 'taxonomy-aiovg_tags', $columns, array(
			'post_meta' => __( 'Additional Info', 'all-in-one-video-gallery' ),
			'post_id'   => __( 'ID', 'all-in-one-video-gallery' )
		));

		$columns['taxonomy-aiovg_categories'] = __( 'Categories', 'all-in-one-video-gallery' );
		$columns['taxonomy-aiovg_tags'] = __( 'Tags', 'all-in-one-video-gallery' );
		
		return $columns;		
	}

	/**
	 * Retrieve the sortable table columns.
	 *
	 * @since  2.5.1
	 * @param  array $columns Array of default sortable columns.
	 * @return array          Filtered sortable columns array.
	 */
	public function sortable_columns( $columns ) {			
		$columns['post_id'] = 'post_id';
		return $columns;		
	}
	
	/**
	 * This function renders the custom columns in the list table.
	 *
	 * @since 1.0.0
	 * @param string $column  The name of the column.
	 * @param string $post_id Post ID.
	 */
	public function custom_column_content( $column, $post_id ) {	
		switch ( $column ) {
			case 'image':
				$image_data = aiovg_get_image( $post_id, 'thumbnail', 'post', true );

				printf(
					'<img src="%s" alt="" style="width: 75px;" />',
					$image_data['src']
				);
				break;
			case 'post_meta':
				$meta = array();

				// Views
				$views = (int) get_post_meta( $post_id, 'views', true );

				$meta[] = sprintf( 
					'<span class="aiovg-views-meta">%s: %d</span>', 
					esc_html__( 'Views', 'all-in-one-video-gallery' ),
					$views
				);

				// Likes
				$likes = (int) get_post_meta( $post_id, 'likes', true );

				$meta[] = sprintf( 
					'<span class="aiovg-likes-meta">%s: %d</span>', 
					esc_html__( 'Likes', 'all-in-one-video-gallery' ),
					$likes
				);

				// Dislikes
				$dislikes = (int) get_post_meta( $post_id, 'dislikes', true );

				$meta[] = sprintf( 
					'<span class="aiovg-dislikes-meta">%s: %d</span>', 
					esc_html__( 'Dislikes', 'all-in-one-video-gallery' ),
					$dislikes
				);

				// Featured
				if ( current_user_can( 'manage_aiovg_options' ) ) {
					$value = get_post_meta( $post_id, 'featured', true );

					$meta[] = sprintf( 
						'<span class="aiovg-featured-meta">%s: %s</span>', 
						esc_html__( 'Featured', 'all-in-one-video-gallery' ),
						( 1 == $value ? '&#x2713;' : '&#x2717;' ) 
					);
				}
				
				echo implode( '<br />', $meta );
				break;
			case 'post_id':
				echo $post_id;
				break;
		}		
	}
	
	/**
	 * Disable Gutenberg on our custom post type "aiovg_videos".
	 *
	 * @since  2.4.4
	 * @param  bool   $use_block_editor Default status.
	 * @param  string $post_type        The post type being checked.
	 * @return bool   $use_block_editor Filtered editor status.
	 */
	public function disable_gutenberg( $use_block_editor, $post_type ) {
		if ( 'aiovg_videos' === $post_type ) return false;
		return $use_block_editor;
	}
	
	/**
	 * Delete video attachments.
	 *
	 * @since 1.0.0
	 * @param int   $post_id Post ID.
	 */
	public function before_delete_post( $post_id ) {		
		if ( 'aiovg_videos' != get_post_type( $post_id ) ) {
			return;
		}
		  
		aiovg_delete_video_attachments( $post_id );	
	}

}
