<?php

/**
 * Rank Math SEO.
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
 * AIOVG_Public_Rank_Math class.
 *
 * @since 3.9.5
 */
class AIOVG_Public_Rank_Math {

	/**
	 * Updates the page titles on the plugin pages.
	 *
	 * @since  3.9.5
	 * @param  string $title Default page title.
	 * @return string $title The filtered page title.
	 */
	public function meta_title( $title ) {
		global $post;

		// Category Page
		if ( $this->is_plugin_page( 'category' ) && ( $slug = get_query_var( 'aiovg_category' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
				// Get the global title template for the category pages 
				$title_template = RankMath\Helper::get_settings( 'titles.tax_aiovg_categories_title' );

				// Get the title template for the current category page (if available)
				$current_term_title_template = get_term_meta( $term->term_id, 'rank_math_title', true );
				if ( ! empty( $current_term_title_template ) ) {
					$title_template = $current_term_title_template;
				}
				
				if ( ! empty( $title_template ) ) {
					$title = RankMath\Helper::replace_vars( $title_template, $term );
				}
			}
		}

		// Tag Page
		if ( $this->is_plugin_page( 'tag' ) && ( $slug = get_query_var( 'aiovg_tag' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
				// Get the global title template for the tag pages 
				$title_template = RankMath\Helper::get_settings( 'titles.tax_aiovg_tags_title' );			

				// Get the title template for the current tag page (if available)
				$current_term_title_template = get_term_meta( $term->term_id, 'rank_math_title', true );
				if ( ! empty( $current_term_title_template ) ) {
					$title_template = $current_term_title_template;
				}
				
				if ( ! empty( $title_template ) ) {
					$title = RankMath\Helper::replace_vars( $title_template, $term );
				}
			}
		}

		// User Videos Page
		if ( $this->is_plugin_page( 'user_videos' ) && ( $slug = get_query_var( 'aiovg_user' ) ) ) {
			if ( $user = get_user_by( 'slug', $slug ) ) {
				// Get the global title template for the pages 
				$title_template = RankMath\Helper::get_settings( 'titles.pt_page_title' );
				
				// Get the title template for the current page (if available)
				$current_page_title_template = get_post_meta( $post->ID, 'rank_math_title', true );
				if ( ! empty( $current_page_title_template ) ) {
					$title_template = $current_page_title_template;
				}

				if ( ! empty( $title_template ) ) {				
					$title_template = str_replace( '%title%', $user->display_name, $title_template );
					$title = RankMath\Helper::replace_vars( $title_template, $post );
				}
			}
		}

		return $title;
	}

	/**
	 * Updates the page description on the plugin pages.
	 *
	 * @since  3.9.5
	 * @param  string $description Default page description.
	 * @return string $description The filtered page description.
	 */
	public function meta_description( $description ) {
		global $post;

		// Category Page
		if ( $this->is_plugin_page( 'category' ) && ( $slug = get_query_var( 'aiovg_category' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
				// Get the global description template for the category pages 
				$description_template = RankMath\Helper::get_settings( 'titles.tax_aiovg_categories_description' );

				// Get the description template for the current category page (if available)
				$current_term_description_template = get_term_meta( $term->term_id, 'rank_math_description', true );
				if ( ! empty( $current_term_description_template ) ) {
					$description_template = $current_term_description_template;
				}
				
				if ( ! empty( $description_template ) ) {
					$description = RankMath\Helper::replace_vars( $description_template, $term );
				}
			}
		}

		// Tag Page
		if ( $this->is_plugin_page( 'tag' ) && ( $slug = get_query_var( 'aiovg_tag' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
				// Get the global description template for the tag pages 
				$description_template = RankMath\Helper::get_settings( 'titles.tax_aiovg_tags_description' );

				// Get the description template for the current tag page (if available)
				$current_term_description_template = get_term_meta( $term->term_id, 'rank_math_description', true );
				if ( ! empty( $current_term_description_template ) ) {
					$description_template = $current_term_description_template;
				}

				if ( ! empty( $description_template ) ) {
					$description = RankMath\Helper::replace_vars( $description_template, $term );
				}
			}
		}

		// User Videos Page
		if ( $this->is_plugin_page( 'user_videos' ) && ( $slug = get_query_var( 'aiovg_user' ) ) ) {
			if ( $user = get_user_by( 'slug', $slug ) ) {
				// Get the global description template for the pages 
				$description_template = RankMath\Helper::get_settings( 'titles.pt_page_description' );
				
				// Get the description template for the current page (if available)
				$current_page_description_template = get_post_meta( $post->ID, 'rank_math_description', true );
				if ( ! empty( $current_page_description_template ) ) {
					$description_template = $current_page_description_template;
				}

				if ( ! empty( $description_template ) ) {
					$description_template = str_replace( '%title%', $user->display_name, $description_template );
					$description = RankMath\Helper::replace_vars( $description_template, $post );
				}
			}
		}

		return $description;
	}

	/**
	 * Updates the canonical URL on the plugin pages.
	 *
	 * @since  3.9.5
	 * @param  string $canonical_url The canonical URL.
	 * @return string $canonical_url The filtered canonical URL.
	 */
	public function canonical_url( $canonical_url ) {	
		// Category Page
		if ( $this->is_plugin_page( 'category' ) && ( $slug = get_query_var( 'aiovg_category' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
				$canonical_url = aiovg_add_page_number_to_url( aiovg_get_category_page_url( $term ) );
			}
		}				

		// Tag Page
		if ( $this->is_plugin_page( 'tag' ) && ( $slug = get_query_var( 'aiovg_tag' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
				$canonical_url = aiovg_add_page_number_to_url( aiovg_get_tag_page_url( $term ) );
			}
		}				
		
		// User Videos Page
		if ( $this->is_plugin_page( 'user_videos' ) && ( $slug = get_query_var( 'aiovg_user' ) ) ) {
			if ( $user = get_user_by( 'slug', $slug ) ) {
				$canonical_url = aiovg_add_page_number_to_url( aiovg_get_user_videos_page_url( $user->ID ) );
			}
		}			
		
		// Video Archive Page, from page 2 onwards
		if ( aiovg_is_video_archive_page( get_queried_object_id() ) ) {
			$canonical_url = aiovg_add_page_number_to_url( $canonical_url );
		}

		return $canonical_url;	
	}

	/**
	 * Updates the Open Graph image URL on the plugin pages.
	 *
	 * @since  3.9.5
	 * @param  string $image_url The Image URL.
	 * @return string $image_url The filtered image URL.
	 */
	public function opengraph_image_url( $image_url ) {
		// Category Page
		if ( $this->is_plugin_page( 'category' ) && ( $slug = get_query_var( 'aiovg_category' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
				// Get the opengraph image URL for the current category page (if available)
				$opengraph_image_url = get_term_meta( $term->term_id, 'rank_math_facebook_image', true );
				if ( ! empty( $opengraph_image_url ) ) {
					$image_url = $opengraph_image_url;
				} else {
					// Get the actual image URL for the current category page (if available)
					$image_data = aiovg_get_image( $term->term_id, 'large', 'term' );
					if ( ! empty( $image_data['src'] ) ) {
						$image_url = $image_data['src'];
					}
				}
			}
		}

		// Tag Page
		if ( $this->is_plugin_page( 'tag' ) && ( $slug = get_query_var( 'aiovg_tag' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
				// Get the opengraph image URL for the current category page (if available)
				$opengraph_image_url = get_term_meta( $term->term_id, 'rank_math_facebook_image', true );
				if ( ! empty( $opengraph_image_url ) ) {
					$image_url = $opengraph_image_url;
				}
			}
		}

		return $image_url;
	}

	/**
	 * Filters the Breadcrumb items.
	 *
	 * @since  3.9.5
	 * @param  array $crumbs Array of crumbs.
	 * @return array $crumbs The filtered array of crumbs.
	 */
	public function breadcrumb_items( $crumbs ) {
		// Single Video Page
		if ( is_singular( 'aiovg_videos' ) ) {
			$permalink_settings = aiovg_get_option( 'aiovg_permalink_settings' );
			$post_type_object   = get_post_type_object( 'aiovg_videos' );

			$page_id = isset( $permalink_settings['video_archive_page'] ) ? (int) $permalink_settings['video_archive_page'] : 0;

			// A valid custom archive page disables has_archive, so Rank Math adds no archive crumb
			if ( $page_id > 0 && ! $post_type_object->has_archive ) {
				$page_id = (int) apply_filters( 'aiovg_object_id', $page_id, 'page' );

				$page_ids   = array_reverse( get_post_ancestors( $page_id ) );
				$page_ids[] = $page_id;

				$archive_crumbs = array();

				foreach ( $page_ids as $id ) {
					$title = RankMath\Helper::get_post_meta( 'breadcrumb_title', $id );

					$archive_crumbs[] = array(
						0 => ! empty( $title ) ? $title : wp_strip_all_tags( get_the_title( $id ), true ),
						1 => get_permalink( $id )
					);
				}

				// Insert after the Home crumb
				$position = RankMath\Helper::get_settings( 'general.breadcrumbs_home' ) ? 1 : 0;

				array_splice( $crumbs, $position, 0, $archive_crumbs );
			}
		}

		// Category Page
		if ( $this->is_plugin_page( 'category' ) && ( $slug = get_query_var( 'aiovg_category' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_categories' ) ) {
				// Include the parent categories if available
				if ( $ancestors = get_ancestors( $term->term_id, 'aiovg_categories' ) ) {
					$ancestors = array_reverse( $ancestors );

					foreach ( $ancestors as $term_id ) {
						if ( $parent_term = get_term_by( 'term_id', $term_id, 'aiovg_categories' ) ) {
							$crumbs[] = array(
								0 => $parent_term->name,
								1 => aiovg_get_category_page_url( $parent_term )
							);
						}
					}
				}

				// Include the current category page
				$crumbs[] = array(
					0 => $term->name,
					1 => aiovg_get_category_page_url( $term )
				);	
			}
		}
			
		// Tag Page
		if ( $this->is_plugin_page( 'tag' ) && ( $slug = get_query_var( 'aiovg_tag' ) ) ) {
			if ( $term = get_term_by( 'slug', $slug, 'aiovg_tags' ) ) {
				$crumbs[] = array(
					0 => $term->name,
					1 => aiovg_get_tag_page_url( $term )
				);	
			}
		}

		// User Videos Page
		if ( $this->is_plugin_page( 'user_videos' ) && ( $slug = get_query_var( 'aiovg_user' ) ) ) {
			if ( $user = get_user_by( 'slug', $slug ) ) {
				$crumbs[] = array(
					0 => $user->display_name,
					1 => aiovg_get_user_videos_page_url( $user->ID )
				);
			}
		}	

		return $crumbs;
	}

	/**
	 * Set thumbnailUrl field in VideoObject Schema.
	 *
	 * @since  2.4.3
	 * @param  array $entity Snippet Data
	 * @return array
	 */
	public function videoobject_entity( $entity ) {
		if ( is_singular( 'aiovg_videos' ) ) {
			global $post;
		   
			$image_data = aiovg_get_image( $post->ID, 'large' );
			$image = $image_data['src'];
	
			if ( ! empty( $image ) ) {
				$entity['thumbnailUrl'] = $image;
			}      
		}
	   
		return $entity;
	}

	/**
	 * Checks whether the current page is the given plugin page.
	 *
	 * @since  4.9.4
	 * @param  string $page Page settings key: "category", "tag", "user_videos" or "playlist".
	 * @return bool         True if the current page is that plugin page.
	 */
	protected function is_plugin_page( $page ) {
		$page_settings = aiovg_get_option( 'aiovg_page_settings' );

		// Our query vars are public, so any URL can carry them
		return ! empty( $page_settings[ $page ] ) && get_queried_object_id() == $page_settings[ $page ];
	}
	
}
