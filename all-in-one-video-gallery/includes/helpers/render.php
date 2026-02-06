<?php

/**
 * Helper functions for rendering HTML output across the plugin.
 *
 * @link    https://plugins360.com
 * @since   4.0.1
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
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
	$stay_open = isset( $args['stay_open'] ) ? $args['stay_open'] : 0;
	$show_search_threshold = isset( $args['show_search_threshold'] ) ? $args['show_search_threshold'] : 20;
	
	unset( $args['show_option_none'], $args['option_none_value'] );

	$args['walker'] = new AIOVG_Walker_Terms_MultiSelect();
	$args['echo']   = false;

	$dropdown_list = wp_dropdown_categories( $args );
	$dropdown_list = preg_replace( '/<select(.*?)>(.*?)<\/select>/s', '<div class="aiovg-dropdown-list">$2</div>', $dropdown_list );

	// Output
	$html  = '<div class="aiovg-dropdown-terms">';
	$html .= sprintf( '<input type="text" class="aiovg-dropdown-input aiovg-form-control" placeholder="%s" readonly />', esc_attr( $input_placeholder )	);	
	$html .= '<div class="aiovg-dropdown" style="display: none;">';

	$html .= sprintf( '<div class="aiovg-dropdown-search" hidden data-stay_open="%d" data-show_search_threshold="%d">', $stay_open, $show_search_threshold );
	$html .= sprintf( '<input type="text" placeholder="%s..." />', esc_html__( 'Search', 'all-in-one-video-gallery' ) );
	$html .= '<button type="button" tabindex="-1" hidden>';
	$html .= '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">';
	$html .= '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>';
	$html .= '</svg>';
	$html .= '</button>';
	$html .= '</div>';

	$html .= $dropdown_list;

	$html .= '<div class="aiovg-dropdown-no-items" hidden>';
	$html .= sprintf( '<label class="aiovg-text-muted aiovg-text-small">%s</label>', esc_html__( 'No items found', 'all-in-one-video-gallery' ) );
	$html .= '</div>';

	$html .= '</div>';
	$html .= '</div>';

	if ( $echo ) {
		echo $html;
	} else {
		return $html;
	}
}

/**
 * Get the category breadcrumbs.
 *
 * @since  3.8.4
 * @param  object $term The current term object.
 * @return string       Category breadcrumbs.
 */
