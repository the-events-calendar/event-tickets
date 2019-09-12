var tribe_tickets_ticket_form = {};

/**
 * Provides global stock handling for frontend ticket forms.
 *
 * @var object tribe_tickets_stock_data
 */
( function( $, my ) {
	var $tickets_lists;
	var $quantity_fields;

	my.init = function() {
		$tickets_lists = $( '.tribe-events-tickets, .tribe-events-tickets-tpp' );
		$quantity_fields = $tickets_lists.find( '.quantity' ).find( '.qty, .edd-input' );
		$quantity_fields.on( 'change', my.on_quantity_change );
	};

	/**
	 * Every time a ticket quantity field is changed we should evaluate
	 * the new quantity and ensure it is still "in bounds" with relation
	 * to global stock.
	 */
	my.on_quantity_change = function() {
		var $this     = $( this );
		var ticket_id = my.get_matching_ticket_id( this );

		if ( my.ticket_uses_global_stock( ticket_id ) ) {
			my.global_stock_quantity_changed( $this, ticket_id );
		} else {
			my.normal_stock_quantity_changed( $this, ticket_id );
		}

		var $form = $this.closest( 'form' );
		var parent = $this.parent( '.tribe-block__tickets__item__quantity__number' ).addClass( 'tribe-block__tickets__item__quantity__number--active' );

		// Only disable / enable if is a Tribe Commerce Paypal form.
		if ( ! $form.hasClass( 'tribe-tickets-tpp' ) ) {
			return;
		}

		var new_quantity = parseInt( $this.val(), 10 );
		new_quantity = isNaN( new_quantity ) ? 0 : new_quantity;

		if ( new_quantity > 0 ) {
			$form
				.find( 'td[data-product-id]:not([data-product-id="' + ticket_id + '"])' )
				.closest( 'tr' )
				.find( 'input, button' )
				.attr( 'disabled', 'disabled' )
				.closest( 'tr' )
				.addClass( 'tribe-tickets-purchase-disabled' );
		} else {
			$form
				.find( 'input, button' )
				.removeAttr( 'disabled' )
				.closest( 'tr' )
				.removeClass( 'tribe-tickets-purchase-disabled' );
		}
	};

	/**
	 * Handle updates and checks where the modified quantity selector belongs to
	 * a ticket that uses global stock.
	 *
	 * @param $input
	 * @param ticket_id
	 */
	my.global_stock_quantity_changed = function( $input, ticket_id ) {
		var new_quantity    = $input.val();
		var event_id        = my.get_event_id( ticket_id );
		var ticket_cap      = my.get_cap( ticket_id );
		var event_stock     = my.get_global_stock( event_id );
		var total_requested = my.currently_requested_global_event_stock( event_id );

		// If the total stock requested across all inputs now exceeds what's available, adjust this one
		if ( total_requested > event_stock ) {
			new_quantity -= total_requested - event_stock;
		}

		// If sales for this input have been capped, adjust if necessary to stay within the cap
		if ( my.stock_mode_is_capped( ticket_id ) && new_quantity > ticket_cap ) {
			new_quantity = ticket_cap;
		}

		// Do not let our adjustments take the new quantity below zero, however
		if ( 0 >= new_quantity ) {
			new_quantity = 0;
		}

		$input.val( new_quantity );
		my.update_available_stock_counts( event_id );
	};

	/**
	 * Handle updates and checks where the modified quantity selector belongs to
	 * a ticket that does not use global stock.
	 *
	 * @param $input
	 * @param ticket_id
	 */
	my.normal_stock_quantity_changed = function( $input, ticket_id ) {
		var new_quantity    = $input.val();
		var available_stock = my.get_single_stock( ticket_id );
		var remaining;

		if ( ! $.isNumeric( available_stock ) ) {
			return;
		}

		// if the stock is unlimited then there is nothing to change
		if ( - 1 === available_stock ) {
			return;
		}

		// Keep in check (should be handled for us by numeric inputs in most browsers, but let's be safe)
		if ( new_quantity > available_stock ) {
			new_quantity = available_stock;
		}

		// Update
		$input.val( new_quantity );
		remaining = available_stock - new_quantity;
		$tickets_lists.find( '.available-stock[data-product-id=' + ticket_id + ']').html( remaining );
	};

	/**
	 * Each ticket list typically shows the remaining inventory next to each
	 * quantity input. This method updates those counts appropriately.
	 *
	 * @param event_id
	 */
	my.update_available_stock_counts = function( event_id ) {
		var tickets   = my.get_tickets_of( event_id );
		var remaining = my.get_global_stock( event_id ) - my.currently_requested_global_event_stock( event_id );

		for ( var ticket_id in tickets ) {
			if ( ! tickets.hasOwnProperty( ticket_id ) ) {
				continue;
			}

			// Do not allow a sub-zero tickets remaining count
			if ( remaining < 0 ) {
				remaining = 0;
			}

			var ticket = tickets[ ticket_id ];

			if ( 'global' === ticket.mode ) {
				$tickets_lists.find( '.available-stock[data-product-id=' + ticket_id + ']').html( remaining );
			}

			if ( 'capped' === ticket.mode ) {
				// If x units of global stock have been requested, the effective cap is the actual cap less value x
				var effective_cap = Math.min( remaining, ticket.cap );

				/**
				 * Quantity Fields
				 * WooCommerce - .input-text.qty
				 * EDD - input.edd-input
				 * Tribe Commerce - input.tribe-ticket-quantity
				 */
				var qty_input = $( '[data-product-id=' + ticket_id + ']' ).find( 'input.tribe-ticket-quantity, .input-text.qty, input.edd-input' );
				var requested_stock = parseInt( qty_input.val(), 10 );
				requested_stock = isNaN( requested_stock ) ? 0 : requested_stock;
				var remaining_under_cap = ticket.cap - requested_stock;

				// As with all other ticket types, capped tickets should not have a sub-zero count either
				if ( remaining_under_cap < 0 ) {
					remaining_under_cap = 0;
				}
				// Nor can their count exceed the effective cap
				else if ( remaining_under_cap > effective_cap ) {
					remaining_under_cap = effective_cap;
				}

				$tickets_lists.find( '.available-stock[data-product-id=' + ticket_id + ']').html( remaining_under_cap );
			}
		}
	};

	/**
	 * Attempts to determine the product ID associated with the passed
	 * element.
	 *
	 * @param element
	 *
	 * @returns null|string
	 */
	my.get_matching_ticket_id = function( element ) {
		// There should be an element close by (parent or grandparent) from which we can
		// obtain the ticket ID
		var $closest_identifier = $( element ).closest( '[data-product-id]' );

		// Custom or legacy templates may mean this isn't possible - safely bail if necessary
		if ( ! $closest_identifier.length ) {
			return;
		}

		return $closest_identifier.data( 'product-id' );
	};

	/**
	 * If possible, returns the value of the specified ticket's property or
	 * false if it does not exist.
	 *
	 * @param ticket_id
	 * @param property
	 *
	 * @returns boolean|string
	 */
	my.get_ticket_property = function( ticket_id, property ) {
		// Don't trigger errors if tribe_tickets_stock_data is not available
		if ( "object" !== typeof tribe_tickets_stock_data ) {
			return false;
		}

		var ticket = tribe_tickets_stock_data.tickets[ ticket_id ];

		// If we don't have any data for this ticket we can assume it doesn't use global stock
		if ( "undefined" === typeof tribe_tickets_stock_data.tickets[ ticket_id ] ) {
			return false;
		}

		return ticket[ property ];
	};

	/**
	 * Provides an array of ticket objects that all belong to the specified
	 * event.
	 *
	 * @param event_id
	 *
	 * @returns Array
	 */
	my.get_tickets_of = function( event_id ) {
		// Don't trigger errors if tribe_tickets_stock_data is not available
		if ( "object" !== typeof tribe_tickets_stock_data ) {
			return false;
		}

		var set_of_tickets = [];

		for ( var ticket_id in tribe_tickets_stock_data.tickets ) {
			var ticket = tribe_tickets_stock_data.tickets[ ticket_id ];
			if ( event_id === ticket.event_id ) {
				set_of_tickets[ ticket_id ] = ticket;
			}
		}

		return set_of_tickets;
	};

	/**
	 * Sum of all quantity inputs that have tickets drawing on the event's global stock.
	 *
	 * @param event_id
	 * @returns {number}
	 */
	my.currently_requested_global_event_stock = function( event_id ) {
		var total   = 0;
		var tickets = my.get_tickets_of( event_id );
		var $ticketStocks = $tickets_lists.find( '.available-stock' );

		$ticketStocks.each( function( i, ticket ) {
			var $ticket = $( ticket );
			var ticketID = $ticket.data( 'productId' );
			var modes = [ 'global', 'capped' ];
			var mode = tribe_tickets_stock_data.tickets[ ticketID ].mode;

			if ( -1 === modes.indexOf( mode ) ) {
				return;
			}

			var $quantity = $ticket.parents( 'tr' ).eq( 0 ).find( '.qty, .edd-input' );
			var quantity = parseInt( $quantity.val(), 10 );

			total += quantity;
		} );

		return total;
	};

	/**
	 * If possible, returns the value of the specified event's property or
	 * false if it does not exist.
	 *
	 * @param event_id
	 * @param property
	 *
	 * @returns boolean|string
	 */
	my.get_event_property = function( event_id, property ) {
		// Don't trigger errors if tribe_tickets_stock_data is not available
		if ( "object" !== typeof tribe_tickets_stock_data ) {
			return false;
		}

		var event = tribe_tickets_stock_data.events[ event_id ];

		// If we don't have any data for this ticket we can assume it doesn't use global stock
		if ( "undefined" === tribe_tickets_stock_data.events[ event_id ] ) {
			return false;
		}

		return event[property];
	};

	my.stock_mode_is_global = function( ticket_id ) {
		return "global" === my.get_mode( ticket_id );
	};

	my.stock_mode_is_capped = function( ticket_id ) {
		return "capped" === my.get_mode( ticket_id );
	};

	my.ticket_uses_global_stock = function( ticket_id ) {
		return my.stock_mode_is_capped( ticket_id ) || my.stock_mode_is_global( ticket_id );
	};

	my.get_mode = function( ticket_id ) {
		return my.get_ticket_property( ticket_id, 'mode' );
	};

	my.get_event_id = function( ticket_id ) {
		return my.get_ticket_property( ticket_id, 'event_id' );
	};

	my.get_cap = function( ticket_id ) {
		return my.get_ticket_property( ticket_id, 'cap' );
	};

	my.get_global_stock = function( event_id ) {
		return my.get_event_property( event_id, 'stock' );
	};

	my.get_single_stock = function( ticket_id ) {
		return my.get_ticket_property( ticket_id, 'stock' );
	};

	$( function() {
		my.init();
	} );

	// Listen for any clicks on an element in the document with the `link` class
	$( document ).on( 'click', '.button-events-list', function( e ) {
		// Prevent the default action (e.g. submit the form)
		e.preventDefault();

		// Get the URL specified in the form
		var url = e.target.parentElement.action;
		window.location = url;
	} );
} )( jQuery, tribe_tickets_ticket_form );
