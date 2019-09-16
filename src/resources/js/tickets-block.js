// @TODO: Take this line off once we actually have the tribe object
if ( 'undefined' === typeof tribe ) {
	tribe = {};
}

// Define the tickets object if not defined.
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
		container                  : '#tribe-tickets',
		item                       : '.tribe-tickets__item',
		itemExtraAvailable         : '.tribe-tickets__item__extra__available',
		itemExtraAvailableQuantity : '.tribe-tickets__item__extra__available_quantity',
		itemOptOut                 : '.tribe-tickets__item__optout',
		itemPrice                  : '.tribe-amount',
		itemQuantity               : '.tribe-tickets__item__quantity',
		itemQuantityInput          : '.tribe-tickets-quantity',
		submit                     : '.tribe-tickets__buy',
	};

	/*
	 * AR Cart Modal Selectors.
	 *
	 * @since TBD
	 *
	 */
	obj.modalSelector = {
		container         : '.tribe-modal__cart',
		itemRemove        : '.tribe-tickets__item__remove',
		itemTotal         : '.tribe-tickets__item__total__wrap .tribe-amount',
	};

	var $tribe_ticket = $( obj.selector.container );

	// Bail if there are no tickets on the current event/page/post.
	if ( 0 === $tribe_ticket.length ) {
		return;
	}

	/**
	 * Maybe display the Opt Out.
	 *
	 * @since TBD
	 *
	 * @param obj $ticket The ticket item element.
	 * @param int new_quantity The new ticket quantity.
	 */
	obj.maybeShowOptOut = function( $ticket, new_quantity ) {
		var $has_optout = $ticket.has( obj.selector.itemOptOut ).length;

		if ( $has_optout ) {
			( 0 < new_quantity ) ? $( obj.selector.itemOptOut ).show() : $( obj.selector.itemOptOut ).hide();
		}
	}

	/**
	 * En/disable the ticket fields for Tribe Commerce.
	 *
	 * @since TBD
	 *
	 * @param int new_quantity The new ticket quantity.
	 * @param obj $form The form element.
	 * @param int ticket_id The ticket ID.
	 */
	obj.tribeCommerceDisable = function( new_quantity, $form, ticket_id ) {
		if ( 0 < new_quantity ) {
			$form
				.find( '[data-ticket-id]:not([data-ticket-id="' + ticket_id + '"])' )
				.closest( 'div.tribe-tickets__item' )
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
	}

	/**
	 * Get the tickets IDs.
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	obj.getTickets = function() {
		var $tickets = $( obj.selector.item ).map(
			function() {
				return $( this ).data( 'ticket-id' );
			}
		).get();

		return $tickets;
	}

	/**
	 * Make dom updates for the AJAX response.
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	obj.updateAvailability = function( tickets ) {
		Object.keys( tickets ).forEach( function( ticket_id ) {
			var available = tickets[ ticket_id ].available;
			var $ticketEl = $( obj.selector.item + "[data-ticket-id='" + ticket_id + "']" );

			if ( 0 === available ) { // Ticket is out of stock.
				var unavailableHtml = tickets[ ticket_id ].unavailable_html;
				// Set the availability data attribute to false.
				$ticketEl.attr( 'available', false );
				// Remove classes for instock and purchasable.
				$ticketEl.removeClass( 'instock' );
				$ticketEl.removeClass( 'purchasable' );

				// Update HTML elements with the "Out of Stock" messages.
				$ticketEl.find( obj.selector.itemExtraAvailable ).replaceWith( unavailableHtml );
				$ticketEl.find( obj.selector.itemQuantity ).html( unavailableHtml );
			}

			if ( 1 < available ) { // Ticket in stock, we may want to update values.
				$ticketEl.find( obj.selector.itemQuantityInput ).attr( { 'max' : available } );
				$ticketEl.find( obj.selector.itemExtraAvailableQuantity ).html( available );
			}
		} );
	}

	/**
	 * Check tickets availability.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.checkAvailability = function() {
		// We're checking availability for all the tickets at once.
		var params = {
			action  : 'ticket_availability_check',
			tickets : obj.getTickets(),
		};

		$.post(
			TribeTickets.ajaxurl,
			params,
			function( response ) {
				var success = response.success;

				// Bail if we don't get a successful response.
				if ( ! success ) {
					return;
				}

				// Get the tickets response with availability.
				var tickets = response.data.tickets;

				// Make DOM updates.
				obj.updateAvailability( tickets );

			}
		);

		// Repeat every 15 seconds
		setTimeout( obj.checkAvailability, 15000 );
	}

	/**
	 * Step up the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 */
	obj.stepUp = function( $input, originalValue ) {
		// We use 0 here as a shorthand for no maximum.
		var max      = $input[ 0 ].max ? Number( $input[ 0 ].max ) : -1;
		var step     = $input[ 0 ].step ? Number( $input [ 0 ].step ) : 1;
		var increase = ( -1 === max || max >= originalValue + step ) ? originalValue + step : max;

		if ( 'function' === typeof $input[ 0 ].stepUp ) {
			try {
				$input[ 0 ].stepUp();
			} catch ( ex ) {
				$input[ 0 ].value = increase;
			}
		} else {
			$input[ 0 ].value = increase;
		}
	}

	/**
	 * Step down the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 */
	obj.stepDown = function( $input, originalValue ) {
		var min      = $input[ 0 ].min ? Number( $input[ 0 ].min ) : 0;
		var step     = $input[ 0 ].step ? Number( $input [ 0 ].step ) : 1;
		var decrease = ( min <= originalValue - step && 0 < originalValue - step ) ? originalValue - step : min;

		if ( 'function' === typeof $input[ 0 ].stepDown ) {
			try {
				$input[ 0 ].stepDown();
			} catch ( ex ) {
				$input[ 0 ].value = decrease;
			}
		} else {
			$input[ 0 ].value = decrease;
		}
	}

	/**
	 * Update all the footer info.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.updateFooter = function( $form ) {
		obj.footerCount( $form );
		obj.footerAmount( $form );
		$form.find( '.tribe-tickets__footer' ).addClass( 'tribe-tickets__footer--active' );
	}

	/**
	 * Adjust the footer count for +/-.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.footerCount = function( $form ) {
		var $field      = $form.find( '.tribe-tickets__footer__quantity__number' );
		var footerCount = 0;
		var $qtys       = $form.find( obj.selector.itemQuantityInput );

		$qtys.each(function(){
			var new_quantity = parseInt( $(this).val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;
			footerCount      += new_quantity;
		} );

		if ( 0 > footerCount ) {
			return;
		}

		$field.text( footerCount );
	}

	/**
	 * Adjust the footer total/amount for +/-.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.footerAmount = function( $form ) {
		var $field       = $form.find( '.tribe-tickets__footer__total__number' );
		var footerAmount = 0;
		var $qtys        = $form.find( obj.selector.itemQuantityInput );

		$qtys.each(function(){
			var $price   = $( this ).closest( '.tribe-tickets__item' ).find( obj.selector.itemPrice );
			var quantity = parseInt( $( this ).val(), 10 );
			quantity     = isNaN( quantity ) ? 0 : quantity;
			footerAmount += parseFloat( $price.text() ) * quantity;
		} );

		if ( 0 > footerAmount ) {
			return;
		}

		$field.text( obj.numberFormat ( footerAmount ) );
	}

	/**
	 * Init the tickets script.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.checkAvailability();
	}

	obj.init();

	/**
	 * Handle the number input + and - actions.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	$( document ).on(
		'click',
		'.tribe-tickets__item__quantity__remove, .tribe-tickets__item__quantity__add',
		function( e ) {
			var $input = $( this ).parent().find( 'input[type="number"]' );

			if( $input.is( ':disabled' ) ) {
				return;
			}

			e.preventDefault();

			var originalValue = Number( $input[ 0 ].value );
			var $modalForm    = $input.closest( obj.modalSelector.container );

			// Step up or Step down the input according to the button that was clicked.
			// Handles IE/Edge.
			if ( $( this ).hasClass( 'tribe-tickets__item__quantity__add' ) ) {
				obj.stepUp( $input, originalValue );
			} else {
				obj.stepDown( $input, originalValue );
			}

			obj.updateFooter( $input.closest( 'form' ) );

			// Trigger the on Change for the input (if it has changed) as it's not handled via stepUp() || stepDown().
			if ( originalValue !== $input[ 0 ].value ) {
				$input.trigger( 'change' );
			}

			if ( $modalForm.length ) {
				var $item = $input.closest( obj.selector.item );
				obj.updateTotal( obj.getQty( $item ), obj.getPrice( $item ), $item );
			}
		}
	);

	/**
	 * Handle the TPP form.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	$( document ).on(
		'change, keyup',
		obj.selector.itemQuantityInput,
		function( e ) {
			var $this        = $( this );
			var $ticket      = $this.closest( obj.selector.item );
			var $ticket_id   = $ticket.data( 'ticket-id' );
			var $form        = $this.closest( 'form' );
			var new_quantity = parseInt( $this.val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;

			obj.maybeShowOptOut( $ticket, new_quantity );
			obj.updateFooter( $form );

			// Only disable / enable if is a Tribe Commerce Paypal form.
			if ( 'Tribe__Tickets__Commerce__PayPal__Main' === $form.data( 'provider' ) ) {
				obj.tribeCommerceDisable( new_quantity, $form, $ticket_id );
			}
		}
	);

	/**
	 * On Change of Modal Cart Qty Update Item.
	 *
	 * @since TBD
	 *
	 */
	$( document ).on(
		'change',
		obj.selector.itemQuantityInput,
		function ( e ) {
			var $cart = $( this ).closest( 'form' );

			e.preventDefault();
			obj.updateFormTotals( $cart );
		}
	);

	/**
	 * Remove Item from Cart Modal.
	 *
	 * @since TBD
	 *
	 */
	$( document ).on(
		'click',
		obj.modalSelector.itemRemove,
		function ( e ) {
			e.preventDefault();


			var ticket    = {};
			var $cart     = $( this ).closest( 'form' );
			var $cartItem = $( this ).closest( obj.selector.item );

			$cartItem.find( obj.selector.itemQuantity ).val( 0 );
			$cartItem.fadeOut() ;

			ticket.id    = $cartItem.data( 'ticketId' );
			ticket.qty   = 0;
			ticket.price = obj.getPrice( $cartItem );

			obj.updateTotal( ticket.qty, ticket.price, $cartItem );
			obj.updateFormTotals( $cart );

			$( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticket.id + '"]' )
				.removeClass( 'tribe-tickets--has-tickets' )
				.find( '.tribe-ticket' ).remove();
		}
	);

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 */
	$( document ).on(
		'focus',
		'.tribe-ticket input, .tribe-ticket select, .tribe-ticket textarea',
		function( e ) {
			var input      = e.target;
			var $container = $( input ).closest( '.tribe-ticket' );

			$container.addClass( 'tribe-ticket-item--has-focus' );
		}
	);

	/**
	 * Removes focus effect from ticket block.
	 *
	 * @since TBD
	 *
	 */
	$( document ).on(
		'blur',
		'.tribe-ticket input, .tribe-ticket select, .tribe-ticket textarea',
		function( e ) {
			var input      = e.target;
			var $container = $( input ).closest( '.tribe-ticket' );

			$container.removeClass( 'tribe-ticket-item--has-focus' );
		}
	);

	/**
	 * When "Get Tickets" is clicked, update the modal.
	 *
	 * @since TBD
	 *
	 */
	$( te ).on(
		'tribe_dialog_show_ar_modal',
		function ( e, dialogEl, event ) {
			var $cart      = $( obj.selector.container );
			var $modalCart = $( obj.modalSelector.container );
			var $cartItems = $cart.find( obj.selector.item );

			$cartItems.each(
				function () {
					var $blockCartItem = $( this );
					var id             = $blockCartItem.data( 'ticketId' );
					var $modalCartItem = $modalCart.find( '[data-ticket-id="' + id + '"]' );

					if ( ! $modalCartItem ) {
						return;
					}

					obj.updateItem( id, $modalCartItem, $blockCartItem );
				}
			);

			obj.updateFormTotals( $modalCart );
		}
	);

	/**
	 * Update Cart Totals in Modal.
	 *
	 * @since TBD
	 *
	 * @param $cart The jQuery form object to update totals.
	 */
	obj.updateFormTotals = function ( $cart ) {
		var total_qty    = 0;
		var total_amount = 0.00;

		$cart.find( obj.selector.item ).each(
			function () {
				var modalCartItem = $( this );
				var qty           = obj.getQty( modalCartItem );

				var total = parseFloat( $( this ).find( obj.modalSelector.itemTotal ).text().replace( ',', '' ) );

				if ( '.' === obj.getCurrencyFormatting().thousands_sep ) {
					total = parseFloat( $( this ).find( obj.modalSelector.itemTotal ).text().replace( /\./g,'' ).replace( ',', '.' ) );
				}

				total_qty    += parseInt( qty, 10 );
				total_amount += total;

			}
		);

		obj.updateFooter( $cart );

		obj.appendARFields( $cart );
	};

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 * @param obj $form The for m we are updating.
	 */
	obj.appendARFields = function ( $form ) {
		$form.find( obj.selector.item ).each(
			function () {
				var $cartItem = $( this );

				if ( $cartItem.is( ':visible' ) ) {
					var ticketID          = $cartItem.closest( '.tribe-tickets__item' ).data( 'ticket-id' );
					var $ticket_container = $( '#tribe-modal__attendee_registration' ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticketID + '"]' );

					// Ticket does not have meta - no need to jump through hoops (and throw errors).
					if ( ! $ticket_container.length ) {
						return;
					}

					var $existing = $ticket_container.find( '.tribe-ticket' );
					var qty       = obj.getQty( $cartItem );

					if ( 0 >= qty ) {
						$ticket_container.removeClass( 'tribe-tickets--has-tickets' );
						$ticket_container.find( '.tribe-ticket' ).remove();

						return;
					}

					if ( $existing.length > qty ) {
						var remove_count = $existing.length - qty;

						$ticket_container.find( '.tribe-ticket:nth-last-child( -n+' + remove_count + ' )' ).remove();
					} else if ( $existing.length < qty ) {
						var ticketTemplate = window.wp.template( 'tribe-registration--' + ticketID );
						var counter        = 0 < $existing.length ? $existing.length + 1 : 1;

						$ticket_container.addClass( 'tribe-tickets--has-tickets' );

						for ( var i = counter; i <= qty; i++ ) {
							var data = { 'attendee_id': i };

							$ticket_container.append( ticketTemplate( data ) );
						}
					}
				}
			}
		);
	}

	/**
	 * Possibly Update an Items Qty and always update the Total.
	 *
	 * @since TBD
	 *
	 * @param int id            The id of the ticket/product.
	 * @param obj $modalCartItem The cart item to update.
	 * @param obj $blockCartItem The optional ticket block cart item.
	 *
	 * @returns {number}
	 */
	obj.updateItem = function ( id, $modalCartItem, $blockCartItem ) {
		var item = {};
		item.id  = id;

		if ( $blockCartItem ) {
			item.qty   = obj.getQty( $blockCartItem );
			item.price = obj.getPrice( $modalCartItem );

			$modalCartItem.find( obj.selector.itemQuantityInput ).val( item.qty );

			if ( item.qty <= 0 ) {
				$modalCartItem.fadeOut();
			}

			obj.updateTotal( item.qty, item.price, $modalCartItem );

			return item;
		}

		item.qty   = obj.getQty( $modalCartItem );
		item.price = obj.getPrice( $modalCartItem );

		obj.updateTotal( item.qty, item.price, $modalCartItem );

		return item;

	};

	/**
	 * Get the Quantity.
	 *
	 * @since TBD
	 *
	 * @param obj cartItem The cart item to update.
	 *
	 * @returns {number}
	 */
	obj.getQty = function ( $cartItem ) {
		var qty = parseInt( $cartItem.find( obj.selector.itemQuantityInput ).val(), 10 );

		return isNaN( qty ) ? 0 : qty;
	};

	/**
	 * Get the Price.
	 *
	 *
	 * @param obj cartItem     The cart item to update.
	 * @params string cssClass The string of the class to get the price from.
	 *
	 * @returns {number}
	 */
	obj.getPrice = function ( $cartItem ) {
		var price = parseFloat( $cartItem.find( obj.selector.itemPrice ).text() );

		return isNaN( price ) ? 0 : price;
	};

	/**
	 * Update the Price for the Given Cart Item.
	 *
	 * @since TBD
	 *
	 * @param number qty   The quantity.
	 * @param number price The price.
	 * @param obj cartItem The cart item to update.
	 *
	 * @returns {string}
	 */
	obj.updateTotal = function ( qty, price, $cartItem ) {
		var total_for_item = ( qty * price ).toFixed( obj.getCurrencyFormatting().number_of_decimals );
		var $field         = $cartItem.find( '.tribe-tickets__item__total' );

		$field.text( obj.numberFormat( total_for_item ) );

		return total_for_item;
	};

	/**
	 * Get the Currency Formatting for a Provider.
	 *
	 * @since TBD
	 *
	 * @returns {*}
	 */
	obj.getCurrencyFormatting = function () {
		var currency = JSON.parse( TribeCurrency.formatting );
		var provider = $( obj.selector.container ).data( 'provider' );

		return currency[provider];
	};

	/**
	 * Format the number according to provider settings.
	 * Based off coding fron https://stackoverflow.com/a/2901136.
	 *
	 * @since TBD
	 *
	 * @param number The number to format.
	 * @returns {string}
	 */
	obj.numberFormat = function ( number ) {
		var decimals      = obj.getCurrencyFormatting().number_of_decimals;
		var dec_point     = obj.getCurrencyFormatting().decimal_point;
		var thousands_sep = obj.getCurrencyFormatting().thousands_sep;

		var n          = !isFinite( +number ) ? 0 : +number;
		var prec       = !isFinite( +decimals ) ? 0 : Math.abs( decimals );
		var sep        = ( 'undefined' === typeof thousands_sep ) ? ',' : thousands_sep;
		var dec        = ( 'undefined' === typeof dec_point ) ? '.' : dec_point;
		var toFixedFix = function ( n, prec ) {
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			var k = Math.pow( 10, prec );
			return Math.round( n * k ) / k;
		};

		var s = ( prec ? toFixedFix( n, prec ) : Math.round( n )).toString().split( '.' );

		if ( s[0].length > 3 ) {
			s[0] = s[0].replace( /\B(?=(?:\d{3} )+(?!\d))/g, sep );
		}

		if ( ( s[1] || '' ).length < prec ) {
			s[1] = s[1] || '';
			s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
		}

		return s.join( dec );
	}

	$( document ).on(
		'click',
		$( obj.modalSelector ).find( '.tribe-modal__close-button' ),
		function ( event ) {
			var form = jQuery( '#tribe-modal__cart' );
			var data = form.serialize();
			sessionStorage.setItem( 'tribe_tickets_cart', data );
		}
	);
} )( jQuery, tribe.tickets.block, tribe_ev.events );
