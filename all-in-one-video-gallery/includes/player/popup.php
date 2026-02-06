<?php

/**
 * Popup Player.
 *
 * @link    https://plugins360.com
 * @since   3.5.0
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Player_Popup class.
 *
 * @since 3.5.0
 */
class AIOVG_Player_Popup extends AIOVG_Player_Base {

	/**
	 * Get things started.
	 *
	 * @since 3.5.0
	 * @param int   $post_id      Post ID.
 	 * @param array $args         Player options.
	 * @param int   $reference_id Player reference ID.
	 */
	public function __construct( $post_id, $args, $reference_id ) {	
		parent::__construct( $post_id, $args, $reference_id );	
	}	

	/**
	 * Get the player HTML.
	 *
	 * @since  3.5.0
 	 * @return string $html Player HTML.
	 */
	public function get_player() {		
		$player_settings  = $this->get_player_settings();
		$general_settings = aiovg_get_option( 'aiovg_general_settings' );
		
		$lazyloading   = ! empty( $general_settings['lazyloading'] ) ? 'loading="lazy" ' : '';
		$popup_content = __( 'Open Popup', 'all-in-one-video-gallery' );
		$is_image      = 0;

		if ( ! isset( $this->args['content'] ) ) {
			$poster = $this->get_poster();

			if ( ! empty( $poster ) ) {
				$popup_content = sprintf( '<img src="%s" alt="" %s/>', esc_url( $poster ), $lazyloading );
				$is_image = 1;
			}
		} else {
			$popup_content = trim( (string) $this->args['content'] );

			if ( $popup_content && filter_var( $popup_content, FILTER_VALIDATE_URL ) ) {
				$parsed = wp_parse_url( $popup_content );

				// Allow only http / https URLs
				if ( isset( $parsed['scheme'] ) && in_array( $parsed['scheme'], array( 'http', 'https' ) ) ) {
					$popup_content = sprintf( '<img src="%s" alt="" %s/>', esc_url( $popup_content ), $lazyloading );
					$is_image = 1;
				}
			}
		}

		if ( $is_image ) {
			$icon = '<svg xmlns="http://www.w3.org/2000/svg" fill="white" width="40" height="40" viewBox="0 0 24 24" class="aiovg-svg-icon-play aiovg-flex-shrink-0">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm14.024-.983a1.125 1.125 0 0 1 0 1.966l-5.603 3.113A1.125 1.125 0 0 1 9 15.113V8.887c0-.857.921-1.4 1.671-.983l5.603 3.113Z" clip-rule="evenodd" />
            </svg>';

			if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
				$has_access = aiovg_current_user_can( 'play_aiovg_video', $this->post_id );

				if ( ! $has_access ) {
					$icon = '<svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="32" height="32" viewBox="0 0 50 50" class="aiovg-svg-icon-locked aiovg-flex-shrink-0">
						<path d="M 25 3 C 18.363281 3 13 8.363281 13 15 L 13 20 L 9 20 C 7.300781 20 6 21.300781 6 23 L 6 47 C 6 48.699219 7.300781 50 9 50 L 41 50 C 42.699219 50 44 48.699219 44 47 L 44 23 C 44 21.300781 42.699219 20 41 20 L 37 20 L 37 15 C 37 8.363281 31.636719 3 25 3 Z M 25 5 C 30.566406 5 35 9.433594 35 15 L 35 20 L 15 20 L 15 15 C 15 9.433594 19.433594 5 25 5 Z M 25 30 C 26.699219 30 28 31.300781 28 33 C 28 33.898438 27.601563 34.6875 27 35.1875 L 27 38 C 27 39.101563 26.101563 40 25 40 C 23.898438 40 23 39.101563 23 38 L 23 35.1875 C 22.398438 34.6875 22 33.898438 22 33 C 22 31.300781 23.300781 30 25 30 Z"></path>
					</svg>';
				}
			}

			$popup_content .= $icon;
		}

		// Enqueue dependencies
		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-magnific-popup' );
		if ( $is_image ) wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-public' );
		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-premium-public' );

		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-magnific-popup' );
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-template-popup' );
	
		// Process output
		$this->args['autoplay'] = 1;
		$this->embed_url = aiovg_get_player_page_url( $this->post_id, $this->args ); 

		$classes = array();
		$classes[] = 'aiovg-video-template-popup';
		if ( $is_image ) $classes[] = 'aiovg-is-image';

		$html = sprintf( 
			'<a href="javascript: void(0);" class="%s" data-mfp-src="%s" data-player_ratio="%s">%s</a>',
			implode( ' ', $classes ),
			esc_url( $this->embed_url ),
			(float) $player_settings['ratio'] . '%',
			$popup_content
		);

		return $html;
	}
	
}
