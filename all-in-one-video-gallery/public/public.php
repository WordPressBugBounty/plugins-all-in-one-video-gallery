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
			$permalink_settings = get_option( 'aiovg_permalink_settings' );

			if ( is_array( $permalink_settings ) && ! empty( $permalink_settings['video_archive_page'] ) ) {
				$page_id  = (int) $permalink_settings['video_archive_page'];
				$page_url = get_permalink( $page_id );

				if ( $page_url ) {
					// Avoid redirect loop if already on the custom archive page
					$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					if ( untrailingslashit( $current_url ) !== untrailingslashit( $page_url ) ) {
						wp_redirect( $page_url, 301 );
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
		$page_settings      = get_option( 'aiovg_page_settings' );
		$permalink_settings = get_option( 'aiovg_permalink_settings' );

		$site_url = home_url();
		
		// Rewrite tags
		add_rewrite_tag( '%aiovg_category%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_tag%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_user%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_type%', '([^/]+)' );
		add_rewrite_tag( '%aiovg_video%', '([^/]+)' );
		
		// Single category page		
		if ( ! empty( $page_settings['category'] ) ) {
			$id   = (int) $page_settings['category'];
			$post = get_post( $id );

			if ( $post && 'publish' === $post->post_status ) {
				$permalink = get_permalink( $id );

				if ( $permalink ) {
					$slug = str_replace( $site_url, '', $permalink );			
					$slug = trim( $slug, '/' );
					$slug = urldecode( $slug );		
					
					add_rewrite_rule( "$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&aiovg_category=$matches[1]&paged=$matches[2]', 'top' );
					add_rewrite_rule( "$slug/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_category=$matches[1]', 'top' );
				}
			}
		}

		// Single tag page
		if ( ! empty( $page_settings['tag'] ) ) {
			$id   = (int) $page_settings['tag'];
			$post = get_post( $id );

			if ( $post && 'publish' === $post->post_status ) {
				$permalink = get_permalink( $id );

				if ( $permalink ) {
					$slug = str_replace( $site_url, '', $permalink );			
					$slug = trim( $slug, '/' );
					$slug = urldecode( $slug );		
					
					add_rewrite_rule( "$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&aiovg_tag=$matches[1]&paged=$matches[2]', 'top' );
					add_rewrite_rule( "$slug/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_tag=$matches[1]', 'top' );
				}
			}
		}
		
		// User videos page
		if ( ! empty( $page_settings['user_videos'] ) ) {
			$id   = (int) $page_settings['user_videos'];
			$post = get_post( $id );

			if ( $post && 'publish' === $post->post_status ) {
				$permalink = get_permalink( $id );

				if ( $permalink ) {
					$slug = str_replace( $site_url, '', $permalink );			
					$slug = trim( $slug, '/' );
					$slug = urldecode( $slug );		
					
					add_rewrite_rule( "$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&aiovg_user=$matches[1]&paged=$matches[2]', 'top' );
					add_rewrite_rule( "$slug/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_user=$matches[1]', 'top' );
				}
			}
		}
		
		// Player page
		if ( ! empty( $page_settings['player'] ) ) {
			$id   = (int) $page_settings['player'];
			$post = get_post( $id );

			if ( $post && 'publish' === $post->post_status ) {
				$permalink = get_permalink( $id );

				if ( $permalink ) {
					$slug = str_replace( $site_url, '', $permalink );			
					$slug = trim( $slug, '/' );
					$slug = urldecode( $slug );		
					
					add_rewrite_rule( "$slug/id/([^/]+)/?$", 'index.php?page_id=' . $id . '&aiovg_type=id&aiovg_video=$matches[1]', 'top' );
				}
			}
		}

		// Video archive page
		if ( ! empty( $permalink_settings['video_archive_page'] ) ) {
			$id   = (int) $permalink_settings['video_archive_page'];
			$post = get_post( $id );

			if ( $post && 'publish' === $post->post_status ) {
				$permalink = get_permalink( $id );

				if ( $permalink ) {
					$slug = str_replace( $site_url, '', $permalink );			
					$slug = trim( $slug, '/' );
					$slug = urldecode( $slug );		
					
					add_rewrite_rule( "$slug/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $id . '&paged=$matches[1]', 'top' );
				}
			}
		}
	}
	
	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function register_styles() {
		$player_settings = get_option( 'aiovg_player_settings' );

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
		$likes_settings = get_option( 'aiovg_likes_settings' );

		$ajax_url   = admin_url( 'admin-ajax.php' );
		$ajax_nonce = wp_create_nonce( 'aiovg_ajax_nonce' );
		$user_id    = get_current_user_id();

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
				'ajax_url'   => $ajax_url,
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
				'ajax_url'   => $ajax_url,
				'ajax_nonce' => $ajax_nonce,
				'i18n'       => array(				
					'stream_not_found' => __( 'This stream is currently not live. Please check back or refresh your page.', 'all-in-one-video-gallery' ),
				)
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
				'plugin_url'           => AIOVG_PLUGIN_URL,
				'plugin_version'       => AIOVG_PLUGIN_VERSION,
				'ajax_url'             => $ajax_url,
				'ajax_nonce'           => $ajax_nonce,
				'scroll_to_top_offset' => $scroll_to_top_offset,
				'i18n'                 => array(				
					'no_tags_found' => __( 'No tags found', 'all-in-one-video-gallery' )
				)
			) 
		);		
	}	

	/**
	 * Set MySQL's RAND function seed value in a cookie.
	 *
	 * @since 3.9.3
	 */
	public function set_mysql_rand_seed_value() {
		$privacy_settings = get_option( 'aiovg_privacy_settings' );
		
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
	 * Enqueue Gutenberg block assets for backend editor.
	 *
	 * @since 3.6.1
	 */
	public function enqueue_block_editor_assets() {
		// Styles
		$this->register_styles();

		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' );

		// Scripts
		$this->register_scripts();
		
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-likes' );
	}

	/**
	 * Flush rewrite rules when it's necessary.
	 *
	 * @since 1.0.0
	 */
	 public function maybe_flush_rules() {
		$general_settings = get_option( 'aiovg_general_settings' );

		if ( empty( $general_settings['maybe_flush_rewrite_rules'] ) ) {
			return false;
		}

		$rewrite_rules = get_option( 'rewrite_rules' );
				
		if ( $rewrite_rules ) {		
			global $wp_rewrite;
			
			foreach ( $rewrite_rules as $rule => $rewrite ) {
				$rewrite_rules_array[ $rule ]['rewrite'] = $rewrite;
			}
			$rewrite_rules_array = array_reverse( $rewrite_rules_array, true );
		
			$maybe_missing = $wp_rewrite->rewrite_rules();
			$missing_rules = false;		
		
			foreach ( $maybe_missing as $rule => $rewrite ) {
				if ( ! array_key_exists( $rule, $rewrite_rules_array ) ) {
					$missing_rules = true;
					break;
				}
			}
		
			if ( true === $missing_rules ) {
				flush_rewrite_rules();
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
		
		$page_settings = get_option( 'aiovg_page_settings' );
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
		
		$page_settings = get_option( 'aiovg_page_settings' );
		
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
			$video_settings = get_option( 'aiovg_video_settings' );
			$socialshare_settings = get_option( 'aiovg_socialshare_settings' );

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
		$player_settings  = get_option( 'aiovg_player_settings' );
		$general_settings = get_option( 'aiovg_general_settings' );

		$player_theme_color = ! empty( $player_settings['theme_color'] ) ? $player_settings['theme_color'] : '#00b2ff';

		echo '<style type="text/css">
			.aiovg-player {
				display: block;
				position: relative;
				padding-bottom: 56.25%;
				width: 100%;
				height: 0;	
				overflow: hidden;
			}
			
			.aiovg-player iframe,
			.aiovg-player .video-js,
			.aiovg-player .plyr {
				--plyr-color-main: ' . esc_attr( $player_theme_color ) . ';
				position: absolute;
				inset: 0;	
				margin: 0;
				padding: 0;
				width: 100%;
				height: 100%;
			}
		</style>';
		
		if ( isset( $general_settings['custom_css'] ) && ! empty( $general_settings['custom_css'] ) ) {
			echo '<style type="text/css">';
			echo esc_html( $general_settings['custom_css'] );
			echo '</style>';
		}
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
							const src = iframe.src;

							if ( src.indexOf( 'nocookie=1' ) == -1 ) {
								const url = new URL( src );

								const searchParams = url.searchParams;
								searchParams.set( 'nocookie', 1 );

                    			url.search = searchParams.toString();

								iframe.src = url.toString();
							}
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
		
		$page_settings = get_option( 'aiovg_page_settings' );
		
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
				$featured_images_settings = get_option( 'aiovg_featured_images_settings' );

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
				$featured_images_settings = get_option( 'aiovg_featured_images_settings' );

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
		check_ajax_referer( 'aiovg_ajax_nonce', 'security' );	
		setcookie( 'aiovg_gdpr_consent', 1, time() + ( 86400 * 30 ), COOKIEPATH, COOKIE_DOMAIN );		
		wp_send_json_success();			
	}

}
