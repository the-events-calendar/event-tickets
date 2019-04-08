var tribe_tickets_rsvp_block = {
	events: {},
};

( function( $, my ) {
	'use strict';

	/**
	 * Handle the "Going" and "Not Going" button toggle,
	 * set them active and inactive so they can only use
	 * one at a time.
	 *
	 * @since 4.9
	 *
	 * @param {obj} $button The dom object of the clicked button
	 */
	my.tribe_rsvp_toggle_actions = function( $button ) {
		// Check if is the going or not going button
		var going      = $button.hasClass( 'tribe-block__rsvp__status-button--going' );
		var sibling    = going ? '.tribe-block__rsvp__status-button--not-going' : '.tribe-block__rsvp__status-button--going';
		var $siblingEl = $button.closest( '.tribe-block__rsvp__status' ).find( sibling );

		// Add active classs to the current button
		$button.addClass( 'tribe-active' );
		$button.removeClass( 'tribe-inactive' );
		$button.attr( 'disabled', 'disabled' );

		// Remove the active class of the other button and disable it
		$siblingEl.addClass( 'tribe-inactive' );
		$siblingEl.removeClass( 'tribe-active' );
		$siblingEl.removeAttr( 'disabled' );
	};



	/**
	 * Handle the "Going" and "Not Going" actions.
	 * Load the RSVP confirmation form via AJAX
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	my.events.handle_rsvp_response = function() {
		var $button   = $( this );
		var $ticket   = $button.closest( '.tribe-block__rsvp__ticket' );
		var ticket_id = $ticket.data( 'rsvp-id' );
		var going     = $button.hasClass( 'tribe-block__rsvp__status-button--going' );

		// Toggle button styles and disable
		my.tribe_rsvp_toggle_actions( $button );

		// Set the AJAX params
		var params = {
			action: 'rsvp-form',
			ticket_id: ticket_id,
			going: going ? 'yes' : 'no',
		};

		// Show the loader for this RSVP
		my.tribe_rsvp_loader_start( $ticket );

		$.post(
			TribeRsvp.ajaxurl,
			params,
			function( response ) {
				var $form = $ticket.find( '.tribe-block__rsvp__form' );
				$form.html( response.data.html );
				if ( window.tribe_event_tickets_plus ) {
					var $input = $form.find( 'input.tribe-tickets-quantity' );
					window.tribe_event_tickets_plus.meta.block_set_quantity( $input, going );
				}
				my.tribe_rsvp_loader_end( $ticket );
			}
		);
	};

	/**
	 * Handle the number input + and - actions
	 *
	 * @since 4.9
	 *
	 * @param {event} e input event
	 */
	my.events.handle_quantity_change = function( e ) {
		e.preventDefault();
		var $input   = $( this ).parent().find( 'input[type="number"]' );
		var increase = $( this ).hasClass( 'tribe-block__rsvp__number-input-button--plus' );
		var step = $input[ 0 ].step ? Number( $input [ 0 ].step ) : 1
		var originalValue = Number( $input[ 0 ].value );

		// stepUp or stepDown the input according to the button that was clicked
		// handle IE/Edge
		if ( increase ) {
			// we use 0 here as a shorthand for no maximum
			var max = $input[ 0 ].max ? Number( $input[ 0 ].max ) : -1;

			if ( typeof $input[ 0 ].stepUp === 'function' ) {
				try {
					$input[ 0 ].stepUp();
				} catch ( ex ) {
					$input[ 0 ].value = ( -1 === max || max >= originalValue + step ) ? originalValue + step : max;
				}
			} else {
				$input[ 0 ].value = ( -1 === max || max >= originalValue + step ) ? originalValue + step : max;
			}
		} else {
			var min = $input[ 0 ].min ? Number( $input[ 0 ].min ) : 0;

			if ( typeof $input[ 0 ].stepDown === 'function' ) {
				try {
					$input[ 0 ].stepDown();
				} catch ( ex ) {
					$input[ 0 ].value = ( min <= originalValue - step ) ? originalValue - step : min;
				}
			} else {
				$input[ 0 ].value = ( min <= originalValue - step ) ? originalValue - step : min;
			}
		}

		// Trigger the on Change for the input (if it has changed) as it's not handled via stepUp() || stepDown()
		if ( originalValue !== $input[ 0 ].value ) {
			$input.trigger( 'change' );
		}
	};



	/**
	 * Show the loader
	 *
	 * @since 4.9
	 *
	 * @param {obj} $ticket ticket object
	 */
	my.tribe_rsvp_loader_start = function( $ticket ) {
		$ticket.find( '.tribe-block__rsvp__loading' ).show();
	};

	/**
	 * Hide the loader
	 *
	 * @since 4.9
	 *
	 * @param {obj} $ticket ticket object
	 */
	my.tribe_rsvp_loader_end = function( $ticket ) {
		$ticket.find( '.tribe-block__rsvp__loading' ).hide();
	};



	/**
	 * Validates the RSVP form
	 *
	 * @param {obj} $form form object
	 *
	 * @returns {bool} is valid
	 */
	my.validate_submission = function( $form ) {
		var $qty = $form.find( 'input.tribe-tickets-quantity' );
		var $name = $form.find( 'input.tribe-tickets-full-name' );
		var $email = $form.find( 'input.tribe-tickets-email' );

		return (
			$.trim( $name.val() ).length &&
				$.trim( $email.val() ).length &&
				parseFloat( $qty.val() ) > 0
		);
	};



	/**
	 * Handle the form submission
	 *
	 * @since 4.9
	 *
	 * @param {event} e submission event
	 */
	my.events.handle_submission = function( e ) {
		e.preventDefault();

		var $ticket   = $( this ).closest( '.tribe-block__rsvp__ticket' );
		var ticket_id = $ticket.data( 'rsvp-id' );
		var $form     = $ticket.find( 'form' );

		var is_rsvp_valid = my.validate_submission( $form );
		var is_meta_valid = true;
		var has_tickets_plus = !! window.tribe_event_tickets_plus;

		if ( has_tickets_plus ) {
			is_meta_valid = window.tribe_event_tickets_plus.meta.validate_meta( $form );
		}

		// Handle invalid form
		if ( ! is_rsvp_valid || ! is_meta_valid ) {
			is_rsvp_valid
				? $form.find( '.tribe-block__rsvp__message__error' ).hide()
				: $form.find( '.tribe-block__rsvp__message__error' ).show();
			has_tickets_plus && is_meta_valid
				? $form.find( '.tribe-event-tickets-meta-required-message' ).hide()
				: $form.find( '.tribe-event-tickets-meta-required-message' ).show();

			$( 'html, body' ).animate( {
				scrollTop: $form.offset().top - 100,
			}, 300 );
		} else {
			// Form is valid, submit form
			var params = $form.serializeArray();
			params.push( { name: 'action', value: 'rsvp-process' } );
			params.push( { name: 'ticket_id', value: ticket_id } );

			my.tribe_rsvp_loader_start( $ticket );

			$.post(
				TribeRsvp.ajaxurl,
				params,
				function( response ) {
					// Get the remaining number
					var remaining = response.data.remaining;

					// Update templates
					$ticket.find( '.tribe-block__rsvp__details .tribe-block__rsvp__availability' ).replaceWith( response.data.remaining_html );
					$ticket.find( '.tribe-block__rsvp__form' ).html( response.data.html );

					if ( 0 === remaining ) {
						// If there are no more RSVPs remaining we update the status section
						$ticket.find( '.tribe-block__rsvp__status' ).replaceWith( response.data.status_html );
					}

					my.tribe_rsvp_loader_end( $ticket );
				}
			);
		}
	};

	/**
	 * Bind events to elements
	 */
	my.bind_events = function() {
		$( '.tribe-block__rsvp__ticket' )
			.on(
				'click',
				'.tribe-block__rsvp__status-button--going, .tribe-block__rsvp__status-button--not-going',
				my.events.handle_rsvp_response
			)
			.on( 'click', 'button[type="submit"]', my.events.handle_submission )
			.on(
				'click',
				'.tribe-block__rsvp__number-input-button--minus, .tribe-block__rsvp__number-input-button--plus',
				my.events.handle_quantity_change
			);
	};

	/**
	 * Initialize RSVP block
	 *
	 * @since 4.9
	 */
	my.init = function() {
		var tribe_rsvp = $( '.tribe-block__rsvp' );

		if ( ! tribe_rsvp.length ) {
			return;
		}

		my.bind_events();
	};

	// Initialize
	my.init();
} )( jQuery, tribe_tickets_rsvp_block );
