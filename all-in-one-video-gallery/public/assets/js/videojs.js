(function( $ ) {
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
			this.cookieConsentEl = null;
			this.emailCaptureEl = null;	

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

			if ( this.settings.hasOwnProperty( 'email_capture' ) ) {
				this.emailCaptureEl = this.querySelector( '.aiovg-email-capture' );
			}			

			if ( this.emailCaptureEl && parseInt( this.emailCaptureEl.dataset.pre_roll ) ) {
				this._initPreRollEmailCapture();
			} else if ( this.settings.cookie_consent ) {
				this.cookieConsentEl        = this.querySelector( '.aiovg-privacy-wrapper' );
				const cookieConsentButtonEl = this.querySelector( '.aiovg-privacy-consent-button' );

				if ( this.cookieConsentEl && cookieConsentButtonEl !== null ) {
					const poster = this.cookieConsentEl.getAttribute( 'data-poster' ) || '';
					if ( AIOVGVideoElement.isValidUrl( poster ) ) {
						this.cookieConsentEl.style.backgroundImage = `url("${poster}")`; 
					} 

					cookieConsentButtonEl.addEventListener( 'click', () => this._onCookieConsent() );
				} else {
					this._initPlayer();
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
			this._setCookie();

			// Remove cookieconsent from other players
			const videos = document.querySelectorAll( '.aiovg-player-element' );
			for ( let i = 0; i < videos.length; i++ ) {
				videos[ i ].removeCookieConsent();
			}

			window.postMessage({
				message: 'aiovg-cookie-consent'
			}, window.location.origin );
		}

		_initPreRollEmailCapture() {
			this.classList.add( 'aiovg-show-email-capture' );

			const poster = this.emailCaptureEl.getAttribute( 'data-poster' ) || '';
			if ( AIOVGVideoElement.isValidUrl( poster ) ) {
				this.emailCaptureEl.style.backgroundImage = `url("${poster}")`; 
			} 

			const firstInput = this.emailCaptureEl.querySelector( 'input' );
			if ( firstInput ) {
				firstInput.focus();
			}

			this._initEmailCaptureForm( ( data ) => {
				if ( ! this.emailCaptureEl ) return false;

				if ( this.emailCaptureEl.classList.contains( 'aiovg-is-loading' ) ) return false; // Prevent multiple submissions
				this.emailCaptureEl.classList.add( 'aiovg-is-loading' );

				const xmlhttp = new XMLHttpRequest();

				xmlhttp.onreadystatechange = () => {
					if ( 4 == xmlhttp.readyState ) {
						let response = null;

						if ( 200 == xmlhttp.status && xmlhttp.responseText ) {
							try {
								response = JSON.parse( xmlhttp.responseText );
							} catch ( e ) {}
						}

						if ( response && response.success ) {
							// Hide the form and remove it from the DOM to prevent it from being submitted again.
							this.classList.remove( 'aiovg-show-email-capture' );

							this.emailCaptureEl.remove();
							this.emailCaptureEl = null;

							// Render player
							this.settings.player.autoplay = true;
							this._initPlayer();

							// Email submission implies consent — remove both gates from other players
							const videos = document.querySelectorAll( '.aiovg-player-element' );
							for ( let i = 0; i < videos.length; i++ ) {
								videos[ i ].removeCookieConsent();
								videos[ i ].removeEmailCapture();
							}

							window.postMessage({
								message: 'aiovg-email-captured'
							}, window.location.origin );
						} else {
							// Show error message
							const errorSpan = this.emailCaptureEl.querySelector( '.aiovg-email-capture-error' );

							if ( errorSpan ) {
								errorSpan.textContent   = ( response && response.data && typeof response.data === 'string' ) ? response.data : errorSpan.getAttribute( 'data-error_generic' );
								errorSpan.style.display = 'block';
							}

							// Restore the form so the user can retry.
							this.emailCaptureEl.classList.remove( 'aiovg-is-loading' );
						}
					}
				};

				xmlhttp.open( 'POST', this._ajaxUrl + '?action=aiovg_save_email_lead&security=' + this._ajaxNonce, true );
				xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
				xmlhttp.send( data );
			} );
		}

		_initPlayer() {
			if ( this.player ) {
				return false;
			}

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
			this._initEmailCapture();
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

			// Pause other players
			const videos = document.querySelectorAll( '.aiovg-player-element' );
			for ( let i = 0; i < videos.length; i++ ) {
				if ( videos[ i ] != this ) {
					videos[ i ].pause();
				}
			}

			window.postMessage({					 				
				message: 'aiovg-video-playing'
			}, window.location.origin );
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
					const markerIndex = markers.findIndex( ( { time: markerTime } ) => markerTime == root._formattedTimeToSeconds( time ) );
			
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
				if ( this.settings.player.controlBar.children.indexOf( 'QualitySelector' ) != -1 ) {
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

			// Logo / Trailer Badge
			let hasLogo    = this.settings.hasOwnProperty( 'logo' );
			let hasTrailer = this.settings.hasOwnProperty( 'trailer' );

			if ( hasLogo || hasTrailer ) {
				let align = 'bottom-left';
				let style = 'margin: 5px;';

				if ( hasLogo ) {
					if ( this.settings.logo.margin ) {
						this.settings.logo.margin = this.settings.logo.margin - 5;
					}
					
					style = 'margin: ' + this.settings.logo.margin + 'px;';

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
				}

				// Determine the href: trailer URL takes precedence when active.
				let logoHref = hasLogo ? this.settings.logo.link : 'javascript:void(0)';
				
				if ( hasTrailer && this.settings.trailer.url ) {
					logoHref = this.settings.trailer.url;
				}

				// Icon: logo image > external-link SVG (only when URL set) > nothing.
				let iconHtml = '';

				if ( hasLogo ) {
					iconHtml = '<img src="' + this.settings.logo.image + '" alt="" />';
				} else if ( hasTrailer && this.settings.trailer.url ) {
					iconHtml = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#fff" aria-hidden="true"><path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>';
				}

				// Label: visible trailer label or sr-only text for logo-only mode.
				let labelHtml   = hasTrailer ? '<span class="vjs-logo-label">' + this.settings.trailer.label + '</span>' : '<span class="vjs-control-text" aria-live="polite">Logo</span>';
				let logoContent = '<a href="' + logoHref + '" target="_top" style="' + style + '">'	+ iconHtml + labelHtml	+ '</a>';

				overlays.push({
					content: logoContent,
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
						wasPlaying = ! this.player.paused();
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
						
						if ( navigator.clipboard && navigator.clipboard.writeText ) {
							navigator.clipboard.writeText( this.value ).catch( () => {} );
						} else {
							document.execCommand( 'copy' );
						}
					});
				}
			}
		}

		_initHotKeys() {
			if ( this.settings.hotkeys ) {
				this.player.hotkeys();
			}
		}

		_initEmailCapture() {
			if ( ! this.emailCaptureEl ) return false;
			
			this.emailCaptureEl.style.backgroundImage = 'none';
			this.player.el().appendChild( this.emailCaptureEl );

			let isModalOpen = false;
			let wasPlaying  = false;

			const showEmailCapture = () => {
				if ( ! this.emailCaptureEl || isModalOpen ) return false;
				isModalOpen = true;

				if ( this.player && ! this.player.ended() ) {
					wasPlaying = ! this.player.paused();
					this.player.pause();
				}

				this.classList.add( 'aiovg-show-email-capture' );

				const firstInput = this.emailCaptureEl.querySelector( 'input' );
				if ( firstInput ) {
					firstInput.focus();
				}
			};

			const hideEmailCapture = () => {
				if ( ! this.emailCaptureEl || ! isModalOpen ) return false;
				isModalOpen = false; // Clear before player.play() so the guard above does not block it.

				this.classList.remove( 'aiovg-show-email-capture' );

				this.emailCaptureEl.remove();
				this.emailCaptureEl = null;

				if ( wasPlaying ) {
					this.player.play();
				}
			}

			const ajaxEmailCapture = ( data ) => {
				if ( ! this.emailCaptureEl ) return false;

				if ( this.emailCaptureEl.classList.contains( 'aiovg-is-loading' ) ) return false; // Prevent multiple submissions
				this.emailCaptureEl.classList.add( 'aiovg-is-loading' );

				const xmlhttp = new XMLHttpRequest();

				xmlhttp.onreadystatechange = () => {
					if ( 4 == xmlhttp.readyState ) {
						let response = null;

						if ( 200 == xmlhttp.status && xmlhttp.responseText ) {
							try {
								response = JSON.parse( xmlhttp.responseText );
							} catch ( e ) {}
						}

						if ( response && response.success ) {
							// Hide the form and remove it from the DOM to prevent it from being submitted again.
							hideEmailCapture();

							// Email submission implies consent — remove both gates from other players
							const videos = document.querySelectorAll( '.aiovg-player-element' );
							for ( let i = 0; i < videos.length; i++ ) {
								videos[ i ].removeCookieConsent();
								videos[ i ].removeEmailCapture();
							}

							window.postMessage({
								message: 'aiovg-email-captured'
							}, window.location.origin );
						} else {
							// Show error message
							const errorSpan = this.emailCaptureEl.querySelector( '.aiovg-email-capture-error' );

							if ( errorSpan ) {
								errorSpan.textContent   = ( response && response.data && typeof response.data === 'string' ) ? response.data : errorSpan.getAttribute( 'data-error_generic' );
								errorSpan.style.display = 'block';
							}

							// Restore the form so the user can retry.
							this.emailCaptureEl.classList.remove( 'aiovg-is-loading' );
						}
					}
				};

				xmlhttp.open( 'POST', this._ajaxUrl + '?action=aiovg_save_email_lead&security=' + this._ajaxNonce, true );
				xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
				xmlhttp.send( data );			
			};

			// Mid-roll — show when currentTime reaches the configured percentage.
			const midRoll = parseInt( this.emailCaptureEl.dataset.mid_roll );

			if ( midRoll ) {
				let midRollFired = false;

				const onMidRollTimeUpdate = () => {
					if ( ! this.emailCaptureEl ) {
						this.player.off( 'timeupdate', onMidRollTimeUpdate );
						return false;
					}

					if ( midRollFired ) return false;

					// Skip during ad playback — currentTime/duration reflect the ad, not the content.
					if ( this.settings.hasOwnProperty( 'ads' ) && this.player.ads && this.player.ads.isInAdMode() ) {
						return false;
					}

					const duration = this.player.duration();
					const current  = this.player.currentTime();

					if ( duration && current >= ( midRoll / 100 ) * duration ) {
						midRollFired = true;
						this.player.off( 'timeupdate', onMidRollTimeUpdate );
						showEmailCapture();
					}
				};

				this.player.on( 'timeupdate', onMidRollTimeUpdate );
			}

			// Post-roll — show after the video ends.
			const postRoll = parseInt( this.emailCaptureEl.dataset.post_roll );

			if ( postRoll ) {
				this.player.on( 'ended', () => {
					showEmailCapture();
				} );
			}

			// Re-pause on any play event while the overlay is open.
			this.player.on( 'play', () => {
				if ( ! this.emailCaptureEl || ! isModalOpen ) return false;

				wasPlaying = true;
				this.player.pause();
			} );

			this.player.on( 'playing', () => {
				if ( ! this.emailCaptureEl || ! isModalOpen ) return false;

				wasPlaying = true;
				this.player.pause();
			} );

			this._initEmailCaptureForm( ( data ) => ajaxEmailCapture( data ) );
		}

		_initEmailCaptureForm( onSubmit ) {
			const submitBtn = this.querySelector( '.aiovg-email-capture-submit' );
			const skipLink  = this.querySelector( '.aiovg-email-capture-skip' );

			if ( submitBtn ) {
				submitBtn.addEventListener( 'click', () => {
					const emailField = this.emailCaptureEl.querySelector( '.aiovg-email-capture-email' );
					const errorSpan  = this.emailCaptureEl.querySelector( '.aiovg-email-capture-error' );

					const email = emailField ? emailField.value.trim() : '';
					const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

					if ( ! email || ! regex.test( email ) ) {
						if ( errorSpan ) {
							const errorMessage = errorSpan.getAttribute( 'data-invalid_email' ) || 'Please enter a valid email address.';

							errorSpan.textContent = errorMessage;
							errorSpan.style.display = 'block';
						}

						if ( emailField ) {
							emailField.classList.add( 'aiovg-is-invalid' );
							emailField.setAttribute( 'aria-invalid', 'true' );
						}

						return false;
					}

					if ( errorSpan ) {
						errorSpan.style.display = 'none';
					}

					if ( emailField ) {
						emailField.classList.remove( 'aiovg-is-invalid' );
						emailField.removeAttribute( 'aria-invalid' );
					}

					let params = 'email=' + encodeURIComponent( email );

					const nameField = this.emailCaptureEl.querySelector( '.aiovg-email-capture-name' );
					if ( nameField && nameField.value.trim() ) {
						params += '&name=' + encodeURIComponent( nameField.value.trim() );
					}

					const phoneField = this.emailCaptureEl.querySelector( '.aiovg-email-capture-phone' );
					if ( phoneField && phoneField.value.trim() ) {
						params += '&phone=' + encodeURIComponent( phoneField.value.trim() );
					}

					if ( 'aiovg_videos' == this.settings.post_type && this.settings.post_id ) {
						params += '&video_id=' + encodeURIComponent( this.settings.post_id );
					} else {
						params += '&video_id=0';

						if ( this.emailCaptureEl.dataset.video_url ) {
							params += '&video_url=' + encodeURIComponent( this.emailCaptureEl.dataset.video_url );
						}
					}

					params += '&page_url=' + encodeURIComponent( window.location.href );

					onSubmit( params );
				} );
			}

			if ( skipLink ) {
				const doSkip = () => onSubmit( 'skipped=1' );

				skipLink.addEventListener( 'click', doSkip );

				skipLink.addEventListener( 'keydown', ( event ) => {
					if ( 13 === event.keyCode || 32 === event.keyCode ) {
						event.preventDefault();
						doSkip();
					}
				} );
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

		_formattedTimeToSeconds( time ) {
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
			$( this ).trigger( event, data ); 						
		}

		_fetch( data ) {
			$.post( this._ajaxUrl, data ); 						
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
			if ( this.settings.statistics && this.settings.post_type == 'aiovg_videos' ) {
				const data = {
					'action': 'aiovg_update_views_count',
					'post_id': this.settings.post_id,
					'duration': this.player.duration() || 0,
					'security': this._ajaxNonce
				};

				this._fetch( data );
			}
		}	

		/**
		 * Define public static methods.
		 */

		static isValidUrl( url ) {
			if ( url === '' ) return false;

			try {
				new URL( url );
				return true;
			} catch ( error ) {
				return false;
			}
		}

		/**
		 * Define API methods.
		 */

		removeCookieConsent() {
			if ( ! this.cookieConsentEl ) return false;

			this.cookieConsentEl.remove();
			this.cookieConsentEl = null;

			this._initPlayer();
		}

		removeEmailCapture() {
			if ( ! this.emailCaptureEl ) return false;

			this.classList.remove( 'aiovg-show-email-capture' );

			this.emailCaptureEl.remove();
			this.emailCaptureEl = null;

			if ( ! this.player ) {
				this._initPlayer();
			}
		}

		pause() {
			if ( this.player ) {
				this.player.pause();
			}
		}

		seekTo( seconds ) {
			if ( this.player ) {
				this.player.currentTime( seconds );
				if ( ! this._hasVideoStarted ) {
					this.player.play();
				}
			}
		}

	}

	/**
	 * Called when the page has loaded.
	 */
	$(function() {
		
		// Register custom element
		if ( ! customElements.get( 'aiovg-video' ) ) {
			customElements.define( 'aiovg-video', AIOVGVideoElement );
		}

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

		// Listen to the iframe player events
		window.addEventListener( 'message', function( event ) {
			if ( event.origin != window.location.origin ) {
				return false;
			}
	
			if ( ! event.data.hasOwnProperty( 'context' ) || event.data.context != 'iframe' ) {
				return false;
			}

			if ( ! event.data.hasOwnProperty( 'message' ) ) {
				return false;
			}			

			if ( event.data.message == 'aiovg-cookie-consent' ) {
				const videos = document.querySelectorAll( '.aiovg-player-element' );
				for ( let i = 0; i < videos.length; i++ ) {
					videos[ i ].removeCookieConsent();
				}
			}

			if ( event.data.message == 'aiovg-email-captured' ) {
				const videos = document.querySelectorAll( '.aiovg-player-element' );
				for ( let i = 0; i < videos.length; i++ ) {
					videos[ i ].removeEmailCapture();
				}
			}

			if ( event.data.message == 'aiovg-video-playing' ) {
				const videos = document.querySelectorAll( '.aiovg-player-element' );
				for ( let i = 0; i < videos.length; i++ ) {
					videos[ i ].pause();
				}
			}
		});

	});

})( jQuery );
