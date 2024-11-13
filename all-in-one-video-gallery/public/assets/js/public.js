(function( $ ) {	
	'use strict';

	// Load script files.
	var loadScript = ( file ) => {
		return new Promise(( resolve, reject ) => { 
			if ( document.querySelector( '#' + file.id ) !== null ) {
				resolve();
				return false;
			}

			const script = document.createElement( 'script' );

			script.id  = file.id;
			script.src = file.src;

			script.onload  = () => resolve();
			script.onerror = () => reject();

			document.body.appendChild( script );
		});
	}

	/**
	 * Called when the page has loaded.
	 */
	$(function() {

		// Load the required script files.
		var plugin_url = aiovg_public.plugin_url;
		var plugin_version = aiovg_public.plugin_version;

		var scripts = [
			{ 
				selector: '.aiovg-autocomplete', 
				id: 'all-in-one-video-gallery-select-js',
				src: plugin_url + 'public/assets/js/select.min.js?ver=' + plugin_version
			}, 
			{
				selector: '.aiovg-more-ajax', 
				id: 'all-in-one-video-gallery-pagination-js',
				src: plugin_url + 'public/assets/js/pagination.min.js?ver=' + plugin_version 
			},
			{
				selector: '.aiovg-pagination-ajax',
				id: 'all-in-one-video-gallery-pagination-js', 
				src: plugin_url + 'public/assets/js/pagination.min.js?ver=' + plugin_version 
			}
		];

		for ( var i = 0; i < scripts.length; i++ ) {
			var script = scripts[ i ];
			if ( document.querySelector( script.selector ) !== null ) {
				loadScript( script );
			}
		}
		
		// Search Form
		$( '.aiovg-search-form-type-filter' ).each(function() {
			var $this = $( this );
			var $form = $this.find( 'form' );

			$this.find( 'input[name="vi"]' ).on( 'blur', function() {
				$form.submit();
			});

			$this.find( 'input[type="checkbox"]' ).on( 'change', function() {
				$form.submit();
			});

			$this.find( 'select' ).on( 'change', function() {
				$form.submit();
			});
		});
		
		// Categories Dropdown.
		$( '.aiovg-categories-template-dropdown select' ).on( 'change', function() {
			var selectedEl = this.options[ this.selectedIndex ];

			if ( parseInt( selectedEl.value ) == 0 ) {
				window.location.href = $( this ).closest( '.aiovg-categories-template-dropdown' ).data( 'uri' );
			} else {
				window.location.href = selectedEl.getAttribute( 'data-uri' );
			}
		});		
		
	});

})( jQuery );
