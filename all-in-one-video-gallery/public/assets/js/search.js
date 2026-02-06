(function( $ ) {	
	'use strict';

	// Progress bar manager.
    var progressBar = {
        bar: null,
        timer: null,
        progress: 0,

        init: function() {
            this.bar = document.querySelector( '.aiovg-filters-progress-bar-inner' );
            this.progress = 0;

            document.querySelector( '.aiovg-filters-progress-bar' ).style.display = 'block';
            this.bar.style.width = '0%';

            this.simulateProgress();
        },

        simulateProgress: function() {
            var self = this;

            this.timer = setInterval(function() {
                self.progress += Math.floor( Math.random() * 10 ) + 5;  // 5% to 15% each tick

                if ( self.progress >= 90 ) {
                    self.progress = 90; // Cap at 90% until real AJAX complete
                    clearInterval( self.timer );
                }

                self.bar.style.width = self.progress + '%';
            }, 200 );
        },

        complete: function() {
            clearInterval( this.timer );
            this.progress = 100;
            this.bar.style.width = '100%';

            setTimeout(function() {
                document.querySelector( '.aiovg-filters-progress-bar' ).style.display = 'none';
            }, 500 );
        }
    };

	/**
	 * Called when the page has loaded.
	 */
	$(function() {

		var aiovg = window.aiovg_search || window.aiovg_public;

		// Search Form: Live
		$( '.aiovg-search-form-mode-live' ).each(function() {
			var $this = $( this );
			var $form = $this.find( 'form' );

			// Submit the form
            var submitForm = function() {
				$form.submit();
			};

			// Attach events to inputs and selects
			$form.find( 'input[name="vi"]' ).on( 'keydown', function( event ) {
				if ( event.keyCode === 13 ) {
					submitForm();
				}
			}).on( 'blur', submitForm );
			$form.find( 'input[type="checkbox"]' ).on( 'change', submitForm );
			$form.find( 'select' ).on( 'change', submitForm );
		});

		// Search Form: Ajax
        $( '.aiovg-search-form-mode-ajax' ).each(function() {
            var $this    = $( this );
			var $el      = $this.closest( '.aiovg-videos-filters-wrapper' );
            var $form    = $this.find( 'form' );
			var template = $this.hasClass( 'aiovg-search-form-template-compact' ) ? 'compact' : null;			

            // Base parameters to send with request
            var params = $el.data( 'params' ) || {};
            params.action = 'aiovg_load_videos';
            params.security = aiovg.ajax_nonce;

			// Track the last AJAX request for aborting
			var xhr = null;

			// Debounce timer
			var debounce = null;

			// Check if the form values has been changed
			var getSortedSerializedArray = function( form ) {
				return $form.serializeArray()
					.map( field => [ field.name, field.value ] )
					.sort( ( a, b ) => {
						if ( a[0] === b[0] ) return a[1].localeCompare( b[1] );
						return a[0].localeCompare( b[0] );
					});
			}

			var defaultData = getSortedSerializedArray( $form );

			var isFormChanged = function() {
				var currentData = getSortedSerializedArray( $form );

				if ( defaultData.length !== currentData.length ) return true;

				for ( var i = 0; i < defaultData.length; i++ ) {
					if ( defaultData[i][0] !== currentData[i][0] ||	defaultData[i][1] !== currentData[i][1]	) {
						return true;
					}
				}

				return false;
			}

            // Fetch videos via AJAX
            var fetchVideos = function( event ) {
				if ( event ) {
					event.preventDefault();
				}

				clearTimeout( debounce );

				debounce = setTimeout( () => {
					progressBar.init();
					$this.trigger( 'aiovg-search-loading' );

					// Clone params into requestData
					var requestData = $.extend( {}, params );

					// Extract and transform form data
					var formData = $form.serializeArray();

					formData.forEach(function( item ) {
						var name  = item.name;
						var value = item.value;

						switch ( name ) {
							case 'vi':
								requestData.search_query = value;
								break;
							case 'sort':
								var sortParts = value.split( '-' );
								requestData.orderby = sortParts[0] || params.orderby;
								requestData.order   = sortParts[1] || params.order;
								break;
						}
					});

					// Categories
					if ( $form.find( '.aiovg-field-category' ).length > 0 ) {
						var categories = [];

						$form.find( '.aiovg-field-category input[type="checkbox"]:checked' ).each(function() {
							categories.push( $( this ).val() );
						});

						requestData.category = categories;
					}

					// Tags
					if ( $form.find( '.aiovg-field-tag' ).length > 0 ) {
						var tags = [];

						$form.find( '.aiovg-field-tag input[type="checkbox"]:checked' ).each(function() {
							tags.push( $( this ).val() );
						});                

						requestData.tag = tags;
					}

					// Toggle buttons visibility
					var showResetButton = isFormChanged();

					if ( 'compact' === template ) {
						$form.find( '.aiovg-button-submit' ).prop( 'hidden', showResetButton );
						$form.find( '.aiovg-button-reset' ).prop( 'hidden', ! showResetButton );
					} else {
						$form.find( '.aiovg-field-reset' ).prop( 'hidden', ! showResetButton );
					}

					// ABORT previous request before making a new one
					if ( xhr !== null ) {
						xhr.abort();
					}

					// Perform the AJAX request
					xhr = $.post( aiovg.ajax_url, requestData, function( response ) {					
						xhr = null;

						if ( response && response.data && response.data.html ) {
							$el.find( '.aiovg-videos' ).replaceWith( response.data.html );
						}

						progressBar.complete();
						$this.trigger( 'aiovg-search-complete' );
					}).fail(function( response ) {
						xhr = null;							

						if ( response.status === 0 ) {
							// Aborted – ignore
						} else if ( response.status === 429 ) {
							console.warn( 'Too many requests (429). Request throttled.' );
						}

						progressBar.complete();
						$this.trigger( 'aiovg-search-failed' );
					});
				}, 400 );
            };

            // Attach events to inputs and selects
            $form.find( 'input[name="vi"]' ).on( 'keydown', function( event ) {
				if ( event.keyCode === 13 ) {
					fetchVideos();
				}
			}).on( 'blur', fetchVideos );
            $form.find( 'select' ).on( 'change', fetchVideos );	
			$form.on( 'submit', fetchVideos );

			var $checkboxes = $form.find( 'input[type="checkbox"]' );			
			$checkboxes.on( 'change', fetchVideos );

			$form.find( '.aiovg-button-reset' ).on( 'click', function( event ) {
                event.preventDefault();

                $form[0].reset();
				
				$checkboxes.off( 'change', fetchVideos );
				$checkboxes.trigger( 'change' );
				$checkboxes.on( 'change', fetchVideos );

				fetchVideos();
            }).removeAttr( 'onclick' );
        });			
		
	});

})( jQuery );
