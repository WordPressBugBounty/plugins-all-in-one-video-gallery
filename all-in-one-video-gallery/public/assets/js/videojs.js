'use strict';

class AIOVGVideoElement extends HTMLElement {

	/**
     * Element created.
     */
    constructor() {
        super();

		// Set references to the DOM elements used by the component
		this.player = null;
		this.playButtonEl = null;		

		// Set references to the public properties used by the component
		this.settings = {};

		// Set references to the private properties used by the component
		this._playerId = '';		
		this._hasVideoStarted = false;
		this._ajaxUrl = aiovg_player.ajax_url;
		this._ajaxNonce = aiovg_player.ajax_nonce;
	}

	/**
     * Browser calls this method when the element is added to the document.
     * (can be called many times if an element is repeatedly added/removed)
     */
    connectedCallback() {
		this._playerId = this.dataset.id || '';	

		this.settings = this.dataset.params ? JSON.parse( this.dataset.params ) : {};	
		
		if ( ! this.settings.hasOwnProperty( 'player' ) ) {
			this.settings.player = {};
		}

		this.settings.player.html5 = {
			vhs: {
				overrideNative: ! videojs.browser.IS_ANY_SAFARI,
			}
		};		

		if ( this.settings.cookie_consent ) {
			const privacyConsentButtonEl = this.querySelector( '.aiovg-privacy-consent-button' );
			if ( privacyConsentButtonEl !== null ) {
				privacyConsentButtonEl.addEventListener( 'click', () => this._onCookieConsent() );
			}
		} else {
			this._initPlayer();
		}		
	}

	/**
     * Browser calls this method when the element is removed from the document.
     * (can be called many times if an element is repeatedly added/removed)
     */
	disconnectedCallback() {
		// TODO
	}

	/**
     * Define private methods.
     */

	_onCookieConsent() {
		this.settings.player.autoplay = true;

		const videos = document.querySelectorAll( 'aiovg-video' );
        for ( let i = 0; i < videos.length; i++ ) {
            videos[ i ].removeCookieConsent();
        }

		const embeds = document.querySelectorAll( '.aiovg-player-element[cookieconsent]' );
        for ( let i = 0; i < embeds.length; i++ ) {
            embeds[ i ].removeAttribute( 'cookieconsent' );
        }

		this._setCookie();
	}

	_initPlayer() {
		this.player = videojs( this.querySelector( 'video-js' ), this.settings.player );				

		this.player.ready( () => this._onReady() );
		this.player.one( 'loadedmetadata', () => this._onMetadataLoaded() );
		this.player.on( 'play', () => this._onPlay() );
		this.player.on( 'playing', () => this._onPlaying() );
		this.player.on( 'ended', () => this._onEnded() );

		this._initOffset();
		this._initChapters();
		this._initQualitySelector();			
		this._initOverlays();	
		this._initHotKeys();
		this._initContextMenu();	

		// Dispatch a player ready event
		const options = {					
			id: this._playerId, // Backward compatibility to 3.3.0
			player_id: this._playerId,
			config: this.settings, // Backward compatibility to 3.3.0
			settings: this.settings,
			player: this.player					
		};

		this._dispatchEvent( 'player.init', options );
	}

	_onReady() {
		this.classList.remove( 'vjs-waiting' );

		this.playButtonEl = this.querySelector( '.vjs-big-play-button' );	
		if ( this.playButtonEl !== null ) {
			this.playButtonEl.addEventListener( 'click', () => this._onPlayClicked() );
		}
	}

