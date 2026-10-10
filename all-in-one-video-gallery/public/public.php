<?php

/**
 * The public-facing functionality of the plugin.
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
 * AIOVG_Public class.
 *
 * @since 1.0.0
 */
class AIOVG_Public {
	
	/**
	 * Template redirect handler.
	 *
	 * - Fixes secondary loop pagination issues on single video pages.
	 * - Redirects default video archive to custom archive page, if configured.
	 *
	 * @since 1.5.5
	 */
	public function template_redirect() {
		// Fix pagination on single video pages	
		if ( is_singular( 'aiovg_videos' ) ) {		
			global $wp_query;
			
			$page = (int) $wp_query->get( 'page' );
			if ( $page > 1 ) {
		  		// Convert 'page' to 'paged'
		 	 	$wp_query->set( 'page', 1 );
		 	 	$wp_query->set( 'paged', $page );
			}
			
			// Prevent redirect
			remove_action( 'template_redirect', 'redirect_canonical' );		
	  	}
		
		// Redirect default archive to custom archive page (if configured)
		if ( is_post_type_archive( 'aiovg_videos' ) ) {
			$permalink_settings = aiovg_get_option( 'aiovg_permalink_settings' );

			if ( is_array( $permalink_settings ) && ! empty( $permalink_settings['video_archive_page'] ) ) {
				$page_id  = (int) $permalink_settings['video_archive_page'];
				$page_url = get_permalink( $page_id );

				if ( $page_url ) {
					// Avoid redirect loop if already on the custom archive page
					$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					if ( untrailingslashit( $current_url ) !== untrailingslashit( $page_url ) ) {
						wp_safe_redirect( $page_url, 301 );
						exit;
					}
				}
			}
		}
	}
	
