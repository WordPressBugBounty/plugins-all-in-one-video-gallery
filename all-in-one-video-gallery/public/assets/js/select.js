(function( $ ) {
	'use strict';

	var aiovg = window.aiovg_select || window.aiovg_public;

	/**
	 * Init Dropdown.
	 */
	function initDropdown( el ) {
		var $this             = $( el );
		var $inputEl          = $this.find( '.aiovg-dropdown-input' );
		var $dropdownEl       = $this.find( '.aiovg-dropdown' );		
		var $dropdownListEl   = $this.find( '.aiovg-dropdown-list' );
		var $dropdownSearchEl = $this.find( '.aiovg-dropdown-search' );	
		var $dropdownItems    = $this.find( '.aiovg-dropdown-item' );	
		var $searchEmptyEl    = $this.find( '.aiovg-dropdown-no-items' ); 
		var $searchInputEl    = $dropdownSearchEl.find( 'input[type="text"]' );     
        var $searchResetBtn   = $dropdownSearchEl.find( 'button' ); 

		var totalItems = $dropdownItems.length;
		var stayOpen = parseInt( $dropdownSearchEl.data( 'stay_open' ) );
		var showSearchThreshold = parseInt( $dropdownSearchEl.data( 'show_search_threshold' ) );
		var currentFocus = -1;

		if ( totalItems == 0 ) {
			return false;
		}

		if ( totalItems >= showSearchThreshold ) {
			$dropdownSearchEl.prop( 'hidden', false );
		}

		// Helpers
		var setCategoryNames = function() {
			var terms = [];

			$dropdownListEl.find( '.aiovg-item-selected' ).each(function() {
				var termName = $( this ).find( '.aiovg-item-name' ).text();
				terms.push( termName );
			});

			$inputEl.val( terms.join( ', ' ) );
		}	
		
		var setActive = function( index ) {
			var $visible = $dropdownItems.filter( ':not([hidden])' );

			if ( $visible.length === 0 ) return;

			// Up arrow at top → return to search box
			if ( index < 0 ) {
				if ( ! $dropdownSearchEl.prop( 'hidden' ) ) {
					$searchInputEl.focus();
				}

				$dropdownItems.removeClass( 'aiovg-item-active' );
				currentFocus = -1;
				return;
			}

			if ( index >= $visible.length ) index = 0;
			currentFocus = index;

			$dropdownItems.removeClass( 'aiovg-item-active' );
			
			var $item = $visible.eq( currentFocus );
			$item.addClass( 'aiovg-item-active' );

			// Auto-scroll
			var container = $dropdownEl.get( 0 );
			var el = $item.get( 0 );

			if ( container && el ) {
				var top = el.offsetTop;
				var bottom = top + el.offsetHeight;

				if ( top < container.scrollTop ) {
					container.scrollTop = top;
				} else if ( bottom > container.scrollTop + container.clientHeight ) {
					container.scrollTop = bottom - container.clientHeight;
				}
			}
		}

		// Toggle Dropdown
		$inputEl.on( 'click', function() {
			$dropdownEl.toggle();				
		});	

		// Filter Items
		$searchInputEl.on( 'input', function( event ) {
			$this.addClass( 'aiovg-is-searching' );	

			$searchResetBtn.prop( 'hidden', false );
			$searchEmptyEl.prop( 'hidden', true );			

			var value = this.value.trim().toLowerCase();			

			if ( value ) {
				var matchesFound = false;

				$dropdownListEl.find( '.aiovg-dropdown-item' ).each(function() {
					var itemName = $( this ).find( '.aiovg-item-name' ).text();

					if ( itemName.toLowerCase().indexOf( value.toString() ) === -1 ) {
						this.hidden = true;
					} else {
						this.hidden = false;
						matchesFound = true;
					}   
				});

				if ( ! matchesFound ) {
					$searchEmptyEl.prop( 'hidden', false );
				}
			} else {
				$this.removeClass( 'aiovg-is-searching' );

				$searchResetBtn.prop( 'hidden', true );
				$dropdownListEl.find( '.aiovg-dropdown-item' ).prop( 'hidden', false );
			}
		});

		// Clear Search
		$searchResetBtn.on( 'click', function( event ) {
			event.preventDefault();

			$this.removeClass( 'aiovg-is-searching' );

			$searchInputEl.val( '' );
			$searchResetBtn.prop( 'hidden', true );

			$searchEmptyEl.prop( 'hidden', true );
			$dropdownListEl.find( '.aiovg-dropdown-item' ).prop( 'hidden', false );									
		});

		// Toggle Checkbox
		$dropdownListEl.find( '.aiovg-dropdown-item' ).on( 'click', function( event ) {
			var tagName = event.target.tagName.toLowerCase();

			if ( tagName !== 'input' ) {
				$( this ).find( 'input[type="checkbox"]' ).trigger( 'click' );
			}			
		});

		// Set Category Names in the input field
		$dropdownListEl.find( 'input[type="checkbox"]' ).on( 'change', function() {
			var isChecked = $( this ).is( ':checked' );
			
			if ( isChecked ) {
				$( this ).closest( '.aiovg-dropdown-item' ).addClass( 'aiovg-item-selected' );
			} else {
				$( this ).closest( '.aiovg-dropdown-item' ).removeClass( 'aiovg-item-selected' );
			}

			setCategoryNames();

			if ( ! stayOpen ) {
				$dropdownEl.hide();
			}
		});	
		
		setCategoryNames();

		// Auto-focus search when typing
		$inputEl.on( 'keydown', function( event ) {
			if ( ! $dropdownSearchEl.prop( 'hidden' ) ) {
				if ( /^[a-zA-Z0-9]$/.test( event.key ) ) {
					$dropdownEl.show();
					$searchInputEl.focus();
					return;
				}
			}
		});

		// Keyboard navigation
		$inputEl.on( 'keydown', function( event ) {
			// TAB → close dropdown
			if ( event.keyCode === 9 ) {
				$dropdownEl.hide();
				currentFocus = -1;
				return;
			}

			// Open on down arrow
			if ( $dropdownEl.is( ':hidden' ) ) {
				if ( event.keyCode === 40 || event.keyCode === 13 || event.keyCode === 32 ) {
					event.preventDefault(); // Prevent page scroll on space
					$dropdownEl.show();
				} else {
					return;
				}
			}

			var key = event.keyCode;

			if ( key === 40 ) { // ↓
				event.preventDefault();
				setActive( currentFocus + 1 );
			} else if ( key === 38 ) { // ↑
				event.preventDefault();
				setActive( currentFocus - 1 );
			} else if ( key === 13 ) { // ENTER
				event.preventDefault();
				var $visible = $dropdownItems.filter( ':not([hidden])' );
				if ( currentFocus > -1 && $visible.length > 0 ) {
					$visible.eq( currentFocus ).trigger( 'click' );
				}
			}
		});

		// ESC inside search field
		$searchInputEl.on( 'keydown', function( event ) {
			// TAB → close dropdown
			if ( event.keyCode === 9 ) {
				$dropdownEl.hide();
				currentFocus = -1;
				return;
			}

			// ESC → close dropdown
			if ( event.keyCode === 27 ) {
				$dropdownEl.hide();
				$inputEl.focus();
				currentFocus = -1;
			}

			// Down key → move from search → first item
			if ( event.keyCode === 40 ) {
				event.preventDefault();
				
				var $visible = $dropdownItems.filter( ':not([hidden])' );
				if ( $visible.length > 0 ) {
					currentFocus = 0;
					setActive( 0 );
					$inputEl.focus();
				}
			}
		});
	}

	/**
	 * Hide other Dropdowns.
	 */
	function hideOtherDropdowns( self = null ) {
		document.querySelectorAll( '.aiovg-dropdown-terms' ).forEach(( el ) => {
			if ( el !== self ) { 
				el.querySelector( '.aiovg-dropdown' ).style.display = 'none';
			}	
		});	
	}

	/**
	 * Init Autocomplete.
	 */
	function initAutocomplete( uid, items, callback ) {
		var inputEl = document.querySelector( '#aiovg-autocomplete-input-' + uid );
		var tagsEl  = document.querySelector( '#aiovg-autocomplete-tags-' + uid );

		var currentFocus = -1;					

		var showDropdown = function() {				
			var options = [];
			currentFocus = -1;

			// Close other dropdowns.
			closeDropdown();			

			// Create a dropdown element.
			var dropdownEl = document.createElement( 'div' );
			dropdownEl.setAttribute( 'id', 'aiovg-autocomplete-items-' + uid );
			dropdownEl.setAttribute( 'class', 'aiovg-autocomplete-items' );

			// Append the dropdown element as a child to the autocomplete container.
			inputEl.parentNode.appendChild( dropdownEl );

			// Bind options.
			items.forEach(( item, index ) => {
				var value = item.value;
				var label = item.label;

				var isValid = false;

				if ( ! inputEl.value ) {
					isValid = true;
				} else if ( label.toLowerCase().indexOf( inputEl.value.toLowerCase() ) !== -1 ) {
					isValid = true;
				}				

				if ( ! isValid && index == items.length - 1 && options.length == 0 ) {
					value = 0;
					label = aiovg.i18n.no_tags_found;

					isValid = true;
				}

			  	if ( isValid ) {
					// Create an option element.
					var option = document.createElement( 'div' );
					option.innerHTML = label;

					if ( value == 0 ) {
						option.className = 'aiovg-text-muted';
					}

					var tagEl = tagsEl.querySelector( '.aiovg-tag-item-' + value );
					if ( tagEl !== null ) {
						option.setAttribute( 'class', 'aiovg-autocomplete-selected' );
					}
					
					// Called when the user clicks on the option.
					option.addEventListener( 'click', function() {
						// Reset the input field.
						inputEl.value = '';

						// Insert the value.					
						callback( value, label );

						// Close the dropdown.
						closeDropdown();
					});

					dropdownEl.appendChild( option );
					options.push( option );
			  	}
			});
		};

		var closeDropdown = function( el = null ) {
			var id = 0;

			if ( el && el.id ) {
				id = el.id.replace( 'aiovg-autocomplete-input-', '' );
			}

			document.querySelectorAll( '.aiovg-autocomplete-items' ).forEach(( dropdownEl ) => {
				if ( dropdownEl.getAttribute( 'id' ) != ( 'aiovg-autocomplete-items-' + id ) ) {
					dropdownEl.remove();
				}
			});
		};

		var addActive = function( dropdownEl ) {
			removeActive( dropdownEl );

			if ( currentFocus >= dropdownEl.length ) {
			  	currentFocus = 0;
			}

			if ( currentFocus < 0 ) {
			  	currentFocus = dropdownEl.length - 1;
			}

			dropdownEl[ currentFocus ].classList.add( 'aiovg-autocomplete-active' );
	  	};

		var removeActive = function( dropdownEl ) {
			dropdownEl.forEach(( el ) => {
				el.classList.remove( 'aiovg-autocomplete-active' );
			});
		};

		// Called when the user focuses on the input field.
		inputEl.addEventListener( 'focus', function( event ) { 
			if ( event.target.value == '' ) {
				showDropdown();
			}
		});

		// Called when the user writes on the input field.
		inputEl.addEventListener( 'input', function() {
			showDropdown();
		});

		// Called when the user presses a key on the keyboard.
		inputEl.addEventListener( 'keydown', function( event ) {
			var dropdownEl = document.querySelector( '#aiovg-autocomplete-items-' + uid );

			if ( dropdownEl ) {
				dropdownEl = dropdownEl.querySelectorAll( 'div' );
			}

			if ( ! dropdownEl ) {
				return false;
			}

			if ( event.keyCode == 40 ) {
			  	// If the arrow DOWN key is pressed,
			  	// increase the currentFocus variable
			  	currentFocus++;
			  	// and and make the current item more visible
			  	addActive( dropdownEl );
			} else if ( event.keyCode == 38 ) {
			  	// If the arrow UP key is pressed,
			  	// decrease the currentFocus variable
			  	currentFocus--;
			  	// and and make the current item more visible
				addActive( dropdownEl );
			} else if ( event.keyCode == 13 ) {
			  	// If the ENTER key is pressed, prevent the form from being submitted,
			  	event.preventDefault();

			  	if ( currentFocus > -1 ) {
					// and simulate a click on the 'active' item
					dropdownEl[ currentFocus ].click();
			  	}
			}
		});

		// Called when the user clicks on the document outside the autocomplete element.
		if ( ! aiovg.hasOwnProperty( 'autocomplete' ) ) {
			aiovg.autocomplete = true;
			
			document.addEventListener( 'click', function( event ) {
				closeDropdown( event.target );
			});
		}		
	}

	/**
	 * Called when the page has loaded.
	 */
	$(function() {	

		// Init Dropdown
		$( '.aiovg-dropdown-terms' ).each(function() {
			initDropdown( this );
		});

		// Hide dropdowns when the user clicks on the document outside the select element.
		document.addEventListener( 'click', ( event ) => {
			const self = event.target.closest( '.aiovg-dropdown-terms' );
			hideOtherDropdowns( self );		
        });

		// Hide dropdowns when the user clicks the ESC key.
		document.addEventListener( 'keydown', ( event ) => {
			if (  event.keyCode == 27 ) {
				hideOtherDropdowns();	
			}
		});

		// Init Autocomplete
		$( '.aiovg-autocomplete' ).each(function() {
			var uid = $( this ).data( 'uid' );

			var name = 'ta[]';
			if ( typeof $( this ).data( 'name' ) !== 'undefined' ) {
				name = $( this ).data( 'name' );
			}

			var items = [];			

			$( 'option', '#aiovg-autocomplete-select-' + uid ).each(function() {
				items.push({
					value: $( this ).val(),
					label: $( this ).text()
				});
			});

			if ( items.length == 0 ) {
				items.push({
					value: 0,
					label: aiovg.i18n.no_tags_found
				});
			}

			var callback = function( value, label ) {
				value = parseInt( value );

				if ( value != 0 ) {				
					var $tags  = $( '#aiovg-autocomplete-tags-' + uid );	
					var length = $tags.find( '.aiovg-tag-item-' + value ).length;

					if ( length == 0 ) {
						var html = '<span class="aiovg-tag-item aiovg-tag-item-' + value + '">';						
						html += '<a href="javascript:void(0);">';
						html += '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="aiovg-flex-shrink-0">' +
							'<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />' +
						'</svg>';
						html += label;
						html += '</a>';
						html += '<input type="hidden" name="' + name + '" value="' + value + '" />';
						html += '</span>';
						
						$tags.append( html );
					}
				}
			};

			initAutocomplete( uid, items, callback );
		});

		$( document ).on( 'click', '.aiovg-tag-item a', function( event ) {
			event.preventDefault();
			$( this ).parent().remove();
		});
		
	});

})( jQuery );