function aiovg_get_category_breadcrumbs( $term = null ) {
	$page_settings = aiovg_get_option( 'aiovg_page_settings' );

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
		$likes_settings = aiovg_get_option( 'aiovg_likes_settings' );

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
 * Wraps the shortcode output HTML string with filters.
 *
 * @since  4.0.1
 * @param  string $content    Default shortcode HTML output (the gallery itself).
 * @param  array  $attributes An associative array of shortcode attributes.
 * @return string             Combined output of filters + gallery.
 */
function aiovg_wrap_with_filters( $content, $attributes ) {
	if (
		empty( $attributes['filters_keyword'] ) &&
		empty( $attributes['filters_category'] ) &&
		empty( $attributes['filters_tag'] ) &&
		empty( $attributes['filters_sort'] )
	) {
		return $content;
	}

	$json_params = aiovg_prepare_attributes_for_ajax( $attributes );

	$attributes['template'] = sanitize_text_field( $attributes['filters_template'] );
	$attributes['has_keyword'] = (int) $attributes['filters_keyword'];
	$attributes['has_category'] = (int) $attributes['filters_category'];
	$attributes['has_tag'] = (int) $attributes['filters_tag'];
	$attributes['has_sort'] = (int) $attributes['filters_sort'];
	$attributes['has_search_button'] = ( 'search' == $attributes['filters_mode'] ) ? 1 : 0;
	$attributes['has_reset_button'] = (int) $attributes['filters_reset_button'];
	$attributes['target'] = 'current';

	$attributes['categories_selected'] = $attributes['category'];
	$attributes['tags_selected'] = $attributes['tag'];

	// Auto-switch to "compact" template if only the keyword field is shown
	if ( 
		empty( $attributes['has_category'] ) && 
		empty( $attributes['has_tag'] ) && 
		empty( $attributes['has_sort'] ) 
	) {
		$attributes['template'] = 'compact';
	}

	// Current page ID, useful for search form target
	$attributes['search_page_id'] = aiovg_get_current_page_id();
	
	// Enqueue dependencies
	wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' );

	if ( 'search' != $attributes['filters_mode'] ) {
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-search' );
	}

	if ( ! empty( $attributes['has_category'] ) || ! empty( $attributes['has_tag'] ) ) {
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-select' );
	}		

	if ( ! empty( $attributes['show_pagination'] ) || ! empty( $attributes['show_more'] ) ) {     
        wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-pagination' );
    }

	// Capture filter form output
	ob_start();
	include apply_filters( 'aiovg_load_template', AIOVG_PLUGIN_DIR . 'public/templates/search-form-template-' . sanitize_file_name( $attributes['template'] ) . '.php', $attributes );
	$filters = ob_get_clean();

	// Now load the combined layout (filters + videos)
	$html = sprintf( 
		'<div class="aiovg-videos-filters-wrapper aiovg-filters-position-%s" data-params=\'%s\'>', 
		esc_attr( $attributes['filters_position'] ),
		( 'ajax' == $attributes['filters_mode'] ? wp_json_encode( $json_params ) : '' )
	);

	if ( 'ajax' == $attributes['filters_mode'] ) {
		$html .= '<div class="aiovg-filters-progress-bar" style="display: none;">';
		$html .= '<div class="aiovg-filters-progress-bar-inner"></div>';
        $html .= '</div>';
	}

	$html .= $filters;
	$html .= $content;
	$html .= '</div>';

	return $html;
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
		
		$content .= aiovg_get_like_button( $post_id );
	}

	if ( ! empty( $attributes['show_player_comment_button'] ) ) {
		$content .= '<aiovg-comment-button>';
		if ( comments_open( $post_id ) ) {
			$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
            </svg>';

			$content .= sprintf(
				'<button type="button" onclick="location.href=\'%s\'">%s %s</button>',
				esc_url( get_comments_link( $post_id ) ),
				$icon,
				esc_html__( 'Leave a Comment', 'all-in-one-video-gallery' )
			);
		}
		$content .= '</aiovg-comment-button>';
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
 * Add content after thumbnail image.
 *
 * @since 4.3.7
 * @param array $attributes Array of attributes.
 */
function the_aiovg_content_after_thumbnail_image( $attributes ) {
	$content = apply_filters( 'aiovg_content_after_thumbnail_image', '', $attributes );

	if ( ! empty( $content ) ) {
		echo $content;
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

			$json_params = aiovg_prepare_attributes_for_ajax( $atts );			

			echo sprintf( '<aiovg-pagination class="aiovg-more aiovg-more-ajax aiovg-text-center" data-params=\'%s\'>', wp_json_encode( $json_params ) );
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
		$pagination_settings = aiovg_get_option( 'aiovg_pagination_settings' );
	
		$pagerange = isset( $pagination_settings['mid_size'] ) ? (int) $pagination_settings['mid_size'] : 2;
					
		if ( empty( $pagerange ) ) {
			$pagerange = 2;
		}
  	}

  	if ( empty( $paged ) ) {
    	$paged = aiovg_get_page_number();
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
		$is_ajax = isset( $atts['pagination_ajax'] ) && ! empty( $atts['pagination_ajax'] );
		
		if ( $is_ajax ) {
			wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-pagination' );

			$json_params = aiovg_prepare_attributes_for_ajax( $atts );	

			printf(
				'<aiovg-pagination class="aiovg-pagination aiovg-pagination-ajax aiovg-text-center" data-params=\'%s\' data-current="%d">',			
				wp_json_encode( $json_params ),
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
 * Display a notice if PHP limits are below recommended values.
 *
 * @since 4.5.2
 */
function the_aiovg_php_server_limits_notice() {
    $recommended_memory = 256; // MB
    $recommended_time   = 120; // seconds

    $current_memory = (int) str_replace( 'M', '', ini_get( 'memory_limit' ) );
    $current_time   = (int) ini_get( 'max_execution_time' );

    $errors = array();

    if ( $current_memory < $recommended_memory ) {
        $errors[] = sprintf(
            __( 'Your PHP memory limit is %dMB. It is recommended to have at least %dMB for smooth import/export operations.', 'all-in-one-video-gallery' ),
            $current_memory,
            $recommended_memory
        );
    }

    if ( $current_time < $recommended_time ) {
        $errors[] = sprintf(
            __( 'Your PHP max execution time is %d seconds. It is recommended to have at least %d seconds to avoid timeouts during batch processing.', 'all-in-one-video-gallery' ),
            $current_time,
            $recommended_time
        );
    }

    if ( ! empty( $errors ) ) {
        foreach ( $errors as $error ) {
            printf(
                '<div class="aiovg-notice aiovg-notice-error">%s</div>',
                esc_html( $error )
            );
        }
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
	
	$socialshare_settings = aiovg_get_option( 'aiovg_socialshare_settings' );
	
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
		
		$html = '<div class="aiovg-social">';

		foreach ( $buttons as $label => $button ) {
			$html .= sprintf( 
				'<a class="aiovg-social-%s aiovg-link-social" href="%s" title="%s" target="_blank"><span class="%s"></span></a>', 
				esc_attr( $label ),
				esc_attr( $button['url'] ), 
				esc_attr( $button['text'] ),
				( isset( $button['icon'] ) ? esc_attr( $button['icon'] ) : '' )
			);
		}

		$html .= '</div>';

		echo apply_filters( 'the_aiovg_socialshare_buttons', $html, $buttons );
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