	/**
	 * Add rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$page_settings      = aiovg_get_page_settings();
		$permalink_settings = aiovg_get_option( 'aiovg_permalink_settings' );

		// Rewrite tags
		add_rewrite_tag( '%aiovg_category%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_tag%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_user%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_type%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_video%', '([^/]+)' );
		
		// Rules for every path the page answers on

		// Single category page
		if ( ! empty( $page_settings['category'] ) ) {
			foreach ( aiovg_get_page_rewrite_bases( $page_settings['category'] ) as $id => $slug ) {
				add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&aiovg_category=$matches[1]&paged=$matches[2]', 'top' );
				add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_category=$matches[1]', 'top' );
			}
		}

		// Single tag page
		if ( ! empty( $page_settings['tag'] ) ) {
			foreach ( aiovg_get_page_rewrite_bases( $page_settings['tag'] ) as $id => $slug ) {
				add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&aiovg_tag=$matches[1]&paged=$matches[2]', 'top' );
				add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_tag=$matches[1]', 'top' );
			}
		}
		
		// User videos page
		if ( ! empty( $page_settings['user_videos'] ) ) {
			foreach ( aiovg_get_page_rewrite_bases( $page_settings['user_videos'] ) as $id => $slug ) {
				add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&aiovg_user=$matches[1]&paged=$matches[2]', 'top' );
				add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_user=$matches[1]', 'top' );
			}
		}
		
		// Player page
		if ( ! empty( $page_settings['player'] ) ) {
			foreach ( aiovg_get_page_rewrite_bases( $page_settings['player'] ) as $id => $slug ) {
				add_rewrite_rule( "^$slug/id/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_type=id&aiovg_video=$matches[1]', 'top' );
			}
		}

		// Video archive page
		if ( ! empty( $permalink_settings['video_archive_page'] ) ) {
			foreach ( aiovg_get_page_rewrite_bases( $permalink_settings['video_archive_page'] ) as $id => $slug ) {
				add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?post_type=aiovg_videos&name=$matches[1]&paged=$matches[2]', 'top' );
				add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?post_type=aiovg_videos&name=$matches[1]', 'top' );
				add_rewrite_rule( "^$slug/([^/]+)/embed/?$", 'index.php?post_type=aiovg_videos&name=$matches[1]&embed=true', 'top' );
				
				add_rewrite_rule( "^$slug/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&paged=$matches[1]', 'top' );
			}
		}
	}
	
	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function register_styles() {
		$player_settings = aiovg_get_option( 'aiovg_player_settings' );

		wp_register_style( 
			AIOVG_PLUGIN_SLUG . '-magnific-popup', 
			AIOVG_PLUGIN_URL . 'vendor/magnific-popup/magnific-popup.min.css', 
			array(), 
			'1.2.0', 
			'all' 
		);
		
		wp_register_style( 
			AIOVG_PLUGIN_SLUG . '-icons', 
			AIOVG_PLUGIN_URL . 'public/assets/css/icons.min.css', 
			array(), 
			AIOVG_PLUGIN_VERSION, 
			'all' 
		);
		
		if ( 'vidstack' == $player_settings['player'] ) {
			wp_register_style( 
				AIOVG_PLUGIN_SLUG . '-player', 
				AIOVG_PLUGIN_URL . 'public/assets/css/vidstack.min.css',
				array(), 
				AIOVG_PLUGIN_VERSION, 
				'all' 
			);
		} else {
			wp_register_style( 
				AIOVG_PLUGIN_SLUG . '-player', 
				AIOVG_PLUGIN_URL . 'public/assets/css/videojs.min.css',
				array(), 
				AIOVG_PLUGIN_VERSION, 
				'all' 
			);
		}

		wp_register_style( 
			AIOVG_PLUGIN_SLUG . '-public', 
			AIOVG_PLUGIN_URL . 'public/assets/css/public.min.css', 
			array(), 
			AIOVG_PLUGIN_VERSION, 
			'all' 
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		$likes_settings = aiovg_get_option( 'aiovg_likes_settings' );

		$ajax_url   = aiovg_get_ajax_url();
		$ajax_nonce = wp_create_nonce( 'aiovg_public_ajax_nonce' );
		$user_id    = get_current_user_id();

		// The player and embed scripts append "?action=" themselves, so they need the URL without query args
		$plain_ajax_url = admin_url( 'admin-ajax.php' );

		$scroll_to_top_offset = 20;
		if ( is_admin_bar_showing() ) $scroll_to_top_offset += 32;
		$scroll_to_top_offset = apply_filters( 'aiovg_scroll_to_top_offset', $scroll_to_top_offset );

		// Register the dependencies
		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-magnific-popup', 
			AIOVG_PLUGIN_URL . 'vendor/magnific-popup/magnific-popup.min.js', 
			array( 'jquery' ), 
			'1.2.0', 
			array( 'strategy' => 'defer' ) 
		);		
		
		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-categories', 
			AIOVG_PLUGIN_URL . 'public/assets/js/categories.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-search', 
			AIOVG_PLUGIN_URL . 'public/assets/js/search.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-search', 
			'aiovg_search', 
			array(
				'ajax_url'   => $ajax_url,
				'ajax_nonce' => $ajax_nonce
			) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-pagination', 
			AIOVG_PLUGIN_URL . 'public/assets/js/pagination.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);			

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-pagination', 
			'aiovg_pagination', 
			array(
				'ajax_url'             => $ajax_url,
				'ajax_nonce'           => $ajax_nonce,
				'scroll_to_top_offset' => $scroll_to_top_offset
			) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-select', 
			AIOVG_PLUGIN_URL . 'public/assets/js/select.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-select', 
			'aiovg_select', 
			array(
				'ajax_url'   => $ajax_url,
				'ajax_nonce' => $ajax_nonce,
				'i18n'       => array(				
					'no_tags_found' => __( 'No tags found', 'all-in-one-video-gallery' )
				)
			) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-likes', 
			AIOVG_PLUGIN_URL . 'public/assets/js/likes.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' )
		);

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-likes', 
			'aiovg_likes', 
			array(
				'ajax_url'               => $ajax_url,
				'ajax_nonce'             => $ajax_nonce,
				'user_id'                => $user_id,
				'show_like_button'       => ( ! empty( $likes_settings['like_button'] ) ? 1 : 0 ),
				'show_dislike_button'    => ( ! empty( $likes_settings['dislike_button'] ) ? 1 : 0 ),
				'login_required_to_vote' => ( ! empty( $likes_settings['login_required_to_vote'] ) ? 1 : 0 ),
				'i18n'                   => array(				
					'likes'                => __( 'Likes', 'all-in-one-video-gallery' ),
					'dislikes'             => __( 'Dislikes', 'all-in-one-video-gallery' ),
					'alert_login_required' => __( 'Sorry, you must login to vote.', 'all-in-one-video-gallery' )
				)
			) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-embed', 
			AIOVG_PLUGIN_URL . 'public/assets/js/embed.min.js',
			array(), 
			AIOVG_PLUGIN_VERSION,
			array( 'strategy' => 'defer' )
		);

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-embed', 
			'aiovg_embed',
			array(
				'ajax_url'   => $plain_ajax_url,
				'ajax_nonce' => $ajax_nonce
			) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-player', 
			AIOVG_PLUGIN_URL . 'public/assets/js/videojs.min.js',
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);	
		
		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-player', 
			'aiovg_player',
			array(
				'ajax_url'   => $plain_ajax_url,
				'ajax_nonce' => $ajax_nonce,
				'i18n'       => array(				
					'stream_not_found' => __( 'This stream is currently not live. Please check back or refresh your page.', 'all-in-one-video-gallery' ),
				)
			) 
		);
		
		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-chapters', 
			AIOVG_PLUGIN_URL . 'public/assets/js/chapters.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-chapters', 
			'aiovg_chapters', 
			array(
				'scroll_to_top_offset' => $scroll_to_top_offset
			) 
		);

		wp_register_script( 
			AIOVG_PLUGIN_SLUG . '-public', 
			AIOVG_PLUGIN_URL . 'public/assets/js/public.min.js', 
			array( 'jquery' ), 
			AIOVG_PLUGIN_VERSION, 
			array( 'strategy' => 'defer' ) 
		);			

		wp_localize_script( 
			AIOVG_PLUGIN_SLUG . '-public', 
			'aiovg_public', 
			array(
				'plugin_url'             => AIOVG_PLUGIN_URL,
				'plugin_version'         => AIOVG_PLUGIN_VERSION,
				'user_id'                => $user_id,
				'ajax_url'               => $ajax_url,
				'ajax_nonce'             => $ajax_nonce,				
				'show_like_button'       => ( ! empty( $likes_settings['like_button'] ) ? 1 : 0 ),
				'show_dislike_button'    => ( ! empty( $likes_settings['dislike_button'] ) ? 1 : 0 ),
				'login_required_to_vote' => ( ! empty( $likes_settings['login_required_to_vote'] ) ? 1 : 0 ),
				'scroll_to_top_offset'   => $scroll_to_top_offset,
				'i18n'                   => array(				
					'now_playing'          => __( 'Now Playing', 'all-in-one-video-gallery' ),
					'no_tags_found'        => __( 'No tags found', 'all-in-one-video-gallery' ),
					'likes'                => __( 'Likes', 'all-in-one-video-gallery' ),
					'dislikes'             => __( 'Dislikes', 'all-in-one-video-gallery' ),
					'alert_login_required' => __( 'Sorry, you must login to vote.', 'all-in-one-video-gallery' )
				)
			) 
		);		
	}	

	/**
	 * Enqueue frontend CSS and JavaScript files based on the "force_load_assets" setting.
	 *
	 * @since 4.7.0
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		$general_settings = aiovg_get_option( 'aiovg_general_settings' );
		if ( ! isset( $general_settings['force_load_assets'] ) ) {
			return;
		}

		// Styles
		if ( isset( $general_settings['force_load_assets']['css'] ) ) {
			wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-icons' ); 
			wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' ); 
		}

		// Scripts
		if ( isset( $general_settings['force_load_assets']['js'] ) ) {
			if ( is_singular( 'aiovg_videos' ) ) {
				$player_settings = aiovg_get_option( 'aiovg_player_settings' );	

				if ( isset( $player_settings['controls']['chapters'] ) ) {
					wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-chapters' );
				}
			}

			wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-public' );
		}
	}

	/**
	 * Enqueue block assets inside the block editor (iframe).
	 *
	 * Hooked to enqueue_block_assets with an is_admin() guard so styles and scripts
	 * are injected inside the iframed block editor (WP 6.3+ / WP 7.0 always) only,
	 * and not duplicated on the front end where wp_enqueue_scripts already handles them.
	 *
	 * @since 4.7.6
	 */
	public function enqueue_block_assets() {
		if ( ! is_admin() ) {
			return;
		}

		$this->enqueue_editor_assets();
		wp_add_inline_style( AIOVG_PLUGIN_SLUG . '-public', $this->get_inline_css() );
	}

