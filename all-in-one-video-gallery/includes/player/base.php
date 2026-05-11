<?php

/**
 * Video Player.
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
 * AIOVG_Player_Base class.
 *
 * @since 3.5.0
 */
class AIOVG_Player_Base {

	/**
	 * Player unique ID.
	 *
	 * @since  3.5.0
	 * @access protected
	 * @var    int	 
	 */
	protected $player_uid = 0;

	/**
	 * Player type.
	 *
	 * @since  4.7.6
	 * @access protected
	 * @var    string	 
	 */
	protected $player_type = '';

	/**
	 * Post ID.
	 *
	 * @since  3.5.0
	 * @access protected
	 * @var    int	 
	 */
	protected $post_id = 0;

	/**
	 * Post Type.
	 *
	 * @since  3.5.0
	 * @access protected
	 * @var    string	 
	 */
	protected $post_type = 'page';

	/**
	 * Post Title.
	 *
	 * @since  3.5.0
	 * @access protected
	 * @var    string	 
	 */
	protected $post_title = '';	

	/**
	 * Player options.
	 *
	 * @since  3.5.0
	 * @access protected
	 * @var    array	 
	 */
	protected $args = array();

	/**
	 * Player embed URL.
	 *
	 * @since  3.5.0
	 * @access protected
	 * @var    string
	 */
	protected $embed_url = '';

	/**
	 * Whether the current user has access to view the video.
	 *
	 * @since  4.7.6
	 * @access protected
	 * @var    bool
	 */
	protected $has_access = true;

	/**
	 * Whether the player is currently rendering a trailer.
	 *
	 * @since  4.7.6
	 * @access protected
	 * @var    bool
	 */
	protected $is_trailer = false;

	/**
	 * An array of cached player options.
	 *
	 * @since  3.5.0
	 * @access private
	 * @var    array	 
	 */
	private $cache = array();

	/**
	 * Get things started.
	 *
	 * @since 3.5.0
	 * @param int    $post_id     Post ID.
 	 * @param array  $args        Player options.
	 * @param int    $player_uid  Player unique ID.
	 * @param string $player_type Player type.
	 */
	public function __construct( $post_id, $args, $player_uid, $player_type ) {	
		$this->post_id     = $post_id;
		$this->args        = $args;						
		$this->player_uid  = $player_uid;			
		$this->player_type = $player_type;

		if ( $this->post_id > 0 ) {
			$this->post_type  = get_post_type( $this->post_id );
			$this->post_title = get_the_title( $this->post_id );

			if ( 'iframe' !== $this->player_type ) {
				$this->has_access = aiovg_current_user_can( 'play_aiovg_video', $this->post_id );

				// If the user doesn't have access, check if there's a trailer configured and use that for the player instead.
				if ( ! $this->has_access ) {
					$trailer_settings = $this->get_trailer_settings();
					if ( ! empty( $trailer_settings['enable_trailers'] ) ) {
						$this->is_trailer = true;
					}
				}
			}
		}
	}

	/**
	 * Get the options that can be passed through the WP filter hooks.
	 *
	 * @since  3.5.0
 	 * @return array Player options.
	 */
	public function get_params() {
		$params = array(
			'uid'        => $this->player_uid,
			'post_id'    => $this->post_id,
			'post_type'  => $this->post_type,
			'post_title' => $this->post_title,
			'embed_url'  => $this->embed_url,
			'has_access' => $this->has_access,
			'is_trailer' => $this->is_trailer
		);

		// Output
		return array_merge( $params, $this->args );		
	}	

	/**
	 * Get the videos.
	 *
	 * @since  3.5.0
 	 * @return array $videos Array of videos.
	 */
	public function get_videos() {
		if ( isset( $this->cache['videos'] ) ) {
			return $this->cache['videos'];
		}

		$defaults = array(
			'mp4'         => '',
			'webm'        => '',
			'ogv'         => '',
			'hls'         => '',
			'dash'        => '',
			'youtube'     => '',
			'vimeo'       => '',
			'dailymotion' => '',
			'rumble'      => '',
			'facebook'    => ''
		);

		$videos = shortcode_atts( $defaults, $this->args );

		// Is a video post?
		if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
			if ( ! $this->has_access && $this->is_trailer ) {
				// Trailer mode: override all video sources with the trailer source.
				$trailer_type = get_post_meta( $this->post_id, 'trailer_type', true );
				$trailer_src  = get_post_meta( $this->post_id, 'trailer_src', true );

				if ( ! empty( $trailer_src ) ) {
					if ( 'embedcode' == $trailer_type ) {
						$iframe_src = aiovg_extract_iframe_src( $trailer_src );
						
						if ( $iframe_src ) {
							if ( false !== strpos( $iframe_src, 'youtube.com' ) || false !== strpos( $iframe_src, 'youtu.be' ) ) {
								$iframe_src = add_query_arg( 'enablejsapi', 1, $iframe_src );
							}

							$videos['iframe'] = $iframe_src;
						} else {
							$videos['embedcode'] = $trailer_src;
						}
					} else {
						// URL auto-detect: mirror the run_shortcode_video() detection chain.
						if ( false !== strpos( $trailer_src, 'youtube.com' ) || false !== strpos( $trailer_src, 'youtu.be' ) ) {
							$videos['youtube'] = aiovg_resolve_youtube_url( $trailer_src );
						} elseif ( false !== strpos( $trailer_src, 'vimeo.com' ) ) {
							$videos['vimeo'] = $trailer_src;
						} elseif ( false !== strpos( $trailer_src, 'dailymotion.com' ) ) {
							$videos['dailymotion'] = $trailer_src;
						} elseif ( false !== strpos( $trailer_src, 'rumble.com' ) ) {
							$videos['rumble'] = $trailer_src;
						} elseif ( false !== strpos( $trailer_src, 'facebook.com' ) ) {
							$videos['facebook'] = $trailer_src;
						} else {
							$filetype = wp_check_filetype( $trailer_src );
							if ( 'webm' == $filetype['ext'] ) {
								$videos['webm'] = $trailer_src;
							} elseif ( 'ogv' == $filetype['ext'] ) {
								$videos['ogv'] = $trailer_src;
							} elseif ( 'm3u8' == $filetype['ext'] ) {
								$videos['hls'] = $trailer_src;
							} elseif ( 'mpd' == $filetype['ext'] ) {
								$videos['dash'] = $trailer_src;
							} else {
								$videos['mp4'] = $trailer_src;
							}

							// Bunny Stream trailers are stored under the mp4 key regardless of extension.
							$trailer_video_id = get_post_meta( $this->post_id, 'trailer_bunny_stream_video_id', true );
							if ( ! empty( $trailer_video_id ) ) {
								$videos['mp4'] = $trailer_src;
							}
						}
					}
				}
			} else {
				// Main video source.
				$source_type = get_post_meta( $this->post_id, 'type', true );

				switch ( $source_type ) {
					case 'adaptive':
						$hls = get_post_meta( $this->post_id, 'hls', true );
						if ( ! empty( $hls ) ) {
							$videos['hls'] = $hls;
						}

						$dash = get_post_meta( $this->post_id, 'dash', true );
						if ( ! empty( $dash ) ) {
							$videos['dash'] = $dash;
						}
						break;

					case 'youtube':
					case 'vimeo':
					case 'dailymotion':
					case 'rumble':
					case 'facebook':
						$src = get_post_meta( $this->post_id, $source_type, true );
						if ( ! empty( $src ) ) {
							$videos[ $source_type ] = $src;
						}
						break;

					case 'embedcode':
						$embedcode = get_post_meta( $this->post_id, 'embedcode', true );
						if ( ! empty( $embedcode ) ) {
							$iframe_src = aiovg_extract_iframe_src( $embedcode );
							if ( $iframe_src ) {
								if ( false !== strpos( $iframe_src, 'youtube.com' ) || false !== strpos( $iframe_src, 'youtu.be' ) ) {
									$iframe_src = add_query_arg( 'enablejsapi', 1, $iframe_src );
								}

								$videos['iframe'] = $iframe_src;
							} else {
								$videos['embedcode'] = $embedcode;
							}
						}
						break;

					default:
						$mp4 = get_post_meta( $this->post_id, 'mp4', true );
						if ( ! empty( $mp4 ) ) {
							$videos['mp4'] = $mp4;
						}

						$webm = get_post_meta( $this->post_id, 'webm', true );
						if ( ! empty( $webm ) ) {
							$videos['webm'] = $webm;
						}

						$ogv = get_post_meta( $this->post_id, 'ogv', true );
						if ( ! empty( $ogv ) ) {
							$videos['ogv'] = $ogv;
						}

						$quality_level = get_post_meta( $this->post_id, 'quality_level', true );
						if ( ! empty( $quality_level ) ) {
							$videos['quality_level'] = $quality_level;
						}

						$sources = get_post_meta( $this->post_id, 'sources', true );
						if ( ! empty( $sources ) && is_array( $sources ) ) {
							foreach ( $sources as $index => $source ) {
								$sources[ $index ]['src'] = aiovg_make_url_absolute( $source['src'] );
							}

							$videos['sources'] = $sources;
						}

						break;
				}
			}
		}

