<?php

/**
 * VideoJS Player.
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
 * AIOVG_Player_VideoJS class.
 *
 * @since 3.5.0
 */
class AIOVG_Player_VideoJS extends AIOVG_Player_Base {

	/**
	 * Array of videos.
	 *
	 * @since  3.5.0
	 * @access private
	 * @var    array	 
	 */
	private $videos = array();

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
 	 * @return string Player HTML.
	 */
	public function get_player() {
		// Check if the current user has access to this video
		if ( $this->post_id > 0 && 'aiovg_videos' == $this->post_type ) {
			if ( ! aiovg_current_user_has_video_access( $this->post_id ) ) {
				return $this->get_player_restricted_message();
			}
		}

		$videos = $this->get_videos();

		// Raw embedcode that contains script tags
		if ( isset( $videos['embedcode'] ) ) {
			return $this->get_player_raw_embed();
		}

		// Iframe embedcode
		if ( isset( $videos['iframe'] ) ) {
			return $this->get_player_lite_embed();
		}

		// Videojs player
		return $this->get_player_videojs();
	}

	/**
	 * Get the videojs player HTML.
	 *
	 * @since  3.5.0
	 * @access private
 	 * @return string  Player HTML.
	 */
	public function get_player_videojs() {
		$player_settings = $this->get_player_settings();
		$privacy_settings = $this->get_privacy_settings();
		$logo_settings = $this->get_logo_settings();

		$videos = $this->get_videos();
		$poster = $this->get_poster();		

		$params = $this->get_params();

		$settings = array(
			'post_id'        => (int) $this->post_id,
			'post_type'      => sanitize_text_field( $this->post_type ),
			'cookie_consent' => 0,
			'cc_load_policy' => (int) $player_settings['cc_load_policy'],			
			'hotkeys'        => (int) $player_settings['hotkeys'],
			'player'         => array(
				'controlBar'                => array(),
				'liveui'                    => true,
				'textTrackSettings'         => false,
				'playbackRates'             => array( 0.5, 0.75, 1, 1.5, 2 ),
				'techCanOverridePoster'     => true,
				'suppressNotSupportedError' => true
			)
		);			

		// Video Sources
		$sources = array();

		$formats = array( 'mp4', 'webm', 'ogv', 'hls', 'dash', 'youtube', 'vimeo' );

		foreach ( $formats as $format ) {
			if ( empty( $videos[ $format ] ) ) {
				continue;
			}

			$mime_type = "video/{$format}";
			$label = '';

			switch ( $format ) {
				case 'mp4':
					$extension = aiovg_get_file_ext( $videos[ $format ] );
					if ( ! in_array( $extension, array( 'webm', 'ogv' ) ) ) {
						$extension = 'mp4';
					}

					$mime_type = "video/{$extension}";

					if ( ! empty( $videos['quality_level'] ) ) {
						$label = $videos['quality_level'];
					}
					break;

				case 'hls':
					$mime_type = 'application/x-mpegurl';
					break;

				case 'dash':
					$mime_type = 'application/dash+xml';
					break;
			}

			$sources[ $format ] = array(
				'type' => $mime_type,
				'src'  => $videos[ $format ]
			);

			if ( ! empty( $label ) ) {
				$sources[ $format ]['label'] = $label;
			}
		}

		if ( isset( $videos['sources'] ) ) {
			foreach ( $videos['sources'] as $source ) {
				if ( ! empty( $source['quality'] ) && ! empty( $source['src'] ) ) {	
					$extension = aiovg_get_file_ext( $source['src'] );
					if ( ! in_array( $extension, array( 'webm', 'ogv' ) ) ) {
						$extension = 'mp4';
					}

					$label = $source['quality'];

					$sources[ $label ] = array(
						'type'  => "video/{$extension}",
						'src'   => $source['src'],
						'label' => $label
					);
				}
			}
		}

		$sources = apply_filters( 'aiovg_player_sources', $sources, $params ); // Backward compatibility to 3.3.0
		$sources = apply_filters( 'aiovg_videojs_player_sources', $sources, $params );

		// Video Tracks  
		$has_tracks	= ! empty( $player_settings['tracks'] ) || ! empty( $settings['cc_load_policy'] );
		$tracks = array();

		if ( $has_tracks ) {
			$tracks = $this->get_tracks();

			$has_srt_found = 0;

			foreach ( $tracks as $index => $track ) {
				$ext = pathinfo( $track['src'], PATHINFO_EXTENSION );
				if ( 'srt' == strtolower( $ext ) ) {
					$has_srt_found = 1;			
					break;
				}
			}

			if ( $has_srt_found ) {
				$settings['tracks'] = $tracks;
				$tracks = array();
			}
		}	
		
		$tracks = apply_filters( 'aiovg_player_tracks', $tracks, $params ); // Backward compatibility to 3.3.0
		$tracks = apply_filters( 'aiovg_videojs_player_tracks', $tracks, $params );

		// Video Chapters		
		if ( ! empty( $player_settings['chapters'] ) ) {
			$chapters = $this->get_chapters();

			if ( ! empty( $chapters ) ) {			
				$settings['chapters'] = $chapters;
			}
		}		

		// Video Attributes
		$attributes = array(
			'id'       => 'aiovg-player-' . (int) $this->reference_id,
			'class'    => 'video-js vjs-fill vjs-theme-' . sanitize_text_field( $player_settings['theme'] ),
			'style'    => 'width: 100%; height: 100%;',
			'controls' => '',
			'preload'  => esc_attr( $player_settings['preload'] )
		);

		if ( ! empty( $player_settings['autoplay'] ) ) {
			$settings['player']['autoplay'] = true;
		}

		if ( ! empty( $player_settings['loop'] ) ) {
			$attributes['loop'] = '';
		}

		if ( ! empty( $player_settings['muted'] ) ) {
			$attributes['muted'] = '';
		}		

		if ( ! empty( $player_settings['playsinline'] ) ) {
			$attributes['playsinline'] = '';
		}

		if ( ! empty( $poster ) ) {
			$attributes['poster'] = esc_url( $poster );
		}

		if ( ! empty( $logo_settings['copyright_text'] ) ) {
			$attributes['controlsList']  = 'nodownload';
			$attributes['oncontextmenu'] = 'return false;';
		}

		// Player Settings
		$controls = array( 
			'playpause'  => 'PlayToggle', 
			'current'    => 'CurrentTimeDisplay', 
			'progress'   => 'ProgressControl', 
			'duration'   => 'DurationDisplay',	
			'spacer'     => 'CustomControlSpacer', 
			'tracks'     => 'CaptionsButton',
			'speed'      => 'PlaybackRateMenuButton', 
			'quality'    => 'QualitySelector',
			'volume'     => 'VolumePanel',
			'pip'        => 'PictureInPictureToggle', 
			'fullscreen' => 'FullscreenToggle'
		);

		foreach ( $controls as $index => $control ) {
			$enabled = ( isset( $player_settings[ $index ] ) && ! empty( $player_settings[ $index ] ) ) ? 1 : 0;

			if ( 'spacer' == $index ) {
				$enabled = 1;
			}

			if ( $enabled && 'pip' == $index ) {
				if ( isset( $sources['youtube'] ) || isset( $sources['vimeo'] ) ) {
					$enabled = 0;
				}
			}

			if ( ! $enabled ) {	
				unset( $controls[ $index ] );	
			}
		}
		
		if ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) {
			if ( isset( $controls['progress'] ) ) {
				$controls = aiovg_insert_array_after( 'progress', $controls, array( 
					'liveui' => 'SeekToLive'
				));
			}
		}

