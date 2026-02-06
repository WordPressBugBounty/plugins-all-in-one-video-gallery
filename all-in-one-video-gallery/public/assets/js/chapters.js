(function( $ ) {	
	'use strict';

	/**
	 * Called when the page has loaded.
	 */
	$(function() {

		// Chapters
		$( '.aiovg-single-video .aiovg-chapter-timestamp' ).on( 'click', function( event ) {
			event.preventDefault();

			var seconds  = parseInt( event.currentTarget.dataset.time );
			var playerEl = document.querySelector( '.aiovg-single-video .aiovg-player-element' );
					
			if ( playerEl !== null ) {
				playerEl.seekTo( seconds );
			} else {
				playerEl = document.querySelector( '.aiovg-single-video iframe' );

				if ( playerEl !== null ) {
					playerEl.contentWindow.postMessage({ 				
						message: 'aiovg-video-seek',
						seconds: seconds
					}, window.location.origin );
				} else {
					return false;
				}
			}

			// Scroll to Top
			var aiovg = window.aiovg_chapters || window.aiovg_public;

			$( 'html, body' ).animate({
				scrollTop: $( '.aiovg-single-video' ).offset().top - parseInt( aiovg.scroll_to_top_offset )
			}, 500 );
		});
			
	});

})( jQuery );