		// Convert relative file paths into absolute URLs
		if ( ! empty( $videos['mp4'] ) ) {
			$videos['mp4'] = aiovg_make_url_absolute( $videos['mp4'] );
		}

		if ( ! empty( $videos['webm'] ) ) {
			$videos['webm'] = aiovg_make_url_absolute( $videos['webm'] );
		}

		if ( ! empty( $videos['ogv'] ) ) {
			$videos['ogv'] = aiovg_make_url_absolute( $videos['ogv'] );
		}

		// Set embed URL if available
		if ( isset( $videos['iframe'] ) ) {
			$this->embed_url = $videos['iframe'];
		}

		// Output
		$this->cache['videos'] = $videos;
		return $videos;
	}

	/**
	 * Resolve external provider URLs into iframe embed URLs.
	 *
	 * Shared by VideoJS and Vidstack subclasses. AMP handles its own provider
	 * resolution using AMP-specific elements and must not call this method.
	 *
	 * @since  4.7.6
	 * @param  array $videos Raw videos array from get_videos().
	 * @return array Videos array with the 'iframe' key populated for applicable providers.
	 */
	protected function resolve_provider_iframes( $videos ) {
		$player_settings = $this->get_player_settings();

		// Bunny Stream: force native embed when applicable — covers both main video and trailer.
		if ( ! empty( $videos['mp4'] ) ) {
			$use_native_controls = apply_filters( 'aiovg_use_native_controls', isset( $player_settings['use_native_controls']['bunny_stream'] ), 'bunny_stream' );

			if ( $use_native_controls ) {
				$meta_key = ! empty( $this->is_trailer ) ? 'trailer_bunny_stream_video_id' : 'bunny_stream_video_id';
				$video_id = get_post_meta( $this->post_id, $meta_key, true );

				if ( ! empty( $video_id ) && strpos( $videos['mp4'], '/' . $video_id . '/' ) !== false ) {
					$embed_url = aiovg_get_bunny_stream_embed_url( $videos['mp4'], $video_id );

					if ( ! empty( $embed_url ) ) {
						$videos['iframe'] = $this->filter_bunny_stream_embed_url( $embed_url );
					}
				}
			}
		}

		// YouTube
		if ( ! empty( $videos['youtube'] ) ) {
			$use_native_controls = apply_filters( 'aiovg_use_native_controls', isset( $player_settings['use_native_controls']['youtube'] ), 'youtube' );

			if ( $use_native_controls ) {
				$videos['iframe'] = $this->get_youtube_embed_url( $videos['youtube'] );
			}
		}

		// Vimeo
		if ( ! empty( $videos['vimeo'] ) ) {
			$use_native_controls = apply_filters( 'aiovg_use_native_controls', isset( $player_settings['use_native_controls']['vimeo'] ), 'vimeo' );

			if ( $use_native_controls ) {
				$videos['iframe'] = $this->get_vimeo_embed_url( $videos['vimeo'] );
			}
		}

		// Dailymotion, Rumble, Facebook — always served as iframes by both player engines.
		if ( ! empty( $videos['dailymotion'] ) ) {
			$videos['iframe'] = $this->get_dailymotion_embed_url( $videos['dailymotion'] );
		}

		if ( ! empty( $videos['rumble'] ) ) {
			$videos['iframe'] = $this->get_rumble_embed_url( $videos['rumble'] );
		}

		if ( ! empty( $videos['facebook'] ) ) {
			$videos['iframe'] = $this->get_facebook_embed_url( $videos['facebook'] );
		}

		// Set embed URL if available.
		if ( isset( $videos['iframe'] ) ) {
			$this->embed_url = $videos['iframe'];
		}

		return $videos;
	}

	/**
	 * Get the video tracks.
	 *
	 * @since  3.5.0
 	 * @return array $tracks Array of video tracks.
	 */
	public function get_tracks() {
		if ( isset( $this->cache['tracks'] ) ) {
			return $this->cache['tracks'];
		}

		$tracks = array();

		if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
			$tracks = get_post_meta( $this->post_id, 'track' );
			foreach ( $tracks as $index => $track ) {
				$tracks[ $index ]['src'] = aiovg_make_url_absolute( $track['src'] );
			}
		}

		// Output
		$this->cache['tracks'] = $tracks;
		return $tracks;
	}

	/**
	 * Get the video chapters.
	 *
	 * @since  3.6.0
 	 * @return array $chapters Array of video chapters.
	 */
	public function get_chapters() {
		if ( isset( $this->cache['chapters'] ) ) {
			return $this->cache['chapters'];
		}

		$chapters = array();

		if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
			$post     = get_post( $this->post_id );
			$chapters = aiovg_extract_chapters_from_string( $post->post_content );

			if ( $__chapters = get_post_meta( $this->post_id, 'chapter' ) ) {			
				foreach ( $__chapters as $chapter ) {
					$seconds = aiovg_convert_time_to_seconds( $chapter['time'] );

					$chapters[ $seconds ] = array(
						'time'  => $seconds,
						'label' => sanitize_text_field( $chapter['label'] )				
					);
				}
			}
		}

		if ( ! empty( $chapters ) ) {
			$chapters = array_values( $chapters );
		}

		// Output
		$this->cache['chapters'] = $chapters;
		return $chapters;
	}

	/**
	 * Get the poster image.
	 *
	 * @since  3.5.0
 	 * @return array $poster Poster image URL.
	 */
	public function get_poster() {
		if ( isset( $this->cache['poster'] ) ) {
			return $this->cache['poster'];
		}

		$poster = '';

		if ( ! empty( $this->args['poster'] ) ) {
			$poster = aiovg_make_url_absolute( $this->args['poster'] );
		} else {
			// Is a video post?
			if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
				$image_data = aiovg_get_image( $this->post_id, 'large' );

				if ( ! empty( $image_data['src'] ) ) {
					$poster = aiovg_make_url_absolute( $image_data['src'] );
				}
			}
		}

		if ( empty( $poster ) ) {
			$videos = $this->get_videos();

			// YouTube
			if ( ! empty( $videos['youtube'] ) ) {
				$poster = aiovg_get_youtube_image_url( $videos['youtube'] );
			}

			// Vimeo
			if ( ! empty( $videos['vimeo'] ) ) {
				$poster = aiovg_get_vimeo_image_url( $videos['vimeo'] );
			}

			// Dailymotion
			if ( ! empty( $videos['dailymotion'] ) ) {
				$poster = aiovg_get_dailymotion_image_url( $videos['dailymotion'] );
			}

			// Rumble
			if ( ! empty( $videos['rumble'] ) ) {
				$poster = aiovg_get_rumble_image_url( $videos['rumble'] );
			}
		}

		// Output
		$this->cache['poster'] = $poster;
		return $poster;
	}	
	
	/**
	 * Get the player settings.
	 *
	 * @since  3.5.0
 	 * @return array $settings Player settings.
	 */
	public function get_player_settings() {
		if ( isset( $this->cache['player_settings'] ) ) {
			return $this->cache['player_settings'];
		}

		$player_settings  = aiovg_get_option( 'aiovg_player_settings' );
		$general_settings = aiovg_get_option( 'aiovg_general_settings' );			

		$defaults = array(
			'theme'               => ( isset( $player_settings['theme'] ) && 'custom' == $player_settings['theme'] ) ? 'custom' : 'default',
			'width' 			  => $player_settings['width'],
			'ratio' 			  => $player_settings['ratio'],
			'preload'             => $player_settings['preload'],
			'playsinline'         => isset( $player_settings['playsinline'] ) ? $player_settings['playsinline'] : 0,
			'autoplay'            => $player_settings['autoplay'],
			'loop'                => $player_settings['loop'],
			'muted'               => $player_settings['muted'],
			'controls'            => $player_settings['controls'],
			'playpause'           => isset( $player_settings['controls']['playpause'] ),
			'current'             => isset( $player_settings['controls']['current'] ),
			'progress'            => isset( $player_settings['controls']['progress'] ),
			'duration'            => isset( $player_settings['controls']['duration'] ),
			'tracks'              => isset( $player_settings['controls']['tracks'] ),
			'chapters'            => isset( $player_settings['controls']['chapters'] ),
			'speed'               => isset( $player_settings['controls']['speed'] ),
			'quality'             => isset( $player_settings['controls']['quality'] ),			
			'volume'              => isset( $player_settings['controls']['volume'] ),
			'pip'                 => isset( $player_settings['controls']['pip'] ),
			'fullscreen'          => isset( $player_settings['controls']['fullscreen'] ),
			'share'			      => isset( $player_settings['controls']['share'] ),
			'embed'			      => isset( $player_settings['controls']['embed'] ),
			'download'			  => isset( $player_settings['controls']['download'] ),
			'use_native_controls' => $player_settings['use_native_controls'],
			'hide_youtube_logo'   => isset( $player_settings['hide_youtube_logo'] ) ? $player_settings['hide_youtube_logo'] : 0,
			'cc_load_policy'      => $player_settings['cc_load_policy'],
			'hotkeys'             => isset( $player_settings['hotkeys'] ) ? $player_settings['hotkeys'] : 0,
			'lazyloading'         => isset( $general_settings['lazyloading'] ) ? $general_settings['lazyloading'] : 0,
			'statistics'          => isset( $general_settings['statistics'] ) ? $general_settings['statistics'] : 1
		);

		$settings = shortcode_atts( $defaults, $this->args );

		if ( empty( $settings['ratio'] ) ) {
			$settings['ratio'] = 56.25;
		}

		// Output
		$this->cache['player_settings'] = $settings;
		return $settings;
	}

	/**
	 * Get the privacy settings.
	 *
	 * @since  3.5.0
 	 * @return array $settings Privacy settings.
	 */
	public function get_privacy_settings() {
		if ( isset( $this->cache['privacy_settings'] ) ) {
			return $this->cache['privacy_settings'];
		}

		if ( isset( $_COOKIE['aiovg_gdpr_consent'] ) || isset( $_COOKIE['aiovg_email_captured'] ) ) {
			$settings = array( 
				'show_consent' => false 
			);
		} else {
			$privacy_settings = aiovg_get_option( 'aiovg_privacy_settings' );			

			$settings = shortcode_atts( $privacy_settings, $this->args );

			if ( $settings['show_consent'] ) {
				$settings['consent_message'] = apply_filters( 'aiovg_translate_strings', $settings['consent_message'], 'consent_message' );
				$settings['consent_button_label'] = apply_filters( 'aiovg_translate_strings', $settings['consent_button_label'], 'consent_button_label' );
			}

			if ( empty( $settings['consent_message'] ) || empty( $settings['consent_button_label'] ) ) {
				$settings['show_consent'] = false;
			}
		}
 
		// Output
		$this->cache['privacy_settings'] = $settings;
		return $settings;
	}

	/**
	 * Get the email capture settings, merged with per-video and shortcode overrides.
	 *
	 * @since  4.7.6
 	 * @return array $settings Email capture settings.
	 */
	public function get_email_capture_settings() {
		if ( isset( $this->cache['email_capture_settings'] ) ) {
			return $this->cache['email_capture_settings'];
		}

		$email_capture_settings = aiovg_get_option( 'aiovg_email_capture_settings' );
		$settings = array( 'enable_email_capture' => false );

		if ( ! empty( $email_capture_settings['enable_email_capture'] ) && ! is_user_logged_in() && ! isset( $_COOKIE['aiovg_email_captured'] ) ) {
			$show_email_capture = 1;

			if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
				$email_capture = get_post_meta( $this->post_id, 'email_capture', true );

				if ( '' !== $email_capture ) {
					$show_email_capture = (int) $email_capture;
				}
			}

			if ( isset( $this->args['email_capture'] ) ) {
				$show_email_capture = (int) $this->args['email_capture'];
			}

			if ( $show_email_capture === -1 ) { // Global
				$show_email_capture = 1;
			}

			if ( ! empty( $show_email_capture ) ) {
				$email_capture_settings = shortcode_atts( $email_capture_settings, $this->args );

				$pre_roll     = ! empty( $email_capture_settings['pre_roll'] ) ? 1 : 0;
				$mid_roll     = ! empty( $email_capture_settings['mid_roll'] ) ? (int) $email_capture_settings['mid_roll'] : 0;
				$post_roll    = ! empty( $email_capture_settings['post_roll'] ) ? 1 : 0;
				$header_text  = ! empty( $email_capture_settings['header_text'] ) ? $email_capture_settings['header_text'] : __( 'Enter your email to continue watching', 'all-in-one-video-gallery' );
				$footer_text  = ! empty( $email_capture_settings['footer_text'] ) ? $email_capture_settings['footer_text'] : '';
				$button_label = ! empty( $email_capture_settings['button_label'] ) ? $email_capture_settings['button_label'] : __( 'Submit', 'all-in-one-video-gallery' );
				$skip_text    = ! empty( $email_capture_settings['skip_text'] ) ? $email_capture_settings['skip_text'] : __( 'Skip', 'all-in-one-video-gallery' );

				if ( ! empty( $pre_roll ) || ! empty( $mid_roll ) || ! empty( $post_roll ) ) {
					$settings = array(
						'enable_email_capture' => true,
						'pre_roll'             => $pre_roll,
						'mid_roll'             => $mid_roll,
						'post_roll'            => $post_roll,
						'collect_name'         => ! empty( $email_capture_settings['collect_name'] ) ? 1 : 0,
						'collect_phone'        => ! empty( $email_capture_settings['collect_phone'] ) ? 1 : 0,
						'allow_skip'           => ! empty( $email_capture_settings['allow_skip'] ) ? 1 : 0,
						'header_text'          => apply_filters( 'aiovg_translate_strings', $header_text, 'header_text' ),
						'footer_text'          => apply_filters( 'aiovg_translate_strings', $footer_text, 'footer_text' ),
						'button_label'         => apply_filters( 'aiovg_translate_strings', $button_label, 'button_label' ),
						'skip_text'            => apply_filters( 'aiovg_translate_strings', $skip_text, 'skip_text' )
					);
				}
			}
		}

		// Output
		$this->cache['email_capture_settings'] = $settings;
		return $settings;
	}

	/**
	 * Get the logo settings.
	 *
	 * @since  3.5.0
 	 * @return array $settings Logo settings.
	 */
	public function get_logo_settings() {
		if ( isset( $this->cache['logo_settings'] ) ) {
			return $this->cache['logo_settings'];
		}

		$brand_settings = aiovg_get_option( 'aiovg_brand_settings' );			

		$settings = shortcode_atts( $brand_settings, $this->args );

		if ( ! empty( $settings['logo_image'] ) ) {
			$settings['logo_image'] = aiovg_make_url_absolute( $settings['logo_image'] );
		} else {
			$settings['show_logo'] = false;
		}

		if ( ! empty( $settings['copyright_text'] ) ) {
			$settings['copyright_text'] = apply_filters( 'aiovg_translate_strings', $settings['copyright_text'], 'copyright_text' );
		}
 
		// Output
		$this->cache['logo_settings'] = $settings;
		return $settings;
	}

	/**
	 * Get the trailer settings.
	 *
	 * @since  4.7.6
 	 * @return array $settings Trailer settings.
	 */
	public function get_trailer_settings() {
		if ( isset( $this->cache['trailer_settings'] ) ) {
			return $this->cache['trailer_settings'];
		}

		$trailer_settings = aiovg_get_option( 'aiovg_trailer_settings' );
		$settings = array( 'enable_trailers' => false );

		if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
			if ( ! empty( $trailer_settings['enable_trailers'] ) ) {
				$trailer_src = get_post_meta( $this->post_id, 'trailer_src', true );
				
				if ( ! empty( $trailer_src ) ) {
					$player_overlay_link = isset( $trailer_settings['player_overlay_link'] ) ? $trailer_settings['player_overlay_link'] : '';
					$player_overlay_link = apply_filters( 'aiovg_player_overlay_link', $player_overlay_link, $this->post_id );

					$player_overlay_text = isset( $trailer_settings['player_overlay_text'] ) ? $trailer_settings['player_overlay_text'] : '';
					if ( empty( $player_overlay_text ) ) {
						$player_overlay_text = ! empty( $player_overlay_link ) ? __( 'Watch Full Video', 'all-in-one-video-gallery' ) : __( 'Trailer', 'all-in-one-video-gallery' );
					}
					$player_overlay_text = apply_filters( 'aiovg_player_overlay_text', $player_overlay_text, $this->post_id );

					$settings = array(
						'enable_trailers' => true,
						'label'           => $player_overlay_text,
						'url'             => $player_overlay_link
					);
				}
			}
		}

		// Output
		$this->cache['trailer_settings'] = $settings;
		return $settings;
	}

	/**
	 * Get the share buttons.
	 *
	 * @since  3.5.0
 	 * @return array Share buttons.
	 */
	public function get_share_buttons() {
		if ( isset( $this->cache['share_buttons'] ) ) {
			return $this->cache['share_buttons'];
		}

		$socialshare_settings = aiovg_get_option( 'aiovg_socialshare_settings' );

		$share_url = get_permalink( $this->post_id );
		$share_url_encoded = rawurlencode( $share_url );

		$share_title = rawurlencode( $this->post_title );
	
		$share_image = $this->get_poster();
	
		$share_buttons = array();
			
		if ( isset( $socialshare_settings['services']['facebook'] ) ) {
			$share_buttons[] = array(
				'service' => 'facebook',		
				'url'     => "https://www.facebook.com/sharer/sharer.php?u={$share_url_encoded}",
				'icon'    => 'aiovg-icon-facebook',
				'text'    => __( 'Facebook', 'all-in-one-video-gallery' )				
			);
		}
	
		if ( isset( $socialshare_settings['services']['twitter'] ) ) {
			$share_buttons[] = array(
				'service' => 'twitter',			
				'url'     => "https://twitter.com/intent/tweet?text={$share_title}&amp;url={$share_url_encoded}",
				'icon'    => 'aiovg-icon-twitter',
				'text'    => __( 'Twitter', 'all-in-one-video-gallery' )
			);
		}		
	
		if ( isset( $socialshare_settings['services']['linkedin'] ) ) {
			$share_buttons[] = array(
				'service' => 'linkedin',			
				'url'     => "https://www.linkedin.com/shareArticle?url={$share_url_encoded}&amp;title={$share_title}",
				'icon'    => 'aiovg-icon-linkedin',
				'text'    => __( 'Linkedin', 'all-in-one-video-gallery' )
			);
		}
	
		if ( isset( $socialshare_settings['services']['pinterest'] ) ) {
			$pinterest_url = "https://pinterest.com/pin/create/button/?url={$share_url_encoded}&amp;description={$share_title}";
	
			if ( ! empty( $share_image ) ) {
				$pinterest_url .= "&amp;media={$share_image}";
			}
	
			$share_buttons[] = array(
				'service' => 'pinterest',			
				'url'     => $pinterest_url,
				'icon'    => 'aiovg-icon-pinterest',
				'text'    => __( 'Pinterest', 'all-in-one-video-gallery' )
			);
		}
	
		if ( isset( $socialshare_settings['services']['tumblr'] ) ) {
			$tumblr_url = "https://www.tumblr.com/share/link?url={$share_url_encoded}&amp;name={$share_title}";
	
			$share_description = aiovg_get_excerpt( $this->post_id, 160, '', false ); 
			if ( ! empty( $share_description ) ) {
				$share_description = rawurlencode( $share_description );
	
				$tumblr_url .= "&amp;description={$share_description}";
			}
	
			$share_buttons[] = array(	
				'service' => 'tumblr',		
				'url'     => $tumblr_url,
				'icon'    => 'aiovg-icon-tumblr',
				'text'    => __( 'Tumblr', 'all-in-one-video-gallery' )
			);
		}
	
		if ( isset( $socialshare_settings['services']['whatsapp'] ) ) {
			if ( wp_is_mobile() ) {
				$whatsapp_url = "whatsapp://send?text={$share_title} {$share_url_encoded}";
			} else {
				$whatsapp_url = "https://api.whatsapp.com/send?text={$share_title}%20{$share_url_encoded}";
			}
	
			$share_buttons[] = array(
				'service' => 'whatsapp',				
				'url'     => $whatsapp_url,
				'icon'    => 'aiovg-icon-whatsapp',
				'text'    => __( 'WhatsApp', 'all-in-one-video-gallery' )
			);
		}

		$share_buttons = apply_filters( 'aiovg_player_socialshare_buttons', $share_buttons );
	
		// Output
		$this->cache['share_buttons'] = $share_buttons;
		return $share_buttons;
	}

	/**
	 * Get the video embedcode.
	 *
	 * @since  3.5.0
 	 * @return string $embedcode Video embedcode.
	 */
	public function get_embedcode() {
		if ( isset( $this->cache['embedcode'] ) ) {
			return $this->cache['embedcode'];
		}

		$player_settings = $this->get_player_settings();

		$embedcode = sprintf(
			'<div style="position:relative;padding-bottom:%s%%;height:0;overflow:hidden;"><iframe src="%s" title="%s" width="100%%" height="100%%" style="position:absolute;width:100%%;height:100%%;top:0px;left:0px;overflow:hidden;" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>',
			(float) $player_settings['ratio'],
			esc_url( aiovg_get_player_page_url( $this->post_id, $this->args ) ),
			esc_attr( $this->post_title )
		);

		// Output
		$this->cache['embedcode'] = $embedcode;
		return $embedcode;
	}

	/**
	 * Get the video file download URL.
	 *
	 * @since  3.5.0
 	 * @return string Video file download URL.
	 */
	public function get_download_url() {	
		if ( isset( $this->cache['download_url'] ) ) {
			return $this->cache['download_url'];
		}

		$videos = $this->get_videos();
		$download_url = '';

		if ( ! empty( $videos['mp4'] ) ) {
			$can_download = 1;

			// Is a video post?
			if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
				if ( metadata_exists( 'post', $this->post_id, 'download' ) ) {
					$can_download = (int) get_post_meta( $this->post_id, 'download', true );
				}

				if ( $can_download ) {
					$download_url =  home_url( '?vdl=' . $this->post_id );
				}
			}

			if ( empty( $download_url ) && $can_download ) {
				$download_url = home_url( '?vdl=' . aiovg_get_temporary_file_download_id( $videos['mp4'] ) );
			}
		}

		// Output
		$this->cache['download_url'] = $download_url;
		return $download_url;
	}			

	/**
	 * Get the HTML output for the restricted access message.
	 *
	 * @since  3.9.6
 	 * @return string $html The HTML message for restricted access.
	 */
	public function get_player_restricted_message() {
		$restrictions_settings = aiovg_get_option( 'aiovg_restrictions_settings' );
		$player_settings = $this->get_player_settings();

		$restricted_message = $restrictions_settings['restricted_message'];
		if ( empty( $restricted_message ) ) {
			$restricted_message = __( 'Sorry, but you do not have permission to view this video.', 'all-in-one-video-gallery' );
		}

		$poster = $this->get_poster();

		// Enqueue dependencies
		wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-player' );

		// HTML output
		$html = sprintf(
			'<div class="aiovg-player-container" style="max-width: %s;">',
			( ! empty( $player_settings['width'] ) ? (int) $player_settings['width'] . 'px' : '100%' )
		);

		$html .= sprintf(
			'<div class="aiovg-player" style="padding-bottom: %s%%;">',
			(float) $player_settings['ratio']
		);

		$wrapper_style = ! empty( $poster ) ? sprintf( ' style="background-image: url(\'%s\')"', esc_url( $poster ) ) : '';

		$html .= sprintf(
			'<div class="aiovg-restrictions-wrapper"%s><div class="aiovg-restrictions-overlay"><div class="aiovg-restrictions-message">%s</div></div></div>',
			$wrapper_style,
			wp_kses_post( trim( $restricted_message ) )
		);

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
	
	/**
	 * Get the raw player embedcode.
	 *
	 * @since  3.5.0
 	 * @return string $html Player HTML.
	 */
	public function get_player_raw_embed() {
		$player_settings = $this->get_player_settings();

		$videos = $this->get_videos();

		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-embed' );

		$html = sprintf(
			'<div class="aiovg-player-raw" data-post_id="%d" data-statistics="%d">%s</div>',
			(int) $this->post_id,
			( ! empty( $player_settings['statistics'] ) && ! $this->is_trailer ) ? 1 : 0,
			do_shortcode( $videos['embedcode'] )		
		);

		return $html;
	}

	/**
	 * Get the web component based lite player embed code.
	 * 
	 * @since  3.5.0
 	 * @return string $html Player HTML.
	 */
	public function get_player_lite_embed() {
		$player_settings        = $this->get_player_settings();
		$privacy_settings       = $this->get_privacy_settings();
		$email_capture_settings = $this->get_email_capture_settings();

		$videos = $this->get_videos();
		$poster = $this->get_poster();				

		// Enqueue dependencies
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-embed' );
		
		// Vars
		$provider = 'embed';
		if ( ! empty( $videos['youtube'] ) || false !== strpos( $videos['iframe'], 'youtube.com' ) || false !== strpos( $videos['iframe'], 'youtu.be' ) ) {					
			$provider = 'youtube';
		} elseif ( ! empty( $videos['vimeo'] ) || false !== strpos( $videos['iframe'], 'vimeo.com' ) ) {
			$provider = 'vimeo';
		} elseif ( ! empty( $videos['dailymotion'] ) ) {
			$provider = 'dailymotion';
		}

		$attributes = array(
			'class'     => 'aiovg-player-element',
			'title'     => esc_attr( $this->post_title ),
			'src'       => esc_url( $videos['iframe'] ),
			'poster'    => esc_url( $poster ),
			'ratio'     => (float) $player_settings['ratio'],
			'post_id'   => (int) $this->post_id,
			'post_type' => esc_attr( $this->post_type )
		);
		
		if ( ! empty( $player_settings['lazyloading'] ) ) {
			$attributes['lazyloading'] = '';
		}

		if ( ! empty( $player_settings['statistics'] ) && ! $this->is_trailer ) {
			$attributes['statistics'] = '';
		}

		if ( ! empty( $privacy_settings['show_consent'] ) ) {
			$attributes['cookieconsent'] = '';
		}

		if ( ! empty( $email_capture_settings['pre_roll'] ) ) { // Email Capture
			$attributes['email-capture'] = '';

			// Suppress GDPR when email capture is active
			unset( $attributes['cookieconsent'] );
		}

		// Player
		$html = sprintf(
			'<div class="aiovg-player-container" style="max-width: %s;">',
			( ! empty( $player_settings['width'] ) ? (int) $player_settings['width'] . 'px' : '100%' )
		);

		$html .= sprintf(
			'<aiovg-%s %s>',
			$provider,
			aiovg_combine_video_attributes( $attributes )
		);

		if ( isset( $attributes['cookieconsent'] ) ) { // Cookie consent
			$html .= sprintf( '<div slot="cookieconsent-message">%s</div>', wp_kses_post( trim( $privacy_settings['consent_message'] ) ) );
			$html .= sprintf( '<span slot="cookieconsent-button-label">%s</span>', esc_html( $privacy_settings['consent_button_label'] ) );
		}

		if ( isset( $attributes['email-capture'] ) ) { // Email Capture
			$html .= '<template class="aiovg-player-template">';
			$html .= '<div class="email-capture">';
			$html .= '<div class="email-capture-block">';	
					
			$html .= sprintf( '<div class="email-capture-header">%s</div>', esc_html( $email_capture_settings['header_text'] ) );
			
			$html .= '<div class="email-capture-fields">';

			if ( ! empty( $email_capture_settings['collect_name'] ) ) {
				$html .= sprintf( '<input type="text" class="email-capture-name" name="name" placeholder="%s" autocomplete="name" />', esc_attr__( 'Your name', 'all-in-one-video-gallery' ) );
			}

			$html .= sprintf( '<input type="email" class="email-capture-email" name="email" placeholder="%s" required autocomplete="email" aria-describedby="email-capture-error" />', esc_attr__( 'Your email address *', 'all-in-one-video-gallery' ) );
			

			if ( ! empty( $email_capture_settings['collect_phone'] ) ) {
				$html .= sprintf( '<input type="tel" class="email-capture-phone" name="phone" placeholder="%s" autocomplete="tel" />', esc_attr__( 'Your phone number', 'all-in-one-video-gallery' ) );
			}

			$html .= '</div>'; // .email-capture-fields			

			if ( ! empty( $email_capture_settings['footer_text'] ) ) {
				$html .= sprintf( '<div class="email-capture-footer">%s</div>', wp_kses_post( $email_capture_settings['footer_text'] ) );
			}

			$html .= sprintf( 
				'<div id="email-capture-error" class="email-capture-error" role="alert" aria-live="assertive" tabindex="-1" data-invalid_email="%s" data-error_generic="%s"></div>', 
				esc_attr__( 'Please enter a valid email address.', 'all-in-one-video-gallery' ), 
				esc_attr__( 'Something went wrong. Please try again.', 'all-in-one-video-gallery' ) 
			);

			$html .= '<div class="email-capture-actions">';			
			$html .= sprintf( '<button type="button" class="email-capture-submit">%s</button>', esc_html( $email_capture_settings['button_label'] ) );

			if ( ! empty( $email_capture_settings['allow_skip'] ) ) {
				$html .= sprintf( '<a class="email-capture-skip" role="button" tabindex="0">%s</a>', esc_html( $email_capture_settings['skip_text'] ) );
			}

			$html .= '</div>'; // .email-capture-actions
			$html .= '</div>'; // .email-capture-block
			$html .= '</div>'; // .email-capture
			$html .= '</template>';
		}

		$html .= sprintf( '</aiovg-%s>', $provider );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get the cookie consent (GDPR) HTML block.
	 *
	 * @since  4.7.6
	 * @return string Cookie consent HTML.
	 */
	protected function get_cookie_consent_html() {
		$privacy_settings = $this->get_privacy_settings();
		$poster = $this->get_poster();	

		return sprintf(
			'<div class="aiovg-privacy-wrapper" data-poster="%s"><div class="aiovg-privacy-overlay"><div class="aiovg-privacy-consent-block"><div class="aiovg-privacy-consent-message">%s</div><div><button type="button" class="aiovg-privacy-consent-button">%s</button></div></div></div></div>',
			esc_url( $poster ),
			wp_kses_post( trim( $privacy_settings['consent_message'] ) ),
			esc_html( $privacy_settings['consent_button_label'] )
		);
	}

	/**
	 * Get the email capture form HTML block.
	 *
	 * @since  4.7.6
	 * @param  string $video_url Optional video URL for shortcode-based players.
	 * @return string            Email capture HTML.
	 */
	protected function get_email_capture_html( $video_url = '' ) {
		$email_capture_settings = $this->get_email_capture_settings();

		$poster = $this->get_poster();

		$html = sprintf(
			'<div class="aiovg-email-capture" data-poster="%s" data-pre_roll="%d" data-mid_roll="%d" data-post_roll="%d" data-video_url="%s" style="display: none;">',
			esc_url( $poster ),
			(int) $email_capture_settings['pre_roll'],
			(int) $email_capture_settings['mid_roll'],
			(int) $email_capture_settings['post_roll'],
			esc_url( $video_url )
		);

		$html .= '<div class="aiovg-email-capture-overlay">';
		$html .= '<div class="aiovg-email-capture-block">';
		$html .= sprintf( '<div class="aiovg-email-capture-header">%s</div>', esc_html( $email_capture_settings['header_text'] ) );
		$html .= '<div class="aiovg-email-capture-fields">';

		if ( ! empty( $email_capture_settings['collect_name'] ) ) {
			$html .= sprintf( '<input type="text" class="aiovg-email-capture-name" name="name" placeholder="%s" autocomplete="name" />', esc_attr__( 'Your name', 'all-in-one-video-gallery' ) );
		}

		$html .= sprintf(
			'<input type="email" class="aiovg-email-capture-email" name="email" placeholder="%s" required autocomplete="email" aria-describedby="aiovg-email-capture-error-%d" />',
			esc_attr__( 'Your email address *', 'all-in-one-video-gallery' ),
			(int) $this->player_uid
		);

		if ( ! empty( $email_capture_settings['collect_phone'] ) ) {
			$html .= sprintf( '<input type="tel" class="aiovg-email-capture-phone" name="phone" placeholder="%s" autocomplete="tel" />', esc_attr__( 'Your phone number', 'all-in-one-video-gallery' ) );
		}

		$html .= '</div>'; // .aiovg-email-capture-fields

		if ( ! empty( $email_capture_settings['footer_text'] ) ) {
			$html .= sprintf( '<div class="aiovg-email-capture-footer">%s</div>', wp_kses_post( $email_capture_settings['footer_text'] ) );
		}

		$html .= sprintf(
			'<div id="aiovg-email-capture-error-%d" class="aiovg-email-capture-error" role="alert" aria-live="assertive" tabindex="-1" data-invalid_email="%s" data-error_generic="%s"></div>',
			(int) $this->player_uid,
			esc_attr__( 'Please enter a valid email address.', 'all-in-one-video-gallery' ),
			esc_attr__( 'Something went wrong. Please try again.', 'all-in-one-video-gallery' )
		);

		$html .= '<div class="aiovg-email-capture-actions">';
		$html .= sprintf( '<button type="button" class="aiovg-email-capture-submit">%s</button>', esc_html( $email_capture_settings['button_label'] ) );

		if ( ! empty( $email_capture_settings['allow_skip'] ) ) {
			$html .= sprintf( '<a class="aiovg-email-capture-skip" role="button" tabindex="0">%s</a>', esc_html( $email_capture_settings['skip_text'] ) );
		}

		$html .= '</div>'; // .aiovg-email-capture-actions
		$html .= '</div>'; // .aiovg-email-capture-block
		$html .= '</div>'; // .aiovg-email-capture-overlay
		$html .= '</div>'; // .aiovg-email-capture

		return $html;
	}

	/**
	 * Get the YouTube embed URL.
	 *
	 * @since  3.5.0
	 * @param  string $url YouTube video URL.
 	 * @return string $url Embed URL.
	 */
	public function get_youtube_embed_url( $url ) {
		$player_settings = $this->get_player_settings();

		parse_str( $url, $queries );

		$url = 'https://www.youtube.com/embed/' . aiovg_get_youtube_id_from_url( $url ) . '?iv_load_policy=3&modestbranding=1&rel=0&showinfo=0&enablejsapi=1';									
		
		if ( isset( $queries['start'] ) ) {
			$url = add_query_arg( 'start', (int) $queries['start'], $url );
		}

		if ( isset( $queries['t'] ) ) {
			$url = add_query_arg( 'start', (int) $queries['t'], $url );
		}

		if ( isset( $queries['end'] ) ) {
			$url = add_query_arg( 'end', (int) $queries['end'], $url );
		}

		if ( ! empty( $player_settings['autoplay'] ) ) {
			$url = add_query_arg( 'autoplay', 1, $url );
		}

		$url = add_query_arg( 'cc_load_policy', (int) $player_settings['cc_load_policy'], $url );

		if ( empty( $player_settings['controls'] ) ) {
			$url = add_query_arg( 'controls', 0, $url );
		}		
		
		if ( empty( $player_settings['fullscreen'] ) ) {
			$url = add_query_arg( 'fs', 0, $url );
		}

		if ( ! empty( $player_settings['loop'] ) ) {
			$url = add_query_arg( 'loop', 1, $url );
		}

		if ( ! empty( $player_settings['muted'] ) ) {
			$url = add_query_arg( 'mute', 1, $url );
		}

		$url = add_query_arg( 'playsinline', (int) $player_settings['playsinline'], $url );				

		$url = apply_filters( 'aiovg_youtube_embed_url', $url, $this->post_id );
		return $url;
	}

	/**
	 * Get the Vimeo embed URL.
	 *
	 * @since  3.5.0
	 * @param  string $url Vimeo video URL.
 	 * @return string $url Embed URL.
	 */
	public function get_vimeo_embed_url( $url ) {
		$player_settings = $this->get_player_settings();

		$oembed = aiovg_get_vimeo_oembed_data( $url );

		$url = 'https://player.vimeo.com/video/' . $oembed['video_id'] . '?byline=0&portrait=0&title=0&vimeo_logo=0';

		if ( ! empty( $oembed['html'] ) ) {
			if ( $iframe_src = aiovg_extract_iframe_src( $oembed['html'] ) ) {
				$parsed_url = parse_url( $iframe_src, PHP_URL_QUERY );
				parse_str( $parsed_url, $queries );

				if ( isset( $queries['app_id'] ) ) {
					$url = add_query_arg( 'app_id', $queries['app_id'], $url );
				}

				if ( isset( $queries['h'] ) ) {
					$url = add_query_arg( 'h', $queries['h'], $url );
				}				
			}
		}

		if ( ! empty( $player_settings['autoplay'] ) ) {
			$url = add_query_arg( 'autoplay', 1, $url );
		}

		if ( ! empty( $player_settings['loop'] ) ) {
			$url = add_query_arg( 'loop', 1, $url );
		}

		if ( ! empty( $player_settings['muted'] ) ) {
			$url = add_query_arg( 'muted', 1, $url );
		}

		$url = add_query_arg( 'playsinline', (int) $player_settings['playsinline'], $url );

		if ( ! empty( $player_settings['tracks'] ) || ! empty( $player_settings['cc_load_policy'] ) ) {
			$url = add_query_arg( 'texttrack', 'en-x-autogen', $url );
		}

		$url = apply_filters( 'aiovg_vimeo_embed_url', $url, $this->post_id );
		return $url;
	}

	/**
	 * Get the Dailymotion embed URL.
	 *
	 * @since  3.5.0
	 * @param  string $url Dailymotion video URL.
 	 * @return string $url Embed URL.
	 */
	public function get_dailymotion_embed_url( $url ) {
		$player_settings = $this->get_player_settings();

		$url = 'https://www.dailymotion.com/embed/video/' . aiovg_get_dailymotion_id_from_url( $url ) . '?queue-autoplay-next=0&queue-enable=0&sharing-enable=0&ui-logo=0&ui-start-screen-info=0';

		if ( ! empty( $player_settings['autoplay'] ) ) {
			$url = add_query_arg( 'autoplay', 1, $url );
		}

		if ( ! empty( $player_settings['loop'] ) ) {
			$url = add_query_arg( 'loop', 1, $url );
		}

		if ( ! empty( $player_settings['muted'] ) ) {
			$url = add_query_arg( 'mute', 1, $url );
		}

		$url = apply_filters( 'aiovg_dailymotion_embed_url', $url, $this->post_id );
		return $url;
	}

	/**
	 * Get the Rumble embed URL.
	 *
	 * @since  3.5.0
	 * @param  string $url Rumble video URL.
 	 * @return string $url Embed URL.
	 */
	public function get_rumble_embed_url( $url ) {
		$player_settings = $this->get_player_settings();

		$oembed = aiovg_get_rumble_oembed_data( $url );

		if ( ! empty( $oembed['html'] ) ) {
			if ( $iframe_src = aiovg_extract_iframe_src( $oembed['html'] ) ) {
				$url = add_query_arg( 'rel', 0, $iframe_src );	

				if ( ! empty( $player_settings['autoplay'] ) ) {
					$url = add_query_arg( 'autoplay', 2, $url );
				}
			}
		}

		$url = apply_filters( 'aiovg_rumble_embed_url', $url, $this->post_id );
		return $url;
	}

	/**
	 * Get the Facebook embed URL.
	 *
	 * @since  3.5.0
	 * @param  string $url Facebook video URL.
 	 * @return string $url Embed URL.
	 */
	public function get_facebook_embed_url( $url ) {
		$player_settings = $this->get_player_settings();

		$url = 'https://www.facebook.com/plugins/video.php?href=' . urlencode( $url ) . '&width=560&height=315&show_text=false&appId';

		if ( ! empty( $player_settings['autoplay'] ) ) {
			$url = add_query_arg( 'autoplay', 1, $url );
		}

		if ( ! empty( $player_settings['loop'] ) ) {
			$url = add_query_arg( 'loop', 1, $url );
		}

		if ( ! empty( $player_settings['muted'] ) ) {
			$url = add_query_arg( 'muted', 1, $url );
		}

		$url = apply_filters( 'aiovg_facebook_embed_url', $url, $this->post_id );
		return $url;
	}

	/**
	 * Filters the Bunny Stream embed URL with custom player parameters.
	 *
	 * @since  4.2.0
	 * @param  string $url Bunny Stream video URL.
 	 * @return string $url Embed URL.
	 */
	public function filter_bunny_stream_embed_url( $url ) {
		$player_settings = $this->get_player_settings();

		$autoplay = ! empty( $player_settings['autoplay'] ) ? 'true' : 'false';
        $url = add_query_arg( 'autoplay', $autoplay, $url );

		$preload = ( 'none' == $player_settings['preload'] ) ? 'false' : 'true';
		$url = add_query_arg( 'preload', $preload, $url );

        $muted = ! empty( $player_settings['muted'] ) ? 'true' : 'false';
        $url = add_query_arg( 'muted', $muted, $url );

        $loop = ! empty( $player_settings['loop'] ) ? 'true' : 'false';
        $url = add_query_arg( 'loop', $loop, $url );

        $playsinline = ! empty( $player_settings['playsinline'] ) ? 'true' : 'false';
        $url = add_query_arg( 'playsinline', $playsinline, $url );

        $speed = ! empty( $player_settings['speed'] ) ? 'true' : 'false';
        $url = add_query_arg( 'showSpeed', $speed, $url );

		return $url;
	}
		
}