		if ( isset( $controls['current'] ) && isset( $controls['duration'] ) ) {
			if ( 'custom' == $player_settings['theme'] || ! isset( $controls['progress'] ) ) {
				$controls = aiovg_insert_array_after( 'current', $controls, array( 
					'timedivider' => 'TimeDivider'
				));
			}
		}
		
		if ( isset( $controls['tracks'] ) ) {
			$controls = aiovg_insert_array_after( 'tracks', $controls, array( 
				'audio' => 'AudioTrackButton'
			));
		}

		$settings['player']['controlBar']['children'] = array_values( $controls );

		if ( ! isset( $controls['progress'] ) ) {
			$attributes['class'] .= ' vjs-no-progress-control';
		}
		
		if ( empty( $controls ) || ( 1 == count( $controls ) && isset( $controls['spacer'] ) ) ) {
			$attributes['class'] .= ' vjs-no-control-bar';
		}

		$attributes = apply_filters( 'aiovg_player_attributes', $attributes, $params ); // Backward compatibility to 3.3.0
		$attributes = apply_filters( 'aiovg_videojs_player_attributes', $attributes, $params );

		if ( ! empty( $player_settings['share'] ) ) {
			$settings['share'] = 1;
		}

		if ( ! empty( $player_settings['embed'] ) ) {
			$settings['embed'] = 1;
		}		

