var tribe_tickets_tpp = {
	num_attendees: 0,
	event: {}
};

(function( $, my ) {
	'use strict';

	my.init = function() {
		this.$rsvp = $( '.tribe-events-tickets-rsvp' );
		this.attendee_template = $( document.getElementById( 'tribe-tickets-tpp-tmpl' ) ).html();

		this.$rsvp.on( 'change', '.tribe-tickets-quantity', this.event.quantity_changed );

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
		var i = 0;
		var $rsvp = $quantity.closest( '.tribe-events-tickets-tpp' );
		var quantity = parseInt( $quantity.val(), 10 );

		if ( ! quantity ) {
			$rsvp.removeClass( 'tribe-tickets-has-tpp' );
		} else {
			$rsvp.addClass( 'tribe-tickets-has-tpp' );
		}
	};

	my.validate_submission = function() {
		var $name = $( document.getElementById( 'tribe-tickets-full-name' ) );
		var $email = $( document.getElementById( 'tribe-tickets-email' ) );

		if ( ! $.trim( $name.val() ).length || ! $.trim( $email.val() ).length ) {
			return false;
		}

		return true;
	};

	my.event.quantity_changed = function() {
		my.quantity_changed( $( this ) );
	};

	/**
	 * Handle Submission of RSVP
	 *
	 * @since 4.10.9 - Prevent multiple clicks on "Confirm RSVP" from submitting.
	 *
	 * @param e The event passed to the method.
	 * @returns {my.event|boolean}
	 */
	my.event.handle_submission = function ( e ) {

		var $form = $( this ).closest( 'form' );

		if ( ! my.validate_submission() ) {
			e.preventDefault();

			$form.addClass( 'tribe-tpp-message-display' );
			$form.find( '.tribe-tpp-message-confirmation-error' ).show();

			$( 'html, body' ).animate( {
				scrollTop: $form.offset().top
			}, 300 );

			return false;
		}

		// Check if Form is Submitted Already.
		if ( $form.data( 'submitted' ) === true ) {
			e.preventDefault();
		} else {
			$form.data( 'submitted', true );
		}

		// Keep chainability.
		return this;
	};

	$( function() {
		my.init();
	} );
})( jQuery, tribe_tickets_tpp );
