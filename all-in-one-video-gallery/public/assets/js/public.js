(function( $ ) {	
	'use strict';

	/**
	 * Load css files.
	 */
	const stylePromises = {};

	window.aiovgLoadStyle = ( file ) => {
		if ( ! file || ! file.id ) {
			return Promise.resolve();
		}
		
		if ( stylePromises[ file.id ] ) {
			return stylePromises[ file.id ];
		}

		if ( document.getElementById( file.id ) ) {
			return Promise.resolve();
		}

		stylePromises[ file.id ] = new Promise(( resolve, reject ) => {
			const link = document.createElement( 'link' );

			link.id      = file.id;
			link.rel     = 'stylesheet';
			link.href    = file.href;
			link.onload  = resolve;
			link.onerror = reject;

			document.head.appendChild( link );
		});

		return stylePromises[ file.id ];
	};

	/**
	 * Load script files.
	 */
	const scriptPromises = {};

	window.aiovgLoadScript = ( file ) => {
		if ( ! file || ! file.id ) {
			return Promise.resolve();
		}
		
		if ( scriptPromises[ file.id ] ) {
			return scriptPromises[ file.id ];
		}

		if ( document.getElementById( file.id ) ) {
			return Promise.resolve();
		}

		scriptPromises[ file.id ] = new Promise(( resolve, reject ) => {
			const script = document.createElement( 'script' );

			script.id      = file.id;
			script.src     = file.src;
			script.defer   = true;
			script.onload  = resolve;
            script.onerror = reject;

			document.body.appendChild( script );
		});

		return scriptPromises[ file.id ];
	};

	/**
	 * Called when the page has loaded.
	 */
	$(function() {

		/**
		 * Init assets.
		 */
		const plugin_url     = aiovg_public.plugin_url;
		const plugin_version = aiovg_public.plugin_version;

		const assets = [
			{
				selector: '.aiovg-categories-template-dropdown', 
				script: {
					id: 'all-in-one-video-gallery-categories-js',
					src: plugin_url + 'public/assets/js/categories.min.js?ver=' + plugin_version
				}
			},
			{
				selector: '.aiovg-search-form-mode-live, .aiovg-search-form-mode-ajax', 
				script: {
					id: 'all-in-one-video-gallery-search-js',
					src: plugin_url + 'public/assets/js/search.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: '.aiovg-dropdown-terms, .aiovg-autocomplete', 
				script: {
					id: 'all-in-one-video-gallery-select-js',
					src: plugin_url + 'public/assets/js/select.min.js?ver=' + plugin_version
				}
			}, 
			{
				selector: 'aiovg-pagination, .aiovg-more-ajax, .aiovg-pagination-ajax', 
				script: {
					id: 'all-in-one-video-gallery-pagination-js',
					src: plugin_url + 'public/assets/js/pagination.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: 'aiovg-like-button', 
				script: {
					id: 'all-in-one-video-gallery-likes-js',
					src: plugin_url + 'public/assets/js/likes.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: 'aiovg-playlist-button', 
				script: {
					id: 'all-in-one-video-gallery-playlists-js',
					src: plugin_url + 'premium/public/assets/js/playlists.min.js?ver=' + plugin_version
				}
			}, 
			{ 
				selector: 'aiovg-template-compact, .aiovg-videos-template-compact', 
				script: {
					id: 'all-in-one-video-gallery-template-compact-js',
					src: plugin_url + 'premium/public/assets/js/template-compact.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: 'aiovg-template-popup, .aiovg-videos-template-popup, .aiovg-video-template-popup', 
				script: {
					id: 'all-in-one-video-gallery-template-popup-js',
					src: plugin_url + 'premium/public/assets/js/template-popup.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: 'aiovg-template-inline, .aiovg-videos-template-inline', 
				script: {
					id: 'all-in-one-video-gallery-template-inline-js',
					src: plugin_url + 'premium/public/assets/js/template-inline.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: 'aiovg-template-slider, aiovg-template-slider-compact, aiovg-template-slider-popup, aiovg-template-slider-inline, .aiovg-videos-template-slider', 
				script: {
					id: 'all-in-one-video-gallery-template-slider-js',
					src: plugin_url + 'premium/public/assets/js/template-slider.min.js?ver=' + plugin_version
				}
			},
			{ 
				selector: 'aiovg-template-playlist, .aiovg-videos-template-playlist', 
				script: {
					id: 'all-in-one-video-gallery-template-playlist-js',
					src: plugin_url + 'premium/public/assets/js/template-playlist.min.js?ver=' + plugin_version
				}
			}
		];

		/**
		 * Scan DOM and load required assets.
		 */
		const loadAssets = ( root = document ) => {
			if ( ! root || ( root.nodeType !== 1 && root !== document ) ) {
				return;
			}

			for ( const asset of assets ) {
				if ( root.matches?.( asset.selector ) || root.querySelector( asset.selector ) ) {
					if ( asset.style ) window.aiovgLoadStyle( asset.style );
					if ( asset.script ) window.aiovgLoadScript( asset.script );
				}
			}
		};

		/**
		 * Initial scan (page load).
		 */
		loadAssets( document );

		/**
		 * Observe dynamically added elements (Elementor, DIVI, AJAX).
		 */
		const observer = new MutationObserver(( mutations ) => {
			for ( const mutation of mutations ) {
				for ( const node of mutation.addedNodes ) {
					loadAssets( node );
				}
			}
		});

		observer.observe( document.body, {
			childList: true,
			subtree: true
		});

	});

})( jQuery );