	_onMetadataLoaded() {
		// Quality selector
		const qualitySelectorEl = this.querySelector( '.vjs-quality-selector' );

		if ( qualitySelectorEl !== null ) {
			const items = qualitySelectorEl.querySelectorAll( '.vjs-menu-item' );

			for ( let i = 0; i < items.length; i++ ) {
				let item = items[ i ];

				const textNode   = item.querySelector( '.vjs-menu-item-text' );
				const resolution = textNode.innerHTML.replace( /\D/g, '' );

				if ( resolution >= 2160 ) {
					item.innerHTML += '<span class="vjs-quality-menu-item-sub-label">4K</span>';
				} else if ( resolution >= 720 ) {
					item.innerHTML += '<span class="vjs-quality-menu-item-sub-label">HD</span>';
				}
			}
		}

		// Add support for SRT
		if ( this.settings.hasOwnProperty( 'tracks' ) ) {
			for ( let i = 0, max = this.settings.tracks.length; i < max; i++ ) {
				const track = this.settings.tracks[ i ];

				let mode = '';
				if ( i == 0 && this.settings.cc_load_policy == 1 ) {
					mode = 'showing';
				}

				if ( /srt/.test( track.src.toLowerCase() ) ) {
					this._addSrtTextTrack( track, mode );
				} else {
					const obj = {
						kind: 'captions',
						src: track.src,									
						label: track.label,
						srclang: track.srclang
					};

					if ( mode ) {
						obj.mode = mode;
					}

					this.player.addRemoteTextTrack( obj, true ); 
				}					               
			}
		}
		
		// Chapters
		if ( this.settings.hasOwnProperty( 'chapters' ) ) {
			this._addMarkers();
		}
	}

	_onPlayClicked() {
		if ( ! this._hasVideoStarted ) {
			this.classList.add( 'vjs-waiting' );
		}

		this.playButtonEl.removeEventListener( 'click', () => this._onPlayClicked() );
	}

	_onPlay() {
		if ( ! this._hasVideoStarted ) {
			this._hasVideoStarted = true;
			this.classList.remove( 'vjs-waiting' );

			this._updateViewsCount();
		}	

		// Pause other videos
		const videos = document.querySelectorAll( 'aiovg-video' );
        for ( let i = 0; i < videos.length; i++ ) {
			if ( videos[ i ] != this ) {
            	videos[ i ].pause();
			}
        }
	}

	_onPlaying() {
		this.player.trigger( 'controlsshown' );
	}

	_onEnded() {
		this.player.trigger( 'controlshidden' );
	}

	_initOffset() {
		const offset = {};

		if ( this.settings.hasOwnProperty( 'start' ) ) {
			offset.start = this.settings.start;
		}

		if ( this.settings.hasOwnProperty( 'end' ) ) {
			offset.end = this.settings.end;
		}
		
		if ( Object.keys( offset ).length > 1 ) {
			offset.restart_beginning = false;
			this.player.offset( offset );
		}
	}

	_initChapters() {
		if ( ! this.settings.hasOwnProperty( 'chapters' ) ) {
			return false;
		}

		const root = this;

		try {
			this.player.getDescendant([
				'ControlBar',
				'ProgressControl',
				'SeekBar',
				'MouseTimeDisplay',
				'TimeTooltip',
			]).update = function( seekBarRect, seekBarPoint, time ) {
				const markers = root.settings.chapters;
				const markerIndex = markers.findIndex( ( { time: markerTime } ) => markerTime == root._formatedTimeToSeconds( time ) );
		
				if ( markerIndex > -1 ) {
					const label = markers[ markerIndex ].label;
			
					videojs.dom.emptyEl( this.el() );
					videojs.dom.appendContent( this.el(), [ root._labelEl( label ), root._timeEl( time ) ] );
			
					return false;
				}
		
				this.write( time );
			};
		} catch ( error ) {
			// console.log( error );
		}
	}

	_initQualitySelector() {
		// Standard quality selector
		this.player.on( 'qualitySelected', ( event, source ) => {
			const resolution = source.label.replace( /\D/g, '' );

			this.player.removeClass( 'vjs-4k' );
			this.player.removeClass( 'vjs-hd' );

			if ( resolution >= 2160 ) {
				this.player.addClass( 'vjs-4k' );
			} else if ( resolution >= 720 ) {
				this.player.addClass( 'vjs-hd' );
			}
		});

		// HLS quality selector
		const src = this.player.src();

		if ( /.m3u8/.test( src ) || /.mpd/.test( src ) ) {
			if ( this.settings.player.controlBar.children.indexOf( 'qualitySelector' ) != -1 ) {
				this.player.qualityMenu();
			}
		}
	}

