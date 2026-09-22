<?php

/**
 * Paid Memberships Pro.
 *
 * @link    https://plugins360.com
 * @since   4.9.4
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Public_PMPro class.
 *
 * @since 4.9.4
 */
class AIOVG_Public_PMPro {

	/**
	 * Resolved PMPro access, keyed by user and post.
	 *
	 * @since  4.9.4
	 * @access private
	 * @var    array
	 */
	private $access_cache = array();

	/**
	 * Filter whether the current user can play a video restricted by membership level.
	 *
	 * @since  4.9.4
	 * @param  bool   $has_access True if the user has access, false otherwise.
	 * @param  string $capability Capability being checked.
	 * @param  int    $post_id    Video post ID.
	 * @return bool               True if the user has access, false otherwise.
	 */
	public function filter_user_access( $has_access, $capability, $post_id = 0 ) {
		// Never grant back access another restriction has already denied
		if ( 'play_aiovg_video' != $capability || ! $has_access ) {
			return $has_access;
		}

		$post_id = (int) $post_id;

		if ( $post_id <= 0 || ! $this->is_active() || ! $this->is_video( $post_id ) ) {
			return $has_access;
		}

		$access = $this->get_access( $post_id );

		return $access['allowed'];
	}

	/**
	 * Filter the restricted message so Paid Memberships Pro shows its own message.
	 *
	 * @since  4.9.4
	 * @param  string $html    Restricted message HTML.
	 * @param  int    $post_id Video post ID.
	 * @param  string $context "page", "player" or "iframe".
	 * @param  string $content Post content filtered so far, "page" context only.
	 * @return string          Filtered message HTML.
	 */
	public function filter_restricted_message_html( $html, $post_id, $context, $content = '' ) {
		$post_id = (int) $post_id;

		if ( $post_id <= 0 || ! $this->is_active() || ! $this->is_video( $post_id ) ) {
			return $html;
		}

		$access = $this->get_access( $post_id );

		// Access granted here, so a role restriction refused and its own message stands
		if ( $access['allowed'] ) {
			return $html;
		}

		// PMPro filters the content before us, so its message is already in place
		if ( 'page' == $context && '' !== $content && doing_filter( 'the_content' ) ) {
			return $content;
		}

		if ( 'iframe' == $context ) {
			global $post;

			// Point PMPro's login link back to the video, not the player page
			$previous_post = $post;
			$post          = get_post( $post_id );

			$html = pmpro_get_no_access_message( '', $access['ids'], $access['names'] );

			$post = $previous_post;

			return $html;
		}

		return pmpro_get_no_access_message( '', $access['ids'], $access['names'] );
	}

	/**
	 * Link the trailer overlay to Paid Memberships Pro when no link URL is set.
	 *
	 * Follows the rule PMPro uses for the link in its own no-access message.
	 *
	 * @since  4.9.4
	 * @param  string $link    Player overlay link URL.
	 * @param  int    $post_id Video post ID.
	 * @return string          Filtered link URL.
	 */
	public function filter_player_overlay_link( $link, $post_id = 0 ) {
		$post_id = (int) $post_id;

		if ( ! empty( $link ) || $post_id <= 0 || ! $this->is_active() || ! $this->is_video( $post_id ) ) {
			return $link;
		}

		if ( ! function_exists( 'pmpro_url' ) || ! function_exists( 'pmpro_getLevel' ) ) {
			return $link;
		}

		$access = $this->get_access( $post_id );

		if ( $access['allowed'] ) {
			return $link;
		}

		$level_ids = $access['ids'];

		// Levels that disallow signups are hidden, as in PMPro's own message
		if ( ! apply_filters( 'pmpro_membership_content_filter_disallowed_levels', false, $level_ids, $access['names'] ) ) {
			foreach ( $level_ids as $key => $level_id ) {
				$level = pmpro_getLevel( $level_id );

				if ( empty( $level ) || empty( $level->allow_signups ) ) {
					unset( $level_ids[ $key ] );
				}
			}
		}

		if ( 1 == count( $level_ids ) ) {
			$url = pmpro_url( 'checkout', '?pmpro_level=' . (int) reset( $level_ids ) );
		} else {
			$url = pmpro_url( 'levels' );
		}

		return ! empty( $url ) ? $url : $link;
	}

