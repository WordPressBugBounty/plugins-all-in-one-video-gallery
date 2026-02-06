<?php

/**
 * Multilingual Compatibility.
 *
 * @link    https://plugins360.com
 * @since   3.0.0
 *
 * @package All_In_One_Video_Gallery
 */
 
// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Public_Multilingual class.
 *
 * @since 3.0.0
 */
class AIOVG_Public_Multilingual {

	/**
	 * Add custom rewrite rules.
	 *
	 * @since 4.2.2
	 */
	public function init() {
		if ( ! function_exists( 'pll_current_language' ) && ! function_exists( 'pll_get_post_translations' ) ) {
			return;
		}

		$pll_current_language = pll_current_language();
		if ( ! empty( $pll_current_language ) ) {
			return;
		}

		$page_settings = aiovg_get_option( 'aiovg_page_settings' );
		$site_url = home_url();
		
		// Single category page		
		if ( ! empty( $page_settings['category'] ) ) {
			$id = (int) $page_settings['category'];
			$translations = pll_get_post_translations( $id );

			foreach ( $translations as $lang => $post_id ) {
				$post = get_post( $post_id );

				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $post_id );

					if ( $permalink ) {
						$slug = str_replace( $site_url, '', $permalink );			
						$slug = trim( $slug, '/' );
						$slug = urldecode( $slug );		
						
						add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $post_id . '&aiovg_category=$matches[1]&paged=$matches[2]', 'top' );
						add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_category=$matches[1]', 'top' );
					}
				}
			}
		}

		// Single tag page
		if ( ! empty( $page_settings['tag'] ) ) {
			$id = (int) $page_settings['tag'];
			$translations = pll_get_post_translations( $id );

			foreach ( $translations as $lang => $post_id ) {
				$post = get_post( $post_id );

				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $post_id );

					if ( $permalink ) {
						$slug = str_replace( $site_url, '', $permalink );			
						$slug = trim( $slug, '/' );
						$slug = urldecode( $slug );		
						
						add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $post_id . '&aiovg_tag=$matches[1]&paged=$matches[2]', 'top' );
						add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_tag=$matches[1]', 'top' );
					}
				}
			}
		}
		
		// User videos page
		if ( ! empty( $page_settings['user_videos'] ) ) {
			$id = (int) $page_settings['user_videos'];
			$translations = pll_get_post_translations( $id );

			foreach ( $translations as $lang => $post_id ) {
				$post = get_post( $post_id );

				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $post_id );

					if ( $permalink ) {
						$slug = str_replace( $site_url, '', $permalink );			
						$slug = trim( $slug, '/' );
						$slug = urldecode( $slug );		
						
						add_rewrite_rule( "^$slug/([^/]+)/page/?([0-9]{1,})/?$", 'index.php?page_id=' . $post_id . '&aiovg_user=$matches[1]&paged=$matches[2]', 'top' );
						add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_user=$matches[1]', 'top' );
					}
				}
			}
		}
		
		// Player page
		if ( ! empty( $page_settings['player'] ) ) {
			$id = (int) $page_settings['player'];
			$translations = pll_get_post_translations( $id );

			foreach ( $translations as $lang => $post_id ) {
				$post = get_post( $post_id );

				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $post_id );

					if ( $permalink ) {
						$slug = str_replace( $site_url, '', $permalink );			
						$slug = trim( $slug, '/' );
						$slug = urldecode( $slug );		
						
						add_rewrite_rule( "^$slug/id/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_type=id&aiovg_video=$matches[1]', 'top' );
					}
				}
			}
		}

		// Video form page	
		if ( ! empty( $page_settings['video_form'] ) ) {
			$id = (int) $page_settings['video_form'];
			$translations = pll_get_post_translations( $id );

			foreach ( $translations as $lang => $post_id ) {
				$post = get_post( $post_id );
				
				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $post_id );

					if ( $permalink ) {
						$slug = str_replace( $site_url, '', $permalink );			
						$slug = trim( $slug, '/' );	
						$slug = urldecode( $slug );		
						
						add_rewrite_rule( "^$slug/edit/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_action=edit&aiovg_video=$matches[1]', 'top' );
						add_rewrite_rule( "^$slug/delete/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_action=delete&aiovg_video=$matches[1]', 'top' );
					}
				}
			}
		}

		// Single playlist page
		if ( ! empty( $page_settings['playlist'] ) ) {
			$id = (int) $page_settings['playlist'];
			$translations = pll_get_post_translations( $id );

			foreach ( $translations as $lang => $post_id ) {
				$post = get_post( $post_id );
				
				if ( $post && 'publish' === $post->post_status ) {
					$permalink = get_permalink( $post_id );

					if ( $permalink ) {
						$slug = str_replace( $site_url, '', $permalink );			
						$slug = trim( $slug, '/' );	
						$slug = urldecode( $slug );		
						
						add_rewrite_rule( "^$slug/([^/]+)/?$", 'index.php?page_id=' . $post_id . '&aiovg_playlist=$matches[1]', 'top' );
					}
				}
			}
		}
	}

	/**
	 * [Polylang] Filter the 'aiovg_page_settings' option.
	 *
	 * @since  3.0.0
	 * @param  array $settings Default settings array.
	 * @return array $settings Filtered array of settings.
	 */
	public function filter_page_settings_for_polylang( $settings ) {
		if ( ! function_exists( 'pll_current_language' ) && ! function_exists( 'pll_get_post' ) ) {
			return $settings;
		}

		$pll_current_language = pll_current_language();
		if ( empty( $pll_current_language ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $value ) {
			if ( $value > 0 ) {				
				$id = pll_get_post( $value );

				if ( ! empty( $id ) ) {
					$settings[ $key ] = $id;
				}
			}
		}

		return $settings;
	}	
	
}
