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

	my.add_attendee = function( $rsvp ) {
		var html = this.attendee_template;
		html = html.replace( 'attendee[]', 'attendee[' + this.num_attendees + ']' );
		html = html.replace( 'data-attendee=""', 'data-attendee="' + this.num_attendees + '"' );

		$rsvp.find( '.tribe-tickets-attendees' ).append( html );
		$rsvp.find( '[data-attendee="' + this.num_attendees +'"] .tribe-tickets-attendee-heading' ).html( tribe_tickets_rsvp_strings.attendee.replace( '%1$s', this.num_attendees + 1 ) );

		this.num_attendees++;
	};

	my.quantity_changed = function( $quantity ) {
		var $rsvp = $quantity.closest( '.tribe-events-tickets-rsvp' );
		var quantity = parseInt( $quantity.val(), 10 );

		if ( ! quantity ) {
			$rsvp.removeClass( 'tribe-tickets-has-rsvp' );
		} else {
			$rsvp.addClass( 'tribe-tickets-has-rsvp' );
		}

		if ( quantity > this.num_attendees ) {
			for ( var i = this.num_attendees; i < quantity; i++ ) {
				this.add_attendee( $rsvp );
			}
		}
	};

	my.event.quantity_changed = function() {
		my.quantity_changed( $( this ) );
	};

	$( function() {
		my.init();
	} );
})( jQuery, tribe_tickets_rsvp );