	/**
	 * Keep the video description on single video pages that show a trailer.
	 *
	 * @since  4.9.4
	 * @param  bool|string $filter     False to let PMPro filter the content.
	 * @param  string      $content    Post content.
	 * @param  bool        $has_access Whether the current user has access.
	 * @return bool|string             The content to keep, or false.
	 */
	public function filter_membership_content( $filter, $content, $has_access = true ) {
		if ( false !== $filter ) {
			return $filter;
		}

		// Same guards as AIOVG_Public_Video::the_content()
		$in_the_loop   = apply_filters( 'aiovg_the_content_in_the_loop', in_the_loop() );
		$is_main_query = apply_filters( 'aiovg_the_content_is_main_query', is_main_query() );

		if ( ! is_singular( 'aiovg_videos' ) || ! $in_the_loop || ! $is_main_query ) {
			return $filter;
		}

		global $wp_the_query, $post;

		if ( empty( $post ) || $post->ID != $wp_the_query->get_queried_object_id() || post_password_required( $post->ID ) ) {
			return $filter;
		}

		return $this->has_trailer( $post->ID ) ? $content : $filter;
	}

	/**
	 * Keep single video pages with a trailer from being redirected by the CPT add-on.
	 *
	 * @since  4.9.4
	 * @param  string $redirect_to URL the add-on redirects restricted CPT pages to.
	 * @return string              Filtered URL, empty to skip the redirect.
	 */
	public function filter_cpt_redirect_to( $redirect_to ) {
		// Runs on every front-end request, so bail out cheaply first
		if ( empty( $redirect_to ) || ! is_singular( 'aiovg_videos' ) ) {
			return $redirect_to;
		}

		return $this->has_trailer( get_queried_object_id() ) ? '' : $redirect_to;
	}

	/**
	 * Whether a video shows a trailer to users who cannot play it.
	 *
	 * @since  4.9.4
	 * @access private
	 * @param  int  $post_id Video post ID.
	 * @return bool          True if trailers are enabled and the video has one.
	 */
	private function has_trailer( $post_id ) {
		$trailer_settings = aiovg_get_option( 'aiovg_trailer_settings' );

		if ( empty( $trailer_settings['enable_trailers'] ) ) {
			return false;
		}

		$trailer_src = get_post_meta( $post_id, 'trailer_src', true );

		return ! empty( $trailer_src );
	}

	/**
	 * Whether the Paid Memberships Pro functions we rely on are available.
	 *
	 * @since  4.9.4
	 * @access private
	 * @return bool True if Paid Memberships Pro is active, false if not.
	 */
	private function is_active() {
		return function_exists( 'pmpro_has_membership_access' ) && function_exists( 'pmpro_get_no_access_message' );
	}

	/**
	 * Whether a post is a video.
	 *
	 * Not tied to `pmpro_restrictable_post_types`: levels stored on a video are still
	 * enforced by PMPro after the post type is deselected.
	 *
	 * @since  4.9.4
	 * @access private
	 * @param  int  $post_id Post ID.
	 * @return bool          True for a video post, false otherwise.
	 */
	private function is_video( $post_id ) {
		return 'aiovg_videos' == get_post_type( $post_id );
	}

	/**
	 * Get the PMPro access decision for a video, cached for the request.
	 *
	 * @since  4.9.4
	 * @access private
	 * @param  int   $post_id Video post ID.
	 * @return array          Decision with "allowed", "ids" and "names" keys.
	 */
	private function get_access( $post_id ) {
		$post_id   = (int) $post_id;
		$cache_key = get_current_user_id() . ':' . $post_id;

		if ( ! isset( $this->access_cache[ $cache_key ] ) ) {
			$access = pmpro_has_membership_access( $post_id, null, true );

			// Only an array when membership levels are requested
			if ( ! is_array( $access ) ) {
				$access = array( ! empty( $access ), array(), array() );
			}

			$this->access_cache[ $cache_key ] = array(
				'allowed' => ! empty( $access[0] ),
				'ids'     => ! empty( $access[1] ) ? (array) $access[1] : array(),
				'names'   => ! empty( $access[2] ) ? (array) $access[2] : array()
			);
		}

		return $this->access_cache[ $cache_key ];
	}

}
