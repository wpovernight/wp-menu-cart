jQuery(
	function ( $ ) {
		$( '.hidden-input' ).on( 'click', function() {
			$( this ).closest( '.hidden-input' ).prev( '.pro-feature' ).show( 'slow' );
			$( this ).closest( '.hidden-input' ).hide();
		} );

		$( '.hidden-input-icon' ).on( 'click', function() {
			$( '.pro-icon' ).show( 'slow' );
		} );

		// Range slider functionality
		function setSliderFill( $range ) {
			var min = parseFloat( $range.attr( 'min' ) ) || 0;
			var max = parseFloat( $range.attr( 'max' ) ) || 100;
			var val = parseFloat( $range.val() ) || min;
			var pct = max > min ? ( ( val - min ) / ( max - min ) ) * 100 : 0;
			$range.css( '--wpmenucart-slider-fill', pct + '%' );
		}

		$( '.wpmenucart-range-slider' ).each( function() {
			var $range  = $( this );
			var $input  = $( '#' + $range.data( 'target' ) );
			var $hidden = $( '#' + $input.data( 'hidden' ) );

			// Initialize fill on load
			setSliderFill( $range );

			// Sync range slider with input
			$range.on( 'input change', function() {
				var val = $( this ).val();
				$input.val( val );
				$hidden.val( val );
				setSliderFill( $range );
			} );

			// Sync input with range slider
			$input.on( 'input change', function() {
				var min = parseInt( $range.attr( 'min' ) ) || 0;
				var max = parseInt( $range.attr( 'max' ) ) || 100;
				var val = parseInt( $( this ).val() ) || min;
				val = Math.max( min, Math.min( max, val ) );
				$( this ).val( val );
				$range.val( val );
				$hidden.val( val );
				setSliderFill( $range );
			} );
		} );

		// Generic conditional field visibility: shows or hides a field's row
		// (or a select's own custom sub-panel) based on another field's value.
		function initConditionalVisibility() {
			var rowSelector = 'tr, .wpmenucart-custom-field-row, .wpmenucart-select-custom-panel, .wpmenucart-subpanel';
			var bound       = {};

			function update( $controller ) {
				var name = ( $controller.attr( 'name' ) || '' ).replace( /\[\]$/, '' );

				if ( ! name ) {
					return;
				}

				var checkbox = $controller.is( ':checkbox' );
				var radio    = $controller.is( ':radio' );
				var value;

				if ( checkbox ) {
					value = $controller.is( ':checked' );
				} else if ( radio ) {
					// Radios share one name across several elements. Resolve
					// from whichever one is actually checked, not from
					// whichever radio happened to trigger this call.
					var $checked = $( 'input[type="radio"][name="' + name + '"]:checked' );
					value = $checked.length ? $checked.val() : null;
				} else {
					value = $controller.val();
				}

				var $controllerRow      = $controller.closest( rowSelector );
				var controllerIsVisible = ! $controllerRow.length || $controllerRow.is( ':visible' );

				$( '[data-show_for_option_name="' + name + '"]' ).each( function() {
					var $conditional = $( this );
					var $target      = $conditional.closest( rowSelector );
					var showFor      = $conditional.data( 'show_for_option_values' );
					var keepValue    = $conditional.data( 'keep_current_value' );
					var show;

					if ( ! Array.isArray( showFor ) ) {
						showFor = [ showFor ];
					}
					showFor = showFor.map( String );

					if ( ! controllerIsVisible ) {
						show = false;
					} else if ( checkbox ) {
						show = value;
					} else if ( Array.isArray( value ) ) {
						show = value.some( function( item ) {
							return showFor.indexOf( String( item ) ) !== -1;
						} );
					} else {
						show = showFor.indexOf( String( value ) ) !== -1;
					}

					if ( show ) {
						var wasHidden = ! $target.is( ':visible' );

						$target.show();

						// Re-evaluate nested conditionals when this target becomes visible.
						if ( wasHidden ) {
							$target.find( ':input' ).trigger( 'change' );
						}
					} else {
						$target.hide();

						if ( ! keepValue ) {
							$target.find( ':input' ).each( function() {
								var $input = $( this );

								if ( $input.is( 'select' ) ) {
									$input.prop( 'selectedIndex', 0 );
								} else if ( $input.is( ':checkbox' ) ) {
									$input.prop( 'checked', false );
								} else {
									$input.val( '' );
								}

								$input.trigger( 'change' );
							} );
						}
					}
				} );
			}

			$( '[data-show_for_option_name]' ).each( function() {
				var name = $( this ).data( 'show_for_option_name' );

				if ( bound[ name ] ) {
					return;
				}
				bound[ name ] = true;

				$( document ).on( 'change', '[name="' + name + '"], [name="' + name + '[]"]', function() {
					update( $( this ) );
				} );

				$( '[name="' + name + '"], [name="' + name + '[]"]' ).each( function() {
					update( $( this ) );
				} );
			} );
		}

		initConditionalVisibility();

		// Cart mode selector: card selection styling.
		function initCartModeSelectors() {
			$( document ).on( 'change', '.wpmenucart-mode-card input[type="radio"]', function() {
				$( this ).closest( '.wpmenucart-mode-group' ).find( '.wpmenucart-mode-card' ).removeClass( 'wpmenucart-mode-card--selected' );
				$( this ).closest( '.wpmenucart-mode-card' ).addClass( 'wpmenucart-mode-card--selected' );
			} );
		}

		initCartModeSelectors();

		// Icon Style: toggle the Custom section, disabling the template gallery while it's on.
		function initIconStyleCustomToggle() {
			var $toggle = $( '#icon_style_custom_enabled' );

			if ( ! $toggle.length ) {
				return;
			}

			var $cards  = $( '.wpmenucart-section--icon-style .wpmenucart-mode-cards' );
			var $notice = $( '.wpmenucart-icon-style-disabled-notice' );
			var $fields = $( '.wpmenucart-custom-section-fields' );

			function update() {
				var enabled = $toggle.is( ':checked' );

				$cards.toggleClass( 'wpmenucart-mode-cards--disabled', enabled );
				$notice.toggle( enabled );
				$fields.toggleClass( 'wpmenucart-custom-section-fields--collapsed', ! enabled );

				// The fields block's own visibility just changed. Re-trigger
				// change on anything inside it so initConditionalVisibility()
				// re-evaluates dependents (e.g. icon_display's) against the
				// current state, instead of leaving them on whatever was
				// computed the last time this block was visible.
				$fields.find( ':input' ).trigger( 'change' );

				$( document ).trigger( 'wpmenucart:icon_style_custom_toggled', [ enabled ] );
			}

			$toggle.on( 'change', update );
			update();
		}

		initIconStyleCustomToggle();
	}
);
