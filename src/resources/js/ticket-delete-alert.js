(function( window, $ ) {
	'use strict';
	var message = ticket_notices.alert;
	console.log(message);
	$(".ticket_delete").click( function(){
		alert('Are you sure you want to delete this ticket?');
	})
})( window, jQuery );
