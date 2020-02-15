var tribe_tickets_rsvp = {
	num_attendees: 0,
	event        : {},
};

(function( $, my ) {
	'use strict';

	my.init = function() {
		this.$rsvp = $( '.tribe-events-tickets-rsvp' );
		this.attendee_template = $( document.getElementById( 'tribe-tickets-rsvp-tmpl' ) ).html();

		this.$rsvp.on( 'change input keyup', '.tribe-tickets-quantity', this.event.quantity_changed );

		this.$rsvp.closest( '.cart' )
			.on( 'submit', this.event.handle_submission );

		$( '.tribe-rsvp-list' ).on( 'click', '.attendee-meta-row .toggle', function() {
			$( this )
				.toggleClass( 'on' )
				.siblings( '.attendee-meta-details' )
				.slideToggle();
		} );
	};

	my.quantity_changed = function( $quantity ) {
		let $rsvp = $quantity.closest( '.tribe-events-tickets-rsvp' );
		let $rsvpQtys = $rsvp.find( '.tribe-tickets-quantity' );
		let rsvpQty = 0;
		$rsvpQtys.each( function() {
			rsvpQty = rsvpQty + parseInt( $( this ).val() );
		} );

		if ( 0 === rsvpQty ) {
			$rsvp.removeClass( 'tribe-tickets-has-rsvp' );
		} else {
			$rsvp.addClass( 'tribe-tickets-has-rsvp' );
		}
	};

	my.validate_rsvp_info = function( $form ) {
		let rsvpQty = 0;
		let $qty = $form.find( 'input.tribe-tickets-quantity' );
		let $name = $form.find( 'input#tribe-tickets-full-name' );
		let $email = $form.find( 'input#tribe-tickets-email' );

		$qty.each( function() {
			rsvpQty = rsvpQty + parseInt( $( this ).val() );
		} );

		return !! (
			$.trim( $name.val() ).length &&
			$.trim( $email.val() ).length &&
			rsvpQty
		);
	};

	my.validate_meta = function( $form ) {
		let isMetaValid = true;
		let hasTicketsPlus = !! window.tribe_event_tickets_plus;

		if ( hasTicketsPlus ) {
			isMetaValid = window.tribe_event_tickets_plus.meta.validate_meta( $form );
		}

		return isMetaValid;
	};

	my.event.quantity_changed = function() {
		my.quantity_changed( $( this ) );
	};

	my.event.handle_submission = function( e ) {
		let $form = $( this ).closest( 'form' );

		let $rsvpMessages = $form.find( '.tribe-rsvp-messages, .tribe-rsvp-message-confirmation-error' );
		let $etpMetaMessages = $form.find( '.tribe-event-tickets-meta-required-message' );

		let isRsvpInfoValid = !! my.validate_rsvp_info( $form );
		let isAttendeeMetaValid = !! my.validate_meta( $form );

		// Show/Hide message about missing RSVP details (name, email, going/not) and/or missing ETP fields (if applicable).
		if (
			! isRsvpInfoValid
			|| ! isAttendeeMetaValid
		) {
			isRsvpInfoValid
				? $rsvpMessages.hide()
				: $rsvpMessages.show();

			if ( isAttendeeMetaValid ) {
				$etpMetaMessages.hide();
				$form.removeClass( 'tribe-event-tickets-plus-meta-missing-required' );
			} else {
				$form.addClass( 'tribe-event-tickets-plus-meta-missing-required' );
				$etpMetaMessages.show();
			}

			$( 'html, body' ).animate( {
				scrollTop: $form.offset().top - 100,
			}, 300 );

			return false;
		}

		return true;
	};

	$( function() {
		my.init();
	} );
})( jQuery, tribe_tickets_rsvp );