		if ( ! empty( $player_settings['download'] ) && $download_url = $this->get_download_url() ) {
			$settings['download'] = array(
				'url' => esc_url( $download_url )
			);
		}

		if ( ! empty( $logo_settings['show_logo'] ) ) {
			$settings['logo'] = array(
				'image'    => esc_url( $logo_settings['logo_image'] ),
				'link'     => esc_url( $logo_settings['logo_link'] ),
				'position' => sanitize_text_field( $logo_settings['logo_position'] ),
				'margin'   => (int) $logo_settings['logo_margin']
			);
		}

		if ( ! empty( $logo_settings['copyright_text'] ) ) {
			$settings['contextmenu'] = array(
				'content' => htmlspecialchars( esc_attr( $logo_settings['copyright_text'] ) )
			);
		}

		if ( ! empty( $privacy_settings['show_consent'] ) ) {
			if ( isset( $sources['youtube'] ) || isset( $sources['vimeo'] ) ) {
				$settings['cookie_consent'] = 1;
			}
		}

		if ( isset( $sources['youtube'] ) ) { // YouTube
			$settings['player']['techOrder'] = array( 'youtube' );
			$settings['player']['youtube'] = array( 
				'iv_load_policy' => 3,
				'playsinline'    => ( ! empty( $player_settings['playsinline'] ) ? 1 : 0 )
			);

			parse_str( $sources['youtube']['src'], $queries );

			if ( isset( $queries['start'] ) ) {
				$settings['start'] = (int) $queries['start'];
			}

			if ( isset( $queries['t'] ) ) {
				$settings['start'] = (int) $queries['t'];
			}

			if ( isset( $queries['end'] ) ) {
				$settings['end'] = (int) $queries['end'];
			}
		}
		
		if ( isset( $sources['vimeo'] ) ) { // Vimeo
			$settings['player']['techOrder'] = array( 'vimeo2' );
			$settings['player']['vimeo2'] = array( 
				'playsinline' => ( ! empty( $player_settings['playsinline'] ) ? 1 : 0 )
			);

			if ( strpos( $sources['vimeo']['src'], 'player.vimeo.com' ) !== false ) {
				$video_id = aiovg_get_vimeo_id_from_url( $sources['vimeo']['src'] );
				$sources['vimeo']['src'] = 'https://vimeo.com/' . $video_id;
			}
		}

		$settings = apply_filters( 'aiovg_player_settings', $settings, $params ); // Backward compatibility to 3.3.0
		$settings = apply_filters( 'aiovg_videojs_player_settings', $settings, $params );

		// Include Dependencies
		wp_enqueue_style( 
			AIOVG_PLUGIN_SLUG . '-videojs', 
			AIOVG_PLUGIN_URL . 'vendor/videojs/video-js.min.css', 
			array(), 
			'8.18.1', 
			'all' 
		);

