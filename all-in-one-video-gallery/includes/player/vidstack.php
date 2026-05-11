<?php

/**
 * Vidstack Player.
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
 * AIOVG_Player_Vidstack class.
 *
 * @since 3.5.0
 */
class AIOVG_Player_Vidstack extends AIOVG_Player_Base {

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
	 * @param int   $post_id    Post ID.
 	 * @param array $args       Player options.
	 * @param int   $player_uid Player unique ID.
	 */
	public function __construct( $post_id, $args, $player_uid ) {	
		parent::__construct( $post_id, $args, $player_uid, 'vidstack' );			
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
			if ( ! $this->has_access && ! $this->is_trailer ) {
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

		// Videostack player
		return $this->get_player_vidstack();
	}

	/**
	 * Get the vidstack player HTML.
	 *
	 * @since  3.5.0
	 * @access private
 	 * @return string  Player HTML.
	 */
	public function get_player_vidstack() {
		$player_settings        = $this->get_player_settings();
		$privacy_settings       = $this->get_privacy_settings();
		$logo_settings          = $this->get_logo_settings();
		$trailer_settings       = $this->get_trailer_settings();
		$email_capture_settings = $this->get_email_capture_settings();

		$videos = $this->get_videos();
		$poster = $this->get_poster();	

		$params = $this->get_params();

		$settings = array(
			'post_id'     => $this->post_id,
			'post_type'   => sanitize_text_field( $this->post_type ),
			'ajax_url'    => sanitize_url( admin_url( 'admin-ajax.php' ) ),
			'ajax_nonce'  => wp_create_nonce( 'aiovg_public_ajax_nonce' ),
			'lazyloading' => (int) $player_settings['lazyloading'],
			'statistics'  => (int) $player_settings['statistics'],
			'player'      => array(
				'iconUrl' => AIOVG_PLUGIN_URL . 'vendor/vidstack/plyr.svg',
				'volume'  => 0.5				
			)
		);		

		// Video Sources	
		$formats = array( 'mp4', 'webm', 'ogv', 'hls', 'dash', 'youtube', 'vimeo' );
		$sources = array();

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

		$sources = apply_filters( 'aiovg_vidstack_player_sources', $sources, $params );

		// Video Captions
		$tracks = array();

		if ( ! empty( $player_settings['tracks'] ) ) {	
			$tracks = $this->get_tracks();

			if ( ! empty( $player_settings['cc_load_policy'] ) ) {
				$settings['player']['captions'] = array(
					'active'   => true,
					'language' => 'auto',
					'update'   => false
				); 
			}
		}
		
		$tracks = apply_filters( 'aiovg_vidstack_player_tracks', $tracks, $params );

		// Video Chapters
		if ( ! empty( $player_settings['chapters'] ) ) {	
			$chapters = $this->get_chapters();

			if ( ! empty( $chapters ) ) {
				$settings['player']['markers'] = array(
					'enabled' => true,
					'points'  => $chapters
				); 
			}
		}		

		// Video Attributes
		$attributes = array(
			'id'       => 'aiovg-player-' . $this->player_uid,
			'style'    => 'width: 100%; height: 100%',
			'controls' => '',
			'preload'  => esc_attr( $player_settings['preload'] )
		);

		if ( ! empty( $player_settings['autoplay'] ) ) {
			$attributes['autoplay'] = '';
			$settings['player']['autoplay'] = true;
		}

		if ( ! empty( $player_settings['loop'] ) ) {
			$attributes['loop'] = '';
			$settings['player']['loop'] = array( 'active' => true );
		}

		if ( ! empty( $player_settings['muted'] ) ) {
			$attributes['muted'] = '';
			$settings['player']['muted'] = true;
		}	
		
		if ( ! empty( $player_settings['playsinline'] ) ) {
			$attributes['playsinline'] = '';
			$settings['player']['playsinline'] = true;
		} else {
			$settings['player']['playsinline'] = false;
		}

		if ( ! empty( $poster ) ) {
			$attributes['data-poster'] = esc_url( $poster );
		}			

		$attributes = apply_filters( 'aiovg_vidstack_player_attributes', $attributes, $params );

		// Player Settings
		$controls = array();
		$controls[] = 'play-large';

		if ( ! empty( $player_settings['playpause'] ) ) {
			$controls[] = 'play';
		}

		if ( 'custom' === $player_settings['theme'] ) {
			if ( ! empty( $player_settings['progress'] ) ) {
				$controls[] = 'progress';
			}

			if ( ! empty( $player_settings['current'] ) ) {
				$controls[] = 'current-time';
			}			
		} else {
			if ( ! empty( $player_settings['current'] ) ) {
				$controls[] = 'current-time';
			}
			
			if ( ! empty( $player_settings['progress'] ) ) {
				$controls[] = 'progress';
			}
		}

		if ( ! empty( $player_settings['duration'] ) ) {
			$controls[] = 'duration';
		}

		if ( 'custom' === $player_settings['theme'] ) {
			$controls[] = 'restart';
		}

		if ( ! empty( $player_settings['volume'] ) ) {
			$controls[] = 'mute';

			if ( ! wp_is_mobile() ) {
				$controls[] = 'volume';
			}
		}

		if ( ! empty( $player_settings['tracks'] ) ) {
			if ( ! wp_is_mobile() ) {
				$controls[] = 'captions';	
			}		
		}

		if ( ! empty( $player_settings['quality'] ) || ! empty( $player_settings['tracks'] ) || ! empty( $player_settings['speed'] ) ) {
			$controls[] = 'settings';
		
			$settings['player']['settings'] = array();

			if ( ! empty( $player_settings['quality'] ) ) {
				$settings['player']['settings'][] = 'quality';
			}

			if ( ! empty( $player_settings['tracks'] ) ) {
				$settings['player']['settings'][] = 'captions';
			}

			if ( ! empty( $player_settings['speed'] ) ) {
				$settings['player']['settings'][] = 'speed';

				$settings['player']['speed'] = array(
					'selected' => 1,
					'options'  => array( 0.5, 0.75, 1, 1.5, 2 )
				);
			}
		}

		if ( ! empty( $player_settings['download'] ) && $download_url = $this->get_download_url() ) {
			$controls[] = 'download';

			$settings['player']['urls'] = array(
				'download' => esc_url( $download_url )
			);
		}

		if ( ! empty( $player_settings['pip'] ) ) {
			$controls[] = 'pip';
		}

		if ( ! empty( $player_settings['fullscreen'] ) ) {
			$controls[] = 'fullscreen';

			$settings['player']['fullscreen'] = array(
				'enabled'   => true,
				'iosNative' => true
			);
		}
		
		$settings['player']['controls'] = $controls;

		if ( ! empty( $player_settings['hotkeys'] ) ) {
			$settings['player']['keyboard'] = array(
				'focused' => true,
				'global'  => false
			);
		}

		if ( ! empty( $player_settings['share'] ) ) {
			$settings['share'] = 1;
		}

		if ( ! empty( $player_settings['embed'] ) ) {
			$settings['embed'] = 1;
		}			

		if ( ! empty( $logo_settings['show_logo'] ) ) {
			$settings['logo'] = array(
				'image'    => esc_url( $logo_settings['logo_image'] ),
				'link'     => ! empty( $logo_settings['logo_link'] ) ? esc_url( $logo_settings['logo_link'] ) : 'javascript:void(0)',
				'position' => sanitize_text_field( $logo_settings['logo_position'] ),
				'margin'   => ! empty( $logo_settings['logo_margin'] ) ? (int) $logo_settings['logo_margin'] : 15
			);
		}

		if ( ! empty( $logo_settings['copyright_text'] ) ) {
			$settings['contextmenu'] = array(
				'content' => wp_strip_all_tags( $logo_settings['copyright_text'] )
			);
		}

		if ( $this->is_trailer ) {
			$settings['trailer'] = array(
				'label' => wp_strip_all_tags( $trailer_settings['label'] ),
				'url'   => esc_url( $trailer_settings['url'] )
			);
		}

		if ( ! empty( $privacy_settings['show_consent'] ) ) {
			if ( isset( $sources['youtube'] ) || isset( $sources['vimeo'] ) ) {
				$settings['cookie_consent'] = 1;
			}
		}

		if ( ! empty( $email_capture_settings['enable_email_capture'] ) ) {
			$settings['email_capture'] = 1;
		}

		if ( isset( $sources['youtube'] ) ) { // YouTube
			$settings['player']['youtube'] = array(
				'noCookie'       => false,
				'rel'            => 0,
				'showinfo'       => 0,
				'iv_load_policy' => 3,
				'modestbranding' => 1
			);

			parse_str( $sources['youtube']['src'], $queries );

			if ( isset( $queries['start'] ) ) {
				$settings['player']['youtube']['start'] = (int) $queries['start'];
			}

			if ( isset( $queries['t'] ) ) {
				$settings['player']['youtube']['start'] = (int) $queries['t'];
			}

			if ( isset( $queries['end'] ) ) {
				$settings['player']['youtube']['end'] = (int) $queries['end'];
			}
		}

		if ( isset( $sources['vimeo'] ) ) { // Vimeo
			$settings['player']['vimeo'] = array(
				'byline'      => false,
				'portrait'    => false,
				'title'       => false,
				'speed'       => true,
				'transparent' => false
			);
		}

		if ( isset( $sources['hls'] ) ) { // HLS
			$settings['hls'] = $sources['hls']['src'];

			if ( ! empty( $player_settings['tracks'] ) ) {
				$settings['player']['captions'] = array(
					'active'   => ! empty( $player_settings['cc_load_policy'] ) ? true : false,
					'language' => 'auto',
					'update'   => true
				); 
			}
		}

		if ( isset( $sources['dash'] ) ) { // Dash
			$settings['dash'] = $sources['dash']['src'];

			if ( ! empty( $player_settings['tracks'] ) ) {
				$settings['player']['captions'] = array(
					'active'   => ! empty( $player_settings['cc_load_policy'] ) ? true : false,
					'language' => 'auto',
					'update'   => true
				);
			}
		}

		$settings = apply_filters( 'aiovg_vidstack_player_settings', $settings, $params );

		// Trailers should not send statistics and should have limited controls to encourage users to watch the full video.
		if ( $this->is_trailer ) {
			$tracks = array();

			$settings['statistics'] = 0;

			unset( $settings['player']['captions'] );
			unset( $settings['player']['markers'] );
			if ( ! empty( $settings['player']['controls'] ) ) {
				$settings['player']['controls'] = array_values( array_diff( $settings['player']['controls'], array( 'download' ) ) );
			}
		}
		
		// Include Dependencies
		wp_enqueue_style( 
			AIOVG_PLUGIN_SLUG . '-plyr', 
			AIOVG_PLUGIN_URL . 'vendor/vidstack/plyr.css', 
			array(), 
			'3.7.8', 
			'all' 
		);

		if ( isset( $settings['share'] ) ) {
			wp_enqueue_style( AIOVG_PLUGIN_SLUG . '-icons' );
		}
		
		wp_dequeue_style( AIOVG_PLUGIN_SLUG . '-player' );

		wp_enqueue_style( 
			AIOVG_PLUGIN_SLUG . '-player', 
			AIOVG_PLUGIN_URL . 'public/assets/css/vidstack.min.css', 
			array(), 
			AIOVG_PLUGIN_VERSION, 
			'all' 
		);

		wp_enqueue_script( 
			AIOVG_PLUGIN_SLUG . '-plyr', 
			AIOVG_PLUGIN_URL . 'vendor/vidstack/plyr.polyfilled.js', 
			array(), 
			'3.7.8', 
			array( 'strategy' => 'defer' ) 
		);		

		if ( isset( $sources['hls'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-hls', 
				AIOVG_PLUGIN_URL . 'vendor/vidstack/hls.min.js', 
				array(), 
				'1.5.17', 
				array( 'strategy' => 'defer' ) 
			);	
		}

		if ( isset( $sources['dash'] ) ) {
			wp_enqueue_script( 
				AIOVG_PLUGIN_SLUG . '-dash', 
				AIOVG_PLUGIN_URL . 'vendor/vidstack/dash.all.min.js', 
				array(), 
				'4.7.4', 
				array( 'strategy' => 'defer' ) 
			);
		}
		
		do_action( 'aiovg_vidstack_player_scripts', $settings, $attributes, $sources, $tracks );

		wp_enqueue_script(
			AIOVG_PLUGIN_SLUG . '-vidstack',
			AIOVG_PLUGIN_URL . 'public/assets/js/vidstack.min.js',
			array( AIOVG_PLUGIN_SLUG . '-plyr' ),
			AIOVG_PLUGIN_VERSION,
			array( 'strategy' => 'defer' )
		);

		// Output
		$player_html = '';

		if ( isset( $sources['youtube'] ) ) { // YouTube
			$video_id = aiovg_get_youtube_id_from_url( $sources['youtube']['src'] );

			$player_html .= sprintf(
				'<div id="%s" style="%s" data-plyr-provider="youtube" data-plyr-embed-id="%s" data-poster="%s"></div>',
				esc_attr( $attributes['id'] ),
				esc_attr( $attributes['style'] ),
				esc_attr( $video_id ),
				( isset( $attributes['data-poster'] ) ? esc_url( $attributes['data-poster'] ) : '' )
			);
		}

		elseif ( isset( $sources['vimeo'] ) ) { // Vimeo
			$video_id = aiovg_get_vimeo_id_from_url( $sources['vimeo']['src'] );
	
			$player_html .= sprintf(
				'<div id="%s" style="%s" data-plyr-provider="vimeo" data-plyr-embed-id="%s" data-poster="%s"></div>',
				esc_attr( $attributes['id'] ),
				esc_attr( $attributes['style'] ),
				esc_attr( $video_id ),
				( isset( $attributes['data-poster'] ) ? esc_url( $attributes['data-poster'] ) : '' )
			);
		}

		elseif ( isset( $sources['hls'] ) || isset( $sources['dash'] ) ) { // HLS or Dash
			$player_html .= sprintf( '<video %s></video>', aiovg_combine_video_attributes( $attributes ) );
		}

		elseif ( ! empty( $sources ) ) { // HTML5 Video
			$player_html .= sprintf( '<video %s>', aiovg_combine_video_attributes( $attributes ) );
		
			foreach ( $sources as $source ) { // Sources
				$player_html .= sprintf( 
					'<source type="%s" src="%s" size="%d" />', 
					esc_attr( $source['type'] ), 
					esc_url( $source['src'] ),
					( isset( $source['label'] ) ? (int) $source['label'] : '' )
				);
			}		
			
			foreach ( $tracks as $index => $track ) { // Tracks
				$player_html .= sprintf( 
					'<track kind="captions" src="%s" label="%s" srclang="%s" />', 
					esc_url( $track['src'] ),						 
					esc_attr( $track['label'] ),
					esc_attr( $track['srclang'] )
				);
			}

			$player_html .= '</video>';
		}		
		
		if ( isset( $settings['share'] ) || isset( $settings['embed'] ) ) { // Share / Embed
			$player_html .= '<div class="plyr__share-embed-modal" style="display: none;">';
			$player_html .= '<div class="plyr__share-embed-modal-content">';
			
			if ( isset( $settings['share'] ) ) { // Share Buttons
				$share_buttons = $this->get_share_buttons();

				$player_html .= '<div class="plyr__share">';
				foreach ( $share_buttons as $button ) {
					$player_html .= sprintf( 
						'<a href="%s" class="plyr__share-button plyr__share-button-%s" target="_blank"><span class="%s"></span><span class="plyr__sr-only">%s</span></a>',							
						esc_url( $button['url'] ), 
						esc_attr( $button['service'] ),
						esc_attr( $button['icon'] ),
						esc_attr( $button['text'] )  
					);
				}
				$player_html .= '</div>';
			}
			
			if ( isset( $settings['embed'] ) ) { // Embed Code
				$embedcode = $this->get_embedcode();

				$player_html .= '<div class="plyr__embed">';
				$player_html .= '<label for="plyr__embed-code-input-' . $this->player_uid . '">' . esc_html__( 'Paste this code in your HTML page', 'all-in-one-video-gallery' ) . '</label>';
				$player_html .= '<input type="text" id="plyr__embed-code-input-' . $this->player_uid . '" class="plyr__embed-code-input" value="' . esc_attr( $embedcode ) . '" readonly />';
				$player_html .= '</div>';
			}

			$player_html .= '<button type="button" class="plyr__controls__item plyr__control plyr__share-embed-modal-close-button aiovg-icon-close"><span class="plyr__sr-only">Close</span></button>';

			$player_html .= '</div>';
			$player_html .= '</div>';
		}
		
		if ( isset( $settings['cookie_consent'] ) && empty( $email_capture_settings['pre_roll'] ) ) { // Cookie Consent
			$player_html .= $this->get_cookie_consent_html();
		}

		if ( ! empty( $settings['email_capture'] ) ) { // Email Capture
			$video_url = '';
			if ( 'aiovg_videos' != $this->post_type && empty( $this->post_id ) && ! empty( $sources ) ) {
				$video_url = reset( $sources )['src'];
			}

			$player_html .= $this->get_email_capture_html( $video_url );
		}

		// Return			
		$html = sprintf( 
			'<div class="aiovg-player-container" style="max-width: %s;">', 
			( ! empty( $player_settings['width'] ) ? (int) $player_settings['width'] . 'px' : '100%' )
		);

		$attributes = array(			
			'class' => 'aiovg-player aiovg-player-element aiovg-player-theme-' . sanitize_text_field( $player_settings['theme'] ),
			'style' => 'padding-bottom: ' . (float) $player_settings['ratio'] . '%;'
		);		

		if ( $this->is_trailer ) {
			$attributes['class'] .= ' aiovg-is-trailer';
		}

		if ( ! empty( $player_settings['hide_youtube_logo'] ) ) { // Hide YouTube Logo
			$attributes['class'] .= ' aiovg-hide-youtube-logo';
		}

		if ( isset( $settings['cookie_consent'] ) ) {
			$attributes['cookieconsent'] = '';
		}

		if ( isset( $settings['email_capture'] ) ) {
			$attributes['emailcapture'] = '';
		}

		$attributes['data-id']     = 'aiovg-player-' . $this->player_uid;
		$attributes['data-params'] = esc_attr( wp_json_encode( $settings ) );

		$html .= sprintf( 
			'<aiovg-video %s>%s</aiovg-video>',
			aiovg_combine_video_attributes( $attributes ),
			$player_html
		);

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

		$this->videos = $this->resolve_provider_iframes( parent::get_videos() );
		return $this->videos;
	}
		
}
