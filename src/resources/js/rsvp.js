var tribe_tickets_rsvp = {
	num_attendees: 0,
	event: {}
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
		});
	};

	my.quantity_changed = function( $quantity ) {
		var $rsvp = $quantity.closest( '.tribe-events-tickets-rsvp' );
		var $rsvp_qtys = $rsvp.find( '.tribe-tickets-quantity' );
		var rsvp_qty = 0;
		$rsvp_qtys.each( function() {
			rsvp_qty = rsvp_qty + parseInt( $( this ).val() );
		} );

		if( 0 === rsvp_qty ) {
			$rsvp.removeClass( 'tribe-tickets-has-rsvp' );
		} else {
			$rsvp.addClass( 'tribe-tickets-has-rsvp' );
		}
	};

	my.validate_rsvp_info = function( $form ) {
		var rsvp_qty = 0;
		var $qty = $form.find( 'input.tribe-tickets-quantity' );
		var $name = $form.find( 'input#tribe-tickets-full-name' );
		var $email = $form.find( 'input#tribe-tickets-email' );

		$qty.each( function() {
			rsvp_qty = rsvp_qty + parseInt( $( this ).val() );
		} );

		return !!(
			$.trim( $name.val() ).length
			&& $.trim( $email.val() ).length
			&& rsvp_qty
		);
	};

	my.validate_meta = function( $form ) {
		var is_meta_valid = true;
		var has_tickets_plus = !!window.tribe_event_tickets_plus;

		if( has_tickets_plus ) {
			is_meta_valid = window.tribe_event_tickets_plus.meta.validate_meta( $form );
		}

		return is_meta_valid;
	};

	my.event.quantity_changed = function() {
		my.quantity_changed( $( this ) );
	};

	my.event.handle_submission = function( e ) {
		var $form = $( this ).closest( 'form' );

		var $rsvp_msgs = $form.find( '.tribe-rsvp-messages, .tribe-rsvp-message-confirmation-error' );
		var $etp_meta_msgs = $form.find( '.tribe-event-tickets-meta-required-message' );

		var is_rsvp_info_valid = !!my.validate_rsvp_info( $form );
		var is_attendee_meta_valid = !!my.validate_meta( $form );

		// Show/Hide message about missing RSVP details (name, email, going/not) and/or missing ETP fields (if applicable).
		if(
			!is_rsvp_info_valid
			|| !is_attendee_meta_valid
		) {
			is_rsvp_info_valid
				? $rsvp_msgs.hide()
				: $rsvp_msgs.show();

			if( is_attendee_meta_valid ) {
				$etp_meta_msgs.hide();
				$form.removeClass( 'tribe-event-tickets-plus-meta-missing-required' );
			} else {
				$form.addClass( 'tribe-event-tickets-plus-meta-missing-required' );
				$etp_meta_msgs.show();
			}

			$( 'html, body' ).animate( {
				scrollTop: $form.offset().top - 100,
			}, 300 );

			return false;
		} else {
			return true;
		}
	};

	$( function() {
		my.init();
	} );
})( jQuery, tribe_tickets_rsvp );
