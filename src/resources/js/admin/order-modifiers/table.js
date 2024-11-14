/**
 * Order Modifier Fees Table
 *
 * @since TBD
 */

(function ( $ ) {
	'use strict';

	$(document).on( 'click', '.row-actions .delete a', (e) => {
		if ( ! confirm( wp.i18n.__( 'Are you sure you want to delete this Fee?', 'event-tickets' ) ) ) {
			e.preventDefault();
		}
	} );
})( jQuery );
