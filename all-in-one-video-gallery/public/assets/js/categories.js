(function( $ ) {	
	'use strict';

	/**
	 * Called when the page has loaded.
	 */
	$(function() {

		// Categories Dropdown
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
