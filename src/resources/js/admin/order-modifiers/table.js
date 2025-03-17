/**
 * Order Modifier Fees Table
 *
 * @since 5.18.0
 */

/* global wp */
( function( $, tableSettings ) {
	$( document ).on( 'click', '.row-actions .delete a', ( e ) => {
		let message;
		switch ( tableSettings.modifier ) {
			case 'fee':
				message = wp.i18n.__( 'Are you sure you want to delete this Fee?', 'event-tickets' );
				break;

			case 'coupon':
			default:
				message = wp.i18n.__( 'Are you sure you want to delete this Coupon?', 'event-tickets' );
				break;
		}
		/* eslint-disable no-undef */
		if ( ! confirm( message ) ) {
			e.preventDefault();
		}
		/* eslint-enable no-undef */
	} );
} )( jQuery, window.etOrderModifiersTable || {} );
