/* Minimal, accessible slide-out behavior */
( function( $ ) {

	function getSlideout() {
		return $( '.wpmenucart-slideout' ).first();
	}

	function openSlideout() {
		var $slideout = getSlideout();

		if ( ! $slideout.length ) {
			return;
		}

		$( 'html, body' ).css( 'overflow', 'hidden' );
		$( 'body' ).addClass( 'wpmenucart-slideout-open' );
		$slideout.addClass( 'open' );
		$slideout.find( '.wpmenucart-slideout__panel' ).attr( 'aria-hidden', 'false' ).focus();
	}

	function closeSlideout() {
		var $slideout = getSlideout();

		if ( ! $slideout.length ) {
			return;
		}

		if ( ! $slideout.hasClass( 'open' ) ) {
			return;
		}

		$slideout.removeClass( 'open' );
		$( 'body' ).removeClass( 'wpmenucart-slideout-open' );
		$slideout.find( '.wpmenucart-slideout__panel' ).attr( 'aria-hidden', 'true' );
		$( 'html, body' ).css( 'overflow', '' );
	}

	function initEvents() {
		// trigger: clicking the menu cart anchor opens the slideout instead of navigating
		$( document ).on( 'click', 'a.wpmenucart-contents, .wpmenucart-contents', function( e ) {
			if ( ! $( 'div.wpmenucart-slideout' ).length ) {
				return; // no slideout present
			}

			var $li        = $( this ).closest( '.wpmenucartli' );
			var breakpoint = parseInt( wpmenucart_slideout.mobile_breakpoint, 10 ) || 768;
			var isMobile   = window.innerWidth <= breakpoint;
			var modeClass  = isMobile ? 'mobile-active-mode-sidebar' : 'desktop-active-mode-sidebar';

			if ( ! $li.hasClass( modeClass ) ) {
				return;
			}

			e.preventDefault();
			openSlideout();
		} );

		// close actions
		$( document ).on( 'click', '.wpmenucart-slideout__close', function( e ) {
			e.preventDefault();
			closeSlideout();
		} );

		$( document ).on( 'click', '.wpmenucart-slideout__overlay', function( e ) {
			e.preventDefault();
			closeSlideout();
		} );

		$( document ).on( 'keydown', function( e ) {
			if ( e.key === 'Escape' && getSlideout().hasClass( 'open' ) ) {
				closeSlideout();
			}
		} );
	}

	$( initEvents );

} )( jQuery );
