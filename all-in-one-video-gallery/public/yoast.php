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

		$page_settings = aiovg_get_option( 'aiovg_page_settings' );

		if ( $post->ID != $page_settings['category'] && $post->ID != $page_settings['tag'] && $post->ID != $page_settings['user_videos'] ) {
			return $title;
		}

		$wpseo_titles = get_option( 'wpseo_titles' );

		$sep = $this->get_separator( $wpseo_titles );

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
			if ( ( $slug = get_query_var( 'aiovg_user' ) ) && ( $user = get_user_by( 'slug', $slug ) ) ) {
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

		$page_settings = aiovg_get_option( 'aiovg_page_settings' );
		
		if ( $post->ID != $page_settings['category'] && $post->ID != $page_settings['tag'] && $post->ID != $page_settings['user_videos'] ) {
			return $desc;
		}

		$wpseo_titles = get_option( 'wpseo_titles' );

		$sep = $this->get_separator( $wpseo_titles );

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
			if ( ( $slug = get_query_var( 'aiovg_user' ) ) && ( $user = get_user_by( 'slug', $slug ) ) ) {
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
		
		$page_settings = aiovg_get_option( 'aiovg_page_settings' );

		// Category page
		if ( $post->ID == $page_settings['category'] ) {			
			if ( $slug = get_query_var( 'aiovg_category' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
					$url = aiovg_add_page_number_to_url( aiovg_get_category_page_url( $term ) );
				}
			}				
		}

		// Tag page
		if ( $post->ID == $page_settings['tag'] ) {			
			if ( $slug = get_query_var( 'aiovg_tag' ) ) {
				if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
					$url = aiovg_add_page_number_to_url( aiovg_get_tag_page_url( $term ) );
				}
			}				
		}
		
		// User videos page
		if ( $post->ID == $page_settings['user_videos'] ) {		
			if ( ( $slug = get_query_var( 'aiovg_user' ) ) && ( $user = get_user_by( 'slug', $slug ) ) ) {
				$url = aiovg_add_page_number_to_url( aiovg_get_user_videos_page_url( $user->ID ) );
			}			
		}
		
		// Video archive page, from page 2 onwards
		if ( aiovg_is_video_archive_page( $post->ID ) ) {
			$url = aiovg_add_page_number_to_url( $url );
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
			$page_settings = aiovg_get_option( 'aiovg_page_settings' );

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
			$page_settings = aiovg_get_option( 'aiovg_page_settings' );

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
	 * Add our Open Graph image when Yoast SEO has found none.
	 *
	 * @since  4.9.4
	 * @param  object $image_container Yoast SEO image container.
	 * @return object                  The image container.
	 */
	public function wpseo_add_opengraph_additional_images( $image_container ) {
		// wpseo_opengraph_image only filters images Yoast has already found
		if ( method_exists( $image_container, 'has_images' ) && ! $image_container->has_images() ) {
			$image_container->add_image_by_url( $this->wpseo_opengraph_image( '' ) );
		}

		return $image_container;
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
			$permalink_settings = aiovg_get_option( 'aiovg_permalink_settings' );
			$post_type_object   = get_post_type_object( 'aiovg_videos' );

			$page_id = isset( $permalink_settings['video_archive_page'] ) ? (int) $permalink_settings['video_archive_page'] : 0;

			// A valid custom archive page disables has_archive, so Yoast adds no archive crumb
			if ( $page_id > 0 && ! $post_type_object->has_archive ) {
				$page_id = (int) apply_filters( 'aiovg_object_id', $page_id, 'page' );

				$page_ids   = array_reverse( get_post_ancestors( $page_id ) );
				$page_ids[] = $page_id;

				$archive_crumbs = array();

				foreach ( $page_ids as $id ) {
					$title = get_post_meta( $id, '_yoast_wpseo_bctitle', true );

					$archive_crumbs[] = array(
						'text' => ! empty( $title ) ? $title : wp_strip_all_tags( get_the_title( $id ), true ),
						'url'  => get_permalink( $id ),
						'id'   => $id
					);
				}

				// Insert after the Home crumb
				$breadcrumbs_home = WPSEO_Options::get( 'breadcrumbs-home' );
				$position         = '' !== $breadcrumbs_home ? 1 : 0;

				array_splice( $crumbs, $position, 0, $archive_crumbs );
			} else {
				foreach ( $crumbs as $index => $crumb ) {
					if ( ! empty( $crumb['ptarchive'] ) && 'aiovg_videos' == $crumb['ptarchive'] ) {
						$crumbs[ $index ] = array(
							'text' => $post_type_object->labels->name,
							'url'  => aiovg_get_search_page_url()
						);
					}
				}
			}
		} else {
			$page_settings = aiovg_get_option( 'aiovg_page_settings' );

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
							'text' => $term->name,
							'url'  => aiovg_get_category_page_url( $term )
						);	
					}		
				}
			}

			if ( $post->ID == $page_settings['tag'] ) {
				if ( $slug = get_query_var( 'aiovg_tag' ) ) {
					if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
						$crumbs[] = array(
							'text' => $term->name,
							'url'  => aiovg_get_tag_page_url( $term )
						);	
					}		
				}
			}
			
			if ( $post->ID == $page_settings['user_videos'] ) {				
				if ( ( $slug = get_query_var( 'aiovg_user' ) ) && ( $user = get_user_by( 'slug', $slug ) ) ) {
					$crumbs[] = array(
						'text' => $user->display_name,
						'url'  => aiovg_get_user_videos_page_url( $user->ID )
					);			
				}	
			}
		}

		return $crumbs;
	}

	/**
	 * Point Yoast's schema graph at the category, tag, user or playlist URL, as the canonical does.
	 *
	 * @since  4.9.6
	 * @param  array  $graph   Schema graph pieces.
	 * @param  object $context Yoast's meta tags context.
	 * @return array           Filtered graph.
	 */
	public function wpseo_schema_graph( $graph, $context ) {
		$from = is_object( $context ) ? (string) $context->permalink : '';
		$to   = '' === $from ? '' : (string) $this->wpseo_canonical( $from );

		if ( '' === $to || $to === $from ) {
			return $graph;
		}

		// The page's own ids and URLs only: breadcrumb items rightly link to the plugin page
		$swap = function ( $value ) use ( $from, $to ) {
			if ( ! is_string( $value ) ) {
				return $value;
			}

			if ( $value === $from ) {
				return $to;
			}

			return 0 === strpos( $value, $from . '#' ) ? $to . substr( $value, strlen( $from ) ) : $value;
		};

		foreach ( $graph as $index => $piece ) {
			if ( isset( $piece['@id'], $piece['name'] ) && $piece['@id'] === $from ) {
				$piece['name'] = $this->wpseo_title( $piece['name'] );
			}

			$graph[ $index ] = $this->swap_schema_urls( $piece, $swap );
		}

		return $graph;
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

	/**
	 * Get Yoast SEO's title separator.
	 *
	 * @since  4.9.6
	 * @access protected
	 * @param  array     $wpseo_titles Yoast SEO's title settings.
	 * @return string                  Separator.
	 */
	protected function get_separator( $wpseo_titles ) {
		$sep_options = WPSEO_Option_Titles::get_instance()->get_separator_options();

		if ( isset( $wpseo_titles['separator'] ) && isset( $sep_options[ $wpseo_titles['separator'] ] ) ) {
			return $sep_options[ $wpseo_titles['separator'] ];
		}

		return '-'; // Setting default separator if Admin didn't set it from backed
	}

	/**
	 * Replace the page's own "@id", "url" and "target" values in a schema piece.
	 *
	 * @since  4.9.6
	 * @access private
	 * @param  array    $data Schema piece.
	 * @param  callable $swap Maps one value to its replacement.
	 * @return array          Filtered schema piece.
	 */
	private function swap_schema_urls( $data, $swap ) {
		foreach ( $data as $key => $value ) {
			if ( 'target' === $key ) {
				$data[ $key ] = is_array( $value ) ? array_map( $swap, $value ) : $swap( $value );
			} elseif ( is_array( $value ) ) {
				$data[ $key ] = $this->swap_schema_urls( $value, $swap );
			} elseif ( '@id' === $key || 'url' === $key ) {
				$data[ $key ] = $swap( $value );
			}
		}

		return $data;
	}

}
