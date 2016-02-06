/**
 * Provides global stock handling for frontend ticket forms.
 *
 * @var object tribe_global_stock_data
 */
jQuery( document ).ready( function( $ ) {
	var $tickets_lists   = $( '.tribe-events-tickets' );
	var $quantity_fields = $tickets_lists.find( '.quantity' ).find( 'input' );

	$quantity_fields.on( 'change', on_quantity_change );

	/**
	 * Every time a ticket quantity field is changed we should evaluate
	 * the new quantity and ensure it is still "in bounds" with relation
	 * to global stock.
	 */
	function on_quantity_change() {
		var $this = $( this );
		var ticket_id = get_matching_ticket_id( this );

		if ( ! ticket_uses_global_stock( ticket_id ) ) {
			return;
		}

		var new_quantity    = $this.val();
		var ticket_cap      = get_cap( ticket_id );
		var event_id        = get_event_id( ticket_id );
		var event_stock     = get_global_stock( event_id );
		var total_requested = currently_requested_event_stock( event_id );

		// If the total stock requested across all inputs now exceeds what's available, adjust this one
		if ( total_requested > event_stock ) {
			new_quantity -= total_requested - event_stock;
		}

		// If sales for this input have been capped, adjust if necessary to stay within the cap
		if ( stock_mode_is_capped( ticket_id ) && new_quantity > ticket_cap ) {
			new_quantity = ticket_cap;
		}

		// Do not let our adjustments take the new quantity below zero, however
		if ( 0 >= new_quantity ) {
			new_quantity = 0;
		}

		// Adjust the input value and adjust the available stock counts
		$this.val( new_quantity );
		update_available_stock_counts( event_id );
	}

	/**
	 * Each ticket list typically shows the remaining inventory next to each
	 * quantity input. This method updates those counts appropriately.
	 *
	 * @param event_id
     */
	function update_available_stock_counts( event_id ) {
		var tickets   = get_tickets_of( event_id );
		var remaining = get_global_stock( event_id ) - currently_requested_event_stock( event_id );

		for ( var ticket_id in tickets ) {
			var ticket = tickets[ ticket_id ];

			if ( "global" === ticket.mode ) {
				$tickets_lists.find( '.available-stock[data-product-id=' + ticket_id + ']').html( remaining );
			}

			if ( "capped" === ticket.mode ) {
				remaining = ( remaining > ticket.cap ) ? ticket.cap : remaining;
				$tickets_lists.find( '.available-stock[data-product-id=' + ticket_id + ']').html( remaining );
			}
		}
	}

	/**
	 * Attempts to determine the product ID associated with the passed
	 * element.
	 *
	 * @param element
	 *
	 * @returns null|string
     */
	function get_matching_ticket_id( element ) {
		// There should be an element close by (parent or grandparent) from which we can
		// obtain the ticket ID
		var $closest_identifier = $( element ).closest( '[data-product-id]' );

		// Custom or legacy templates may mean this isn't possible - safely bail if necessary
		if ( ! $closest_identifier.length ) {
			return;
		}

		return $closest_identifier.data( 'product-id' );
	}

	/**
	 * If possible, returns the value of the specified ticket's property or
	 * false if it does not exist.
	 *
	 * @param ticket_id
	 * @param property
	 *
	 * @returns boolean|string
     */
	function get_ticket_property( ticket_id, property ) {
		// Don't trigger errors if tribe_global_stock_data is not available
		if ( "object" !== typeof tribe_global_stock_data ) {
			return false;
		}

		var ticket = tribe_global_stock_data.tickets[ ticket_id ];

		// If we don't have any data for this ticket we can assume it doesn't use global stock
		if ( "undefined" === tribe_global_stock_data.tickets[ ticket_id ] ) {
			return false;
		}

		return ticket[property];
	}

	/**
	 * Provides an array of ticket objects that all belong to the specified
	 * event.
	 *
	 * @param event_id
	 *
	 * @returns Array
     */
	function get_tickets_of( event_id ) {
		// Don't trigger errors if tribe_global_stock_data is not available
		if ( "object" !== typeof tribe_global_stock_data ) {
			return false;
		}

		var set_of_tickets = [];

		for ( var ticket_id in tribe_global_stock_data.tickets ) {
			var ticket = tribe_global_stock_data.tickets[ ticket_id ];
			if ( event_id === ticket.event_id ) {
				set_of_tickets[ ticket_id ] = ticket;
			}
		}

		return set_of_tickets;
	}

	/**
	 * Sum of all quantity inputs that have tickets drawing on the event's global stock.
	 *
	 * @param event_id
	 * @returns {number}
     */
	function currently_requested_event_stock( event_id ) {
		var total   = 0;
		var tickets = get_tickets_of( event_id );

		for ( var ticket_id in tickets ) {
			total += parseInt( $tickets_lists.find( '[data-product-id=' + ticket_id + ']').find( 'input').val(), 10 );
		}

		return total;
	}

	/**
	 * If possible, returns the value of the specified event's property or
	 * false if it does not exist.
	 *
	 * @param event_id
	 * @param property
	 *
	 * @returns boolean|string
	 */
	function get_event_property( event_id, property ) {
		// Don't trigger errors if tribe_global_stock_data is not available
		if ( "object" !== typeof tribe_global_stock_data ) {
			return false;
		}

		var event = tribe_global_stock_data.events[ event_id ];

		// If we don't have any data for this ticket we can assume it doesn't use global stock
		if ( "undefined" === tribe_global_stock_data.events[ event_id ] ) {
			return false;
		}

		return event[property];
	}

	function stock_mode_is_global( ticket_id ) {
		return "global" === get_mode( ticket_id );
	}

	function stock_mode_is_capped( ticket_id ) {
		return "capped" === get_mode( ticket_id );
	}

	function ticket_uses_global_stock( ticket_id ) {
		return stock_mode_is_capped( ticket_id ) || stock_mode_is_global( ticket_id );
	}

	function get_mode( ticket_id ) {
		return get_ticket_property( ticket_id, 'mode' );
	}

	function get_event_id( ticket_id ) {
		return get_ticket_property( ticket_id, 'event_id' );
	}

	function get_cap( ticket_id ) {
		return get_ticket_property( ticket_id, 'cap' );
	}

	function get_global_stock( event_id ) {
		return get_event_property( event_id, 'stock' );
	}
} );