	_initOverlays() {
		const overlays = [];

		// Share / Embed
		if ( this.settings.hasOwnProperty( 'share' ) || this.settings.hasOwnProperty( 'embed' ) ) {
			overlays.push({
				content: '<button type="button" class="vjs-share-embed-button" title="Share"><span class="vjs-icon-share" aria-hidden="true"></span><span class="vjs-control-text" aria-live="polite">Share</span></button>',
				class: 'vjs-share',
				align: 'top-right',
				start: 'controlsshown',
				end: 'controlshidden',
				showBackground: false					
			});					
		}

		// Download
		if ( this.settings.hasOwnProperty( 'download' ) ) {
			let className = 'vjs-download';

			if ( this.settings.hasOwnProperty( 'share' ) || this.settings.hasOwnProperty( 'embed' ) ) {
				className += ' vjs-has-share';
			}

			overlays.push({
				content: '<a href="' + this.settings.download.url + '" class="vjs-download-button" title="Download" target="_blank"><span class="vjs-icon-file-download" aria-hidden="true"></span><span class="vjs-control-text" aria-live="polite">Download</span></a>',
				class: className,
				align: 'top-right',
				start: 'controlsshown',
				end: 'controlshidden',
				showBackground: false					
			});
		}

		// Logo
		if ( this.settings.hasOwnProperty( 'logo' ) ) {
			if ( this.settings.logo.margin ) {
				this.settings.logo.margin = this.settings.logo.margin - 5;
			}
			
			let style = 'margin: ' + this.settings.logo.margin + 'px;';
			let align = 'bottom-left';

			switch ( this.settings.logo.position ) {
				case 'topleft':						
					align = 'top-left';
					break;

				case 'topright':						
					align = 'top-right';
					break;

				case 'bottomright':
					align = 'bottom-right';
					break;				
			}

			const logo = '<a href="' + this.settings.logo.link + '" style="' + style + '">' + 
				'<img src="' + this.settings.logo.image + '" alt="" />' + 
				'<span class="vjs-control-text" aria-live="polite">Logo</span>' + 
			'</a>';

			overlays.push({
				content: logo,
				class: 'vjs-logo',
				align: align,
				start: 'controlsshown',
				end: 'controlshidden',
				showBackground: false					
			});
		}

		// Overlay
		if ( overlays.length > 0 ) {
			this.player.overlay({
				content: '',
				overlays: overlays
			});

			if ( this.settings.hasOwnProperty( 'share' ) || this.settings.hasOwnProperty( 'embed' ) ) {
				const options = {};
				options.content = this.querySelector( '.vjs-share-embed' );
				options.temporary = false;

				const ModalDialog = videojs.getComponent( 'ModalDialog' );
				const modal = new ModalDialog( this.player, options );
				modal.addClass( 'vjs-modal-dialog-share-embed' );

				this.player.addChild( modal );

				let wasPlaying = true;

				this.querySelector( '.vjs-share-embed-button' ).addEventListener( 'click', () => {
					wasPlaying = ! this.player.paused;
					modal.open();						
				});

				modal.on( 'modalclose', () => {
					if ( wasPlaying ) {
						this.player.play();
					}						
				});
			}

			if ( this.settings.hasOwnProperty( 'embed' ) ) {
				this.querySelector( '.vjs-input-embed-code' ).addEventListener( 'focus', function() {
					this.select();
					document.execCommand( 'copy' );					
				});
			}
		}
	}

	_initHotKeys() {
		if ( this.settings.hotkeys ) {
			this.player.hotkeys();
		}
	}