	/**
	 * Enqueue the plugin's public styles and scripts in any editor context.
	 *
	 * Called by enqueue_block_assets() (WordPress block editor, guarded by is_admin())
	 * and hooked directly to Elementor actions so assets are also available in the
	 * Elementor editor panel and its frontend live-preview iframe:
	 *   - elementor/editor/after_enqueue_scripts  (admin context)
	 *   - elementor/preview/enqueue_scripts       (frontend context, is_admin() = false)
	 *
	 * @since 4.7.6
	 */
	public function enqueue_editor_assets() {
		// Styles
		$this->register_styles();

		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' );

		// Scripts
		$this->register_scripts();
		
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-public' );
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-likes' );
	}

	/**
	 * Filters the allowed query variables.
	 *
	 * @since  4.7.2
	 * @param  array $vars The array of allowed query variable names.
	 * @return array       The filtered query variables.
	 */
	public function query_vars( $vars ) {
		$vars[] = 'aiovg_category';
		$vars[] = 'aiovg_tag';
		$vars[] = 'aiovg_user';
		$vars[] = 'aiovg_type';
		$vars[] = 'aiovg_video';
		$vars[] = 'aiovg_action';
		$vars[] = 'aiovg_playlist';

		return $vars;
	}

	/**
	 * Set MySQL's RAND function seed value in a cookie.
	 *
	 * @since 3.9.3
	 */
	public function set_mysql_rand_seed_value() {
		$privacy_settings = aiovg_get_option( 'aiovg_privacy_settings' );
		
		if ( isset( $privacy_settings['disable_cookies'] ) && isset( $privacy_settings['disable_cookies']['aiovg_rand_seed'] ) ) {
			unset( $_COOKIE['aiovg_rand_seed'] );
			return false;
		}
		
		if ( headers_sent() ) {
			return false;
		}
		
		$paged = aiovg_get_page_number();
		if ( ! isset( $_COOKIE['aiovg_rand_seed'] ) || $paged == 1 ) {
			$seed = wp_rand();
			setcookie( 'aiovg_rand_seed', $seed, time() + ( 86400 * 1 ), COOKIEPATH, COOKIE_DOMAIN );

			// Update $_COOKIE for immediate use in this request
			$_COOKIE['aiovg_rand_seed'] = $seed;
		}
	}

