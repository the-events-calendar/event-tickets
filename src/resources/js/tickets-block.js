// @TODO: Take this line off once we actually have the tribe object
if ( 'undefined' === typeof tribe ) {
	tribe = {};
}

// Define the tickets object if not defined
if ( 'undefined' === typeof tribe.tickets ) {
	tribe.tickets = {};
}

tribe.tickets.block = {
	num_attendees: 0,
	event        : {}
};

( function( $, obj, te ) {
	'use strict';

	obj.selector = {
		container                 : '.tribe-block__tickets',
		submit                    : '.tribe-block__tickets__buy',
		item                      : '.tribe-block__tickets__item',
		itemOptOut                : '.tribe-block__tickets__item__optout',
		itemQuantity              : '.tribe-block__tickets__item__quantity',
		itemQuantityInput         : '.tribe-ticket-quantity',
		itemExtraAvailable        : '.tribe-block__tickets__item__extra__available',
		itemExtraAvailableQuantity: '.tribe-block__tickets__item__extra__available_quantity'
	};

	var $tribe_ticket = $( obj.selector.container );

	// Bail if there are no tickets on the current event/page/post
	if ( 0 === $tribe_ticket.length ) {
		return;
	}

	/**
	 * Handle the number input + and - actions
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	$( document ).on( 'click',
		'.tribe-block__tickets__item__quantity__remove, .tribe-block__tickets__item__quantity__add',
		function( e ) {
			e.preventDefault();
			var $input = $( this ).parent().find( 'input[type="number"]' );
			var add = $( this ).hasClass( 'tribe-block__tickets__item__quantity__add' );
			var step = $input[ 0 ].step ? Number( $input [ 0 ].step ) : 1
			var originalValue = Number( $input[ 0 ].value );


			// stepUp or stepDown the input according to the button that was clicked
			// handle IE/Edge
			if ( add ) {
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
		}
	);

	/**
	 * Handle the TPP form
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	$( obj.selector.item ).on( 'change',
		'.tribe-ticket-quantity',
		function( e ) {
			var $this      = $( this );
			var $ticket    = $this.closest( obj.selector.item );
			var $ticket_id = $ticket.data( 'ticket-id' );

			var $form = $this.closest( obj.selector.container );

			var new_quantity = parseInt( $this.val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;

			// Maybe display the Opt Out
			var $has_optout = $ticket.has( obj.selector.itemOptOut ).length;
			if ( $has_optout ) {
				( new_quantity > 0 ) ? $( obj.selector.itemOptOut ).show() : $( obj.selector.itemOptOut ).hide();
			}

			// Only disable / enable if is a Tribe Commerce Paypal form.
			if ( 'Tribe__Tickets__Commerce__PayPal__Main' !== $form.data( 'provider' ) ) {
				return;
			}

			if ( new_quantity > 0 ) {
				$form
					.find( '[data-ticket-id]:not([data-ticket-id="' + $ticket_id + '"])' )
					.closest( 'div.tribe-block__tickets__item' )
					.find( 'input, button' )
					.attr( 'disabled', 'disabled' )
					.closest( 'div' )
					.addClass( 'tribe-tickets-purchase-disabled' );

			} else {
				$form
					.find( 'input, button' )
					.removeAttr( 'disabled' )
					.closest( 'div' )
					.removeClass( 'tribe-tickets-purchase-disabled' );
			}

	} );

	/**
	 * Get the tickets IDs
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	obj.getTickets = function() {

		var $tickets = $( obj.selector.item ).map( function() {
			return $( this ).data( 'ticket-id' );
		} ).get();

		return $tickets;
	}

	/**
	 * Make dom updates for the AJAX response
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	obj.updateAvailability = function( tickets ) {

		Object.keys( tickets ).forEach( function( ticket_id ) {

			var available = tickets[ ticket_id ].available;
			var $ticketEl = $( obj.selector.item + "[data-ticket-id='" + ticket_id + "']" );

			if ( 0 === available ) { // ticket is out of stock

				var unavailableHtml = tickets[ ticket_id ].unavailable_html;
				// Set the availability data attribute to false
				$ticketEl.attr( 'available', false );
				// Remove classes for instock and purchasable
				$ticketEl.removeClass( 'instock' );
				$ticketEl.removeClass( 'purchasable' );

				// Update HTML elements with the "Out of Stock" messages
				$ticketEl.find( obj.selector.itemExtraAvailable ).replaceWith( unavailableHtml );
				$ticketEl.find( obj.selector.itemQuantity ).html( unavailableHtml );
			}

			if ( 1 < available ) { // Ticket in stock, we may want to update values
				$ticketEl.find( obj.selector.itemQuantityInput ).attr( { 'max' : available } );
				$ticketEl.find( obj.selector.itemExtraAvailableQuantity ).html( available );
			}

		});
	}

	/**
	 * Check tickets availability
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.checkAvailability = function() {

		// We're checking availability for all the tickets at once
		var params = {
			action  : 'ticket_availability_check',
			tickets : obj.getTickets(),
		};

		$.post(
			TribeTickets.ajaxurl,
			params,
			function( response ) {
				var success = response.success;

				// Bail if we don't get a successful response
				if ( ! success ) {
					return;
				}

				// Get the tickets response with availability
				var tickets = response.data.tickets;

				// Make DOM updates
				obj.updateAvailability( tickets );

			}
		);

		// Repeat every 15 seconds
		setTimeout( obj.checkAvailability, 15000 );

	}

	/*
	 * NEW CODING STARTING HERE
	 */

	/**
	 * On Change of Modal Cart Qty Update Item
	 *
	 * @since TBD
	 *
	 */
	$( document ).on( 'change', '#tribe-modal-cart__tickets .tribe-ticket-quantity', function ( e ) {
			e.preventDefault();

			var $cartItem = $( this ).closest( '.tribe-block__tickets__item' );
			var id = $cartItem.data( 'ticketId' );
			var ticket = obj.updateItem( id, $cartItem );

			$( te ).trigger( 'tribe_tickets_qty_updated', ticket );

		}
	);

	/**
	 * Remove Item from Cart Modal
	 *
	 * @since TBD
	 *
	 */
	$( document ).on( 'click', '#tribe-modal-cart__tickets .tribe-block__tickets__item__remove_btn', function ( e ) {
			e.preventDefault();

			var $cartItem = $( this ).closest( '.tribe-block__tickets__item' );
			$cartItem.find( '.tribe-ticket-quantity' ).val( 0 );
			$cartItem.fadeOut() ;

			var ticket = {};
			ticket.id = $cartItem.data( 'ticketId' );
			ticket.qty = 0;
			ticket.price = obj.getPrice( $cartItem, '.tribe-block__tickets__item__extra__price' );

			obj.updatePrice( ticket.qty, ticket.price, $cartItem );

			$( te ).trigger( 'tribe_tickets_qty_updated', ticket );
		}
	);

	/**
	 * When Modal Qty is updated, update the cart totals in modal
	 *
	 * @since TBD
	 *
	 */
	$( te ).on( 'tribe_tickets_qty_updated', function ( e, ticket ) {

		var $cart = $( '#tribe-modal-cart__tickets' );

		obj.updateCartTotals( $cart );

	} );

	/**
	 * When Get Tickets CLicked Update the Modal
	 *
	 * @since TBD
	 *
	 */
	$( te ).on( 'tribe_tickets_get_tickets', function ( e, dialogEl, event ) {

		var $cart = $( '#tribe-block__tickets' );
		var $modalCart = $( '#tribe-modal-cart__tickets' );

		$cart.find( ".tribe-block__tickets__item" ).each( function () {

			var blockCartItem = $( this );
			var id = blockCartItem.data( 'ticketId' );
			var modalCartItem = $modalCart.find( '[data-ticket-id="' + id + '"]' );

			obj.updateItem( id, modalCartItem, blockCartItem );

		} );

		obj.updateCartTotals( $modalCart );

	} );

	/**
	 * Update Cart Totals in Modal
	 *
	 * @since TBD
	 *
	 * @param $cart the jQuery cart object to update totals
	 */
	obj.updateCartTotals = function ( $cart ) {

		var total_qty = 0;
		var total_amount = 0.00;
		var $cart_totals = $( '.tribe-cart-totals' );

		$cart.find( ".tribe-block__tickets__item" ).each( function () {

			var modalCartItem = $( this );
			var qty = obj.getQty( modalCartItem );
			var total = parseFloat( $( this ).find( '.tribe-block__tickets__item__modal_total_amount' ).text() );

			total_qty += parseInt( qty, 10 );
			total_amount += total;

		} );

		$cart_totals.find( '.total-qty' ).text( total_qty );
		$cart_totals.find( '.total-amount' ).text( total_amount.toFixed( 2 ) );

	};

	/**
	* Possibly Update an Items Qty and always update the Total
	*
	* @since TBD
	*
	* @param id
	* @param obj modalCartItem the cart item to update
	* @param obj blockCartItem the optional ticket block cart item
	*
	* @returns {number}
	*/
	obj.updateItem = function ( id, modalCartItem, blockCartItem ) {

		var item = {};
			item.id = id;

		if ( blockCartItem ) {

			item.qty = obj.getQty( blockCartItem );
			item.price = obj.getPrice( modalCartItem, '.tribe-block__tickets__item__extra__price' );

			modalCartItem.find( '.tribe-ticket-quantity' ).val( item.qty );
			modalCartItem.fadeOut();
			if ( item.qty > 0 ) {
				modalCartItem.fadeIn();
			}
			obj.updatePrice( item.qty, item.price, modalCartItem );

			return item;
		}

		item.qty = obj.getQty( modalCartItem );
		item.price = obj.getPrice( modalCartItem, '.tribe-block__tickets__item__extra__price' );

		obj.updatePrice( item.qty, item.price, modalCartItem );

		return item;

	};

	/**
	 * Get the Quantity
	 *
	 * @since TBD
	 *
	 * @param obj cartItem the cart item to update
	 *
	 * @returns {number}
	 */
	obj.getQty = function ( cartItem ) {

		var qty = parseInt( cartItem.find( '.tribe-ticket-quantity' ).val(), 10 );

		return isNaN( qty ) ? 0 : qty;
	};

	/**
	 * Get the Price
	 *
	 *
	 * @param obj cartItem the cart item to update
	 * @params string cssClass the string of the class to get the price from
	 *
	 * @returns {number}
	 */
	obj.getPrice = function ( cartItem, cssClass ) {

		var price = parseFloat( cartItem.find( cssClass ).text().replace("$", "") );

		return isNaN( price ) ? 0 : price;
	};

	/**
	 * Update the Price for the Given Cart Item
	 *
	 * @since TBD
	 *
	 * @param number qty the quantity
	 * @param number price the price
	 * @param obj cartItem the cart item to update
	 *
	 * @returns {string}
	 */
	obj.updatePrice = function ( qty, price, cartItem ) {

		var total_for_item = (qty * price).toFixed( 2 );

		cartItem.find( '.tribe-block__tickets__item__modal_total_amount' ).text( total_for_item );

		return total_for_item;
	};

	/**
	 * Init the tickets script
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.checkAvailability();
	}

	obj.init();


})( jQuery, tribe.tickets.block, tribe_ev.events );