		if ( in_array( 'QualitySelector', $settings['player']['controlBar']['children'] ) ) {
			if ( isset( $sources['mp4'] ) || isset( $sources['webm'] ) || isset( $sources['ogv'] ) ) {
				wp_enqueue_style( 
					AIOVG_PLUGIN_SLUG . '-quality-selector', 
					AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/quality-selector/quality-selector.min.css', 
					array(), 
					'1.3.1', 
					'all' 
				);
			}

			if ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) {
				wp_enqueue_style( 
					AIOVG_PLUGIN_SLUG . '-videojs-contrib-quality-menu', 
					AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/contrib-quality-menu/videojs-contrib-quality-menu.min.css', 
					array(), 
					'1.0.3',
					'all' 
				);
			}
		}

		if ( isset( $settings['share'] ) || isset( $settings['embed'] ) || isset( $settings['download'] ) || isset( $settings['logo'] ) ) {
			wp_enqueue_style( 
				AIOVG_PLUGIN_SLUG . '-overlay', 
				AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/overlay/videojs-overlay.min.css', 
				array(), 
				'3.1.0', 
				'all' 
			);
		}

		if ( isset( $settings['share'] ) ) {
			wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-icons' );
		}

		wp_dequeue_style( AIOVG_PLUGIN_SLUG . '-player' );

		wp_enqueue_style( 
			AIOVG_PLUGIN_SLUG . '-player', 
			AIOVG_PLUGIN_URL . 'public/assets/css/videojs.min.css', 
			array(), 
			AIOVG_PLUGIN_VERSION, 
			'all' 
		);

		wp_enqueue_script( 
			AIOVG_PLUGIN_SLUG . '-videojs', 
			AIOVG_PLUGIN_URL . 'vendor/videojs/video.min.js', 
			array(), 
			'8.18.1', 
			array( 'strategy' => 'defer' ) 
		);

		if ( in_array( 'QualitySelector', $settings['player']['controlBar']['children'] ) ) {
			if ( isset( $sources['mp4'] ) || isset( $sources['webm'] ) || isset( $sources['ogv'] ) ) {
				wp_enqueue_script( 
					AIOVG_PLUGIN_SLUG . '-quality-selector', 
					AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/quality-selector/silvermine-videojs-quality-selector.min.js', 
					array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
					'1.3.1', 
					array( 'strategy' => 'defer' ) 
				);
			}

			if ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) {
				wp_enqueue_script( 
					AIOVG_PLUGIN_SLUG . '-videojs-contrib-quality-menu', 
					AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/contrib-quality-menu/videojs-contrib-quality-menu.min.js', 
					array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
					'1.0.3', 
					array( 'strategy' => 'defer' ) 
				);	
			}
		}

		if ( isset( $sources['youtube'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-youtube', 
				AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/youtube/Youtube.min.js', 
				array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
				'3.1.0',
				array( 'strategy' => 'defer' ) 
			);
		}

		if ( isset( $settings['start'] ) || isset( $settings['end'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-offset', 
				AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/offset/videojs-offset.min.js', 
				array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
				'2.1.3',
				array( 'strategy' => 'defer' ) 
			);
		}

		if ( isset( $sources['vimeo'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-vimeo', 
				AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/vimeo/Vimeo.min.js', 
				array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
				'3.1.0', 
				array( 'strategy' => 'defer' ) 
			);
		}

		if ( isset( $settings['share'] ) || isset( $settings['embed'] ) || isset( $settings['download'] ) || isset( $settings['logo'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-overlay', 
				AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/overlay/videojs-overlay.min.js', 
				array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
				'3.1.0', 
				array( 'strategy' => 'defer' ) 
			);
		}

		if ( ! empty( $settings['hotkeys'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-hotkeys', 
				AIOVG_PLUGIN_URL . 'vendor/videojs/plugins/hotkeys/videojs.hotkeys.min.js', 
				array( AIOVG_PLUGIN_SLUG . '-videojs' ), 
				'0.2.30', 
				array( 'strategy' => 'defer' ) 
			);
		}
		
		$_params = $params;
		$_params['settings'] = $settings;
		$_params['attributes'] = $attributes;
		$_params['sources'] = $sources;
		$_params['tracks'] = $tracks;
		do_action( 'aiovg_player_scripts', $_params ); // Backward compatibility to 3.3.0
		
		do_action( 'aiovg_videojs_player_scripts', $settings, $attributes, $sources, $tracks );
		
		wp_enqueue_script( AIOVG_PLUGIN_SLUG . '-player' );

		// Output
		$player_html = sprintf( '<video-js %s>', aiovg_combine_video_attributes( $attributes ) );
		
		foreach ( $sources as $source ) { // Sources
			$player_html .= sprintf( 
				'<source type="%s" src="%s" label="%s" />', 
				esc_attr( $source['type'] ), 
				esc_url( $source['src'] ),
				( isset( $source['label'] ) ? esc_attr( $source['label'] ) : '' ) 
			);
		}		
		
		foreach ( $tracks as $index => $track ) { // Tracks
			$player_html .= sprintf( 
				'<track kind="captions" src="%s" label="%s" srclang="%s" %s/>', 
				esc_url( $track['src'] ), 				
				esc_attr( $track['label'] ),
				esc_attr( $track['srclang'] ), 
				( 0 == $index && 1 == $settings['cc_load_policy'] ? 'default' : '' ) 
			);
		}

		$player_html .= '</video-js>';
		
		if ( isset( $settings['share'] ) || isset( $settings['embed'] ) ) { // Share / Embed
			$player_html .= '<div class="vjs-share-embed" style="display: none;">';
			
			if ( isset( $settings['share'] ) ) { // Share Buttons
				$share_buttons = $this->get_share_buttons();

				$player_html .= '<div class="vjs-share-buttons">';
				foreach ( $share_buttons as $button ) {
					$player_html .= sprintf( 
						'<a href="%1$s" class="vjs-share-button vjs-share-button-%2$s" title="%3$s" target="_blank"><span class="%4$s" aria-hidden="true"></span><span class="vjs-control-text" aria-live="polite">%3$s</span></a>',							
						esc_url( $button['url'] ), 
						esc_attr( $button['service'] ),
						esc_attr( $button['text'] ),
						esc_attr( $button['icon'] )
					);
				}
				$player_html .= '</div>';
			}
			
			if ( isset( $settings['embed'] ) ) { // Embed Code
				$embedcode = $this->get_embedcode();

				$player_html .= '<div class="vjs-embed-code">';
				$player_html .= '<label for="vjs-input-embed-code-' . $this->reference_id . '">' . esc_html__( 'Paste this code in your HTML page', 'all-in-one-video-gallery' ) . '</label>';
				$player_html .= '<input type="text" id="vjs-input-embed-code-' . $this->reference_id . '" class="vjs-input-embed-code" value="' . htmlspecialchars( $embedcode ) . '" readonly />';
				$player_html .= '</div>';
			}

			$player_html .= '</div>';
		}

		if ( ! empty( $settings['cookie_consent'] ) ) { // Cookie Consent
			$player_html .= sprintf(
				'<div class="aiovg-privacy-wrapper" %s><div class="aiovg-privacy-consent-block"><div class="aiovg-privacy-consent-message">%s</div><button type="button" class="aiovg-privacy-consent-button">%s</button></div></div>',
				( isset( $attributes['poster'] ) ? 'style="background-image: url(' . esc_url( $attributes['poster'] ) . ');"' : '' ),
				wp_kses_post( trim( $privacy_settings['consent_message'] ) ),
				esc_html( $privacy_settings['consent_button_label'] )
			);
		}

		// Return
		$html = sprintf( 
			'<div class="aiovg-player-container" style="max-width: %s;">', 
			( ! empty( $player_settings['width'] ) ? (int) $player_settings['width'] . 'px' : '100%' )
		);

		$html .= sprintf( 
			'<aiovg-video class="aiovg-player aiovg-player-element aiovg-player-standard vjs-waiting" style="padding-bottom: %s%%;" data-id="%s" data-params=\'%s\'>',
			(float) $player_settings['ratio'],
			esc_attr( $attributes['id'] ),
			wp_json_encode( $settings )
		);

		$html .= $player_html;

		$html .= '</aiovg-video>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get the videos.
	 *
	 * @since  3.5.0
 	 * @return array $videos Array of videos.
	 */
	public function get_videos() {
		if ( ! empty( $this->videos ) ) {
			return $this->videos;
		}

		$player_settings = $this->get_player_settings();
		$videos = parent::get_videos();

		// Force native embed when applicable
		if ( ! empty( $videos['youtube'] ) ) {
			$use_native_controls = apply_filters( 'aiovg_use_native_controls', isset( $player_settings['use_native_controls']['youtube'] ), 'youtube' );
			if ( $use_native_controls ) {
				$videos['iframe'] = $this->get_youtube_embed_url( $videos['youtube'] );
			}
		}

		if ( ! empty( $videos['vimeo'] ) ) {
			$use_native_controls = apply_filters( 'aiovg_use_native_controls', isset( $player_settings['use_native_controls']['vimeo'] ), 'vimeo' );
			if ( $use_native_controls ) {
				$videos['iframe'] = $this->get_vimeo_embed_url( $videos['vimeo'] );
			}
		}

		if ( ! empty( $videos['dailymotion'] ) ) {
			$videos['iframe'] = $this->get_dailymotion_embed_url( $videos['dailymotion'] );
		}

		if ( ! empty( $videos['rumble'] ) ) {
			$videos['iframe'] = $this->get_rumble_embed_url( $videos['rumble'] );
		}

		if ( ! empty( $videos['facebook'] ) ) {
			$videos['iframe'] = $this->get_facebook_embed_url( $videos['facebook'] );
		}

		// Set embed URL if available
		if ( isset( $videos['iframe'] ) ) {
			$this->embed_url = $videos['iframe'];
		}

		// Output
		$this->videos = $videos;
		return $this->videos;
	}
	
}