	_initContextMenu() {
		if ( ! this.settings.hasOwnProperty( 'contextmenu' ) ) {
			return false;
		}

		let contextmenuEl = document.querySelector( '#aiovg-contextmenu' );
		if ( contextmenuEl === null ) {
			contextmenuEl = document.createElement( 'div' );
			contextmenuEl.id = 'aiovg-contextmenu';
			contextmenuEl.style.display = 'none';
			contextmenuEl.innerHTML = '<div class="aiovg-contextmenu-content">' + this.settings.contextmenu.content + '</div>';
			
			document.body.appendChild( contextmenuEl );	

			document.addEventListener( 'click', () => {
				contextmenuEl.style.display = 'none';								 
			});
		}

		let timeoutHandler = '';			
		
		this.addEventListener( 'contextmenu', function( event ) {						
			if ( event.keyCode == 3 || event.which == 3 ) {
				event.preventDefault();
				event.stopPropagation();
				
				let width = contextmenuEl.offsetWidth,
					height = contextmenuEl.offsetHeight,
					x = event.pageX,
					y = event.pageY,
					documentElement = document.documentElement,
					scrollLeft = ( window.pageXOffset || documentElement.scrollLeft ) - ( documentElement.clientLeft || 0 ),
					scrollTop = ( window.pageYOffset || documentElement.scrollTop ) - ( documentElement.clientTop || 0 ),
					left = x + width > window.innerWidth + scrollLeft ? x - width : x,
					top = y + height > window.innerHeight + scrollTop ? y - height : y;
		
				contextmenuEl.style.display = '';
				contextmenuEl.style.left = left + 'px';
				contextmenuEl.style.top = top + 'px';
				
				clearTimeout( timeoutHandler );

				timeoutHandler = setTimeout( () => {
					contextmenuEl.style.display = 'none';
				}, 1500 );				
			}														 
		});
	}

	_addSrtTextTrack( track, mode ) {
		let xmlhttp;

		if ( window.XMLHttpRequest ) {
			xmlhttp = new XMLHttpRequest();
		} else {
			xmlhttp = new ActiveXObject( 'Microsoft.XMLHTTP' );
		}
		
		xmlhttp.onreadystatechange = () => {				
			if ( xmlhttp.readyState == 4 && xmlhttp.status == 200 && xmlhttp.responseText ) {					
				const text = this._srtToWebVTT( xmlhttp.responseText );

				if ( text ) {
					const blob = new Blob( [ text ], { type : 'text/vtt' } );
					const src = URL.createObjectURL( blob );

					const obj = {
						kind: 'captions',
						src: src,							
						label: track.label,
						srclang: track.srclang							
					};

					if ( mode ) {
						obj.mode = mode;
					}

					this.player.addRemoteTextTrack( obj, true );
				}
			}					
		};

		xmlhttp.open( 'GET', track.src, true );
		xmlhttp.send();							
	}

	_srtToWebVTT( data ) {
		// Remove dos newlines
		let srt = data.replace( /\r+/g, '' );

		// Trim white space start and end
		srt = srt.replace( /^\s+|\s+$/g, '' );

		// Get cues
		let cuelist = srt.split( '\n\n' );
		let result  = '';

		if ( cuelist.length > 0 ) {
			result += "WEBVTT\n\n";

			for ( let i = 0; i < cuelist.length; i++ ) {
				result += this._convertSrtCue( cuelist[ i ] );
			}
		}

		return result;
	}

	_convertSrtCue( caption ) {
		// Remove all html tags for security reasons
		// srt = srt.replace( /<[a-zA-Z\/][^>]*>/g, '' );

		let cue = '';
		let s = caption.split( /\n/ );

		// Concatenate muilt-line string separated in array into one
		while ( s.length > 3 ) {
			for ( let i = 3; i < s.length; i++ ) {
				s[2] += "\n" + s[ i ];
			}

			s.splice( 3, s.length - 3 );
		}

		let line = 0;

		// Detect identifier
		if ( ! s[0].match( /\d+:\d+:\d+/ ) && s[1].match( /\d+:\d+:\d+/ ) ) {
			cue  += s[0].match( /\w+/ ) + "\n";
			line += 1;
		}

		// Get time strings
		if ( s[ line ].match( /\d+:\d+:\d+/ ) ) {
			// Convert time string
			let m = s[1].match( /(\d+):(\d+):(\d+)(?:,(\d+))?\s*--?>\s*(\d+):(\d+):(\d+)(?:,(\d+))?/ );

			if ( m ) {
				cue  += m[1] + ":" + m[2] + ":" + m[3] + "." + m[4] + " --> " + m[5] + ":" + m[6] + ":" + m[7] + "." + m[8] + "\n";
				line += 1;
			} else {
				// Unrecognized timestring
				return '';
			}
		} else {
			// File format error or comment lines
			return '';
		}

		// Get cue text
		if ( s[ line ] ) {
			cue += s[ line ] + "\n\n";
		}

		return cue;
	}

