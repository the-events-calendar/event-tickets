(function( $, my ) {
	'use strict';

	// base elements
	var tribe_rsvp = $( '.tribe-block__rsvp' );

	// Bail if we don't have what we need
	if ( 0 === tribe_rsvp.length ) {
		return;
	}

	/**
	 * Handle the "Going" and "Not Going" button toggle,
	 * set them active and inactive so they can only use
	 * one at a time.
	 *
	 * @since TBD
	 *
	 * @param obj button The dom object of the clicked button
	 * @return void
	 */
	function tribe_rsvp_toggle_actions( button ) {

		// Check if is the going or not going button
		var going     = button.hasClass( 'tribe-block__rsvp__status-button--going' );
		var sibling   = going ? '.tribe-block__rsvp__status-button--not-going' : '.tribe-block__rsvp__status-button--going';
		var siblingEl = button.closest( '.tribe-block__rsvp__status' ).find( sibling );

		// Add active classs to the current button
		button.addClass( 'tribe-active' );
		button.attr( 'disabled', 'disabled' );

		// Remove the active class of the other button and disable it
		siblingEl.removeClass( 'tribe-active' );
		siblingEl.removeAttr( 'disabled' );

	}

	/**
	 * Handle the "Going" and "Not Going" actions.
	 * Load the RSVP confirmation form via AJAX
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	$( '.tribe-block__rsvp__ticket' ).on( 'click',
		'.tribe-block__rsvp__status-button--going, .tribe-block__rsvp__status-button--not-going',
		function( e ) {

			var button    = $( this );
			var ticket    = $( this ).closest( '.tribe-block__rsvp__ticket' );
			var ticket_id = ticket.data( 'rsvp-id' );
			var going     = button.hasClass( 'tribe-block__rsvp__status-button--going' );

			// Toggle button styles and disable
			tribe_rsvp_toggle_actions( $( this ) );

			// Set the AJAX params
			var params = {
				action    : 'rsvp-form',
				ticket_id : ticket_id,
				going     : going ? 'yes' : 'no',
			};

			// Show the loader for this RSVP
			tribe_rsvp_loader_start( ticket );

			$.post(
				TribeRsvp.ajaxurl,
				params,
				function( response ) {
					var form = ticket.find( '.tribe-block__rsvp__form' );
					form.html( response.data.html );
					tribe_rsvp_loader_end( ticket );
				}
			);
	} );

	/**
	 * Handle the number input + and - actions
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	$( '.tribe-block__rsvp__ticket' )
		.on( 'click',
		'.tribe-block__rsvp__number-input-button--minus, .tribe-block__rsvp__number-input-button--plus' ,
		function( e ) {
			e.preventDefault();
			var input    = $( this ).parent().find( 'input[type="number"]' );
			var increase = $( this ).hasClass( 'tribe-block__rsvp__number-input-button--plus' );

			// stepUp or stepDown the input according to the button that was clicked
			increase ? input[0].stepUp() : input[0].stepDown();

			// Trigger the on Change for the input as it's not handled via stepUp() || stepDown()
			input.trigger( 'change' );

		} );

	/**
	 * Show the loader
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function tribe_rsvp_loader_start( ticket ) {
		ticket.find( '.tribe-block__rsvp__loading' ).show();
	}

	/**
	 * Hide the loader
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function tribe_rsvp_loader_end( ticket ) {
		ticket.find( '.tribe-block__rsvp__loading' ).hide();
	}

	/**
	 * Handle the form submission
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	$( '.tribe-block__rsvp__ticket' ).on( 'click', 'button[type="submit"]', function( e ) {
		e.preventDefault();

		var ticket    = $( this ).closest( '.tribe-block__rsvp__ticket' );
		var ticket_id = ticket.data( 'rsvp-id' );
		var form      = ticket.find( 'form' );

		// Get form values in order to validate
		var qty       = form.find( 'input.tribe-tickets-quantity' );
		var name      = form.find( 'input.tribe-tickets-full-name' );
		var email     = form.find( 'input.tribe-tickets-email' );

		// Validate the form
		if (
			! $.trim( name.val() ).length
			|| ! $.trim( email.val() ).length
			|| parseFloat( qty.val() ) < 1
		) {
			form.find( '.tribe-block__rsvp__message__error' ).show();
			return false;
		}

		var params = form.serializeArray();
		params.push( { name: 'action', value: 'rsvp-process' } );
		params.push( { name: 'ticket_id', value: ticket_id } );
		tribe_rsvp_loader_start( ticket );
		$.post(
			TribeRsvp.ajaxurl,
			params,
			function( response ) {
				// Get the remaining number
				var remaining = response.data.remaining;

				// Update the remaining template part
				ticket.find( '.tribe-block__rsvp__details .tribe-block__rsvp__availability' ).replaceWith( response.data.remaining_html );

				ticket.find( '.tribe-block__rsvp__form' ).html( response.data.html );

				if ( 0 === remaining ) {
					// If there are no more RSVPs remaining we update the status section
					ticket.find( '.tribe-block__rsvp__status' ).replaceWith( response.data.status_html );
				}

				tribe_rsvp_loader_end( ticket );
			}
		);

		return;

	} );

})( jQuery, {} );