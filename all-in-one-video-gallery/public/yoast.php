<?php

/**
 * Yoast SEO.
 *
 * @link    https://plugins360.com
 * @since   3.9.5
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Public_Yoast class.
 *
 * @since 3.9.5
 */
class AIOVG_Public_Yoast {

	/**
	 * Construct Yoast SEO title for our category and user videos pages.
	 *
	 * @since  3.9.5
	 * @param  array $title The Yoast title.
	 * @return              Filtered title.
	 */
	public function wpseo_title( $title ) {	
		global $post;

		if ( ! isset( $post ) ) {
			return $title;
		}

		$page_settings = get_option( 'aiovg_page_settings' );

		if ( $post->ID != $page_settings['category'] && $post->ID != $page_settings['tag'] && $post->ID != $page_settings['user_videos'] ) {
			return $title;
		}

		$wpseo_titles = get_option( 'wpseo_titles' );

		$sep_options = WPSEO_Option_Titles::get_instance()->get_separator_options();

		if ( isset( $wpseo_titles['separator'] ) && isset( $sep_options[ $wpseo_titles['separator'] ] ) ) {
			$sep = $sep_options[ $wpseo_titles['separator'] ];
		} else {
			$sep = '-'; // Setting default separator if Admin didn't set it from backed
		}

		$replacements = array(
			'%%sep%%'              => $sep,						
			'%%page%%'             => '',
			'%%primary_category%%' => '',
			'%%sitename%%'         => sanitize_text_field( get_bloginfo( 'name' ) )
		);

		$title_template = '';
		
		// Category page
		if ( $post->ID == $page_settings['category'] ) {			
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				// Get Archive SEO title
				if ( array_key_exists( 'title-tax-aiovg_categories', $wpseo_titles ) ) {
					$title_template = $wpseo_titles['title-tax-aiovg_categories'];
				}

				// Get Term SEO title
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {		
					$replacements['%%term_title%%'] = $term->name;
					
					$meta = get_option( 'wpseo_taxonomy_meta' );

					if ( array_key_exists( 'aiovg_categories', $meta ) ) {
						if ( array_key_exists( $term->term_id, $meta['aiovg_categories'] ) ) {
							if ( array_key_exists( 'wpseo_title', $meta['aiovg_categories'][ $term->term_id ] ) ) {
								$title_template = $meta['aiovg_categories'][ $term->term_id ]['wpseo_title'];
							}
						}
					}
				}
			}				
		}

