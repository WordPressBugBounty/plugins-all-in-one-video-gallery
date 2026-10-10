(function() {
	'use strict';

	class AIOVGVideoElement extends HTMLElement {

		/**
		 * Element created.
		 */
		constructor() {
			super();        
			
			// Set references to the private properties used by the component
			this._isRendered = false;
			this._isCookieConsentLoaded = false;
			this._isPlayerLoaded = false;
			this._hasVideoStarted = false;
			this._player = null;
			this._playerEl = null;
			this._cookieConsentEl = null;
			this._emailCaptureEl = null;
			this._intersectionObserver = null;
			this._isInViewport = false;
			this._playerId = '';
			this._params = {};
		}

		/**
		 * Browser calls this method when the element is added to the document.
		 * (can be called many times if an element is repeatedly added/removed)
		 */
		connectedCallback() {
			this._playerId = this.dataset.id || '';
			this._params = this.dataset.params ? JSON.parse( this.dataset.params ) : {};

			this._render();
		}
	
		/**
		 * Define getters and setters for attributes.
		 */

		get cookieConsent() {
			return this.hasAttribute( 'cookieconsent' );
		}

		set cookieConsent( value ) {
			const isEnabled = Boolean( value );

			if ( isEnabled ) {
				this.setAttribute( 'cookieconsent', '' );
			} else {
				this.removeAttribute( 'cookieconsent' );
			}
		}    

		get emailCapture() {
			return this.hasAttribute( 'emailcapture' );
		}

		set emailCapture( value ) {
			const isEnabled = Boolean( value );

			if ( isEnabled ) {
				this.setAttribute( 'emailcapture', '' );
			} else {
				this.removeAttribute( 'emailcapture' );
			}
		} 

		/**
		 * Define private methods.
		 */

		_render() {
			if ( this._isRendered ) return false;

			if ( this._params.lazyloading && ! this._isInViewport ) {
				this._initIntersectionObserver();
				return false;
			}

			if ( this.emailCapture ) {
				this._emailCaptureEl = this.querySelector( '.aiovg-email-capture' );

				if ( this._emailCaptureEl ) {
					if ( parseInt( this._emailCaptureEl.dataset.pre_roll ) ) {
						this._initPreRollEmailCapture();
						return false;
					}
				} else {
					this.emailCapture = false;
				}
			}

			if ( this.cookieConsent ) {
				this._addCookieConsent();
				return false;
			}

			this._isRendered = true;

			this._addPlayer();
		}

		_addCookieConsent() {
			if ( this._isCookieConsentLoaded ) return false; 
			this._isCookieConsentLoaded = true;

			this._cookieConsentEl = this.querySelector( '.aiovg-privacy-wrapper' );

			// Not rendered when a pre-roll email capture took its place, and a capture implies consent.
			if ( ! this._cookieConsentEl ) {
				this.cookieConsent = false;
				this._isRendered = true;
				this._addPlayer();
				return false;
			}
			
			const poster = this._cookieConsentEl.getAttribute( 'data-poster' ) || '';
			if ( AIOVGVideoElement.isValidUrl( poster ) ) {
				this._cookieConsentEl.style.backgroundImage = `url("${poster}")`; 
			} 

			this._cookieConsentEl.querySelector( '.aiovg-privacy-consent-button' ).addEventListener( 'click', () => this._onCookieConsent() );
		}

		_onCookieConsent() {
			this._isRendered = true;

			this._cookieConsentEl.remove();  
			this.cookieConsent = false;   
			
			this._params.player.autoplay = true;
			this._addPlayer();  

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

			const poster = this._emailCaptureEl.getAttribute( 'data-poster' ) || '';
			if ( AIOVGVideoElement.isValidUrl( poster ) ) {
				this._emailCaptureEl.style.backgroundImage = `url("${poster}")`; 
			} 

			const firstInput = this._emailCaptureEl.querySelector( 'input' );
			if ( firstInput && ! firstInput.value ) {
				firstInput.focus();
			}

			this._initEmailCaptureForm( ( data ) => {
				if ( ! this._emailCaptureEl || this._isRendered ) return false;

				if ( this._emailCaptureEl.classList.contains( 'aiovg-is-loading' ) ) return false; // Prevent multiple submissions
				this._emailCaptureEl.classList.add( 'aiovg-is-loading' );

				// Per-video scoping, read while the form is still in the DOM — it is removed
				// before the response arrives. An empty key means "once per visitor", where
				// one capture clears every player on the page.
				const captureKey = this._emailCaptureEl.dataset.capture_key || '';

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
							this._isRendered = true;

							this.classList.remove( 'aiovg-show-email-capture' );

							this._emailCaptureEl.remove();
							this._emailCaptureEl = null;

							this.emailCapture = false;

							// Render player
							this._params.player.autoplay = true;
							this._addPlayer();

							// A skip the server did not remember clears only the consent gates.
							const remembered = ! ( response.data && false === response.data.remembered );

							// Email submission implies consent — remove both gates from other players
							const videos = document.querySelectorAll( '.aiovg-player-element' );
							for ( let i = 0; i < videos.length; i++ ) {
								videos[ i ].removeCookieConsent();

								if ( remembered ) {
									videos[ i ].removeEmailCapture( captureKey );
								}
							}

							window.postMessage( remembered ? {
								message: 'aiovg-email-captured',
								capture_key: captureKey
							} : {
								message: 'aiovg-cookie-consent'
							}, window.location.origin );
						} else {
							// Show error message
							const errorSpan = this._emailCaptureEl.querySelector( '.aiovg-email-capture-error' );

							if ( errorSpan ) {
								errorSpan.textContent   = ( response && response.data && typeof response.data === 'string' ) ? response.data : errorSpan.getAttribute( 'data-error_generic' );
								errorSpan.style.display = 'block';
							}

							// Restore the form so the user can retry.
							this._emailCaptureEl.classList.remove( 'aiovg-is-loading' );
							return false;
						}
					}
				};

				xmlhttp.open( 'POST', this._params.ajax_url + '?action=aiovg_save_email_lead&security=' + this._params.ajax_nonce, true );
				xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
				xmlhttp.send( data );
			} );
		}

		_addPlayer() {
			if ( this._isPlayerLoaded ) return false; 
			this._isPlayerLoaded = true;

			// Scoped lookup: separately rendered block previews can repeat the same ID.
			const video = this.querySelector( '#' + this._playerId );
			if ( ! video ) return false; // No video to play, e.g. none published yet
			
			this._player = new Plyr( video, this._params.player );

			this._playerEl = this.querySelector( '.plyr' );
			
			// Dispatch an event
			const options = {	
				player: this._player,
				settings: this._params					
			};

			this.dispatchEvent(new CustomEvent('player.init', {
				detail: options,
				bubbles: true,
				cancelable: true,
			}));

			this._disableYouTubeCaptions();

			// On ready
			this._player.on( 'ready', () => {
				// Insert custom spacer
				const restartButton = this._player.elements.container.querySelector( '[data-plyr="restart"]' );

				if ( restartButton ) {
					const spacer = document.createElement( 'div' );
					spacer.className = 'plyr__controls__item plyr__spacer';
					spacer.setAttribute( 'aria-hidden', 'true' );

					// Replace the restart button with the spacer
					restartButton.parentNode.replaceChild( spacer, restartButton );
				}

				// Share / Embed				
				this._initShareEmbed();

				// Logo
				this._initLogo();

				// Trailer Badge
				this._initTrailerBadge();

				// Email Capture
				this._initEmailCapture();
			});

			// Update views count
			this._player.on( 'playing', () => {
				if ( ! this._hasVideoStarted ) {
					this._hasVideoStarted = true;
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
			});

			// On ended
			this._player.on( 'ended', () => {
				this._playerEl.className += ' plyr--stopped';

				// Plyr also sets plyr--stopped on the poster, so it can't mark the end.
				this._playerEl.classList.add( 'aiovg-is-ended' );
			});

			this._player.on( 'play', () => {
				this._playerEl.classList.remove( 'aiovg-is-ended' );
			});

			// HLS
			if ( this._params.hasOwnProperty( 'hls' ) ) {
				const hls = new Hls();
				hls.loadSource( this._params.hls );
				hls.attachMedia( video );

				// Handle changing captions
				this._player.on( 'languagechange', () => {
					setTimeout( () => hls.subtitleTrack = this._player.currentTrack, 50 );
				});
			}

			// Dash
			if ( this._params.hasOwnProperty( 'dash' ) ) {
				const dash = dashjs.MediaPlayer().create();
				dash.initialize( video, this._params.dash, this._params.player.autoplay || false );
			}

			// Init Ads
			if ( this._player.ads ) {
				this._initAds();					
			}

			// Custom ContextMenu
			if ( this._params.hasOwnProperty( 'contextmenu' ) ) {
				this._initContextMenu();
			}
		}

		_disableYouTubeCaptions() {
			const youtube = this._params.player.youtube;
			if ( ! youtube || 0 !== youtube.cc_load_policy ) return;

			// YouTube can switch captions on by itself even with cc_load_policy=0,
			// so unload its captions module once the player is ready and on play.
			const disable = () => {
				const embed = this._player.embed;
				if ( ! embed || 'function' !== typeof embed.unloadModule ) return;

				try { embed.setOption( 'captions', 'track', {} ); } catch ( e ) {}
				try { embed.unloadModule( 'captions' ); } catch ( e ) {}
				try { embed.unloadModule( 'cc' ); } catch ( e ) {}
			};

			const disableWithRetries = () => {
				[ 0, 300, 1200 ].forEach( ( delay ) => setTimeout( disable, delay ) );
			};

			this._player.on( 'ready', disableWithRetries );
			this._player.on( 'playing', disableWithRetries );
		}

		_initAds() {
			this._player.ads.config.tagUrl = this._getVastUrl();
			
			let loaded = false;

			this._player.ads.on( 'loaded', () => {			
				if ( loaded ) return false;
				loaded = true;                        

				const adsManager = this._player.ads.manager;

				let playButton = document.createElement( 'button' );
				playButton.type = 'button';
				playButton.className = 'plyr__control plyr__control--overlaid';
				playButton.style.display = 'none';
				playButton.innerHTML = '<svg aria-hidden="true" focusable="false"><use href="' + this._params.player.iconUrl + '#plyr-play"></use></svg><span class="plyr__sr-only">Play</span>';
				
				this.querySelector( '.plyr__ads' ).appendChild( playButton );                        

				playButton.addEventListener( 'click', () => {
					playButton.style.display = 'none';
					adsManager.resume();
				});

				adsManager.addEventListener( google.ima.AdEvent.Type.STARTED, ( event ) => {
					this.classList.add( 'aiovg-ads-playing' );

					if ( this._params.player.ads.companion ) {
						this._initCompanionAds( event );
					}								
				});

				adsManager.addEventListener( google.ima.AdEvent.Type.PAUSED, ( event ) => {
					playButton.style.display = '';
				});

				adsManager.addEventListener( google.ima.AdEvent.Type.RESUMED, ( event ) => {
					playButton.style.display = 'none';
				});

				adsManager.addEventListener( google.ima.AdEvent.Type.CONTENT_RESUME_REQUESTED, ( event ) => {
					this.classList.remove( 'aiovg-ads-playing' );
				});
			});
		}

		_getVastUrl() {
			let url = this._params.player.ads.tagUrl;

			url = url.replace( '[domain]', encodeURIComponent( this._params.site_url ) );
			url = url.replace( '[player_width]', this._player.elements.container.offsetWidth );
			url = url.replace( '[player_height]', this._player.elements.container.offsetHeight );
			url = url.replace( '[random_number]', Date.now() );
			url = url.replace( '[timestamp]', Date.now() );
			url = url.replace( '[page_url]', encodeURIComponent( window.location ) );
			url = url.replace( '[referrer]', encodeURIComponent( document.referrer ) );
			url = url.replace( '[ip_address]', this._params.ip_address );
			url = url.replace( '[post_id]', this._params.post_id );
			url = url.replace( '[post_title]', encodeURIComponent( this._params.post_title ) );
			url = url.replace( '[post_excerpt]', encodeURIComponent( this._params.post_excerpt ) );
			url = url.replace( '[video_file]', encodeURIComponent( this._player.source ) );
			url = url.replace( '[video_duration]', this._player.duration || '' );
			url = url.replace( '[autoplay]', this._params.player.autoplay || false );

			return url;
		}

		_initCompanionAds( event ) {
			const ad = event.getAd();					
			let elements = [];

			try {
				elements = window.AIOVGGetCompanionElements();
			} catch ( error ) { 
				/** console.log( error ); */
			}
			
			if ( elements.length ) {		
				let criteria = new google.ima.CompanionAdSelectionSettings();
				criteria.resourceType = google.ima.CompanionAdSelectionSettings.ResourceType.ALL;
				criteria.creativeType = google.ima.CompanionAdSelectionSettings.CreativeType.ALL;
				criteria.sizeCriteria = google.ima.CompanionAdSelectionSettings.SizeCriteria.SELECT_NEAR_MATCH;        
				
				for ( let i = 0; i < elements.length; i++ ) {													
					let id     = elements[ i ].id;
					let width  = elements[ i ].width;
					let height = elements[ i ].height;
					
					try {
						// Get a list of companion ads for an ad slot size and CompanionAdSelectionSettings
						const companionAds = ad.getCompanionAds( width, height, criteria );
						let companionAd = companionAds[0];
					
						// Get HTML content from the companion ad.
						const content = companionAd.getContent();
				
						// Write the content to the companion ad slot.
						let div = document.getElementById( id );
						div.innerHTML = content;
					} catch ( adError ) { 
						/** console.log( error ); */
					}				
				}
			}
		}    

		_initShareEmbed() {
			if ( ! this._params.hasOwnProperty( 'share' ) && ! this._params.hasOwnProperty( 'embed' ) ) {					
				return false;
			}

			let shareButton = document.createElement( 'button' );
			shareButton.type = 'button';
			shareButton.className = 'plyr__controls__item plyr__control plyr__share-embed-button aiovg-icon-share';
			shareButton.innerHTML = '<span class="plyr__sr-only">Share</span>';

			this._playerEl.appendChild( shareButton );	
				
			let closeButton = this.querySelector( '.plyr__share-embed-modal-close-button' );

			let modal = this.querySelector( '.plyr__share-embed-modal' );
			this._playerEl.appendChild( modal );	
			modal.style.display = '';

			// Show Modal
			let wasPlaying = false;

			shareButton.addEventListener( 'click', () => {
				if ( this._player.playing ) {
					wasPlaying = true;
					this._player.pause();
				} else {
					wasPlaying = false;
				}                    

				shareButton.style.display = 'none';						
				modal.className += ' fadein';				
			});

			// Hide Modal
			closeButton.addEventListener( 'click', () => {
				if ( wasPlaying ) {
					this._player.play();
				}
				
				modal.className = modal.className.replace( ' fadein', '' );
				setTimeout(function() {
					shareButton.style.display = ''; 
				}, 500 );					                           	
			});

			// Copy Embedcode
			if ( this._params.hasOwnProperty( 'embed' ) ) {
				this.querySelector( '.plyr__embed-code-input' ).addEventListener( 'focus', function() {
					this.select();
					
					if ( navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( this.value ).catch( () => {} );
					} else {
						document.execCommand( 'copy' );
					}
				});
			}
		}

		_initLogo() {
			if ( ! this._params.hasOwnProperty( 'logo' ) ) {
				return false;
			}

			let style = 'bottom:50px; left:' + this._params.logo.margin + 'px;';

			switch ( this._params.logo.position ) {
				case 'topleft':
					style = 'top:' + this._params.logo.margin + 'px; left:' + this._params.logo.margin + 'px;';
					break;

				case 'topright':
					style = 'top:' + this._params.logo.margin + 'px; right:' + this._params.logo.margin + 'px;';
					break;

				case 'bottomright':
					style = 'bottom:50px; right:' + this._params.logo.margin + 'px;';
					break;
			}

			const logo = document.createElement( 'div' );
			logo.className = 'plyr__logo';
			logo.innerHTML = '<a href="' + this._params.logo.link + '" target="_top" style="' + style + '"><img src="' + this._params.logo.image + '" alt="" /><span class="plyr__sr-only">Logo</span></a>';

			this._playerEl.appendChild( logo );
		}

		_initTrailerBadge() {
			if ( ! this._params.hasOwnProperty( 'trailer' ) ) {
				return false;
			}

			const trailer = this._params.trailer;

			const badge = document.createElement( trailer.url ? 'a' : 'span' );
			badge.className = 'aiovg-trailer-badge';
			badge.textContent = trailer.label;

			if ( trailer.url ) {
				badge.href = trailer.url;
				badge.target = '_top';

				// Turns the badge into a button when the trailer ends.
				this._playerEl.classList.add( 'aiovg-has-trailer-cta' );
			}

			// Keep clear of a top-left logo.
			if ( this._params.hasOwnProperty( 'logo' ) && 'topleft' == this._params.logo.position ) {
				badge.classList.add( 'aiovg-trailer-badge-bottom' );
			}

			this._playerEl.appendChild( badge );
		}

		_initEmailCapture() {
			if ( ! this.emailCapture || ! this._emailCaptureEl ) return false;

			this._emailCaptureEl.style.backgroundImage = 'none';
			this._playerEl.appendChild( this._emailCaptureEl );

			let isModalOpen = false;
			let wasPlaying  = false;

			const showEmailCapture = () => {
				if ( ! this._emailCaptureEl || isModalOpen ) return false;
				isModalOpen = true;

				if ( ! this._player.ended ) {
					wasPlaying = ! this._player.paused || this._player.seeking;
					this._player.pause();
				}

				this.classList.add( 'aiovg-show-email-capture' );

				const firstInput = this._emailCaptureEl.querySelector( 'input' );
				if ( firstInput && ! firstInput.value ) {
					firstInput.focus();
				}
			};

			const hideEmailCapture = () => {
				if ( ! this._emailCaptureEl || ! isModalOpen ) return false;
				isModalOpen = false; // Clear before player.play() so the guard above does not block it.

				this.classList.remove( 'aiovg-show-email-capture' );

				this._emailCaptureEl.remove();
				this._emailCaptureEl = null;

				this.emailCapture = false;

				if ( wasPlaying ) {
					this._player.play();
				}
			};

			const ajaxEmailCapture = ( data ) => {
				if ( ! this._emailCaptureEl ) return false;

				if ( this._emailCaptureEl.classList.contains( 'aiovg-is-loading' ) ) return false; // Prevent multiple submissions
				this._emailCaptureEl.classList.add( 'aiovg-is-loading' );

				// Per-video scoping, read while the form is still in the DOM — it is removed
				// before the response arrives. An empty key means "once per visitor", where
				// one capture clears every player on the page.
				const captureKey = this._emailCaptureEl.dataset.capture_key || '';

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

							// A skip the server did not remember clears only the consent gates.
							const remembered = ! ( response.data && false === response.data.remembered );

							// Email submission implies consent — remove both gates from other players
							const videos = document.querySelectorAll( '.aiovg-player-element' );
							for ( let i = 0; i < videos.length; i++ ) {
								videos[ i ].removeCookieConsent();

								if ( remembered ) {
									videos[ i ].removeEmailCapture( captureKey );
								}
							}

							window.postMessage( remembered ? {
								message: 'aiovg-email-captured',
								capture_key: captureKey
							} : {
								message: 'aiovg-cookie-consent'
							}, window.location.origin );
						} else {
							// Show error message
							const errorSpan = this._emailCaptureEl.querySelector( '.aiovg-email-capture-error' );

							if ( errorSpan ) {
								errorSpan.textContent   = ( response && response.data && typeof response.data === 'string' ) ? response.data : errorSpan.getAttribute( 'data-error_generic' );
								errorSpan.style.display = 'block';
							}

							// Restore the form so the user can retry.
							this._emailCaptureEl.classList.remove( 'aiovg-is-loading' );
						}
					}
				};

				xmlhttp.open( 'POST', this._params.ajax_url + '?action=aiovg_save_email_lead&security=' + this._params.ajax_nonce, true );
				xmlhttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
				xmlhttp.send( data );
			};

			// Mid-roll — show when currentTime reaches the configured percentage.
			const midRoll = parseInt( this._emailCaptureEl.dataset.mid_roll );

			if ( midRoll ) {
				let midRollFired = false;

				const onMidRollTimeUpdate = () => {
					if ( ! this._emailCaptureEl ) {
						this._player.off( 'timeupdate', onMidRollTimeUpdate );
						return false;
					}

					if ( midRollFired ) return false;

					const duration = this._player.duration;
					const current  = this._player.currentTime;

					if ( duration && current >= ( midRoll / 100 ) * duration ) {
						midRollFired = true;
						this._player.off( 'timeupdate', onMidRollTimeUpdate );
						showEmailCapture();
					}
				};

				this._player.on( 'timeupdate', onMidRollTimeUpdate );
			}

			// Post-roll — show after the video ends (suppressed when autoadvance is active),
			// unless a trailer ends on its button instead.
			const postRoll = parseInt( this._emailCaptureEl.dataset.post_roll );
			const hasTrailerButton = this._params.hasOwnProperty( 'trailer' ) && this._params.trailer.url;

			if ( postRoll && ! hasTrailerButton ) {
				this._player.on( 'ended', () => {
					showEmailCapture();
				} );
			}

			// Re-pause on any play event while the overlay is open.
			this._player.on( 'play', () => {
				if ( ! this._emailCaptureEl || ! isModalOpen ) return false;

				wasPlaying = true;
				this._player.pause();
			} );

			this._player.on( 'playing', () => {
				if ( ! this._emailCaptureEl || ! isModalOpen ) return false;

				wasPlaying = true;
				this._player.pause();
			} );
			
			this._initEmailCaptureForm( ( data ) => ajaxEmailCapture( data ) );
		}

		_initEmailCaptureForm( onSubmit ) {
			const submitBtn = this._emailCaptureEl.querySelector( '.aiovg-email-capture-submit' );
			const skipLink  = this._emailCaptureEl.querySelector( '.aiovg-email-capture-skip' );

			// Which video this is. The skip path sends it too: without it the server
			// cannot derive a capture key, and "Once per video" would never remember
			// the skip, asking again on every play.
			const videoParams = () => {
				if ( 'aiovg_videos' == this._params.post_type && this._params.post_id ) {
					return '&video_id=' + encodeURIComponent( this._params.post_id );
				}

				let params = '&video_id=0';

				if ( this._emailCaptureEl && this._emailCaptureEl.dataset.video_url ) {
					params += '&video_url=' + encodeURIComponent( this._emailCaptureEl.dataset.video_url );
				}

				return params;
			};

			if ( submitBtn ) {
				submitBtn.addEventListener( 'click', () => {
					const emailField = this._emailCaptureEl.querySelector( '.aiovg-email-capture-email' );
					const errorSpan  = this._emailCaptureEl.querySelector( '.aiovg-email-capture-error' );

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

					const nameField = this._emailCaptureEl.querySelector( '.aiovg-email-capture-name' );
					if ( nameField && nameField.value.trim() ) {
						params += '&name=' + encodeURIComponent( nameField.value.trim() );
					}

					const phoneField = this._emailCaptureEl.querySelector( '.aiovg-email-capture-phone' );
					if ( phoneField && phoneField.value.trim() ) {
						params += '&phone=' + encodeURIComponent( phoneField.value.trim() );
					}

					params += videoParams();
					params += '&page_url=' + encodeURIComponent( window.location.href );

					onSubmit( params );
				} );
			}

			if ( skipLink ) {
				const doSkip = () => onSubmit( 'skipped=1' + videoParams() );

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
			if ( ! window.AIOVGIsContextMenuAdded ) {
				window.AIOVGIsContextMenuAdded = true;

				let contextmenu = document.createElement( 'div' );
				contextmenu.id = 'aiovg-contextmenu';
				contextmenu.style.display = 'none';
				contextmenu.innerHTML = '<div class="aiovg-contextmenu-content">' + this._params.contextmenu.content + '</div>'; 

				document.body.appendChild( contextmenu );
			}

			const contextmenuEl = document.getElementById( 'aiovg-contextmenu' );
			let timeoutHandler = '';
			
			this._playerEl.addEventListener( 'contextmenu', function( e ) {						
				if ( e.keyCode == 3 || e.which == 3 ) {
					e.preventDefault();
					e.stopPropagation();
					
					const width = contextmenuEl.offsetWidth,
						height = contextmenuEl.offsetHeight,
						x = e.pageX,
						y = e.pageY,
						doc = document.documentElement,
						scrollLeft = ( window.pageXOffset || doc.scrollLeft ) - ( doc.clientLeft || 0 ),
						scrollTop = ( window.pageYOffset || doc.scrollTop ) - ( doc.clientTop || 0 ),
						left = x + width > window.innerWidth + scrollLeft ? x - width : x,
						top = y + height > window.innerHeight + scrollTop ? y - height : y;
			
					contextmenuEl.style.display = '';
					contextmenuEl.style.left = left + 'px';
					contextmenuEl.style.top = top + 'px';
					
					clearTimeout( timeoutHandler );
					timeoutHandler = setTimeout(function() {
						contextmenuEl.style.display = 'none';
					}, 1500 );				
				}														 
			});
			
			document.addEventListener( 'click', () => {
				contextmenuEl.style.display = 'none';								 
			});
		}
		
		_initIntersectionObserver() {
			if ( this._intersectionObserver ) return false;

			const options = {
				root: null,
				rootMargin: '0px',
				threshold: 0
			};

			this._intersectionObserver = new IntersectionObserver(( entries, observer ) => {
				entries.forEach(entry => {
					if ( entry.isIntersecting ) {
						this._isInViewport = true;
						this._render();
						
						if ( this._isRendered ) observer.unobserve( this );
					} else {
						this._isInViewport = false;
					}
				});
			}, options);

			this._intersectionObserver.observe( this );
		} 
	
		/**
		 * Define private async methods.
		 */

		async _updateViewsCount() {
			if ( this._params.statistics && this._params.post_type == 'aiovg_videos' ) {
				let formData = new FormData();
				formData.append( 'action', 'aiovg_update_views_count' );
				formData.append( 'post_id', parseInt( this._params.post_id ) );
				formData.append( 'duration', ( this._player.duration || 0 ) );			
				formData.append( 'security', this._params.ajax_nonce );

				fetch( this._params.ajax_url, { method: 'POST', body: formData } );
			}
		}
		
		async _setCookie() {
			let formData = new FormData();
			formData.append( 'action', 'aiovg_set_cookie' );
			formData.append( 'security', this._params.ajax_nonce );

			fetch( this._params.ajax_url, { method: 'POST', body: formData } );
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
			if ( this._isRendered ) return false;
			if ( ! this._cookieConsentEl ) return false;

			this._cookieConsentEl.remove();
			this.cookieConsent = false;

			this._render();
		}

		removeEmailCapture( captureKey ) {
			if ( ! this._emailCaptureEl ) return false;

			// Per-video scoping. No key means "once per visitor", where any capture clears
			// every player; a key clears only the player showing that same video.
			if ( captureKey && this._emailCaptureEl.dataset.capture_key !== captureKey ) {
				return false;
			}

			this.classList.remove( 'aiovg-show-email-capture' );

			this._emailCaptureEl.remove();
			this._emailCaptureEl = null;

			this.emailCapture = false;

			if ( ! this._isRendered ) {
				this._render();
			}
		}

		pause() {
			if ( this._player ) {
				this._player.pause();
			}
		}

		seekTo( seconds ) {
			if ( this._player ) {
				this._player.currentTime = seconds;
				if ( ! this._hasVideoStarted ) {
					this._player.play();
				}
			}
		}

	}

	window.AIOVGIsContextMenuAdded = false;

	/**
	 * Called when the page has loaded.
	 */
	document.addEventListener( 'DOMContentLoaded', function() {
		// Register custom element
		if ( ! customElements.get( 'aiovg-video' ) ) {
			customElements.define( 'aiovg-video', AIOVGVideoElement );
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
					// Consent is site-wide, so it clears everywhere — unlike the email
					// capture, which is scoped by key. Matches what the producers do for
					// players in their own window; this covers a capture inside an iframe.
					videos[ i ].removeCookieConsent();
					videos[ i ].removeEmailCapture( event.data.capture_key || '' );
				}
			}

			if ( event.data.message == 'aiovg-video-playing' ) {
				const videos = document.querySelectorAll( 'aiovg-video' );
				for ( let i = 0; i < videos.length; i++ ) {
					videos[ i ].pause();
				}
			}
		});
	});
	
})();
