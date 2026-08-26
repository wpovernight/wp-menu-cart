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

		// Cart mode selector: show/hide sub-panels based on selected mode per context
		function initCartModeSelectors() {
			function updateSubPanels( context, selectedMode ) {
				$( '.wpmenucart-subpanel[data-context="' + context + '"]' ).hide();

				if ( selectedMode && selectedMode !== 'none' ) {
					$( '.wpmenucart-subpanel[data-context="' + context + '"][data-mode="' + selectedMode + '"]' ).show();
				}
			}

			$( document ).on( 'change', '.wpmenucart-mode-card input[type="radio"]', function() {
				var context      = $( this ).closest( '.wpmenucart-mode-group' ).data( 'context' );
				var selectedMode = $( this ).val();

				$( this ).closest( '.wpmenucart-mode-group' ).find( '.wpmenucart-mode-card' ).removeClass( 'wpmenucart-mode-card--selected' );
				$( this ).closest( '.wpmenucart-mode-card' ).addClass( 'wpmenucart-mode-card--selected' );

				updateSubPanels( context, selectedMode );
			} );

			// Initialize on page load for both contexts
			$( '.wpmenucart-mode-group' ).each( function() {
				var context      = $( this ).data( 'context' );
				var selectedMode = $( this ).find( '.wpmenucart-mode-card input[type="radio"]:checked' ).val();
				updateSubPanels( context, selectedMode );
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

				$cards.find( '.wpmenucart-mode-card' ).removeClass( 'wpmenucart-mode-card--selected' );
				$( '.wpmenucart-subpanel[data-context="icon_style"]' ).hide();

				if ( ! enabled ) {
					var $checked = $cards.find( 'input[type="radio"]:checked' );
					$checked.closest( '.wpmenucart-mode-card' ).addClass( 'wpmenucart-mode-card--selected' );

					var selectedMode = $checked.val();
					if ( selectedMode ) {
						$( '.wpmenucart-subpanel[data-context="icon_style"][data-mode="' + selectedMode + '"]' ).show();
					}
				}
			}

			$toggle.on( 'change', update );
			update();
		}

		initIconStyleCustomToggle();
	}
);
