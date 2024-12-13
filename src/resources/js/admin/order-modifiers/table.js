/**
 * Order Modifier Fees Table
 *
 * @since 5.18.0
 */

( function( $ ) {
	$( document ).on( 'click', '.row-actions .delete a', ( e ) => {
		/* eslint-disable no-undef */
		if ( ! confirm( wp.i18n.__( 'Are you sure you want to delete this Fee?', 'event-tickets' ) ) ) {
			e.preventDefault();
		}
		/* eslint-enable no-undef */
	} );
} )( jQuery );
