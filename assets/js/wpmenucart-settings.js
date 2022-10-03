jQuery( function ( $ ) {

	$( '.hidden-input' ).on( 'click', function() {
		$( this ).closest( '.hidden-input' ).prev( '.pro-feature' ).show( 'slow' );
		$( this ).closest( '.hidden-input' ).hide();
	} );

	$( '.hidden-input-icon' ).on( 'click', function() {
		$( '.pro-icon' ).show( 'slow' );
	} );

} );