	/**
	 * Flush rewrite rules when it's necessary.
	 *
	 * @since 1.0.0
	 */
	public function maybe_flush_rules() {
		// One-time delayed flush after activation/update/settings change
		if ( ! get_option( 'aiovg_rewrite_rules_flushed' ) ) {

			flush_rewrite_rules( false );
			update_option( 'aiovg_rewrite_rules_flushed', 1 );

			return;
		}

		$general_settings = aiovg_get_option( 'aiovg_general_settings' );

		if ( empty( $general_settings['maybe_flush_rewrite_rules'] ) ) {
			return;
		}

		// Allow developers to restrict the check to admin only
		$admin_only = apply_filters( 'aiovg_flush_rewrite_rules_admin_only', false );

		if ( $admin_only && ! is_admin() ) {
			return;
		}

		$stored_rules = get_option( 'rewrite_rules' );

		if ( empty( $stored_rules ) || ! is_array( $stored_rules ) ) {
			return;
		}

		global $wp_rewrite;

		if ( empty( $wp_rewrite ) ) {
			return;
		}

		$generated_rules = $wp_rewrite->rewrite_rules();

		if ( empty( $generated_rules ) || ! is_array( $generated_rules ) ) {
			return;
		}

		foreach ( $generated_rules as $rule => $rewrite ) {
			if ( ! array_key_exists( $rule, $stored_rules ) ) {
				flush_rewrite_rules( false );
				return;
			}
		}
	}
	
