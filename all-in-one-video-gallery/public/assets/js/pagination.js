(function( $ ) {
	'use strict';

	/**
	 * Pagination
	 */
	class AIOVGPaginationElement extends HTMLElement {

		/**
		 * Element created.
		 */
		constructor() {
			super();

			// Set references to the DOM elements used by the component
			this.$el = null;
			this.$container = null;

			// Set references to the private properties used by the component
			const aiovg = window.aiovg_pagination || window.aiovg_public;

			this._params = {};
			this._isAjaxEnabled = false;
			this._ajaxUrl = aiovg.ajax_url;
			this._ajaxNonce = aiovg.ajax_nonce;
			this._pageTopOffset = parseInt( aiovg.scroll_to_top_offset );

			// Bind the event handlers to ensure the reference remains stable		
			this._onNextOrPreviousPageButtonClicked = this._onNextOrPreviousPageButtonClicked.bind( this );
			this._onMoreButtonClicked = this._onMoreButtonClicked.bind( this );
		}

		/**
		 * Browser calls this method when the element is added to the document.
		 * (can be called many times if an element is repeatedly added/removed)
		 */
		connectedCallback() {
			this.$el = $( this );		

			if ( this.$el.hasClass( 'aiovg-pagination-ajax' ) || this.$el.hasClass( 'aiovg-more-ajax' ) ) {
				this._isAjaxEnabled = true;
			}

			if ( ! this._isAjaxEnabled ) {
				return false;
			}

			this._params = this.$el.data( 'params' );
			this._params.action = 'aiovg_load_' + this._params.source;
			this._params.security = this._ajaxNonce;

			this.$container = $( '#aiovg-' + this._params.uid );

			this.$el.on( 'click', 'a.page-numbers', this._onNextOrPreviousPageButtonClicked );
			this.$el.on( 'click', 'button', this._onMoreButtonClicked );
		}

		/**
		 * Browser calls this method when the element is removed from the document.
		 * (can be called many times if an element is repeatedly added/removed)
		 */
		disconnectedCallback() {
			if ( ! this._isAjaxEnabled ) {
				return false;
			}

			this.$el.off( 'click', 'a.page-numbers', this._onNextOrPreviousPageButtonClicked );
			this.$el.off( 'click', 'button', this._onMoreButtonClicked );
		}

		/**
		 * Define private methods.
		 */

		_onNextOrPreviousPageButtonClicked( event ) {
			event.preventDefault();

			const $this = $( event.target );

			this.$el.addClass( 'aiovg-spinner' );

			let current = parseInt( this.$el.data( 'current' ) );			
			
			let paged = parseInt( $this.html() );
			this._params.paged = paged;

			if ( $this.hasClass( 'prev' ) ) {
				this._params.paged = current - 1;
			}
			
			if ( $this.hasClass( 'next' ) ) {
				this._params.paged = current + 1;
			}		

			this._fetch( this._params, ( response ) => {
				if ( response.success ) {
					const html = $( response.data.html ).html();
					this.$container.html( html );
					
					this.$el.trigger( 'AIOVG.onGalleryUpdated' );

					$( 'html, body' ).animate({
						scrollTop: this.$container.offset().top - this._pageTopOffset
					}, 500);
				} else {
					this.$el.removeClass( 'aiovg-spinner' );
				}
			});
		}

		_onMoreButtonClicked( event ) {
			event.preventDefault();

			const $this = $( event.target );

			this.$el.addClass( 'aiovg-spinner' );

			const numpages = parseInt( $this.data( 'numpages' ) );			
			
			let paged = parseInt( $this.data( 'paged' ) );
			this._params.paged = ++paged;		

			this._fetch( this._params, ( response ) => {
				this.$el.removeClass( 'aiovg-spinner' );						
				
				if ( response.success ) {	
					const html = $( response.data.html ).find( '.aiovg-grid' ).html();				
					this.$container.find( '.aiovg-grid' ).append( html );
					
					if ( paged < numpages ) {
						$this.data( 'paged', this._params.paged );	
					} else {
						$this.hide();
					}
					
					this.$el.trigger( 'AIOVG.onGalleryUpdated' );
				}
			});
		}

		_fetch( data, callback ) {
			$.post( this._ajaxUrl, data, callback ); 						
		}

	}

	/**
	 * Called when the page has loaded.
	 */
	$(function() {
		// Register custom element
		if ( ! customElements.get( 'aiovg-pagination' ) ) {
			customElements.define( 'aiovg-pagination', AIOVGPaginationElement );
		}
	});

})( jQuery );
