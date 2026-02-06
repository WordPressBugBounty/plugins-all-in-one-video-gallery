(function( $ ) {		
	'use strict';

	/**
	 * Validate all required fields in a form.
	 */
	function validateRequiredFields( $form ) {
		let isValid = true;

		$form.find( '[required]' ).each(function() {
			if ( ! validateRequiredField( $( this ) ) ) {
				isValid = false;
			}
		});

		return isValid;
	}

	/**
	 * Validate single required field in a form.
	 */
	function validateRequiredField( $el ) {
		let isValid = true;

		const $field = $el.closest( '.aiovg-form-control' );
		const value  = $el.val().trim();

		if ( value === '' ) {
			$field.addClass( 'aiovg-form-invalid' );
			isValid = false;
		} else {
			$field.removeClass( 'aiovg-form-invalid' );
		}

		return isValid;
	}

	/**
	 * Validate CSV columns.
	 */
	function validateCSVColumns() {
		let isValid  = true;
		let selected = [];

		// Collect all selected values
		$( '#aiovg-field-csv_columns select' ).each(function() {
			const value = $( this ).val().trim();

			if ( value ) {
				selected.push( value );
			}
		});

		// Validation: Check if both required fields are selected
		const hasTitle = selected.indexOf( 'post_title' ) !== -1;
		const hasVideo = selected.indexOf( 'video' ) !== -1;

		if ( ! hasTitle || ! hasVideo ) {
			$( '#aiovg-field-csv_columns' ).addClass( 'aiovg-form-invalid' ); 
			isValid = false;
		} else {
			$( '#aiovg-field-csv_columns' ).removeClass( 'aiovg-form-invalid' ); 			
		}

		return isValid;
	}

	/**
	 * Removes a dot (.) at the end of a string.
	 */
	function removeEndingDot( str ) {
		return str.charAt( str.length - 1 ) === '.' ? str.slice( 0, -1 ) : str;
	}

	/**
	 * Called when the page has loaded.
	 */
	$(function() {
		
		// Validate required fields
		$( '#aiovg-import-export' ).find( '[required]' ).each(function() { 		 
			$( this ).on( 'blur keyup', function() { 
				validateRequiredField( $( this ) ); 
			}); 
		});

		// Validate CSV Columns
		$( '#aiovg-field-csv_columns' ).on( 'change', 'select', function() {
			let selected = [];

			// Collect all selected values
			$( '#aiovg-field-csv_columns select' ).each(function() {
				const value = $( this ).val().trim();
				
				if ( value ) {
					selected.push( value );
				}
			});

			if ( selected.length === 0 ) {
				$( '#aiovg-field-csv_columns' ).addClass( 'aiovg-form-invalid' ); 
			} else {
				$( '#aiovg-field-csv_columns' ).removeClass( 'aiovg-form-invalid' ); 			
			}
		});

		// Fetch and render column mapping fields via AJAX
		$( '#aiovg-csv_file' ).on( 'change file.uploaded', function() {
			const $container = $( '#aiovg-field-csv_columns' );
			const $button    = $( '#aiovg-button-import-csv' );
			const $status    = $( '#aiovg-import-csv-status' );

			$container.find( '.aiovg-form-grid' ).html( '' );
			$container.hide();

			const csvFile = $( '#aiovg-csv_file' ).val().trim();
			if ( ! csvFile ) return;

			$button.prop( 'disabled', true );
			$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( aiovg_import_export.i18n.fetching_csv_columns ) + '</span><span class="aiovg-animate-dots"></span>' );

			const formData = {
				action: 'aiovg_get_csv_columns',
				security: aiovg_admin.ajax_nonce,
				csv_file: csvFile,
				columns_separator: $( '#aiovg-columns_separator' ).val()				
			};

			$.post( ajaxurl, formData, function( response ) {
				if ( ! response.success ) {
					$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + response.data.error + '</span>' );
					$button.prop( 'disabled', false );
					return;
				}

				$status.html( '<span class="dashicons dashicons-info-outline"></span> <span>' + aiovg_import_export.i18n.csv_columns_loaded + '</span>' );
				$button.prop( 'disabled', false );

				$container.find( '.aiovg-form-grid' ).html( response.data.html );
				$container.fadeIn( 200 );
			}, 'json' );
		});
		
		// Import from Folder
		$( '#aiovg-form-import-folder' ).on( 'submit', function( event ) {
			event.preventDefault();

			const $form   = $( this );
			const $button = $form.find( 'button[type="submit"]' );
			const $status = $form.find( '.aiovg-form-status' );

			if ( ! validateRequiredFields( $form ) ) {
				$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + aiovg_import_export.i18n.fields_required + '</span>' );
				
				$( 'html, body' ).animate({
					scrollTop: $form.find( '.aiovg-form-invalid' ).first().offset().top - 75
				}, 300 );
				
				return;
			}

			$button.prop( 'disabled', true );
			$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( aiovg_import_export.i18n.preparing_import ) + '</span><span class="aiovg-animate-dots"></span>' );

			// Local variables to track progress
			const limit  = 20;
			
			let offset   = 0;
			let imported = [];
			let skipped  = [];
			let failed   = [];

			// Prepare serialized array
			let formArray = $form.serializeArray();

			// Add static fields
			formArray.push({ name: 'action', value: 'aiovg_import_folder' });
			formArray.push({ name: 'security', value: aiovg_admin.ajax_nonce });
			formArray.push({ name: 'limit', value: limit });
			formArray.push({ name: 'offset', value: offset });			

			// Recursive import function
			const importBatch = function() {
				// Update offset in formArray
				formArray = formArray.map(( field ) => {
					return ( field.name === 'offset' ) ? { name: 'offset', value: offset } : field;
				});

				$.post( ajaxurl, formArray, function( response ) {
					if ( ! response.success ) {
						$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + response.data.error + '</span>' );
						$button.prop( 'disabled', false );
						return;
					}

					offset   = parseInt( response.data.offset );							
					imported = imported.concat( response.data.imported );
					skipped  = skipped.concat( response.data.skipped );
					failed   = failed.concat( response.data.failed );						

					let message = response.data.message;
					message = message.replace( '%%imported%%', imported.length );
					message = message.replace( '%%skipped%%', skipped.length );
					message = message.replace( '%%failed%%', failed.length );

					if ( response.data.step === 'process' ) {
						$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( message ) + '</span><span class="aiovg-animate-dots"></span>' );
						setTimeout( importBatch, 100 ); // Import next batch
					} else if ( response.data.step === 'done' ) {
						let html = '<span class="aiovg-text-success"><span class="dashicons dashicons-yes-alt"></span> ' + message + '</span>';

						if ( failed.length > 0 ) {
							html += '<div class="aiovg-form-fieldset">';
							html += '<h3 class="aiovg-text-error">' + aiovg_import_export.i18n.import_folder_failed_status_heading + '</h3>';

							failed.forEach(( item ) => {
								const fileName = item.split( '/' ).pop(); // Extract just the file name
								html += '<a class="aiovg-status-item" href="' + encodeURI( item ) + '" target="_blank">' + fileName + '</a>';
							});

							html += '</div>';
						}

						$status.html( html );
						$button.prop( 'disabled', false );
					}
				}, 'json' );
			};

			// Start first batch
			importBatch();
		});

		// Import from CSV file
		$( '#aiovg-form-import-csv' ).on( 'submit', function( event ) {
			event.preventDefault();

			const $form   = $( this );
			const $button = $form.find( 'button[type="submit"]' );
			const $status = $form.find( '.aiovg-form-status' );

			if ( ! validateRequiredFields( $form ) || ! validateCSVColumns() ) {
				$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + aiovg_import_export.i18n.fields_required + '</span>' );
				
				$( 'html, body' ).animate({
					scrollTop: $form.find( '.aiovg-form-invalid' ).first().offset().top - 75
				}, 300 );
				
				return;
			}

			$button.prop( 'disabled', true );
			$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( aiovg_import_export.i18n.preparing_import ) + '</span><span class="aiovg-animate-dots"></span>' );

			// Local variables to track progress
			const limit  = 20;
			
			let offset   = 0;
			let imported = [];
			let updated  = [];
			let skipped  = [];
			let failed   = [];
			let errors   = [];

			// Prepare serialized array
			let formArray = $form.serializeArray();

			// Add static fields
			formArray.push({ name: 'action', value: 'aiovg_import_csv' });
			formArray.push({ name: 'security', value: aiovg_admin.ajax_nonce });
			formArray.push({ name: 'limit', value: limit });
			formArray.push({ name: 'offset', value: offset });			

			// Recursive import function
			const importBatch = function() {
				// Update offset in formArray
				formArray = formArray.map(( field ) => {
					return ( field.name === 'offset' ) ? { name: 'offset', value: offset } : field;
				});

				$.post( ajaxurl, formArray, function( response ) {
					if ( ! response.success ) {
						$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + response.data.error + '</span>' );
						$button.prop( 'disabled', false );
						return;
					}

					offset   = parseInt( response.data.offset );							
					imported = imported.concat( response.data.imported );
					updated  = updated.concat( response.data.updated );
					skipped  = skipped.concat( response.data.skipped );
					failed   = failed.concat( response.data.failed );
					errors   = errors.concat( response.data.errors );							

					let message = response.data.message;
					message = message.replace( '%%imported%%', imported.length );
					message = message.replace( '%%updated%%', updated.length );
					message = message.replace( '%%skipped%%', skipped.length );
					message = message.replace( '%%failed%%', failed.length );

					if ( response.data.step === 'process' ) {
						$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( message ) + '</span><span class="aiovg-animate-dots"></span>' );
						setTimeout( importBatch, 100 ); // Import next batch
					} else if ( response.data.step === 'done' ) {
						$( '#aiovg-csv_file, #aiovg-zip_file' ).val( '' );
						
						let html = '<span class="aiovg-text-success"><span class="dashicons dashicons-yes-alt"></span> ' + message + '</span>';

						if ( errors.length > 0 ) {
							html += '<div class="aiovg-form-fieldset">';
							html += '<h3 class="aiovg-text-error">' + aiovg_import_export.i18n.import_csv_error_status_heading + '</h3>';

							errors.forEach(( error ) => {
								html += '<span class="aiovg-status-item">' + error + '</span>';
							});

							html += '</div>';
						}

						$status.html( html );
						$button.prop( 'disabled', false );
					}
				}, 'json' );
			};

			// Start first batch
			importBatch();
		});

		// Export to CSV file
		$( '#aiovg-button-export-csv' ).on( 'click', function( event ) {
			event.preventDefault();

			const $button = $( this );
			const $status = $( '#aiovg-export-status' );

			$button.prop( 'disabled', true );
			$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( aiovg_import_export.i18n.preparing_export ) + '</span><span class="aiovg-animate-dots"></span>' );

			// Persistent formData across batches
			const formData = {
				action: 'aiovg_export_csv',
				security: aiovg_admin.ajax_nonce,
				offset: 0,
				limit: 200,
				total: 0, // Will be set by first batch
				timestamp: ( new Date().toISOString().replace( /[-:T.Z]/g, '' ).slice( 0, 14 ) ),
				file_name: '' // Will be set by first batch
			};

			// Recursive export function
			const exportBatch = function() {
				$.post( ajaxurl, formData, function( response ) {
					if ( ! response.success ) {
						$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + response.data.error + '</span>' );
						$button.prop( 'disabled', false );
						return;
					}					

					formData.offset    = parseInt( response.data.offset );
					formData.total     = parseInt( response.data.total );
					formData.file_name = response.data.file_name;

					if ( response.data.step === 'process' ) {
						$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( response.data.message ) + '</span><span class="aiovg-animate-dots"></span>' );
						setTimeout( exportBatch, 100 ); // Export next batch
					} else if ( response.data.step === 'done' ) {
						$status.html( '<span class="aiovg-text-success"><span class="dashicons dashicons-yes-alt"></span> ' + response.data.message + '</span>' );
						$button.prop( 'disabled', false );
					}
				}, 'json' );
			};

			// Start first batch
			exportBatch();
		});

		// Export to ZIP file
		$( '#aiovg-button-export-zip' ).on( 'click', function( event ) {
			event.preventDefault();

			const $button = $( this );
			const $status = $( '#aiovg-export-status' );

			$button.prop( 'disabled', true );
			$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( aiovg_import_export.i18n.preparing_export ) + '</span><span class="aiovg-animate-dots"></span>' );

			// Local variables to track progress
			let zip_files = [];
			let skipped   = [];
			let total     = 0;

			// Persistent formData across batches
			const formData = {
				action: 'aiovg_export_zip',
				security: aiovg_admin.ajax_nonce,
				offset: 0,
				limit: 50,
				total: total, // Will be set by first batch
				timestamp: ( new Date().toISOString().replace( /[-:T.Z]/g, '' ).slice( 0, 14 ) )
			};

			// Recursive export function
			const exportBatch = function() {
				$.post( ajaxurl, formData, function( response ) {
					if ( ! response.success ) {
						$status.html( '<span class="aiovg-text-error"><span class="dashicons dashicons-dismiss"></span> ' + response.data.error + '</span>' );
						$button.prop( 'disabled', false );
						return;
					}

					if ( Array.isArray( response.data.zip_files ) ) {
						response.data.zip_files.forEach( function( file ) {
							if ( zip_files.indexOf( file ) === -1 ) {
								zip_files.push( file );
							}
						} );
					}

					skipped = skipped.concat( response.data.skipped );
					total   = parseInt( response.data.total );

					formData.offset = parseInt( response.data.offset );
					formData.total  = total;

					if ( response.data.step === 'process' ) {
						$status.html( '<span class="spinner"></span> <span class="aiovg-text-success">' + removeEndingDot( response.data.message ) + '</span><span class="aiovg-animate-dots"></span>' );
						setTimeout( exportBatch, 100 ); // Export next batch
					} else if ( response.data.step === 'done' ) {
						let message = response.data.message;
						if ( zip_files.length === 0 ) {
							message = aiovg_import_export.i18n.export_zip_empty_status;
						}

						message = message.replace( '%%skipped%%', skipped.length );
						message = message.replace( '%%exported%%', ( total - skipped.length ) );

						let html = '<span class="aiovg-text-success"><span class="dashicons dashicons-yes-alt"></span> ' + message + '</span>';

						if ( zip_files.length > 0 ) {
							html += '<div class="aiovg-form-fieldset">';
							html += '<h3>' + aiovg_import_export.i18n.export_zip_success_status_heading + '</h3>';

							zip_files.forEach(( item ) => {
								const fileName = item.split( '/' ).pop(); // Extract just the file name
								html += '<a class="aiovg-status-item" href="' + encodeURI( item ) + '" target="_blank">' + fileName + '</a>';
							});

							html += '</div>';
						}

						if ( skipped.length > 0 ) {
							html += '<div class="aiovg-form-fieldset">';
							html += '<h3 class="aiovg-text-error">' + aiovg_import_export.i18n.export_zip_failed_status_heading + '</h3>';

							skipped.forEach(( item ) => {
								const fileName = item.split( '/' ).pop(); // Extract just the file name
								html += '<a class="aiovg-status-item" href="' + encodeURI( item ) + '" target="_blank">' + fileName + '</a>';
							});

							html += '</div>';
						}

						$status.html( html );
						$button.prop( 'disabled', false );						
					}
				}, 'json' );
			};

			// Start first batch
			exportBatch();
		});

	});	

})( jQuery );