	/**		 
	 * Override the default page/post title.
	 *		
	 * @since  1.0.0
	 * @param  string $title       The document title.	 
     * @param  string $sep         Title separator.
     * @param  string $seplocation Location of the separator (left or right).		 
	 * @return string              The filtered title.		 
	*/
	public function wp_title( $title, $sep, $seplocation ) {		
		global $post;
		
		if ( ! isset( $post ) ) return $title;
		
		$page_settings = aiovg_get_option( 'aiovg_page_settings' );
		$site_name     = sanitize_text_field( get_bloginfo( 'name' ) );
		$custom_title  = '';		
		
		// Get category page title
		if ( $post->ID == $page_settings['category'] ) {			
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
					$custom_title = $term->name;
				}			
			}				
		}

		// Get tag page title
		if ( $post->ID == $page_settings['tag'] ) {			
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
					$custom_title = $term->name;
				}			
			}				
		}
		
		// Get user videos page title
		if ( $post->ID == $page_settings['user_videos'] ) {		
			if ( $slug = get_query_var( 'aiovg_user' ) ) {
				$user = get_user_by( 'slug', $slug );
				$custom_title = $user->display_name;		
			}			
		}
		
		// ...
		if ( ! empty( $custom_title ) ) {
			$title = ( 'left' == $seplocation ) ? "$site_name $sep $custom_title" : "$custom_title $sep $site_name";
		}
		
		return $title;		
	}
	
	/**
	 * Override the default post/page title depending on the AIOVG view.
	 *
	 * @since  1.0.0
	 * @param  array $title The document title parts.
	 * @return              Filtered title parts.
	 */
	public function document_title_parts( $title ) {	
		global $post;
		
		if ( ! isset( $post ) ) return $title;
		
		$page_settings = aiovg_get_option( 'aiovg_page_settings' );
		
		// Get category page title
		if ( $post->ID == $page_settings['category'] ) {			
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				$term = get_term_by( 'slug', $slug, 'aiovg_categories' );
				$title['title'] = $term->name;			
			}				
		}

		// Get tag page title
		if ( $post->ID == $page_settings['tag'] ) {			
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
					$title['title'] = $term->name;	
				}		
			}				
		}
		
		// Get user videos page title
		if ( $post->ID == $page_settings['user_videos'] ) {		
			if ( $slug = get_query_var( 'aiovg_user' ) ) {
				$user = get_user_by( 'slug', $slug );
				$title['title'] = $user->display_name;		
			}			
		}
		
		// Return
		return $title;	
	}

	/**
	 * Adds the custom common CSS code, Facebook OG tags, and Twitter Cards.
	 *
	 * @since 1.0.0
	 */
	public function wp_head() {	
		global $post;
			
		// Facebook OG tags & Twitter Cards
		if ( isset( $post ) && is_singular( 'aiovg_videos' ) ) {
			$video_settings = aiovg_get_option( 'aiovg_video_settings' );
			$socialshare_settings = aiovg_get_option( 'aiovg_socialshare_settings' );

			if ( isset( $video_settings['display']['share'] ) && ! empty( $socialshare_settings['open_graph_tags'] ) ) {
				$site_name = get_bloginfo( 'name' );
				$page_url = get_permalink();
				$video_title = get_the_title();
				$video_description = aiovg_get_excerpt( $post->ID, 160, '', false );
				$video_url = aiovg_get_player_page_url( $post->ID );
				$twitter_username = $socialshare_settings['twitter_username'];

				$image_data = aiovg_get_image( $post->ID, 'large' );
				$image_url = $image_data['src'];

				printf( '<meta property="og:site_name" content="%s" />', esc_attr( $site_name ) );
				printf( '<meta property="og:url" content="%s" />', esc_url( $page_url ) );
				echo '<meta property="og:type" content="video" />';
				printf( '<meta property="og:title" content="%s" />', esc_attr( $video_title ) );

				if ( ! empty( $video_description ) ) {
					printf( '<meta property="og:description" content="%s" />', esc_attr( $video_description ) );
				}

				if ( ! empty( $image_url ) ) {
					printf( '<meta property="og:image" content="%s" />', esc_url( $image_url ) );
				}

				printf( '<meta property="og:video:url" content="%s" />', esc_url( $video_url ) );

				if ( stripos( $page_url, 'https://' ) === 0 ) {
					printf( '<meta property="og:video:secure_url" content="%s" />', esc_url( $video_url ) );
				}

				echo '<meta property="og:video:type" content="text/html">';
				echo '<meta property="og:video:width" content="1280">';
				echo '<meta property="og:video:height" content="720">';

				printf( '<meta name="twitter:card" content="%s">', ( ! empty( $twitter_username ) ? 'player' : 'summary' ) );

				if ( ! empty( $twitter_username ) ) {
					if ( strpos( $twitter_username, '@' ) === false ) {
						$twitter_username = '@' . $twitter_username;
					}
					
					printf( '<meta name="twitter:site" content="%s" />', esc_attr( $twitter_username ) );
				}

				printf( '<meta name="twitter:title" content="%s" />', esc_attr( $video_title ) );

				if ( ! empty( $video_desc ) ) {
					printf( '<meta name="twitter:description" content="%s" />', esc_attr( $video_desc ) );
				}

				if ( ! empty( $image_url ) ) {
					printf( '<meta name="twitter:image" content="%s" />', esc_url( $image_url ) );
				}

				if ( ! empty( $twitter_username ) ) {
					printf( '<meta name="twitter:player" content="%s" />', esc_url( $video_url ) );
					echo '<meta name="twitter:player:width" content="1280">';
					echo '<meta name="twitter:player:height" content="720">';
				}				
			}				
		}
		
		// Custom common CSS code
		echo '<style type="text/css">' . $this->get_inline_css() . '</style>';
	}

	/**
	 * Builds the common inline CSS string used in both wp_head and the block editor iframe.
	 *
	 * @since  4.7.6
	 * @return string Unescaped CSS string.
	 */
	private function get_inline_css() {
		$general_settings = aiovg_get_option( 'aiovg_general_settings' );

		$primary_color = ! empty( $general_settings['primary_color'] ) ? $general_settings['primary_color'] : '#3b82f6';

		$css = '
			body {
				--aiovg-color-primary: ' . esc_attr( $primary_color ) . ';
			}

			.aiovg-player {
				display: block;
				position: relative;
				border-radius: 3px;
				padding-bottom: 56.25%;
				width: 100%;
				height: 0;
				overflow: hidden;
			}

			.aiovg-player iframe,
			.aiovg-player .video-js,
			.aiovg-player .plyr {
				--plyr-color-main: ' . esc_attr( $primary_color ) . ';
				position: absolute;
				inset: 0;
				margin: 0;
				padding: 0;
				width: 100%;
				height: 100%;
			}
		';

		if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) {
			$css .= wp_strip_all_tags( $general_settings['custom_css'] );
		}

		return $css;
	}

	/**
	 * Load the necessary scripts in the site footer.
	 *
	 * @since 3.9.5
	 */
	public function wp_print_footer_scripts() {	
		?>
        <script type='text/javascript'>
			(function() {
				'use strict';
				
				/**
				 * Listen to the global player events.
				 */
				window.addEventListener( 'message', function( event ) {
					if ( event.origin != window.location.origin ) {
						return false;
					}

					if ( ! event.data.hasOwnProperty( 'message' ) ) {
						return false;
					}

					const iframes = document.querySelectorAll( '.aiovg-player iframe' );

					for ( let i = 0; i < iframes.length; i++ ) {
						const iframe = iframes[ i ];
						
						if ( event.source == iframe.contentWindow ) {
							continue;
						}

						if ( event.data.message == 'aiovg-cookie-consent' ) {
							iframe.contentWindow.postMessage({
								message: 'aiovg-cookie-consent'
							}, window.location.origin );
						}

						if ( event.data.message == 'aiovg-email-captured' ) {
							iframe.contentWindow.postMessage({
								message: 'aiovg-email-captured',
								// Forwarded as sent. An empty key means "once per visitor",
								// where one capture clears every player on the page.
								capture_key: event.data.capture_key || ''
							}, window.location.origin );
						}

						if ( event.data.message == 'aiovg-video-playing' ) {
							iframe.contentWindow.postMessage({
								message: 'aiovg-video-pause'
							}, window.location.origin );
						}
					}
				});

			})();
		</script>
        <?php
	}
	
	/**
	 * Change the current page title if applicable.
	 *
	 * @since  1.0.0
	 * @param  string $title   Current page title.
	 * @param  int    $post_id The post ID.
	 * @return string $title   Filtered page title.
	 */
	public function the_title( $title, $id = 0 ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}

		$post_id = get_the_ID();

		if ( ! empty( $id ) ) {
			if ( $id != $post_id ) {
				return $title;
			}
		}
		
		$page_settings = aiovg_get_option( 'aiovg_page_settings' );
		
		// Change category page title
		if ( $post_id == $page_settings['category'] ) {		
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
					$title = $term->name;	
				}		
			}			
		}

		// Change tag page title
		if ( $post_id == $page_settings['tag'] ) {		
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
					$title = $term->name;
				}			
			}			
		}
		
		// Change search page title
		if ( $post_id == $page_settings['search'] ) {		
			$queries = array();
			
			if ( ! empty( $_GET['vi'] ) ) {
				$queries[] = sanitize_text_field( stripslashes( $_GET['vi'] ) );				
			}
			
			if ( ! empty( $_GET['ca'] ) ) {
				$categories = array_map( 'intval', (array) $_GET['ca'] );
				$categories = array_filter( $categories );

				if ( ! empty( $categories ) ) {
					foreach ( $categories as $category ) {
						if ( $term = get_term_by( 'id', $category, 'aiovg_categories' ) ) {
							$queries[] = $term->name;	
						}
					}	
				}		
			}

			if ( ! empty( $_GET['ta'] ) ) {
				$tags = array_map( 'intval', (array) $_GET['ta'] );
				$tags = array_filter( $tags );

				if ( ! empty( $tags ) ) {
					foreach ( $tags as $tag ) {
						if ( $term = get_term_by( 'id', $tag, 'aiovg_tags' ) ) {
							$queries[] = $term->name;	
						}
					}	
				}						
			}

			if ( ! empty( $_GET['sort'] ) ) {
				$sort_options = aiovg_get_search_form_sort_options();
				$sort = sanitize_text_field( $_GET['sort'] );

				if ( isset( $sort_options[ $sort ] ) ) {
					$queries[] = $sort_options[ $sort ];
				}
			}
			
			if ( ! empty( $queries ) ) {
				$title = sprintf( __( 'Showing results for "%s"', 'all-in-one-video-gallery' ), implode( ', ', $queries ) );	
			}			
		}
		
		// Change user videos page title
		if ( $post_id == $page_settings['user_videos'] ) {		
			if ( $slug = get_query_var( 'aiovg_user' ) ) {
				$user = get_user_by( 'slug', $slug );
				$title = $user->display_name;		
			}			
		}
		
		return $title;	
	}

	/**
	 * Filters whether a video post has a thumbnail.
	 *
	 * @since  2.4.0
	 * @param bool             $has_thumbnail true if the post has a post thumbnail, otherwise false.
	 * @param int|WP_Post|null $post          Post ID or WP_Post object. Default is global `$post`.
	 * @param int|string       $thumbnail_id  Post thumbnail ID or empty string.
	 * @return bool            $has_thumbnail true if the video post has an image attached.
	 */
	public function has_post_thumbnail( $has_thumbnail, $post, $thumbnail_id ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return $has_thumbnail;
		}

		if ( is_singular( 'aiovg_videos' ) ) {		
			global $wp_the_query;
			
			if ( $post->ID == $wp_the_query->get_queried_object_id() ) {
				$featured_images_settings = aiovg_get_option( 'aiovg_featured_images_settings' );

				if ( ! empty( $featured_images_settings['hide_on_single_video_pages'] ) ) {
					return false;
				}
			}
		}

		if ( ! empty( $thumbnail_id ) ) {
			return $has_thumbnail;		
		}

		if ( 'aiovg_videos' == get_post_type( $post->ID ) ) {
			$image_data = aiovg_get_image( $post->ID, 'large' );
			
			if ( ! empty( $image_data['src'] ) ) {
				$has_thumbnail = true;
			}
		}	

		return $has_thumbnail;		
	}

	/**
	 * Filters the video post thumbnail HTML.
	 *
	 * @since  2.4.0
	 * @param string       $html              The post thumbnail HTML.
	 * @param int          $post_id           The post ID.
	 * @param string       $post_thumbnail_id The post thumbnail ID.
	 * @param string|array $size              The post thumbnail size. Image size or array of width and height
	 *                                        values (in that order). Default 'post-thumbnail'.
	 * @param string       $attr              Query string of attributes.
	 * @return bool        $html              Filtered video post thumbnail HTML.
	 */
	public function post_thumbnail_html( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
		if ( is_singular( 'aiovg_videos' ) ) {		
			global $wp_the_query;
			
			if ( $post_id == $wp_the_query->get_queried_object_id() ) {
				$featured_images_settings = aiovg_get_option( 'aiovg_featured_images_settings' );

				if ( ! empty( $featured_images_settings['hide_on_single_video_pages'] ) ) {
					return '';
				}				
			}
		}

		if ( ! empty( $post_thumbnail_id ) ) {
			return $html;	
		}

		if ( 'aiovg_videos' == get_post_type( $post_id ) ) {
			$_html = '';

			$image_id = get_post_meta( $post_id, 'image_id', true );

			if ( ! empty( $image_id ) ) {
				$_html = wp_get_attachment_image( $image_id, $size, false, $attr );
			} 
			
			if ( empty( $_html ) ) {
				$image_url = get_post_meta( $post_id, 'image', true );
				
				if ( ! empty( $image_url ) ) {
					$alt  = get_post_field( 'post_title', $post_id );

					$attr = array( 'alt' => $alt );
					$attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, NULL, $size );
					$attr = array_map( 'esc_attr', $attr );

					$_html = sprintf( '<img src="%s"', esc_url( $image_url ) );

					foreach ( $attr as $name => $value ) {
						$_html .= " $name=" . '"' . $value . '"';
					}

					$_html .= ' />';
				}
			}

			if ( ! empty( $_html ) ) {
				$html = $_html;
			}
		}

		return $html;		
	}

	/**
	 * Use the category, tag or user videos URL as the canonical URL on those plugin pages.
	 *
	 * @since  4.9.6
	 * @param  string  $canonical_url The canonical URL.
	 * @param  WP_Post $post          The queried page.
	 * @return string                 Filtered canonical URL.
	 */
	public function get_canonical_url( $canonical_url, $post ) {
		// Without this, every category, tag and user page is canonical to the plugin page itself
		if ( ! $post || 'page' != $post->post_type ) {
			return $canonical_url;
		}

		$page_settings = aiovg_get_option( 'aiovg_page_settings' );

		// Category page
		if ( ! empty( $page_settings['category'] ) && $post->ID == $page_settings['category'] && ( $slug = get_query_var( 'aiovg_category' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
				$canonical_url = aiovg_add_page_number_to_url( aiovg_get_category_page_url( $term ) );
			}
		}

		// Tag page
		if ( ! empty( $page_settings['tag'] ) && $post->ID == $page_settings['tag'] && ( $slug = get_query_var( 'aiovg_tag' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
				$canonical_url = aiovg_add_page_number_to_url( aiovg_get_tag_page_url( $term ) );
			}
		}

		// User videos page
		if ( ! empty( $page_settings['user_videos'] ) && $post->ID == $page_settings['user_videos'] && ( $slug = get_query_var( 'aiovg_user' ) ) ) {
			if ( $user = get_user_by( 'slug', $slug ) ) {
				$canonical_url = aiovg_add_page_number_to_url( aiovg_get_user_videos_page_url( $user->ID ) );
			}
		}

		// Video archive page, from page 2 onwards
		if ( aiovg_is_video_archive_page( $post->ID ) ) {
			$canonical_url = aiovg_add_page_number_to_url( $canonical_url );
		}

		return $canonical_url;
	}

	/**
	 * Always use our custom page for AIOVG categories & tags.
	 *
	 * @since  1.0.0
	 * @param  string $url      The term URL.
	 * @param  object $term     The term object.
	 * @param  string $taxonomy The taxonomy slug.
	 * @return string $url      Filtered term URL.
	 */
	public function term_link( $url, $term, $taxonomy ) {	
		if ( 'aiovg_categories' == $taxonomy ) {
			$url = aiovg_get_category_page_url( $term );
		}

		if ( 'aiovg_tags' == $taxonomy ) {
			$url = aiovg_get_tag_page_url( $term );
		}
		
		return $url;		
	}	
	
	/**
	 * Set cookie for accepting the privacy consent.
	 *
	 * @since 1.0.0
	 */
	public function set_gdpr_cookie() {
		check_ajax_referer( 'aiovg_public_ajax_nonce', 'security' );
		setcookie( 'aiovg_gdpr_consent', 1, time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		wp_send_json_success();
	}

	/**
	 * Save the email lead and record the capture.
	 *
	 * Handles the aiovg_save_email_lead AJAX action. A logged-in user is accepted only
	 * when aiovg_can_show_email_capture_to_user() allows it; before 4.9.4 they were
	 * always rejected. A skip arrives here too: it records the capture but stores no lead.
	 *
	 * @since 4.7.6
	 */
	public function save_email_lead() {
		check_ajax_referer( 'aiovg_public_ajax_nonce', 'security' );

		// Never accept from a visitor the form would not have been shown to.
		if ( ! aiovg_can_show_email_capture_to_user() ) {
			wp_send_json_error();
			return;
		}

		// Read before the skip branch below, which returns early and needs them too.
		$video_id  = isset( $_POST['video_id'] ) ? absint( $_POST['video_id'] ) : 0;
		$video_url = isset( $_POST['video_url'] ) ? esc_url_raw( $_POST['video_url'] ) : '';

		// Recomputed server-side, never trusted from the client.
		$capture_key = aiovg_get_email_capture_key( $video_id, $video_id > 0 ? 'aiovg_videos' : '', $video_url );

		// Skip — only record the capture; do not store a lead row.
		if ( ! empty( $_POST['skipped'] ) ) {
			$remembered = aiovg_record_email_capture( $capture_key, true );

			if ( ! isset( $_COOKIE['aiovg_gdpr_consent'] ) ) {
				setcookie( 'aiovg_gdpr_consent', 1, time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
			}

			// Lets the page leave other players' forms alone when the skip was not remembered.
			wp_send_json_success( array( 'remembered' => $remembered ) );
			return;
		}

		// Rate limiting, on submissions only — skips are never counted.
		$rate_limit_enabled = apply_filters( 'aiovg_email_capture_rate_limit_enabled', true );

		if ( $rate_limit_enabled ) {
			$ip_hash = wp_hash( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' );

			// Two counters: one video can be hammered, but a genuine visitor may submit
			// once per video across a large gallery.
			$rate_limits = array();

			// Per video — tight; an honest visitor needs one or two. A video with no
			// derivable key falls through to the site-wide counter below.
			if ( '' !== $capture_key ) {
				$rate_limits[] = array(
					'key' => 'aiovg_ec_rate_video_' . $ip_hash . '_' . md5( $capture_key ),
					// This filter was site-wide before 4.9.4.
					'max' => (int) apply_filters( 'aiovg_email_capture_rate_limit', 10, $capture_key )
				);
			}

			// Site-wide — above any human ceiling. Stops an attacker walking video ids
			// from using the notification mailer as a relay.
			$rate_limits[] = array(
				'key' => 'aiovg_ec_rate_all_' . $ip_hash,
				// 0 disables this tier.
				'max' => (int) apply_filters( 'aiovg_email_capture_rate_limit_global', 100 )
			);

			foreach ( $rate_limits as $rate_limit ) {
				if ( $rate_limit['max'] > 0 && (int) get_transient( $rate_limit['key'] ) >= $rate_limit['max'] ) {
					wp_send_json_error( __( 'Too many attempts. Please try again in an hour.', 'all-in-one-video-gallery' ) );
					return;
				}
			}

			// Increment before further validation so probing attempts count against the limit.
			foreach ( $rate_limits as $rate_limit ) {
				if ( $rate_limit['max'] > 0 ) {
					set_transient( $rate_limit['key'], (int) get_transient( $rate_limit['key'] ) + 1, HOUR_IN_SECONDS );
				}
			}
		}

		// Sanitize and validate email.
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Please enter a valid email address.', 'all-in-one-video-gallery' ) );
			return;
		}

		// Sanitize optional fields.
		$name     = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
		$page_url = isset( $_POST['page_url'] ) ? esc_url_raw( $_POST['page_url'] ) : '';

		// Server-side blocked domain check.
		$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );

		$blocked_domains = isset( $email_capture_settings['blocked_email_domains'] ) ? $email_capture_settings['blocked_email_domains'] : '';

		if ( ! empty( $blocked_domains ) ) {
			$parts        = explode( '@', $email );
			$email_domain = strtolower( trim( end( $parts ) ) );
			$lines        = explode( "\n", $blocked_domains );

			foreach ( $lines as $line ) {
				$line = strtolower( trim( $line ) );

				if ( '' !== $line && $email_domain === $line ) {
					// Reject silently — same response as any other server-side failure.
					wp_send_json_error();
					return;
				}
			}
		}

		// Held in a variable because the action below hands it to listeners.
		$data = array(
			'email'     => $email,
			'name'      => $name,
			'phone'     => $phone,
			'video_id'  => $video_id,
			'video_url' => $video_url,
			'page_url'  => $page_url
		);

		$inserted = aiovg_email_capture_insert_lead( $data );

		if ( false === $inserted ) {
			wp_send_json_error();
			return;
		}

		// Before the action and the notification, so third-party code cannot cost the
		// visitor the record of a form they have already filled in.
		aiovg_record_email_capture( $capture_key, false );

		if ( ! isset( $_COOKIE['aiovg_gdpr_consent'] ) ) {
			setcookie( 'aiovg_gdpr_consent', 1, time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );
		}

		/**
		 * Fires after a lead has been captured and stored.
		 *
		 * The integration point for CRMs, marketing platforms and webhooks. Fires for
		 * every stored lead in both integration modes, never for a skip.
		 *
		 * @since 4.9.4
		 * @param int   $lead_id ID of the stored lead row.
		 * @param array $data    Sanitized lead data: email, name, phone, video_id,
		 *                       video_url, page_url.
		 */
		do_action( 'aiovg_email_capture_lead_saved', $inserted, $data );

		// Success either way — playback must not depend on SMTP.
		aiovg_email_capture_notify( $inserted, $data );

		wp_send_json_success();
	}

}
