(function( $ ) {		
	'use strict';

	/**
 	 * Replace element classes with pattern matching.
 	 */
	$.fn.aiovgReplaceClass = function( pattern, additions ) {
		this.removeClass(function( index, classes ) {
			var matches = classes.match( pattern );
			return matches ? matches.join( ' ' ) : '';	
		}).addClass( additions );

		return this;
	};	

	/**
 	 * Init metabox UI (Tabs + Accordion). 
 	 */
	function initMetaboxUI( container ) {
		const $container = $( container );

		// Tabs
		$container.find( '.aiovg-tab' ).on( 'click', function( e ) {
			e.preventDefault();

			const $this  = $( this );
			const target = $this.data( 'target' );

			$container.find( '.aiovg-tab' ).removeClass( 'aiovg-active' );
			$this.addClass( 'aiovg-active' );

			$container.find( '.aiovg-tab-content' ).hide();
			$container.find( target ).fadeIn( 200 );
		});

		// Accordion
		$container.find( '.aiovg-accordion-header' ).on( 'click', function( e ) {
			e.preventDefault();

			const $header    = $( this );
			const $accordion = $header.closest( '.aiovg-accordion' );

			if ( $accordion.data( 'collapsible' ) ) {
				const $icon = $header.find( '.dashicons' );

				$accordion.find( '.aiovg-accordion-body' ).slideToggle( 200 );
				$header.toggleClass( 'aiovg-open' );
				$icon.toggleClass( 'dashicons-arrow-down-alt2 dashicons-arrow-up-alt2' );
			}
		});
	}
	
	/**
	 * Render media uploader.
	 */
	function renderMediaUploader( callback ) { 
		var fileFrame;

		// If an instance of fileFrame already exists, then we can open it rather than creating a new instance.
		if ( fileFrame ) {
			fileFrame.open();
			return false;
		}

		// Use the wp.media library to define the settings of the media uploader.
		fileFrame = wp.media.frames.file_frame = wp.media({
			frame: 'post',
			state: 'insert',
			multiple: false
		});		

		// Setup an event handler for what to do when a media has been selected.
		fileFrame.on( 'insert', function() { 
			// Read the JSON data returned from the media uploader.
			var json = fileFrame.state().get( 'selection' ).first().toJSON();
		
			// First, make sure that we have the URL of the media to display.
			if ( json.url.trim().length == 0 ) {
				return false;
			}
		
			callback( json );			 
		});

		fileFrame.state( 'embed' ).on( 'select', function() {
			// Read the JSON data returned from the media uploader.
			var json = fileFrame.state().props.toJSON();

			// First, make sure that we have the URL of the media to display.
			if ( json.url.trim().length == 0 ) {
				return false;
			}

			json.id = 0;
			callback( json );			 
		});

		// Now display the actual fileFrame.
		fileFrame.on( 'open', function() { 
			jQuery( '#menu-item-gallery, #menu-item-playlist, #menu-item-video-playlist' ).hide();
		});

		fileFrame.open(); 
	}

	/**
	 * Toggle Thumbnail Generator.
	 */
	function toggleThumbnailGenerator() {
		var url = $( '#aiovg-mp4' ).val();

		if ( url && url.trim().length > 0 && ! /\.m3u8/.test( url.toLowerCase() ) ) {
			$( '#aiovg-field-mp4' ).removeClass( 'aiovg-is-bunny-stream' );
			$( '#aiovg-thumbnail-generator' ).show();
		} else {
			$( '#aiovg-thumbnail-generator' ).hide();
		}
	}

	/**
	 * Make tracks in the video form sortable.
	 */
	function sortTracks() {				
		var $el = $( '#aiovg-tracks tbody' );
			
		if ( $el.hasClass( 'ui-sortable' ) ) {
			$el.sortable( 'destroy' );
		}
			
		$el.sortable({
			handle: '.aiovg-handle'
		});
	}

	/**
	 * Make chapters in the video form sortable.
	 */
	function sortChapters() {				
		var $el = $( '#aiovg-chapters tbody' );
			
		if ( $el.hasClass( 'ui-sortable' ) ) {
			$el.sortable( 'destroy' );
		}
			
		$el.sortable({
			handle: '.aiovg-handle'
		});
	}

	/**
 	 * Init autocomplete ui to search videos. 
 	 */
	function initAutocomplete( $el ) {
		$el.autocomplete({
			source: function( request, response ) {
				$.ajax({
					url: ajaxurl,
					dataType: 'json',
					method: 'post',
					data: {
						action: 'aiovg_autocomplete_get_videos',
						security: aiovg_admin.ajax_nonce,
						term: request.term
					},
					success: function( data ) {
						response( $.map( data, function( item ) {
							return {
								label: item.post_title,
								value: item.post_title,
								data: item
							}
						}));
					}
				});
			},
			autoFocus: true,
			minLength: 0,
			select: function( event, ui ) {
				var $field = $( this ).closest( '.aiovg-widget-field' );
				var html = '';

				if ( ui.item.data.ID != 0 ) {
					html  = '<span class="dashicons dashicons-yes-alt"></span> ';
					html += '<span>' + ui.item.data.post_title + '</span> ';
					html += '<a href="javascript:void(0);" class="aiovg-remove-autocomplete-result">' + aiovg_admin.i18n.remove + '</a>';
				} else {
					html  = '<span class="dashicons dashicons-info"></span> ';
					html += '<span>' + aiovg_admin.i18n.no_video_selected + '</span>';
				}
				
				$field.find( '.aiovg-widget-input-id' ).val( ui.item.data.ID ).trigger( 'change' );
				$field.find( '.aiovg-autocomplete-result' ).html( html );
			},
			open: function() {
				$( this ).removeClass( 'ui-corner-all' ).addClass( 'ui-corner-top' );
			},
			close: function() {
				$( this ).removeClass( 'ui-corner-top' ).addClass( 'ui-corner-all' );
				$( this ).val( '' );
			}
		});		
	}

	/**
 	 * Copy to Clipboard.
 	 */
	function copyToClipboard( value ) {
		var input = document.createElement( 'input' );
		input.value = value;

		document.body.appendChild( input );		
		input.focus();
		input.select();

		document.execCommand( 'copy' );

		input.remove();
		alert( aiovg_admin.i18n.copied + "\n" + value );
	}

	/**
	 * Removes a dot (.) at the end of a string.
	 */
	function removeEndingDot( str ) {
		return str.charAt( str.length - 1 ) === '.' ? str.slice( 0, -1 ) : str;
	}

	/**
 	 * Init Bunny Stream Uploader.
 	 */
	class InitBunnyStreamUploader {

		constructor() {
		  	this.$uploadButton = $( '#aiovg-bunny-stream-upload-button' );

		  	if ( this.$uploadButton.length === 0 ) {
				return;
		  	}
	  
		  	this.$root          = $( '#aiovg-field-mp4' );
		  	this.$uploadWrapper = $( '#aiovg-field-mp4 .aiovg-media-uploader' );
		  	this.$uploadField   = $( '#aiovg-field-mp4 input[type="file"]' );
		  	this.$uploadStatus  = $( '#aiovg-field-mp4 .aiovg-upload-status' );
	  
		  	this.upload  = null;
		  	this.timeout = null;
		  	this.options = {};
	  
		  	this.initOptions();
		  	this.bindEvents();
		}
	  
		initOptions() {
		  	this.upload = null;

		  	if ( this.timeout ) clearTimeout( this.timeout );
		  	this.timeout = null;
	  
		  	this.options = {
				status: '',
				videoId: '',
				retryCount: 0,
				maxRetries: 30,
				cache: null
		  	};
		}
	  
		bindEvents() {
		  	this.$uploadButton.on( 'click', ( e ) => {
				e.preventDefault();
				this.$uploadField.click();
		  	});
	  
		  	this.$uploadField.on( 'change', ( e ) => {
				this.handleUpload( e );
		  	});
	  
		  	$( '#aiovg-field-mp4' ).on( 'click', '.aiovg-upload-cancel', ( e ) => {
				e.preventDefault();
				this.cancelUpload();
				toggleThumbnailGenerator();
		  	});
		}				
	  
		handleUpload( e ) {
			const file = e.target.files[0];
			if ( ! file ) return;

		  	this.initOptions();
		  	this.$uploadStatus.html( '<span class="aiovg-text-success">' + aiovg_admin.i18n.preparing_upload + '</span><span class="aiovg-animate-dots"></span>' );
		  	this.$root.addClass( 'aiovg-is-bunny-stream' );
		  	this.$uploadWrapper.addClass( 'aiovg-uploading' );
			
			$( '#aiovg-thumbnail-generator' ).hide();
	  
		  	let title = $( '#title' ).val();
		  	if ( ! title || title.trim().length === 0 ) {
				title = file.name;
		  	}
	  
			const data = {
				action: 'aiovg_create_bunny_stream_video',
				security: aiovg_admin.ajax_nonce,
				title: title
			};
	  
			// Create Bunny video
		  	$.post( ajaxurl, data, ( response ) => {
				if ( ! response.success ) {
					this.$uploadField.val( '' );
					this.$uploadStatus.html( '<span class="aiovg-text-error">' + response.data.error + '</span>' );
					this.$uploadWrapper.removeClass( 'aiovg-uploading' );
					return;
				}
	  
				const metadata = {
					filetype: file.type,
					title: title
				};
	  
				if ( response.data.collection_id ) {
					metadata.collection = response.data.collection_id;
				}
	  
				this.options.videoId = response.data.video_id;
	  
				// TUS Upload
				this.upload = new tus.Upload( file, {
					endpoint: 'https://video.bunnycdn.com/tusupload',
					retryDelays: [0, 3000, 5000, 10000, 20000],
					headers: {
						AuthorizationSignature: response.data.token,
						AuthorizationExpire: response.data.expires,
						VideoId: response.data.video_id,
						LibraryId: response.data.library_id
					},
					metadata: metadata,
					onError: ( error ) => {
						this.$uploadField.val( '' );
						this.$uploadStatus.html( '<span class="aiovg-text-error">' + error + '</span>' );
						this.$uploadWrapper.removeClass( 'aiovg-uploading' );
					},
					onProgress: ( bytesUploaded, bytesTotal ) => {
						if ( this.options.status === 'cancelled' ) return;

						const percent = ( ( bytesUploaded / bytesTotal ) * 100 ).toFixed(2);
						const status  = aiovg_admin.i18n.upload_status.replace( '%d', Math.min( 99.99, percent ) );
			
						if ( this.$uploadStatus.find( '.aiovg-upload-cancel' ).length > 0 ) {
							this.$uploadStatus.find( '.aiovg-text-success' ).html( status );
						} else {
							this.$uploadStatus.html( '<span class="aiovg-text-success">' + status + '</span> <a class="aiovg-upload-cancel" href="javascript: void(0);">' + aiovg_admin.i18n.cancel_upload + '</a>' );
						}
					},
					onSuccess: () => {
						if ( this.options.status === 'cancelled' ) return;

						this.upload = null;
						this.$uploadField.val( '' );
		
						this.options.cache = {
							mp4: $( '#aiovg-mp4' ).val(),
							image: $( '#aiovg-image' ).val(),
							video_id: $( '#aiovg-bunny_stream_video_id' ).val(),
							deletable_video_ids: $( '#aiovg-deletable_bunny_stream_video_ids' ).val()
						};
		
						$( '#aiovg-mp4' ).val( response.data.video_url );
						$( '#aiovg-image' ).val( response.data.thumbnail_url );
						$( '#aiovg-bunny_stream_video_id' ).val( response.data.video_id );
		
						if ( this.options.cache.video_id ) {
							let deletableVideoIds = this.options.cache.deletable_video_ids ? this.options.cache.deletable_video_ids.split( ',' ) : [];
				
							if ( deletableVideoIds.indexOf( this.options.cache.video_id ) === -1 ) {
								deletableVideoIds.push( this.options.cache.video_id );
							}
				
							$( '#aiovg-deletable_bunny_stream_video_ids' ).val( deletableVideoIds.join( ',' ) );
						}
			
						if ( this.$uploadStatus.find( '.aiovg-upload-cancel' ).length > 0 ) {
							this.$uploadStatus.find( '.aiovg-text-success' ).html( removeEndingDot( aiovg_admin.i18n.upload_processing ) + '<span class="aiovg-animate-dots"></span>' );
						} else {
							this.$uploadStatus.html( '<span class="aiovg-text-success">' + removeEndingDot( aiovg_admin.i18n.upload_processing ) + '<span class="aiovg-animate-dots"></span></span> <a class="aiovg-upload-cancel" href="javascript: void(0);">' + aiovg_admin.i18n.cancel_upload + '</a>' );
						}

						this.checkVideoStatus();
					}
				});
		
				this.upload.start();
			}, 'json');
		}		
	
	  	checkVideoStatus() {
			if ( ! this.options.videoId || this.options.retryCount++ >= this.options.maxRetries ) return;
	
			$.ajax({
			  	url: ajaxurl,
			  	method: 'POST',
			  	data: {
					action: 'aiovg_get_bunny_stream_video',
					security: aiovg_admin.ajax_nonce,
					video_id: this.options.videoId
			  	},
			  	success: ( response ) => {
					if ( this.options.status === 'cancelled' ) return;
	
					if ( ! response.success ) {	
						this.resetFieldValues();
					  	this.$uploadStatus.html( '<span class="aiovg-text-error">' + response.data.error + '</span>' );
					  	this.$uploadWrapper.removeClass( 'aiovg-uploading' );
					  	return;
					}
	
					if ( response.data.status == 4 ) {
					  	this.$uploadStatus.html( '<span class="aiovg-text-success">' + response.data.message + '</span>' );
					  	this.$uploadWrapper.removeClass( 'aiovg-uploading' );

					  	$( '#aiovg-duration' ).val( response.data.duration );
					} else {
						if ( this.$uploadStatus.find( '.aiovg-upload-cancel' ).length > 0 ) {
							this.$uploadStatus.find( '.aiovg-text-success' ).html( removeEndingDot( response.data.message ) + '<span class="aiovg-animate-dots"></span>' );
						} else {
					  		this.$uploadStatus.html( '<span class="aiovg-text-success">' + removeEndingDot( response.data.message ) + '<span class="aiovg-animate-dots"></span></span> <a class="aiovg-upload-cancel" href="javascript: void(0);">' + aiovg_admin.i18n.cancel_upload + '</a>' );
						}

						this.timeout = setTimeout( () => this.checkVideoStatus(), 5000 );
					}
			  	}
			});
	  	}
	
		cancelUpload() {
			clearTimeout( this.timeout );
			this.options.status = 'cancelled';			
			this.$uploadField.val( '' );
			this.$uploadStatus.html( '' );
			this.resetFieldValues();			
	
			if ( this.upload && typeof this.upload.abort === 'function' ) {
			  	this.upload.abort().then( () => this.deleteVideo() ).catch( () => this.deleteVideo() );
			} else {
			  	this.deleteVideo();
			}

			this.$uploadWrapper.removeClass( 'aiovg-uploading' );
			this.$root.removeClass( 'aiovg-is-bunny-stream' );
	  	}

	  	deleteVideo() {
			if ( ! this.options.videoId ) return;	
			this.upload = null;

			const data = {
			  	action: 'aiovg_delete_bunny_stream_video',
			  	security: aiovg_admin.ajax_nonce,
			  	video_id: this.options.videoId
			};
	
			setTimeout( () => {
			  	$.post( ajaxurl, data, null, 'json' );
			}, 500 );
	  	}

		resetFieldValues() {
			if ( ! this.options.cache ) return;
	
			$( '#aiovg-mp4' ).val( this.options.cache.mp4 );
			$( '#aiovg-image' ).val( this.options.cache.image );
			$( '#aiovg-bunny_stream_video_id' ).val( this.options.cache.video_id );
			$( '#aiovg-deletable_bunny_stream_video_ids' ).val( this.options.cache.deletable_video_ids );
	  	}

	}	

	/**
	 * Called when the page has loaded.
	 */
	$(function() {
		
		// Common: Init metabox UI (Tabs + Accordion).
		$( '.aiovg-metabox-ui' ).each(function() {
			initMetaboxUI( this );
		});

		// Common: Upload files.
		$( document ).on( 'click', '.aiovg-upload-media', function( event ) { 
            event.preventDefault();

			var $this = $( this );

            renderMediaUploader(function( json ) {
				$this.closest( '.aiovg-media-uploader' )
					.find( 'input[type=text]' )
					.val( json.url )
					.trigger( 'file.uploaded' );
			}); 
		});
		
		// Common: Init color picker.
		if ( $.fn.wpColorPicker ) {
			$( '.aiovg-color-picker' ).wpColorPicker();

			$( document ).on( 'widget-added widget-updated', function( event, widget ) {
				widget.find( '.aiovg-color-picker' ).wpColorPicker({
					change: _.throttle( function() { // For Customizer
						$( this ).trigger( 'change' );
					}, 3000)
				});
			});
		}

		// Common: Init the popup.
		if ( $.fn.magnificPopup ) {
			$( '.aiovg-modal-button' ).magnificPopup({
				type: 'inline',
				mainClass: 'mfp-fade'
			});
		}

		// Dashboard: Toggle shortcode forms.
		$( '#aiovg-shortcode-selector input[type=radio]' ).on( 'change', function() {
			var value = $( '#aiovg-shortcode-selector input[type=radio]:checked' ).val();

			$( '.aiovg-shortcode-form' ).hide();
			$( '#aiovg-shortcode-form-' + value ).show();

			$( '.aiovg-shortcode-instructions' ).hide();			
			$( '#aiovg-shortcode-instructions-' + value ).show();
		}).trigger( 'change' );

		// Dashboard: Toggle field sections.
		$( '#aiovg-shortcode-forms .aiovg-shortcode-section-header' ).on( 'click', function() {
			var $this   = $( this );
			var $parent = $this.parent();

			if ( ! $parent.hasClass( 'aiovg-active' ) ) {
				$this.closest( '.aiovg-shortcode-form' )
					.find( '.aiovg-active' )
					.removeClass( 'aiovg-active' )
					.find( '.aiovg-shortcode-controls' )
					.slideToggle();
			}			

			$parent.toggleClass( 'aiovg-active' )
				.find( '.aiovg-shortcode-controls' )
				.slideToggle();
		});		

		// Dashboard: Toggle fields based on the selected video source type.
		$( '#aiovg-shortcode-form-video select[name=type]' ).on( 'change', function() {			
			var value = $( this ).val();			
			$( '#aiovg-shortcode-form-video' ).aiovgReplaceClass( /\aiovg-type-\S+/ig, 'aiovg-type-' + value );
		});

		// Dashboard: Toggle fields based on the selected videos template.
		$( '#aiovg-shortcode-form-videos select[name=template]' ).on( 'change', function() {			
			var value = $( this ).val();			
			$( '#aiovg-shortcode-form-videos' ).aiovgReplaceClass( /\aiovg-template-\S+/ig, 'aiovg-template-' + value );
		}).trigger( 'change' );

		// Dashboard: Toggle fields based on the selected categories template.
		$( '#aiovg-shortcode-form-categories select[name=template]' ).on( 'change', function() {			
			var value = $( this ).val();			
			$( '#aiovg-shortcode-form-categories' ).aiovgReplaceClass( /\aiovg-template-\S+/ig, 'aiovg-template-' + value );
		}).trigger( 'change' );

		// Dashboard: Generate shortcode.
		$( '#aiovg-generate-shortcode' ).on( 'click', function( event ) { 
			event.preventDefault();			

			// Shortcode
			var shortcode = $( '#aiovg-shortcode-selector input[type=radio]:checked' ).val();

			// Build attributes.
			var attributes = shortcode;
			var obj = {};
			
			$( '.aiovg-shortcode-field', '#aiovg-shortcode-form-' + shortcode ).each(function() {							
				var $this = $( this );
				var type  = $this.attr( 'type' );
				var name  = $this.attr( 'name' );				
				var value = $this.val();
				var def   = 0;
				
				if ( typeof $this.data( 'default' ) !== 'undefined' ) {
					def = $this.data( 'default' );
				}				
				
				// Is checkbox?
				if ( type == 'checkbox' ) {
					value = $this.is( ':checked' ) ? 1 : 0;
				} else {
					// Is category or tag?
					if ( name == 'category' || name == 'tag' ) {					
						value = $this.find( 'input[type=checkbox]:checked' ).map(function() {
							return this.value;
						}).get().join( ',' );
					}
				}
				
				// Add only if the user input differ from the global configuration.
				if ( value != def ) {
					obj[ name ] = value;
				}				
			});
			
			for ( var name in obj ) {
				if ( obj.hasOwnProperty( name ) ) {
					attributes += ( ' ' + name + '="' + obj[ name ] + '"' );
				}
			}

			// Shortcode output.	
			$( '#aiovg-shortcode').val( '[aiovg_' + attributes + ']' ); 
		});
		
		// Dashboard: Toggle checkboxes in the issues table.
		$( '#aiovg-issues-check-all' ).on( 'change', function() {
			var value = $( this ).is( ':checked' ) ? true : false;	
			$( '#aiovg-issues .aiovg-issue' ).prop( 'checked', value );
		});	

		// Dashboard: Validate the issues form.
		$( '#aiovg-issues-form' ).submit(function() {
			var hasValue = $( '#aiovg-issues .aiovg-issue:checked' ).length > 0;

			if ( ! hasValue ) {
				alert( aiovg_admin.i18n.no_issues_selected );
				return false;
			}			
		});

		// Videos: Copy URL.
		$( '.aiovg-copy-url' ).on( 'click', function() {
			var url = $( this ).data( 'url' );
			copyToClipboard( url );			
		});

		// Videos: Copy shortcode.
		$( '.aiovg-copy-shortcode' ).on( 'click', function() {
			var id = parseInt( $( this ).data( 'id' ) );
			var shortcode = '[aiovg_video id="' + id + '"]';

			copyToClipboard( shortcode );
		});
		
		// Videos: Toggle fields based on the selected video source type.
		$( '#aiovg-video-type' ).on( 'change', function( event ) { 
            event.preventDefault();
 
			var type = $( this ).val();
			
			$( '.aiovg-toggle-fields' ).hide();
			$( '.aiovg-type-' + type ).slideDown();

			// Toggle Thumbnail Generator
			if ( type == 'default' ) {
				toggleThumbnailGenerator();
			} else {
				$( '#aiovg-thumbnail-generator' ).hide();
			}
		}).trigger( 'change' );
		
		// Videos: Add new source fields when "Add More Quality Levels" link is clicked.
		$( '#aiovg-add-new-source' ).on( 'click', function( event ) {
			event.preventDefault();				
			
			var $this = $( this );

			var limit  = parseInt( $( this ).data( 'limit' ) );
			var length = $( '#aiovg-field-mp4 .aiovg-quality-selector' ).length;	
			var index  = length - 1;
			
			if ( index == 0 ) {
				$( '#aiovg-field-mp4 .aiovg-quality-selector' ).show();
			}

			var template = document.querySelector( '#aiovg-template-source' );
			if ( template !== null ) {
				var el = template.content.cloneNode( true );
				el.querySelector( 'input[type=radio]' ).setAttribute( 'name', 'quality_levels[' + index + ']' );
				el.querySelector( 'input[type=text]' ).setAttribute( 'name', 'sources[' + index + ']' );

				$this.before( el );
			} 		
			
			if ( ( length + 1 ) >= limit ) {
				$this.hide();
			}
		});

		// Videos: On quality level selected.
		$( '#aiovg-field-mp4' ).on( 'change', '.aiovg-quality-selector input[type=radio]', function() {
			var $this = $( this );
			var values = [];

			$( '.aiovg-quality-selector' ).each(function() {
				var value = $( this ).find( 'input[type=radio]:checked' ).val();

				if (  value ) {
					if ( values.includes( value ) ) {
						$this.prop( 'checked', false );
						alert( aiovg_admin.i18n.quality_exists );
					} else {
						values.push( value );
					}					
				}
			});
		});
		
		// Videos: Toggle Thumbnail Generator.
		$( '#aiovg-mp4' ).on( 'blur file.uploaded', ( e ) => {
			toggleThumbnailGenerator();				
		});

		// Videos: Add new track fields when "Add New File" button is clicked.
		$( '#aiovg-add-new-track' ).on( 'click', function( event ) { 
            event.preventDefault();
			
			var template = document.querySelector( '#aiovg-template-track' );
			if ( template !== null ) {
				var el = template.content.cloneNode( true );		
				$( '#aiovg-tracks tbody' ).append( el );
			} 
        });
		
		if ( $( '#aiovg-tracks .aiovg-tracks-row' ).length == 0 ) {
			$( '#aiovg-add-new-track' ).trigger( 'click' );
		}

		// Videos: Upload tracks.	
		$( document ).on( 'click', '.aiovg-upload-track', function( event ) { 
            event.preventDefault();

			var $this = $( this );

            renderMediaUploader(function( json ) {
				$this.closest( 'tr' )
					.find( '.aiovg-track-src input[type=text]' )
					.val( json.url );
			}); 
        });
		
		// Videos: Delete tracks.
		$( document ).on( 'click', '.aiovg-delete-track', function( event ) { 
            event.preventDefault();			
            $( this ).closest( 'tr' ).remove(); 
        });
		
		// Videos: Make the tracks fields sortable.
		if ( $.fn.sortable ) {
			sortTracks();
		}
		
		// Videos: Add new chapter fields when "Add New Chapter" button is clicked.
		$( '#aiovg-add-new-chapter' ).on( 'click', function( event ) { 
            event.preventDefault();
			
			var template = document.querySelector( '#aiovg-template-chapter' );
			if ( template !== null ) {
				var el = template.content.cloneNode( true );			
				$( '#aiovg-chapters tbody' ).append( el ); 
			}
        });
		
		if ( $( '#aiovg-chapters .aiovg-chapters-row' ).length == 0 ) {
			$( '#aiovg-add-new-chapter' ).trigger( 'click' );
		}
		
		// Videos: Delete chapters.
		$( document ).on( 'click', '.aiovg-delete-chapter', function( event ) { 
            event.preventDefault();			
            $( this ).closest( 'tr' ).remove(); 
        });
		
		// Videos: Make the chapters fields sortable.
		if ( $.fn.sortable ) {
			sortChapters();
		}

		// Videos: Toggle fields based on the selected access control.
		$( '#aiovg-field-access_control select' ).on( 'change', function() {	
			var value = parseInt( $( this ).val() );
			if ( value == 2 ) {
				$( '#aiovg-field-restricted_roles' ).show();
			} else {
				$( '#aiovg-field-restricted_roles' ).hide();
			}
		});

		// Videos: Bunny Stream.
		if ( ( typeof tus !== 'undefined' && typeof tus.Upload === 'function' ) ) {
			new InitBunnyStreamUploader();
		}		

		// Categories: Upload Image.
		$( '#aiovg-categories-upload-image' ).on( 'click', function( event ) { 
            event.preventDefault();

			renderMediaUploader(function( json ) {
				$( '#aiovg-categories-image-wrapper' ).html( '<img src="' + json.url + '" alt="" />' );

				$( '#aiovg-categories-image' ).val( json.url );
				$( '#aiovg-categories-image_id' ).val( json.id );				
			
				$( '#aiovg-categories-upload-image' ).hide();
				$( '#aiovg-categories-remove-image' ).show();
			}); 
        });
		
		// Categories: Remove Image.
		$( '#aiovg-categories-remove-image' ).on( 'click', function( event ) {														 
            event.preventDefault();					
			
			$( '#aiovg-categories-image-wrapper' ).html( '' );

			$( '#aiovg-categories-image' ).val( '' );
			$( '#aiovg-categories-image_id' ).val( '' );			
			
			$( '#aiovg-categories-remove-image' ).hide();
			$( '#aiovg-categories-upload-image' ).show();	
		});
		
		// Categories: Clear the custom fields.
		$( document ).ajaxComplete(function( e, xhr, settings ) {			
			if ( $( '#aiovg-categories-image' ).length && settings.data ) {	
				var queryStringArr = settings.data.split( '&' );
			   
				if ( $.inArray( 'action=add-tag', queryStringArr ) !== -1 ) {
					var response = $( xhr.responseXML ).find( 'term_id' ).text();

					if ( response ) {						
						$( '#aiovg-categories-image-wrapper' ).html( '' );	
						
						$( '#aiovg-categories-image' ).val( '' );	
						$( '#aiovg-categories-image_id' ).val( '' );				
						
						$( '#aiovg-categories-remove-image' ).hide();
						$( '#aiovg-categories-upload-image' ).show();

						$( '#aiovg-categories-exclude_search_form' ).prop( 'checked', false );
						$( '#aiovg-categories-exclude_video_form' ).prop( 'checked', false );
					}
				}		
			}			
		});

		// Settings: Bind section ID.
		$( '#aiovg-settings .form-table' ).each(function() { 
			var str = $( this ).find( 'tr:first th label' ).attr( 'for' );
			var id  = str.split( '[' );
			id = id[0].replace( /_/g, '-' );

			$( this ).attr( 'id', id );
		});
		
		// Settings: Toggle fields based on the selected player library.
		$( '#aiovg-player-settings tr.player input[type=radio]' ).on( 'change', function() {			
			var value = $( '#aiovg-player-settings tr.player input[type=radio]:checked' ).val();			
			$( '#aiovg-player-settings' ).aiovgReplaceClass( /\aiovg-player-\S+/ig, 'aiovg-player-' + value );
		}).trigger( 'change' );

		// Settings: Toggle fields based on the selected categories template.
		$( '#aiovg-categories-settings tr.template select' ).on( 'change', function() {			
			var value = $( this ).val();			
			$( '#aiovg-categories-settings' ).aiovgReplaceClass( /\aiovg-template-\S+/ig, 'aiovg-template-' + value );
		}).trigger( 'change' );

		// Settings: Toggle fields based on the selected videos template.
		$( '#aiovg-videos-settings tr.template select' ).on( 'change', function() {			
			var value = $( this ).val();			
			$( '#aiovg-videos-settings' ).aiovgReplaceClass( /\aiovg-template-\S+/ig, 'aiovg-template-' + value );
		}).trigger( 'change' );

		// Settings: Toggle fields based on whether Token Authentication is enabled or disabled.
		$( '#aiovg-bunny-stream-settings tr.enable_token_authentication input[type="checkbox"]' ).on( 'change', function() {			
			var value = $( this ).is( ':checked' ) ? 'enabled' : 'disabled';			
			$( '#aiovg-bunny-stream-settings' ).aiovgReplaceClass( /\aiovg-token-authentication-\S+/ig, 'aiovg-token-authentication-' + value );
		}).trigger( 'change' );

		// Settings: Toggle fields based on the selected access control for the videos.
		$( '#aiovg-restrictions-settings tr.access_control select' ).on( 'change', function() {	
			var value = parseInt( $( this ).val() );
			if ( value == 2 ) {
				$( '#aiovg-restrictions-settings tr.restricted_roles' ).show();
			} else {
				$( '#aiovg-restrictions-settings tr.restricted_roles' ).hide();
			}
		}).trigger( 'change' );

		// Categories Widget: Toggle fields based on the selected categories template.
		$( document ).on( 'change', '.aiovg-widget-form-categories .aiovg-widget-input-template', function() {			
			var value = $( this ).val();	
			$( this ).closest( '.aiovg-widget-form-categories' ).aiovgReplaceClass( /\aiovg-template-\S+/ig, 'aiovg-template-' + value );
		});

		// Videos Widget: Toggle fields based on the selected videos template.
		$( document ).on( 'change', '.aiovg-widget-form-videos .aiovg-widget-input-template', function() {			
			var value = $( this ).val();
			$( this ).closest( '.aiovg-widget-form-videos' ).aiovgReplaceClass( /\aiovg-template-\S+/ig, 'aiovg-template-' + value );
		});

		// Video Widget: Init autocomplete.
		if ( $.fn.autocomplete ) {
			$( '.aiovg-autocomplete-input' ).each(function() {
				initAutocomplete( $( this ) );
			});

			$( document ).on( 'widget-added widget-updated', function( event, widget ) {
				var $el = widget.find( '.aiovg-autocomplete-input' );
				
				if ( $el.length > 0 ) {
					initAutocomplete( $el );
				}
			});

			$( document ).on( 'click', '.aiovg-remove-autocomplete-result', function() {
				var $field = $( this ).closest( '.aiovg-widget-field' );				

				var html = '<span class="dashicons dashicons-info"></span> ';
				html += '<span>' + aiovg_admin.i18n.no_video_selected + '</span>';

				$field.find( '.aiovg-widget-input-id' ).val( 0 ).trigger( 'change' );
				$field.find( '.aiovg-autocomplete-result' ).html( html );
			});
		}
			   
	});	

})( jQuery );