		// Tag page
		if ( $post->ID == $page_settings['tag'] ) {			
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				// Get Archive SEO title
				if ( array_key_exists( 'title-tax-aiovg_tags', $wpseo_titles ) ) {
					$title_template = $wpseo_titles['title-tax-aiovg_tags'];
				}

				// Get Term SEO title
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {		
					$replacements['%%term_title%%'] = $term->name;
					
					$meta = get_option( 'wpseo_taxonomy_meta' );

					if ( array_key_exists( 'aiovg_tags', $meta ) ) {
						if ( array_key_exists( $term->term_id, $meta['aiovg_tags'] ) ) {
							if ( array_key_exists( 'wpseo_title', $meta['aiovg_tags'][ $term->term_id ] ) ) {
								$title_template = $meta['aiovg_tags'][ $term->term_id ]['wpseo_title'];
							}
						}
					}
				}
			}				
		}
		
		// User videos page
		if ( $post->ID == $page_settings['user_videos'] ) {		
			if ( $slug = get_query_var( 'aiovg_user' ) ) {
				$user = get_user_by( 'slug', $slug );
				$replacements['%%title%%'] = $user->display_name;
				
				// Get Archive SEO title
				if ( array_key_exists( 'title-page', $wpseo_titles ) ) {
					$title_template = $wpseo_titles['title-page'];
				}		
				
				// Get page meta title
				$meta = get_post_meta( $post->ID, '_yoast_wpseo_title', true );

				if ( ! empty( $meta ) ) {
					$title_template = $meta;
				}
			}			
		}

		// Return
		if ( ! empty( $title_template ) ) {
			$title = strtr( $title_template, $replacements );
		}

		return $title;	
	}

	/**
	 * Construct Yoast SEO description for our category and user videos pages.
	 *
	 * @since  3.9.5
	 * @param  array $desc The Yoast description.
	 * @return             Filtered description.
	 */
	public function wpseo_metadesc( $desc ) {	
		global $post;

		if ( ! isset( $post ) ) {
			return $desc;
		}

		$page_settings = get_option( 'aiovg_page_settings' );
		
		if ( $post->ID != $page_settings['category'] && $post->ID != $page_settings['tag'] && $post->ID != $page_settings['user_videos'] ) {
			return $desc;
		}

		$wpseo_titles = get_option( 'wpseo_titles' );

		$sep_options = WPSEO_Option_Titles::get_instance()->get_separator_options();

		if ( isset( $wpseo_titles['separator'] ) && isset( $sep_options[ $wpseo_titles['separator'] ] ) ) {
			$sep = $sep_options[ $wpseo_titles['separator'] ];
		} else {
			$sep = '-'; // Setting default separator if Admin didn't set it from backed
		}

		$replacements = array(
			'%%sep%%'              => $sep,						
			'%%page%%'             => '',
			'%%title%%'            => '',
			'%%primary_category%%' => '',
			'%%sitename%%'         => sanitize_text_field( get_bloginfo( 'name' ) )
		);

		$desc_template = '';

		// Category page
		if ( $post->ID == $page_settings['category'] ) {			
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				// Get Archive SEO desc
				if ( array_key_exists( 'metadesc-tax-aiovg_categories', $wpseo_titles ) ) {
					$desc_template = $wpseo_titles['metadesc-tax-aiovg_categories'];
				}

				// Get Term SEO desc
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
					$replacements['%%term_title%%'] = $term->name;
					
					$meta = get_option( 'wpseo_taxonomy_meta' );

					if ( array_key_exists( 'aiovg_categories', $meta ) ) {
						if ( array_key_exists( $term->term_id, $meta['aiovg_categories'] ) ) {
							if ( array_key_exists( 'wpseo_desc', $meta['aiovg_categories'][ $term->term_id ] ) ) {
								$desc_template = $meta['aiovg_categories'][ $term->term_id ]['wpseo_desc'];
							}
						}
					}
				}
			}				
		}

		// Tag page
		if ( $post->ID == $page_settings['tag'] ) {			
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				// Get Archive SEO desc
				if ( array_key_exists( 'metadesc-tax-aiovg_tags', $wpseo_titles ) ) {
					$desc_template = $wpseo_titles['metadesc-tax-aiovg_tags'];
				}

				// Get Term SEO desc
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
					$replacements['%%term_title%%'] = $term->name;	
					
					$meta = get_option( 'wpseo_taxonomy_meta' );

					if ( array_key_exists( 'aiovg_tags', $meta ) ) {
						if ( array_key_exists( $term->term_id, $meta['aiovg_tags'] ) ) {
							if ( array_key_exists( 'wpseo_desc', $meta['aiovg_tags'][ $term->term_id ] ) ) {
								$desc_template = $meta['aiovg_tags'][ $term->term_id ]['wpseo_desc'];
							}
						}
					}
				}
			}				
		}
		
		// User videos page
		if ( $post->ID == $page_settings['user_videos'] ) {		
			if ( $slug = get_query_var( 'aiovg_user' ) ) {
				$user = get_user_by( 'slug', $slug );
				$replacements['%%title%%'] = $user->display_name;
				
				// Get Archive SEO desc				
				if ( array_key_exists( 'metadesc-page', $wpseo_titles ) ) {
					$desc_template = $wpseo_titles['metadesc-page'];
				}		
				
				// Get page meta desc
				$meta = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );

				if ( ! empty( $meta ) ) {
					$desc_template = $meta;
				}
			}			
		}
		
		// Return
		if ( ! empty( $desc_template ) ) {
			$desc = strtr( $desc_template, $replacements );
		}

		return $desc;	
	}

	/**
	 * Override the Yoast SEO canonical URL on our category and user videos pages.
	 *
	 * @since  3.9.5
	 * @param  array $url The Yoast canonical URL.
	 * @return            Filtered canonical URL.
	 */
	public function wpseo_canonical( $url ) {	
		global $post;

		if ( ! isset( $post ) ) {
			return $url;
		}
		
		$page_settings = get_option( 'aiovg_page_settings' );

		// Category page
		if ( $post->ID == $page_settings['category'] ) {			
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
					$url = aiovg_get_category_page_url( $term );
				}
			}				
		}

		// Tag page
		if ( $post->ID == $page_settings['tag'] ) {			
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
					$url = aiovg_get_tag_page_url( $term );
				}
			}				
		}
		
		// User videos page
		if ( $post->ID == $page_settings['user_videos'] ) {		
			if ( $slug = get_query_var( 'aiovg_user' ) ) {
				$user = get_user_by( 'slug', $slug );
				$url = aiovg_get_user_videos_page_url( $user->ID );
			}			
		}
		
		// Return
		return $url;	
	}

	/**
	 * Override the Yoast SEO Open Graph image URLs on our plugin pages.
	 *
	 * @since  3.9.5
	 * @param  array $url The Yoast image URL.
	 * @return            Filtered image URL.
	 */
	public function wpseo_opengraph_image( $url ) {
		global $post;

		if ( ! isset( $post ) ) {
			return $url;
		}
		
		if ( is_singular( 'aiovg_videos' ) ) {
			$image = get_post_meta( $post->ID, '_yoast_wpseo_opengraph-image', true );

			if ( empty( $image ) ) {
				$image_data = aiovg_get_image( $post->ID, 'large' );
				$image = $image_data['src'];
			}
			
			if ( ! empty( $image ) ) {
				$url = $image;
			}			
		} else {
			$page_settings = get_option( 'aiovg_page_settings' );

			// Category page
			if ( $post->ID == $page_settings['category'] ) {			
				if ( $slug = get_query_var( 'aiovg_category' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
						$meta  = get_option( 'wpseo_taxonomy_meta' );
						$image = '';

						if ( array_key_exists( 'aiovg_categories', $meta ) ) { // Get custom share image from Yoast
							if ( array_key_exists( $term->term_id, $meta['aiovg_categories'] ) ) {
								if ( array_key_exists( 'wpseo_opengraph-image', $meta['aiovg_categories'][ $term->term_id ] ) ) {
									$image = $meta['aiovg_categories'][ $term->term_id ]['wpseo_opengraph-image'];
								}
							}
						}

						if ( empty( $image ) ) {
							$image_data = aiovg_get_image( $term->term_id, 'large', 'term' );
							$image = $image_data['src'];
						}

						if ( ! empty( $image ) ) {
							$url = $image;
						}
					}
				}				
			}

			// Tag page
			if ( $post->ID == $page_settings['tag'] ) {			
				if ( $slug = get_query_var( 'aiovg_tag' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
						$meta  = get_option( 'wpseo_taxonomy_meta' );
						$image = '';

						if ( array_key_exists( 'aiovg_tags', $meta ) ) { // Get custom share image from Yoast
							if ( array_key_exists( $term->term_id, $meta['aiovg_tags'] ) ) {
								if ( array_key_exists( 'wpseo_opengraph-image', $meta['aiovg_tags'][ $term->term_id ] ) ) {
									$image = $meta['aiovg_tags'][ $term->term_id ]['wpseo_opengraph-image'];
								}
							}
						}

						if ( ! empty( $image ) ) {
							$url = $image;
						}
					}
				}				
			}
		}

		// Return
		return $url;
	}

	/**
	 * Add custom Twitter Social Share images for Yoast SEO.
	 *
	 * @since  3.9.5
	 * @param  array $url The Yoast image URL.
	 * @return            Filtered image URL.
	 */
	public function wpseo_twitter_image( $url ) {
		global $post;

		if ( ! isset( $post ) ) {
			return $url;
		}
		
		if ( is_singular( 'aiovg_videos' ) ) {
			$image = get_post_meta( $post->ID, '_yoast_wpseo_twitter-image', true );

			if ( empty( $image ) ) {
				$image_data = aiovg_get_image( $post->ID, 'large' );
				$image = $image_data['src'];
			}
			
			if ( ! empty( $image ) ) {
				$url = $image;
			}			
		} else {
			$page_settings = get_option( 'aiovg_page_settings' );

			// Category page
			if ( $post->ID == $page_settings['category'] ) {			
				if ( $slug = get_query_var( 'aiovg_category' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
						$meta  = get_option( 'wpseo_taxonomy_meta' );
						$image = '';

						if ( array_key_exists( 'aiovg_categories', $meta ) ) { // Get custom share image from Yoast
							if ( array_key_exists( $term->term_id, $meta['aiovg_categories'] ) ) {
								if ( array_key_exists( 'wpseo_twitter-image', $meta['aiovg_categories'][ $term->term_id ] ) ) {
									$image = $meta['aiovg_categories'][ $term->term_id ]['wpseo_twitter-image'];
								}
							}
						}

						if ( empty( $image ) ) {
							$image_data = aiovg_get_image( $term->term_id, 'large', 'term' );
							$image = $image_data['src'];
						}

						if ( ! empty( $image ) ) {
							$url = $image;
						}	
					}				
				}				
			}

			// Tag page
			if ( $post->ID == $page_settings['tag'] ) {			
				if ( $slug = get_query_var( 'aiovg_tag' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
						$meta  = get_option( 'wpseo_taxonomy_meta' );
						$image = '';

						if ( array_key_exists( 'aiovg_tags', $meta ) ) { // Get custom share image from Yoast
							if ( array_key_exists( $term->term_id, $meta['aiovg_tags'] ) ) {
								if ( array_key_exists( 'wpseo_twitter-image', $meta['aiovg_tags'][ $term->term_id ] ) ) {
									$image = $meta['aiovg_tags'][ $term->term_id ]['wpseo_twitter-image'];
								}
							}
						}

						if ( ! empty( $image ) ) {
							$url = $image;	
						}
					}				
				}				
			}
		}

		// Return
		return $url;
	}

	/**
	 * Filter Yoast SEO breadcrumbs.
	 *
	 * @since  3.9.5
	 * @param  array $crumbs Array of crumbs.
	 * @return array $crumbs Filtered array of crumbs.
	 */
	public function wpseo_breadcrumb_links( $crumbs ) {
		global $post;

		if ( ! isset( $post ) ) {
			return $crumbs;
		}

		if ( is_singular( 'aiovg_videos' ) ) {
			foreach ( $crumbs as $index => $crumb ) {
				if ( ! empty( $crumb['ptarchive'] ) && 'aiovg_videos' == $crumb['ptarchive'] ) {
					$obj = get_post_type_object( 'aiovg_videos' );

					$crumbs[ $index ] = array(
						'text' => $obj->labels->name,
						'url'  => aiovg_get_search_page_url()
					);
				}
			}
		} else {
			$page_settings = get_option( 'aiovg_page_settings' );

			if ( $post->ID == $page_settings['category'] ) {
				if ( $slug = get_query_var( 'aiovg_category' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
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
					}		
				}
			}

			if ( $post->ID == $page_settings['tag'] ) {
				if ( $slug = get_query_var( 'aiovg_tag' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
						$crumbs[] = array(
							'text' => $term->name
						);	
					}		
				}
			}
			
			if ( $post->ID == $page_settings['user_videos'] ) {				
				if ( $slug = get_query_var( 'aiovg_user' ) ) {
					$user = get_user_by( 'slug', $slug );
					$crumbs[] = array(
						'text' => $user->display_name
					);			
				}	
			}
		}

		return $crumbs;
	}

	/**
	 * Filter Yoast video sitemap entry details.
	 *
	 * @since  3.9.5
	 * @param  array $details Array of sitemap entry details.
	 * @return array $details Filtered array of sitemap entry details.
	 */
	public function wpseo_video_sitemap_entry( $details ) {
		if ( isset( $details['post_id'] ) && ! empty( $details['post_id'] ) ) {
			$post_id   = (int) $details['post_id'];
			$post_type = get_post_type( $post_id );
	
			if ( 'aiovg_videos' == $post_type ) {
				$thumbnail_loc = get_post_meta(  $post_id, 'image', true );
	
				if ( ! empty( $thumbnail_loc ) ) {
					$details['thumbnail_loc'] = $thumbnail_loc;
				}
			}
		}
	
		return $details;
	}

}
