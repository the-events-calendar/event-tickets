(function( window, $ ) {
	'use strict';
	$( '.ticket_list' ).on( 'click', '.ticket_delete', function() {
		return confirm( tribe_ticket_notices.confirm_alert );
	});
})( window, jQuery );