	_addMarkers() {
		const total = this.player.duration();
		const seekBarEl = this.player.el_.querySelector( '.vjs-progress-control .vjs-progress-holder' );

		if ( seekBarEl !== null ) {
			for ( let i = 0; i < this.settings.chapters.length; i++ ) {
				const elem = document.createElement( 'div' );
				elem.className = 'vjs-marker';
				elem.style.left = ( this.settings.chapters[ i ].time / total ) * 100 + '%';

				seekBarEl.appendChild( elem );
			}
		}
	}

	_formatedTimeToSeconds( time ) {
		let timeSplit = time.split( ':' );
		let seconds   = +timeSplit.pop();

		return timeSplit.reduce( ( acc, curr, i, arr ) => {
			if ( arr.length === 2 && i === 1 ) return acc + +curr * 60 ** 2;
			else return acc + +curr * 60;
		}, seconds );
	}

	_timeEl( time ) {
		return videojs.dom.createEl( 'span', undefined, undefined, '(' + time + ')' );
	}

	_labelEl( label ) {
		return videojs.dom.createEl( 'strong', undefined, undefined, label );
	}

	_dispatchEvent( event, data ) {
        jQuery( this ).trigger( event, data ); 						
    }

	_fetch( data ) {
        jQuery.post( this._ajaxUrl, data ); 						
    }

	/**
     * Define private async methods.
     */

	async _setCookie() {		
		const data = {
			'action': 'aiovg_set_cookie',
			'security': this._ajaxNonce
		};

		this._fetch( data );
	}	

	async _updateViewsCount() {
		if ( this.settings.post_type != 'aiovg_videos' ) {
			return false;
		}

		const data = {
			'action': 'aiovg_update_views_count',
			'post_id': this.settings.post_id,
			'duration': this.player.duration() || 0,
			'security': this._ajaxNonce
		};

		this._fetch( data );
	}	

	/**
     * Define API methods.
     */

	removeCookieConsent() {
		const privacyWrapperEl = this.querySelector( '.aiovg-privacy-wrapper' );
		if ( privacyWrapperEl != null ) {
			privacyWrapperEl.remove();
			this._initPlayer();		
		}	
	}

	pause() {
		if ( this.player ) {
			this.player.pause();
		}
	}

}

(function( $ ) {	

	/**
	 * Refresh iframe player elements upon cookie confirmation.
	 */
	window.onmessage = function( event ) {
		if ( event.data == 'aiovg-cookie-consent' ) {
			$( '.aiovg-player-iframe iframe' ).each(function() {
				const src = $( this ).attr( 'src' );

				if ( src.indexOf( 'refresh=1' ) == -1 ) {
                    const separator = src.indexOf( '?' ) > -1 ? '&' : '?';
					$( this ).attr( 'src', src + separator + 'refresh=1' );
				}
			});
		}
	};

	/**
	 * Called when the page has loaded.
	 */
	$(function() {
		
		// Register custom element
		customElements.define( 'aiovg-video', AIOVGVideoElement );

		// Update views count for the non-iframe embeds
		$( '.aiovg-player-raw' ).each(function() {
			const settings = $( this ).data( 'params' );

			if ( settings.post_type != 'aiovg_videos' ) {
				return;
			}

			const data = {
				'action': 'aiovg_update_views_count',
				'post_id': settings.post_id,
				'security': aiovg_player.ajax_nonce
			};

			$.post( aiovg_player.ajax_url, data );
		});

		// Custom error message
		if ( typeof videojs !== 'undefined' ) {
			videojs.hook( 'beforeerror', function( player, error ) {
				// Prevent current error from being cleared out
				if ( error == null ) {
					return player.error();
				}

				// But allow changing to a new error
				if ( error.code == 2 || error.code == 4 ) {
					const src = player.src();

					if ( /.m3u8/.test( src ) || /.mpd/.test( src ) ) {
						return {
							code: error.code,
							message: aiovg_player.i18n.stream_not_found
						}
					}
				}
				
				return error;
			});
		}

	});

})( jQuery );
