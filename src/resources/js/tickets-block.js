// @TODO: Take this line off once we actually have the tribe object
var tribe            = tribe || {};
tribe.tickets        = tribe.tickets || {};
tribe.dialogs        = tribe.dialogs || {};
tribe.dialogs.events = tribe.dialogs.events || {};
tribe.tickets.block  = {
	num_attendees: 0,
	event        : {}
};

( function( $, obj, tde ) {
	'use strict';

	/* Variables */

	/*
	 * Ticket Block Selectors.
	 *
	 * @since TBD
	 *
	 */
	obj.selector = {
		blockFooter                : '.tribe-tickets__footer',
		blockFooterAmount          : '.tribe-amount',
		blockFooterQuantity        : '.tribe-tickets__footer__quantity__number',
		blockSubmit                : '.tribe-tickets__submit',
		container                  : '#tribe-tickets',
		hidden                     : 'tribe-common-a11y-hidden',
		item                       : '.tribe-tickets__item',
		itemExtraAvailable         : '.tribe-tickets__item__extra__available',
		itemExtraAvailableQuantity : '.tribe-tickets__item__extra__available__quantity',
		itemOptOut                 : '.tribe-tickets-attendees-list-optout--wrapper',
		itemOptOutInput            : '#tribe-tickets-attendees-list-optout-',
		itemPrice                  : '.tribe-amount',
		itemQuantity               : '.tribe-tickets__item__quantity',
		itemQuantityInput          : '.tribe-tickets-quantity',
		loader                     : '.tribe-common-c-loader',
		submit                     : '.tribe-tickets__buy',
		ticketLoader               : '.tribe-tickets-loader__tickets-block',
		validationNotice           : '.tribe-tickets__notice--error',
		ticketInCartNotice         : '#tribe-tickets__notice__tickets-in-cart'
	};

	var $tribe_ticket = $( obj.selector.container );

	// Bail if there are no tickets on the current event/page/post.
	if ( 0 === $tribe_ticket.length ) {
		return;
	}

	obj.document = $( document );

	/*
	 * AR Cart Modal Selectors.
	 *
	 * Note: some of these have the modal class as well, as the js can
	 * pick up the class from elsewhere in the DOM and grab the wrong data.
	 *
	 * @since TBD
	 *
	 */
	obj.modalSelector = {
		cartForm   : '.tribe-modal__wrapper--ar #tribe-modal__cart',
		container  : '.tribe-modal__wrapper--ar',
		itemRemove : '.tribe-tickets__item__remove',
		itemTotal  : '.tribe-tickets__item__total .tribe-amount',
		loader     : '.tribe-tickets-loader__modal',
		metaField  : '.ticket-meta',
		metaForm   : '.tribe-modal__wrapper--ar #tribe-modal__attendee_registration',
		metaItem   : '.tribe-ticket',
		submit     : '.tribe-block__tickets__item__attendee__fields__footer_submit',
	};

	/*
	 * Commerce Provider "lookup table".
	 *
	 * @since TBD
	 *
	 */
	obj.commerceSelector = {
		edd                                              : 'Tribe__Tickets_Plus__Commerce__EDD__Main',
		rsvp                                             : 'Tribe__Tickets__RSVP',
		tpp                                              : 'Tribe__Tickets__Commerce__PayPal__Main',
		Tribe__Tickets__Commerce__PayPal__Main           : 'tribe-commerce',
		Tribe__Tickets__RSVP                             : 'rsvp',
		Tribe__Tickets_Plus__Commerce__EDD__Main         : 'edd',
		Tribe__Tickets_Plus__Commerce__WooCommerce__Main : 'woo',
		tribe_eddticket                                  : 'Tribe__Tickets_Plus__Commerce__EDD__Main',
		tribe_tpp_attendees                              : 'Tribe__Tickets__Commerce__PayPal__Main',
		tribe_wooticket                                  : 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
		woo                                              : 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
	};

	obj.tribe_ticket_provider = $tribe_ticket.data( 'provider' );
	obj.postId                = $( '.status-publish' ).attr( 'id' ).replace( 'post-', '' );

	// Translations - for future use.
	var { __, _x, _n, _nx } = wp.i18n;

	/**
	 * Init the tickets script.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.loaderShow();
		obj.checkAvailability();
		obj.initPrefill();
	}

	/* DOM Updates */

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
				$ticketEl.find( obj.selector.itemQuantity ).html( unavailableHtml );
				$ticketEl.find( obj.selector.itemExtraAvailable ).html( '' );
			}

			if ( 1 < available ) { // Ticket in stock, we may want to update values.
				$ticketEl.find( obj.selector.itemQuantityInput ).attr( { 'max' : available } );
				$ticketEl.find( obj.selector.itemExtraAvailableQuantity ).html( available );
			}
		} );
	}

	/**
	 * Update all the footer info.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.updateFooter = function( $form ) {
		obj.updateFooterCount( $form );
		obj.updateFooterAmount( $form );
		$form.find( '.tribe-tickets__footer' ).addClass( 'tribe-tickets__footer--active' );
	}

	/**
	 * Adjust the footer count for +/-.
	 *
	 * @since TBD
	 *
	 * @param int    $form The form we're updating.
	 */
	obj.updateFooterCount = function( $form ) {
		var $field      = $form.children( obj.selector.blockFooter ).find( obj.selector.blockFooterQuantity );
		var footerCount = 0;
		var $qtys       = $form.children( obj.selector.item ).find( obj.selector.itemQuantityInput );

		$qtys.each( function() {
			var new_quantity = parseInt( $(this).val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;
			footerCount      += new_quantity;
		} );

		if ( $form.hasClass( 'tribe-tickets' ) ) {
			var disabled = 0 >= footerCount ? true : false ;

			$( obj.selector.blockSubmit ).prop( 'disabled', disabled );
		}

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
	obj.updateFooterAmount = function( $form ) {
		var $field       = $form.children( obj.selector.blockFooter ).find( obj.selector.blockFooterAmount );
		var footerAmount = 0;
		var $qtys        = $form.children( obj.selector.item ).find( obj.selector.itemQuantityInput );

		$qtys.each( function() {
			var $price   = $( this ).closest( obj.selector.item ).find( obj.selector.itemPrice ).first();
			var quantity = parseInt( $( this ).val(), 10 );
			quantity     = isNaN( quantity ) ? 0 : quantity;
			var text     = $price.text();
			text         = obj.cleanNumber( text );
			var price    = obj.numberFormat ( text );
			price        = price * quantity;
			footerAmount += price;
		} );

		if ( 0 > footerAmount ) {
			return;
		}

		$field.text( obj.numberFormat ( footerAmount ) );
	}

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

				var total = parseFloat( $( this ).find( obj.modalSelector.itemTotal ).first().text().replace( ',', '' ) );

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

		if ( ! $blockCartItem ) {
			item.qty   = obj.getQty( $modalCartItem );
			item.price = obj.getPrice( $modalCartItem );
		} else {
			item.qty   = obj.getQty( $blockCartItem );
			item.price = obj.getPrice( $modalCartItem );

			$modalCartItem.find( obj.selector.itemQuantityInput ).val( item.qty ).trigger( 'change' );

			// We force new DOM queries here to be sure we pick up dynamically generated items.
			var optoutSelector = obj.selector.itemOptOutInput + $blockCartItem.data( 'ticket-id' );
			item.$optOut = $( optoutSelector );
			var $optoutInput = $( optoutSelector + '-modal' );

			( item.$optOut.length && item.$optOut.is( ':checked' ) ) ? $optoutInput.val( '1' ) : $optoutInput.val( '0' );
		}

		obj.updateTotal( item.qty, item.price, $modalCartItem );

		return item;
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
		var $field         = $cartItem.find( obj.modalSelector.itemTotal );

		$field.text( obj.numberFormat( total_for_item ) );

		return total_for_item;
	};

	/**
	 * Shows/hides the non-ar notice based on the number of tickets passed.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.maybeShowNonMetaNotice = function( $form ) {
		var nonMetaCount = 0;
		var metaCount    = 0;
		var $cartItems   =  $form.children( obj.selector.item ).filter(
			function( index ) {
				return $( this ).find( obj.selector.itemQuantityInput ).val() > 0;
			}
		);

		if ( ! $cartItems.length ) {
			return;
		}

		$cartItems.each(
			function() {
				var $cartItem         = $( this );
				var ticketID          = $cartItem.closest( obj.selector.item ).data( 'ticket-id' );
				var $ticket_container = $( obj.modalSelector.metaForm ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticketID + '"]' );

				// Ticket does not have meta - no need to jump through hoops (and throw errors).
				if ( ! $ticket_container.length ) {
					nonMetaCount += obj.getQty( $cartItem );
				} else {
					metaCount += obj.getQty( $cartItem );
				}
			}
		);

		var $notice = $( '.tribe-tickets__notice--non-ar' );
		var $title  = $( '.tribe-tickets__item__attendee__fields__title' );

		// If there are no non-meta tickets, we don't need the notice
		// Likewise, if there are no tickets with meta the notice seems redundant.
		if ( 0 < nonMetaCount && 0 < metaCount ) {
			$( '#tribe-tickets__non-ar-count' ).text( nonMetaCount );
			$notice.removeClass( 'tribe-common-a11y-hidden' );
			$title.show();
		} else {
			$notice.addClass( 'tribe-common-a11y-hidden' );
			$title.hide();
		}
	}

	/* Utility */

	/**
	 * Get the REST endpoint
	 *
	 * @since TBD
	 */
	obj.getRestEndpoint = function() {
		var url = TribeCartEndpoint.url;
		return url;
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
	 * Maybe display the Opt Out.
	 *
	 * @since TBD
	 *
	 * @param obj $ticket The ticket item element.
	 * @param int new_quantity The new ticket quantity.
	 */
	obj.maybeShowOptOut = function( $ticket, new_quantity ) {
		var has_optout = $ticket.has( obj.selector.itemOptOut ).length;
		if ( has_optout ) {
			var $optout = $ticket.closest( obj.selector.item ).find( obj.selector.itemOptOut );
			( 0 < new_quantity ) ? $optout.show() : $optout.hide();
		}
	}

	/**
	 * Appends AR fields when modal cart quantities are changed.
	 *
	 * @since TBD
	 *
	 * @param obj $form The form we are updating.
	 */
	obj.appendARFields = function ( $form ) {
		$form.find( obj.selector.item ).each(
			function () {
				var $cartItem = $( this );

				if ( $cartItem.is( ':visible' ) ) {
					var ticketID          = $cartItem.closest( obj.selector.item ).data( 'ticket-id' );
					var $ticket_container = $( obj.modalSelector.metaForm ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticketID + '"]' );

					// Ticket does not have meta - no need to jump through hoops (and throw errors).
					if ( ! $ticket_container.length ) {
						return;
					}

					var $existing = $ticket_container.find( obj.modalSelector.metaItem );
					var qty       = obj.getQty( $cartItem );

					if ( 0 >= qty ) {
						$ticket_container.removeClass( 'tribe-tickets--has-tickets' );
						$ticket_container.find( obj.modalSelector.metaItem ).remove();

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
							obj.maybeHydrateAttendeeBlockFromLocal( $existing.length );
						}
					}
				}
			}
		);

		obj.maybeShowNonMetaNotice( $form );
		obj.loaderHide();
		obj.document.trigger( 'tribe-ar-fields-appended' );
	}

	/**
	 * Step up the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 */
	obj.stepUp = function( $input, originalValue ) {
		// We use 0 here as a shorthand for no maximum.
		var max       = $input.attr( 'max' ) ? Number( $input.attr( 'max' ) ) : -1;
		var step      = $input.attr( 'step' ) ? Number( $input.attr( 'step' ) ) : 1;
		var new_value = ( -1 === max || max >= originalValue + step ) ? originalValue + step : max;
		var $parent = $input.closest( obj.selector.item );

		if ( 'true' === $parent.attr( 'data-shared-cap' ) ) {
			var $form        = $parent.closest( 'form' );
			new_value = obj.checkSharedCapacity( $form, new_value );
		}

		if ( 0 === new_value ) {
			return;
		}

		if ( 0 > new_value ) {
			$input[ 0 ].value = originalValue + new_value;
			return;
		}

		if ( 'function' === typeof $input[ 0 ].stepUp ) {
			try {
				$input[ 0 ].stepUp();
			} catch ( ex ) {
				$input.val( new_value );
			}
		} else {
			$input.val( new_value );
		}
	}

	/**
	 * Step down the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 */
	obj.stepDown = function( $input, originalValue ) {
		var min      = $input.attr( 'min' ) ? Number( $input.attr( 'min' ) ) : 0;
		var step     = $input.attr( 'step' ) ? Number( $input.attr( 'step' ) ) : 1;
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
	 * Check if we're updating the qty of a shared cap ticket and
	 * limits it to the shared cap minus any tickets in cart.
	 *
	 * @since TBD
	 *
	 * @param integer qty The quantity we desire.
	 *
	 * @return integer The quantity, limited by exisitng shared cap tickets.
	 */
	obj.checkSharedCapacity = function ( $form, qty ) {
		var sharedCap         = [];
		var currentLoad       = [];
		var $sharedTickets    = $form.children( obj.selector.item ).filter( '[data-shared-cap="true"]' );
		var $sharedCapFields  = $sharedTickets.find( obj.selector.itemExtraAvailableQuantity );
		var $sharedCapTickets = $sharedTickets.find( obj.selector.itemQuantityInput );

		if ( ! $sharedTickets.length ) {
			return qty;
		}

		$sharedCapFields.each(
			function() {
				sharedCap.push( parseInt( $( this ).text(), 10 ) );
			}
		);

		$sharedCapTickets.each(
			function() {
				currentLoad.push( parseInt( $( this ).val(), 10 ) );
			}
		);

		sharedCap   = Math.max( ...sharedCap );
		currentLoad = currentLoad.reduce(
			function( a, b ) {
				return a + b;
			},
			0
		);

		var currentAvailable = sharedCap - currentLoad;

		return Math.min( currentAvailable, qty );
	}

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
		var price = parseFloat( $cartItem.find( obj.selector.itemPrice ).first().text() );

		return isNaN( price ) ? 0 : price;
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

		return currency[ obj.tribe_ticket_provider ];
	};

	/**
	 * Removes separator characters and converts deciaml character to '.'
	 * So they play nice with other functions.
	 *
	 * @since TBD
	 *
	 * @param number The number to clean.
	 * @returns {string}
	 */
	obj.cleanNumber = function( number ) {
		var format = obj.getCurrencyFormatting();
		// we run into issue when the two symbols are the same -
		// which appears to happen by default with some providers.
		var same   = format.thousands_sep === format.decimal_point;

		if ( ! same ) {
			number = number.split(format.thousands_sep).join('');
			number = number.split(format.decimal_point).join('.');
		} else {
			var dec_place = number.length - ( format.number_of_decimals + 1 );
			number = number.substr( 0, dec_place ) + '_' + number.substr( dec_place + 1);
			number = number.split(format.thousands_sep).join('');
			number = number.split('_').join('.');
		}

		return number;
	}

	/**
	 * Format the number according to provider settings.
	 * Based off coding fron https://stackoverflow.com/a/2901136.
	 *
	 * @since TBD
	 *
	 * @param number The number to format.
	 *
	 * @returns {string}
	 */
	obj.numberFormat = function ( number ) {
		var format = obj.getCurrencyFormatting();

		if ( ! format ) {
			return false;
		}

		var decimals      = format.number_of_decimals;
		var dec_point     = format.decimal_point;
		var thousands_sep = format.thousands_sep;
		var n             = !isFinite( +number ) ? 0 : +number;
		var prec          = !isFinite( +decimals ) ? 0 : Math.abs( decimals );
		var sep           = ( 'undefined' === typeof thousands_sep ) ? ',' : thousands_sep;
		var dec           = ( 'undefined' === typeof dec_point ) ? '.' : dec_point;
		var toFixedFix    = function ( n, prec ) {
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			var k = Math.pow( 10, prec );

			return Math.round( n * k ) / k;
		};

		var s = ( prec ? toFixedFix( n, prec ) : Math.round( n )).toString().split( dec );

		if ( s[0].length > 3 ) {
			s[0] = s[0].replace( /\B(?=(?:\d{3} )+(?!\d))/g, sep );
		}

		if ( ( s[1] || '' ).length < prec ) {
			s[1] = s[1] || '';
			s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
		}

		return s.join( dec );
	}

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.focusTicketBlock = function( input ) {
		$( input )
			.closest( obj.modalSelector.metaItem )
			.addClass( 'tribe-ticket-item__has-focus' );
	}

	/**
	 * Remove focus effect from ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.unfocusTicketBlock = function( input ) {
		$( input )
			.closest( obj.modalSelector.metaItem )
			.removeClass( 'tribe-ticket-item__has-focus' );
	}

	/**
	 * Show the loader/spinner.
	 *
	 * @since TBD
	 *
	 * @param string loaderClass A class for targeting a specific loader.
	 * @return void
	 */
	obj.loaderShow = function( loaderClass ) {
		if ( 'undefined' === typeof loaderClass ) {
			loaderClass = obj.selector.ticketLoader;
		}

		var $loader = $( obj.selector.loader ).filter( loaderClass );

		$loader.removeClass( obj.selector.hidden );
	}

	/**
	 * Hide the loader/spinner.
	 *
	 * @since TBD
	 *
	 * @param string loaderClass A class for targeting a specific loader.
	 * @return void
	 */
	obj.loaderHide = function(loaderClass ) {
		if ( 'undefined' === typeof loaderClass ) {
			loaderClass = obj.selector.ticketLoader;
		}

		var $loader = $( obj.selector.loader ).filter( loaderClass );

		$loader.addClass( obj.selector.hidden );
	}

	/* Prefill Handling */

	/**
	 * Init the tickets block prefill.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.initPrefill = function() {
		obj.prefillTicketsBlock();
	}

	/**
	 * Init the form prefills (cart and AR forms).
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.initModalFormPrefills = function() {
		obj.loaderShow( obj.modalSelector.loader );
		$.when(
			obj.getData()
		).then(
			function( data ) {
				obj.prefillModalCartForm( $( obj.modalSelector.cartForm ) );

				if ( data.meta ) {
					var count = false;

					$.each( data.meta, function( ticket ) {
						var $matches = $tribe_ticket.find( `[data-ticket-id="${ticket.ticket_id}"]` );

						if ( $matches.length ) {
							obj.prefillModalMetaForm( data.meta );

							return;
						}
					} );
				}

				// If we didn't get meta from the API, let's fill with sessionStorage.
				var local = obj.getLocal();

				if ( local.meta ) {
					obj.prefillModalMetaForm( local.meta );
				}

				var timeoutID = window.setTimeout( obj.loaderHide, 500, obj.modalSelector.loader );
			}
		);
	}

	/**
	 * Prefills the modal AR fields from supplied data.
	 *
	 * @since TBD
	 *
	 * @param meta Data to fill the form in with.
	 * @param length Starting pointer for partial fill-ins.
	 *
	 * @return void
	 */
	obj.prefillModalMetaForm = function( meta, length ) {
		if ( undefined === meta || 0 >= meta.length ) {
			return;
		}

		if ( undefined === length ) {
			var length = 0;
		}

		var $form       = $( obj.modalSelector.metaForm );
		var $containers = $form.find( '.tribe-tickets__item__attendee__fields__container' );

		if ( 0 < length ) {
			var removed = meta.splice( 0, length - 1 );
		}

		$.each( meta, function( index, ticket ) {
			var current             = 0;
			var $current_containers = $containers.find( obj.modalSelector.metaItem ).filter( `[data-ticket-id="${ticket.ticket_id}"]` );

			if ( ! $current_containers.length ) {
				return;
			}

			$.each( ticket.items, function( index, data ) {
				if ( 'object' !== typeof data ) {
					return;
				}

				$.each( data, function( index, value ) {
					var $field = $current_containers.eq( current ).find( `[name*="${index}"]` );

					if ( ! $field.is( ':radio' ) && ! $field.is( ':checkbox' ) ) {
						$field.val( value);
					} else {
						$field.each( function( index ) {
							var $item = $( this );

							if ( value === $item.val() ) {
								$item.prop( 'checked', true );
							}
						} );
					}
				} );

				current++;
			} );
		} );

		obj.loaderHide( obj.modalSelector.loader );
	}

	/**
	 * Prefill the Cart.
	 *
	 * @since TBD
	 *
	 * @returns {*}
	 */
	obj.prefillModalCartForm = function ( $form ) {
		$form.find( obj.selector.item ).hide();

		var $items = $tribe_ticket.children( obj.selector.item );

		// Override the data with what's in the tickets block.
		$.each( $items, function( index, item ) {
			var $block_item = $( item );
			var $item = $form.find( '[data-ticket-id="' + $block_item.attr( 'data-ticket-id' ) + '"]' );

			if ( $item ) {
				var quantity  = $block_item.find( '.tribe-tickets-quantity' ).val();
				if ( 0 < quantity ) {

					$item.fadeIn();
				}
			}
		} );

		obj.appendARFields($form);

		obj.loaderHide( obj.modalSelector.loader );
	};

	/**
	 * Prefill tickets block from cart.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.prefillTicketsBlock = function() {
		$.when(
			obj.getData( true )
		).then(
			function( data ) {

				var tickets = data.tickets;

				if ( tickets.length ) {
					var $eventCount = 0;

					tickets.forEach( function( ticket ) {
						var $ticketRow = $( `.tribe-tickets__item[data-ticket-id="${ticket.ticket_id}"]` );
						if ( 'true' === $ticketRow.attr( 'data-available' ) ) {
							var $field  = $ticketRow.find( obj.selector.itemQuantityInput );
							var $optout = $ticketRow.find( obj.selector.itemOptOutInput + ticket.ticket_id );

							if ( $field.length ) {
								$field.val( ticket.quantity );
								$field.trigger( 'change' );
								$eventCount += ticket.quantity;
								if ( 1 == parseInt( ticket.optout, 10 ) ) {
									$optout.prop( 'checked', 'true' );
								}
							}
						}
					} );

					if ( 0 < $eventCount ) {
						$( obj.selector.ticketInCartNotice ).fadeIn();
					}
				}

				obj.loaderHide();
			},
			function() {
				var $errorNotice =  $( obj.selector.ticketInCartNotice );
				$errorNotice
					.removeClass( 'tribe-tickets__notice--barred tribe-tickets__notice--barred-left' )
					.addClass( 'tribe-tickets__notice--error' );
				$errorNotice.find( '.tribe-tickets-notice__title' ).text( TribeMessages.api_error_title );
				$errorNotice.find( '.tribe-tickets-notice__content' ).text( TribeMessages.connection_error );
				$errorNotice.fadeIn();

				obj.loaderHide();
			}
		);
	}

	/* sessionStorage ("local") */

	/**
	 * Stores attendee and cart form data to sessionStorage.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.storeLocal = function( data ) {
		var meta  = obj.getMetaForSave();
		sessionStorage.setItem(
			'tribe_tickets_attendees-' + obj.postId,
			window.JSON.stringify( meta )
		);

		var tickets  = obj.getTicketsForCart();
		sessionStorage.setItem(
			'tribe_tickets_cart-' + obj.postId,
			window.JSON.stringify( tickets )
		);
	}

	/**
	 * Gets attendee and cart form data from sessionStorage.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	obj.getLocal = function( postId ) {
		if ( ! postId ) {
			var postId = obj.postId;
		}

		var meta    = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_attendees-' + postId ) );
		var tickets = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_cart-' + postId ) );
		var ret     = {  meta, tickets };

		return ret;
	}

	/**
	 * Clears attendee and cart form data for this event from sessionStorage.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.clearLocal = function( postId ) {
		if ( ! postId ) {
			var postId = obj.postId;
		}

		sessionStorage.removeItem( 'tribe_tickets_attendees-' + postId );
		sessionStorage.removeItem( 'tribe_tickets_cart-' + postId );
	}

	/**
	 * Attempts to hydrate a dynamically-created attendee form "block" from sessionStorage data.
	 *
	 * @since TBD
	 *
	 * @param object data The attendee data.
	 *
	 * @return void
	 */
	obj.maybeHydrateAttendeeBlockFromLocal = function( length ) {
		$.when(
			obj.getData()
		).then(
			function( data ) {
				var cartSkip = data.meta.length;
				if (length < cartSkip ) {
					obj.prefillModalMetaForm( data.meta, length );

					return;
				} else {
					var $attendeeForm = $( obj.modalSelector.metaForm );
					var $newBlocks    = $attendeeForm.find( obj.modalSelector.metaItem ).slice( length - 1 );

					if ( ! $newBlocks ) {
						return;
					}

					$newBlocks.find( obj.modalSelector.metaField ).each(
						function() {
							var $this     = $( this );
							var name      = $this.attr( 'name' );
							var storedVal = data[ name ];

							if ( storedVal ) {
								$this.val( storedVal );
							}
						}
					);
				}
			}
		);
	}

	/* Data Formatting / API Handling */

	/**
	 * Get ticket data to send to cart.
	 *
	 * @since TBD
	 *
	 * @return obj Tickets data object.
	 */
	obj.getTicketsForCart = function() {
		var tickets   = [];
		var $cartForm = $( obj.modalSelector.cartForm );

		// Handle non-modal instances
		if ( ! $cartForm.length ) {
			$cartForm = $( obj.selector.container );
		}

		var $ticketRows = $cartForm.find( obj.selector.item );

		$ticketRows.each(
			function() {
				var $row        = $( this );
				var ticket_id    = $row.data( 'ticketId' );
				var qty          = $row.find( obj.selector.itemQuantityInput ).val();
				var $optoutInput = $row.find( '[name="attendee[optout]"]' );
				var optout       = $optoutInput.val();

				if ( $optoutInput.is( ':checkbox' ) ) {
					optout = $optoutInput.prop( 'checked' ) ? 1 : 0;
				}

				var data          = {};
				data['ticket_id'] = ticket_id;
				data['quantity']  = qty;
				data['optout']    = optout;

				tickets.push( data );
			}
		);

		return tickets;
	}

	/**
	 *
	 *
	 * @since TBD
	 *
	 * @return obj Meta data object.
	 */
	obj.getMetaForSave = function() {
		var $metaForm     = $( obj.modalSelector.metaForm );
		var $ticketRows   = $metaForm.find( obj.modalSelector.metaItem );
		var meta          = [];
		var tempMeta      = [];

		$ticketRows.each(
			function() {
				var data      = {};
				var $row      = $( this );
				var ticket_id = $row.data( 'ticketId' );

				var $fields = $row.find( obj.modalSelector.metaField );

				// Skip tickets with no meta fields
				if ( ! $fields.length ) {
					return;
				}

				if ( ! tempMeta[ ticket_id ] ) {
					tempMeta[ ticket_id ]              = {};
					tempMeta[ ticket_id ]['ticket_id'] = ticket_id;
					tempMeta[ ticket_id ][ 'items' ]   = [];
				}

				$fields.each(
					function() {
						var $field  = $( this );
						var value   = $field.val();
						var isRadio = $field.is( ':radio' );
						var name    = $field.attr( 'name' );

						// Grab everything after the last bracket `[`.
						name = name.split( '[' );
						name = name.pop().replace( ']', '' );

						// Skip unchecked radio/checkboxes.
						if ( isRadio || $field.is( ':checkbox' ) ) {
							if ( ! $field.prop( 'checked' ) ) {
								// If empty radio field, if field already has a value, skip setting it as empty.
								if ( isRadio && '' !== data[name] ) {
									return;
								}

								value = '';
							}
						}

						data[name] = value;
					}
				);

				tempMeta[ ticket_id ]['items'].push(data);
			}
		);

		Object.keys(tempMeta).forEach( function( index ) {
			var newArr = {
				'ticket_id': index,
				'items': tempMeta[index]['items']
			};
			meta.push( newArr );
		} );

		return meta;
	}

	/**
	 * Get cart & meta data from sessionStorage, otherwise make an ajax call.
	 * Always loads tickets from API on page load to be sure we keep up to date with the cart.
	 *
	 * This returns a deferred data object (promise) So when calling you need to use something like
	 * jQuery's $.when()
	 *
	 * Example:
	 * 	$.when(
	 * 		obj.getData()
	 * 	).then(
	 * 		function( data ) {
	 * 			// Do stuff with the data.
	 * 		}
	 * 	);
	 *
	 * @since TBD
	 *
	 * @return obj Deferred data object.
	 */
	obj.getData = function( pageLoad ) {
		var ret      = {};
		var deferred = $.Deferred();
		var meta     = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_attendees-' + obj.postId ) );

		if ( null !== meta ) {
			ret.meta = meta;
		}

		// If we haven't reloaded the page, assume the cart hasn't changed since we did.
		if ( ! pageLoad ) {
			var tickets = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_cart-' + obj.postId ) );

			if ( null !== tickets && tickets.length ) {
				ret.tickets = tickets;
			}

			deferred.resolve( ret );
		}

		if ( ! ret.tickets || ! ret.meta ) {
			$.ajax( {
				type: 'GET',
				data: {
					provider: $tribe_ticket.data( 'providerId' ),
					post_id: obj.postId
				},
				dataType: 'json',
				url: obj.getRestEndpoint(),
				success: function ( data ) {
					// Store for future use.
					if ( null === meta ) {
						sessionStorage.setItem(
							'tribe_tickets_attendees-' + obj.postId,
							window.JSON.stringify( data.meta )
						);
					}

					sessionStorage.setItem(
						'tribe_tickets_cart-' + obj.postId,
						window.JSON.stringify( data.tickets )
					);

					var ret = {
						meta: data.meta,
						tickets: data.tickets
					};

					deferred.resolve( ret );
				},
				error: function() {
					deferred.reject( false );
				}
			} );
		}

		return deferred.promise();
	}

	/* Validation */

	/**
	 * Validates the entire meta form.
	 * Adds errors to the top of the modal.
	 *
	 * @since TBD
	 *
	 * @param $form jQuery object that is the form we are validating.
	 *
	 * @return boolean If the form validates.
	 */
	obj.validateForm = function( $form ) {
		var $containers    = $form.find( obj.modalSelector.metaItem );
		var formValid      = true;
		var invalidTickets = 0;

		$containers.each(
			function() {
				var $container     = $( this );
				var validContainer = obj.validateBlock( $container );

				if ( ! validContainer ) {
					invalidTickets++;
					formValid = false;
				}
			}
		);

		return [formValid, invalidTickets];
	}

	/**
	 * Validates and adds/removes error classes from a ticket meta block.
	 *
	 * @since TBD
	 *
	 * @param $container jQuery object that is the block we are validating.
	 *
	 * @return boolean True if all fields validate, false otherwise.
	 */
	obj.validateBlock = function( $container ) {
		var $fields    = $container.find( obj.modalSelector.metaField );
		var validBlock = true;

		$fields.each(
			function() {
				var $field       = $( this );
				var isValidfield = obj.validateField( $field[0] );

				if ( ! isValidfield ) {
					validBlock = false;
				}
			}
		);

		if ( validBlock ) {
			$container.removeClass( 'tribe-ticket-item__has-error' );
		} else {
			$container.addClass( 'tribe-ticket-item__has-error' );
		}

		return validBlock;
	}

	/**
	 * Validate Checkbox/Radio group.
	 * We operate under the assumption that you must check _at least_ one,
	 * but not necessarily all. Also that the checkboxes are all required.
	 *
	 * @since TBD
	 *
	 * @param $group The jQuery object for the checkbox group.
	 *
	 * @return boolean
	 */
	obj.validateCheckboxRadioGroup = function( $group ) {
		var $checkboxes   = $group.find( obj.modalSelector.metaField );
		var checkboxValid = false;
		var required      = true;

		$checkboxes.each(
			function() {
				var $this = $( this );

				if ( $this.is( ':checked' ) ) {
					checkboxValid = true;
				}

				if ( ! $this.prop( 'required' ) ) {
					required = false;
				}
			}
		);

		var valid = ! required || checkboxValid;

		return valid;
	}

	/**
	 * Adds/removes error classes from a single field.
	 *
	 * @since TBD
	 *
	 * @param input DOM Object that is the field we are validating.
	 *
	 * @return boolean
	 */
	obj.validateField = function( input ) {
		var isValidfield = true;
		var $input       = $( input );
		var isValidfield = input.checkValidity();

		if ( ! isValidfield ) {
			var $input = $( input );
			// Got to be careful of required checkbox/radio groups...
			if ( $input.is( ':checkbox' ) || $input.is( ':radio' ) ) {
				var $group = $input.closest( '.tribe-common-form-control-checkbox-radio-group' );

				if ( $group.length ) {
					isValidfield = obj.validateCheckboxRadioGroup( $group );
				}
			} else {
				isValidfield = false;
			}
		}

		if ( ! isValidfield ) {
			$input.addClass( 'ticket-meta__has-error' );
		} else {
			$input.removeClass( 'ticket-meta__has-error' );
		}

		return isValidfield;
	}

	/* Event Handling */

	/**
	 * Handle the number input + and - actions.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.document.on(
		'click touchend',
		'.tribe-tickets__item__quantity__remove, .tribe-tickets__item__quantity__add',
		function( e ) {
			var $input = $( this ).parent().find( 'input[type="number"]' );

			if ( $input.is( ':disabled' ) ) {
				return;
			}

			e.preventDefault();

			var originalValue = Number( $input[ 0 ].value );
			var $modalForm    = $input.closest( obj.modalSelector.cartForm );

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
	 * Remove Item from Cart Modal.
	 *
	 * @since TBD
	 *
	 */
	obj.document.on(
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
			$cartItem.find( obj.selector.itemQuantityInput ).val( ticket.qty );
			ticket.price = obj.getPrice( $cartItem );

			obj.updateTotal( ticket.qty, ticket.price, $cartItem );
			obj.updateFormTotals( $cart );

			$( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticket.id + '"]' )
				.removeClass( 'tribe-tickets--has-tickets' )
				.find( obj.modalSelector.metaItem ).remove();

			// Short delay to ensure the fadeOut has finished.
			var timeoutID = window.setTimeout( obj.maybeShowNonMetaNotice, 500, $cart );

			// Close then modal if we remove the last item
			// Again, short delay to ensure the fadeOut has finished.
			var maybeCloseModal = window.setTimeout(
				function() {
					var $items = $cart.find( obj.selector.item ).filter( ':visible' );
					if ( 0 >= $items.length ) {
						// Get the object ID
						var id     = $( obj.selector.blockSubmit ).attr( 'data-content' );
						var result = 'dialog_obj_' + id.substring( id.lastIndexOf('-') + 1 );

						// Close the dialog
						window[ result ].hide();
					}
				},
				500,
			);
		}
	);

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.document.on(
		'focus',
		'.tribe-ticket .ticket-meta',
		function( e ) {
			var input = e.target;
			obj.focusTicketBlock( input );
		}
	);

	/**
	 * handles input blur.
	 *
	 * @since TBD
	 *
	 */
	obj.document.on(
		'blur',
		'.tribe-ticket .ticket-meta',
		function( e ) {
			var input = e.target;
			obj.unfocusTicketBlock( input );
		}
	);

	/**
	 * Handle the Ticket form(s).
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.document.on(
		'change keyup',
		obj.selector.itemQuantityInput,
		function( e ) {
			var $this        = $( this );
			var $ticket      = $this.closest( obj.selector.item );
			var $ticket_id   = $ticket.data( 'ticket-id' );
			var $form        = $this.closest( 'form' );
			var max          = $this.attr( 'max' );
			var new_quantity = parseInt( $this.val(), 10 );
			new_quantity     = isNaN( new_quantity ) ? 0 : new_quantity;

			if ( max < new_quantity ) {
				new_quantity = max;
				$this.val( max );
			}

			if ( 'true' === $ticket.attr( 'data-shared-cap' ) ) {
				var maxQty = obj.checkSharedCapacity( $form, new_quantity );
			}

			if ( 0 > maxQty ) {
				new_quantity += maxQty;
				$this.val( new_quantity );
			}

			e.preventDefault();
			obj.maybeShowOptOut( $ticket, new_quantity );
			obj.updateFooter( $form );
			obj.updateFormTotals( $form );
		}
	);

	/**
	 * Stores to sessionStorage onbeforeunload for accidental refreshes, etc.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.document.on(
		'beforeunload',
		function( e ) {
			if ( tribe.tickets.modal_redirect ) {
				return;
			}

			obj.storeLocal();
		}
	);

	/**
	 * Handle Modal submission.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.document.on(
		'click',
		obj.modalSelector.submit,
		function( e ) {
			e.preventDefault();
			var $button      = $( this );
			var $metaForm    = $( obj.modalSelector.metaForm );
			var isValidForm  = obj.validateForm( $metaForm );
			var $errorNotice = $( obj.selector.validationNotice );

			obj.loaderShow( obj.modalSelector.loader );

			if ( ! isValidForm[ 0 ] ) {
				$errorNotice.find( '.tribe-tickets-notice__title' ).text( TribeMessages.validation_error_title );
				$errorNotice.find( 'p' ).html( TribeMessages.validation_error );
				$( obj.selector.validationNotice + '__count' ).text( isValidForm[ 1 ] );
				$errorNotice.show();
				obj.loaderHide( obj.modalSelector.loader );
				document.getElementById( 'tribe-tickets__notice__attendee-modal' ).scrollIntoView({ behavior: 'smooth', block: 'start' });
				return false;
			}

			$errorNotice.hide();

			obj.loaderShow( obj.modalSelector.loader );

			// save meta and cart
			var params = {
				provider: obj.commerceSelector[ obj.tribe_ticket_provider ],
				tickets : obj.getTicketsForCart(),
				meta    : obj.getMetaForSave(),
				post_id : obj.postId,
			};

			$.ajax( {
				type: 'POST',
				url: obj.getRestEndpoint(),
				data: params,
				success: function( response ) {
					$errorNotice.hide();
					//redirect url
					var url = response.checkout_url;

					if ( 'cart-button' === $button.attr( 'name' ) ) {
						url = response.cart_url;
					} else if ( 0 === response.is_stored_meta_up_to_date ) {
						url = response.attendee_registration_url;
					}

					// Clear sessionStorage before redirecting the user.
					obj.clearLocal();
					// Set a var so we don't save what we just erased.
					tribe.tickets.modal_redirect = true;

					obj.loaderHide( obj.modalSelector.loader );

					window.location.href = url;
				},
				error: function( response ) {
					$errorNotice.find( '.tribe-tickets-notice__title' ).text( TribeMessages.api_error_title + ` (${response.responseJSON.code} )` );
					$errorNotice.find( 'p' ).html( TribeMessages.connection_error );
					$errorNotice.fadeIn();
					$( obj.modalSelector.container ).animate( { scrollTop : 0 }, 'slow' );
					obj.loaderHide( obj.modalSelector.loader );
				}
			} );
		}
	);

	/**
	 * Handle Non-modal submission.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.document.on(
		'click',
		obj.selector.submit,
		function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var $button    = $( this );

			// save meta and cart
			var params = {
				provider: obj.commerceSelector[ obj.tribe_ticket_provider ],
				tickets : obj.getTicketsForCart(),
				meta    : {},
				post_id : obj.postId,
			};

			$.ajax( {
				type: 'POST',
				url: obj.getRestEndpoint(),
				data: params,
				success: function( response ) {
					//redirect url
					var url = response.checkout_url;

					if ( 'cart-button' === $button.attr( 'name' ) ) {
						// EDD IS A SPECIAL KITTY
						if ( 'edd' !== obj.commerceSelector[ $tribe_ticket.data( 'provider' ) ]) {
							url = response.cart_url;
						}

					} else if ( 0 === response.is_stored_meta_up_to_date ) {
						url = response.attendee_registration_url;
					}

					// Clear sessionStorage before redirecting the user.
					obj.clearLocal();

					// Set a var so we don't save what we just erased.
					tribe.tickets.modal_redirect = true;

					window.location.href = url;
				},
				error: function( response ) {
					var $errorNotice = $( obj.selector.validationNotice );
					$errorNotice.find( '.tribe-tickets-notice__title' ).text( TribeMessages.api_error_title + ` (${response.responseJSON.code} )` );
					$errorNotice.find( 'p' ).text( TribeMessages.connection_error );
					$errorNotice.fadeIn();
					return;
				}
			} );
		}
	);

	/**
	 * When "Get Tickets" is clicked, update the modal.
	 *
	 * @since TBD
	 *
	 */
	$( tde ).on(
		'tribe_dialog_show_ar_modal',
		function ( e, dialogEl, event ) {
			obj.loaderShow();
			obj.loaderShow( obj.modalSelector.loader );
			var $modalCart = $( obj.modalSelector.cartForm );
			var $cartItems = $tribe_ticket.find( obj.selector.item );

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

			obj.initModalFormPrefills();

			obj.updateFormTotals( $modalCart );
			obj.loaderHide();
			obj.loaderHide( obj.modalSelector.loader );
		}
	);

	/**
	 * Handles storing data to local storage
	 */
	$( tde ).on(
		'tribe_dialog_close_ar_modal',
		function ( e, dialogEl, event ) {
			obj.storeLocal();
		}
	);

	obj.init();

	window.addEventListener( 'pageshow', function ( event ) {
		if (
			event.persisted
			|| (
				typeof window.performance != 'undefined'
				&& window.performance.navigation.type === 2
			)
		) {
			obj.init();
		}
	} );

} )( jQuery, tribe.tickets.block, tribe.dialogs.events );
