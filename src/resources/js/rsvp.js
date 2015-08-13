var tribe_tickets_rsvp = {
	num_attendees: 0,
	event: {}
};

(function( $, my ) {
	'use strict';

	my.init = function() {
		this.$rsvp = $( '.tribe-events-tickets-rsvp' );
		this.attendee_template = $( document.getElementById( 'tribe-tickets-rsvp-tmpl' ) ).html();

		this.$rsvp.on( 'change', '.tribe-ticket-quantity', this.event.quantity_changed );
	};

	my.quantity_changed = function( $quantity ) {
		var i = 0;
		var $rsvp = $quantity.closest( '.tribe-events-tickets-rsvp' );
		var quantity = parseInt( $quantity.val(), 10 );

		if ( ! quantity ) {
			$rsvp.removeClass( 'tribe-tickets-has-rsvp' );
		} else {
			$rsvp.addClass( 'tribe-tickets-has-rsvp' );
		}
	};

	my.event.quantity_changed = function() {
		my.quantity_changed( $( this ) );
	};

	$( function() {
		my.init();
	} );
})( jQuery, tribe_tickets_rsvp );
