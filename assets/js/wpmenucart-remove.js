// used when the plugin setting is enabled
jQuery(
	function ( $ ) {
		var removalTimers = {};

		$( document.body ).on(
			'click', '.wpmenucart-product-remove', function ( event ) {
				event.preventDefault();

				var $removeBtn = $( this );
				var $itemRow   = $removeBtn.closest( '.wpmenucart-submenu-item' );
				var itemKey    = $removeBtn.data( 'key' );
				var source     = $removeBtn.data( 'source' );

				if ( ! itemKey || removalTimers[ itemKey ] || $itemRow.hasClass( 'removing' ) ) {
					return;
				}

				$itemRow.addClass( 'removing' );

				// Delay actual removal to give the user time to undo
				removalTimers[ itemKey ] = setTimeout( function() {
					performActualRemoval( itemKey, $itemRow, source );
				}, 1700 );
			}
		);

		$( document.body ).on(
			'click', '.wpmenucart-undo-button', function( event ) {
				event.preventDefault();

				var $itemRow = $( this ).closest( '.wpmenucart-slideout__item' );
				var itemKey  = $itemRow.find( '.wpmenucart-product-remove' ).data( 'key' );

				if ( removalTimers[ itemKey ] ) {
					clearTimeout( removalTimers[ itemKey ] );
					delete removalTimers[ itemKey ];
				}

				$itemRow.removeClass( 'removing collapsing' );

				// Reset inline styles set by the JS-driven collapse animation
				$itemRow.find( '.wpmenucart-slideout__undo-card' ).css( {
					transform:  '',
					opacity:    '',
					background: ''
				} );

				$itemRow.find( '.wpmenucart-slideout__undo-label' ).css( 'opacity', '' );
			}
		);

		function animateCollapseCard( $card, onComplete ) {
			var startTime  = null;
			var duration   = 600;
			var $undoLabel = $card.find( '.wpmenucart-slideout__undo-label' );

			// Keyframes: progress, scaleY, opacity, background color
			// The dip at 55% then rebound at 72% creates the bounce effect
			var frames = [
				{ p: 0,    s: 1,    o: 1,   bg: '#ae4149' },
				{ p: 0.55, s: 0.05, o: 0.7, bg: '#c9a0a4' },
				{ p: 0.72, s: 0.22, o: 0.5, bg: '#d5d9eb' },
				{ p: 1,    s: 0,    o: 0,   bg: '#d5d9eb' }
			];

			function hexToRgb( hex ) {
				var r = parseInt( hex.slice( 1, 3 ), 16 );
				var g = parseInt( hex.slice( 3, 5 ), 16 );
				var b = parseInt( hex.slice( 5, 7 ), 16 );
				return { r: r, g: g, b: b };
			}

			function lerpColor( a, b, t ) {
				var ca = hexToRgb( a );
				var cb = hexToRgb( b );
				return 'rgb(' +
					Math.round( ca.r + ( cb.r - ca.r ) * t ) + ',' +
					Math.round( ca.g + ( cb.g - ca.g ) * t ) + ',' +
					Math.round( ca.b + ( cb.b - ca.b ) * t ) + ')';
			}

			function lerp( a, b, t ) {
				return a + ( b - a ) * t;
			}

			function getValuesAt( progress ) {
				var from, to;
				for ( var i = 0; i < frames.length - 1; i++ ) {
					if ( progress >= frames[ i ].p && progress <= frames[ i + 1 ].p ) {
						from = frames[ i ];
						to   = frames[ i + 1 ];
						break;
					}
				}
				if ( ! from ) {
					return { s: 0, o: 0, bg: '#d5d9eb' };
				}
				var span = to.p - from.p;
				var t    = span === 0 ? 1 : ( progress - from.p ) / span;
				return {
					s:  lerp( from.s, to.s, t ),
					o:  lerp( from.o, to.o, t ),
					bg: lerpColor( from.bg, to.bg, t )
				};
			}

			function step( timestamp ) {
				if ( ! startTime ) startTime = timestamp;
				var elapsed  = timestamp - startTime;
				var progress = Math.min( elapsed / duration, 1 );
				var v        = getValuesAt( progress );

				$card.css( {
					transform:  'scaleY(' + v.s + ')',
					opacity:    v.o,
					background: v.bg
				} );

				// Fade out undo content early so it's fully gone before the rebound kicks in
				if ( progress <= 0.35 ) {
					$undoLabel.css( 'opacity', 1 );
				} else if ( progress <= 0.5 ) {
					$undoLabel.css( 'opacity', 1 - ( ( progress - 0.35 ) / 0.15 ) );
				} else {
					$undoLabel.css( 'opacity', 0 );
				}

				if ( progress < 1 ) {
					requestAnimationFrame( step );
				} else {
					onComplete();
				}
			}

			requestAnimationFrame( step );
		}

		function performActualRemoval( key, $itemRow, source ) {
			var $slideout = $( '.wpmenucart-slideout' );
			var $panel    = $slideout.find( '.wpmenucart-slideout__panel' );
			var $card     = $itemRow.find( '.wpmenucart-slideout__undo-card' );

			// Bail if already in progress - prevents double-firing from rapid clicks
			if ( $itemRow.hasClass( 'collapsing' ) ) {
				return;
			}

			$itemRow.addClass( 'collapsing' );

			animateCollapseCard( $card, function() {
				$itemRow.addClass( 'collapsed' );

				// Fire AJAX immediately after the visual animation ends,
				// then wait only for the CSS height collapse before swapping the DOM
				$.ajax( {
					type:     'POST',
					url:      wpmenucart_ajax.ajaxurl,
					dataType: 'json',
					data: {
						security: wpmenucart_ajax.nonce,
						action:   'wpmenucart_ajax_remove_cart_item',
						key:      key,
						source:   source,
					},
					success: function( response ) {
						if ( response && response.success && response.data ) {
							setTimeout( function() {
								if ( response.data.menu_cart ) {
									$( '.wpmenucartli' ).html( response.data.menu_cart );
								}
								if ( $slideout.length && response.data.mini_cart_slideout ) {
									var newInnerContent = $( response.data.mini_cart_slideout ).find( '.wpmenucart-slideout__panel' ).html();
									$panel.html( newInnerContent );
								}
								$( document ).trigger( 'wpmenucart_update_cart_ajax' );
							}, 420 );
						} else {
							$itemRow.removeClass( 'removing collapsing collapsed' );
						}
					},
					error: function() {
						$itemRow.removeClass( 'removing collapsing collapsed' );
					}
				} );
			} );
		}
	}
);