tribe.tickets = tribe.tickets || {};
tribe.dialogs = tribe.dialogs || {};
tribe.dialogs.events = tribe.dialogs.events || {};

tribe.tickets.block = {
	num_attendees: 0,
	event: {},
};

( function( $, obj, tde ) {
	'use strict';
	const $document = $( document );

	console.log( tde );

	/*
	 * Ticket Block Selectors.
	 *
	 * @since TBD
	 */
	obj.selectors = {
		containerWrapper: '.tribe-tickets__tickets-wrapper', // This will be container.
		blockFooter: '.tribe-tickets__footer',
		blockFooterAmount: '.tribe-amount',
		blockFooterQuantity: '.tribe-tickets__footer__quantity__number',
		blockSubmit: '#tribe-tickets__submit',
		classicSubmit: '#tribe-tickets__buy',
		container: '.tribe-tickets',
		hidden: 'tribe-common-a11y-hidden',
		item: '.tribe-tickets__item',
		itemExtraAvailable: '.tribe-tickets__item__extra__available',
		itemExtraAvailableQuantity: '.tribe-tickets__item__extra__available__quantity',
		itemOptOut: '.tribe-tickets-attendees-list-optout--wrapper',
		itemOptOutInput: '#tribe-tickets-attendees-list-optout-',
		itemPrice: '.tribe-tickets__sale_price .tribe-amount',
		itemQuantity: '.tribe-tickets__item__quantity',
		itemQuantityInput: '.tribe-tickets-quantity',
		submit: '.tribe-tickets__buy',
		ticketLoader: '.tribe-tickets-loader__tickets-block',
		validationNotice: '.tribe-tickets__notice--error',
		ticketInCartNotice: '#tribe-tickets__notice__tickets-in-cart',
		horizontal_datepicker: {
			container: '.tribe_horizontal_datepicker__container',
			select: '.tribe_horizontal_datepicker__container select',
			day: '.tribe_horizontal_datepicker__day',
			month: '.tribe_horizontal_datepicker__month',
			year: '.tribe_horizontal_datepicker__year',
			value: '.tribe_horizontal_datepicker__value',
		},
	};

	const $tribeTicket = $( obj.selectors.container );

	// Bail if there are no tickets on the current event/page/post.
	if ( 0 === $tribeTicket.length ) {
		return; // @todo: remove this once it's container based.
	}

	obj.document = $( document );

	/*
	 * AR Cart Modal Selectors.
	 *
	 * Note: some of these have the modal class as well, as the js can
	 * pick up the class from elsewhere in the DOM and grab the wrong data.
	 *
	 * @since TBD

	tribe.tickets.modal.selectors = {
		cartForm: '.tribe-modal__wrapper--ar #tribe-modal__cart',
		container: '.tribe-modal__wrapper--ar',
		form: '#tribe-tickets__modal-form',
		itemRemove: '.tribe-tickets__item__remove',
		itemTotal: '.tribe-tickets__item__total .tribe-amount',
		loader: '.tribe-tickets-loader__modal',
		metaField: '.tribe-tickets__form-field-input', //'.ticket-meta',
		metaForm: '.tribe-modal__wrapper--ar #tribe-modal__attendee_registration',
		metaItem: '.tribe-ticket',
		submit: '.tribe-block__tickets__item__attendee__fields__footer_submit',
	};*/

	/*
	 * Commerce Provider "lookup table".
	 *
	 * @since TBD
	 */
	obj.commerceSelector = {
		edd: 'Tribe__Tickets_Plus__Commerce__EDD__Main',
		rsvp: 'Tribe__Tickets__RSVP',
		tpp: 'Tribe__Tickets__Commerce__PayPal__Main',
		Tribe__Tickets__Commerce__PayPal__Main: 'tribe-commerce',
		Tribe__Tickets__RSVP: 'rsvp',
		Tribe__Tickets_Plus__Commerce__EDD__Main: 'edd',
		Tribe__Tickets_Plus__Commerce__WooCommerce__Main: 'woo',
		tribe_eddticket: 'Tribe__Tickets_Plus__Commerce__EDD__Main',
		tribe_tpp_attendees: 'Tribe__Tickets__Commerce__PayPal__Main',
		tribe_wooticket: 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
		woo: 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
	};

	obj.tribe_ticket_provider = $tribeTicket.data( 'provider' );
	obj.postId = TribeTicketOptions.post_id;

	/**
	 * Init the tickets script.
	 *
	 * @since TBD
	 */
	obj.init = function() {
		if ( 0 < TribeTicketOptions.availability_check_interval ) {
			obj.checkAvailability();
		}

		if ( TribeTicketOptions.ajax_preload_ticket_form ) {
			tribe.tickets.loader.show( $document );
			obj.initPrefill();
		}

		obj.disable( $( obj.selectors.submit ), true );

		// @todo: bind stuff.
		// obj.bind

		const $ticketsBlock = $document.find( obj.selectors.containerWrapper );

		$ticketsBlock.each( function( index, block ) {
			obj.bindEvents( $( block ) );
		} );
	};

	obj.bindEvents = function( $container ) {

		$container.trigger( 'beforeSetup.tribeTicketsBlock', [ $container ] );

		$container.trigger( 'afterSetup.tribeTicketsBlock', [ $container ] );
	};

	/* DOM Updates */

	/**
	 * Make dom updates for the AJAX response.
	 *
	 * @param {array} tickets - Array of tickets to iterate over.
	 *
	 * @since TBD
	 */
	obj.updateAvailability = function( tickets ) {
		Object.keys( tickets ).forEach( function( ticketId ) {
			const available = tickets[ ticketId ].available;
			const maxPurchase = tickets[ ticketId ].max_purchase;
			const $ticketEl = $( obj.selectors.item + '[data-ticket-id="' + ticketId + '"]' );

			if ( 0 === available ) { // Ticket is out of stock.
				const unavailableHtml = tickets[ ticketId ].unavailable_html;
				// Set the availability data attribute to false.
				$ticketEl.attr( 'available', false );

				// Remove classes for in-stock and purchasable.
				$ticketEl.removeClass( 'instock' );
				$ticketEl.removeClass( 'purchasable' );

				// Update HTML elements with the "Out of Stock" messages.
				$ticketEl.find( obj.selectors.itemQuantity ).html( unavailableHtml );
				$ticketEl.find( obj.selectors.itemExtraAvailable ).html( '' );
			}

			if ( 1 < available ) { // Ticket in stock, we may want to update values.
				$ticketEl.find( obj.selectors.itemQuantityInput ).attr( { max: maxPurchase } );
				$ticketEl.find( obj.selectors.itemExtraAvailableQuantity ).html( available );
			}
		} );
	};

	/**
	 * Update all the footer info.
	 *
	 * @since TBD
	 *
	 * @param {object} $form The form we're updating.
	 */
	obj.updateFooter = function( $form ) {
		obj.updateFooterCount( $form );
		obj.updateFooterAmount( $form );
		$form.find( '.tribe-tickets__footer' ).addClass( 'tribe-tickets__footer--active' );
	};

	/**
	 * Adjust the footer count for +/-.
	 *
	 * @since TBD
	 *
	 * @param {object} $form The form we're updating.
	 */
	obj.updateFooterCount = function( $form ) {
		const $field = $form.find( obj.selectors.blockFooter + ' ' + obj.selectors.blockFooterQuantity );
		let footerCount = 0;
		const $qtys = $form.find( obj.selectors.item + ' ' + obj.selectors.itemQuantityInput );

		$qtys.each( function() {
			let newQuantity = parseInt( $( this ).val(), 10 );
			newQuantity = isNaN( newQuantity ) ? 0 : newQuantity;
			footerCount += newQuantity;
		} );

		const disabled = 0 >= footerCount ? true : false;
		obj.disable( $( obj.selectors.submit ), disabled );

		if ( 0 > footerCount ) {
			return;
		}

		$field.text( footerCount );
	};

	/**
	 * Adjust the footer total/amount for +/-.
	 *
	 * @since TBD
	 *
	 * @param {object} $form The form we're updating.
	 */
	obj.updateFooterAmount = function( $form ) {
		const $field = $form.find( obj.selectors.blockFooter + ' ' + obj.selectors.blockFooterAmount );
		let footerAmount = 0;
		const $qtys = $form.find( obj.selectors.item + ' ' + obj.selectors.itemQuantityInput );

		$qtys.each( function() {
			const $price = $( this ).closest( obj.selectors.item ).find( obj.selectors.itemPrice ).first();
			let quantity = parseInt( $( this ).val(), 10 );
			quantity = isNaN( quantity ) ? 0 : quantity;
			let text = $price.text();
			text = obj.cleanNumber( text );
			const cost = text * quantity;
			footerAmount += cost;
		} );

		if ( 0 > footerAmount ) {
			return;
		}

		$field.text( obj.numberFormat( footerAmount ) );
	};

	/**
	 * Update Cart Totals in Modal.
	 *
	 * @since TBD
	 *
	 * @param {object} $cart The jQuery form object to update totals.
	 */
	obj.updateFormTotals = function( $cart ) {
		obj.updateFooter( $cart );
		tribe.tickets.modal.appendARFields( $cart );
	};

	/**
	 * Possibly Update an Items Qty and always update the Total.
	 *
	 * @since TBD
	 *
	 * @param {int}    id             The id of the ticket/product.
	 * @param {object} $modalCartItem The cart item to update.
	 * @param {object} $blockCartItem The optional ticket block cart item.
	 *
	 * @returns {object} - Returns the updated item for chaining.
	 */
	obj.updateItem = function( id, $modalCartItem, $blockCartItem ) {
		const item = {};
		item.id = id;

		if ( ! $blockCartItem ) {
			item.qty = obj.getQty( $modalCartItem );
			item.price = obj.getPrice( $modalCartItem );
		} else {
			item.qty = obj.getQty( $blockCartItem );
			item.price = obj.getPrice( $modalCartItem );

			$modalCartItem.find( obj.selectors.itemQuantityInput ).val( item.qty ).trigger( 'change' );

			// We force new DOM queries here to be sure we pick up dynamically generated items.
			const optoutSelector = obj.selectors.itemOptOutInput + $blockCartItem.data( 'ticket-id' );
			item.$optOut = $( optoutSelector );
			const $optoutInput = $( optoutSelector + '-modal' );

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
	 * @param {number} qty       The quantity.
	 * @param {number} price     The price.
	 * @param {object} $cartItem The cart item to update.
	 *
	 * @returns {string} - Formatted currency string.
	 */
	obj.updateTotal = function( qty, price, $cartItem ) {
		const totalForItem = ( qty * price ).toFixed( obj.getCurrencyFormatting().number_of_decimals );
		const $field = $cartItem.find( tribe.tickets.modal.selectors.itemTotal );

		$field.text( obj.numberFormat( totalForItem ) );

		return totalForItem;
	};

	/**
	 * Shows/hides the non-ar notice based on the number of tickets passed.
	 *
	 * @since TBD
	 *
	 * @param {object} $form The form we're updating.
	 */
	// @TODO: Goes to modal or AR related.
	obj.maybeShowNonMetaNotice = function( $form ) {
		let nonMetaCount = 0;
		let metaCount = 0;
		const $cartItems = $form.find( obj.selectors.item ).filter(
			function() {
				return $( this ).find( obj.selectors.itemQuantityInput ).val() > 0;
			}
		);

		if ( ! $cartItems.length ) {
			return;
		}

		$cartItems.each(
			function() {
				const $cartItem = $( this );
				const ticketID = $cartItem.closest( obj.selectors.item ).data( 'ticket-id' );
				const $ticketContainer = $( tribe.tickets.modal.selectors.metaForm ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticketID + '"]' );

				// Ticket does not have meta - no need to jump through hoops ( and throw errors ).
				if ( ! $ticketContainer.length ) {
					nonMetaCount += obj.getQty( $cartItem );
				} else {
					metaCount += obj.getQty( $cartItem );
				}
			}
		);

		const $notice = $( '.tribe-tickets__notice--non-ar' );
		const $title = $( '.tribe-tickets__item__attendee__fields__title' );

		// If there are no non-meta tickets, we don't need the notice.
		// Likewise, if there are no tickets with meta the notice seems redundant.
		if ( 0 < nonMetaCount && 0 < metaCount ) {
			$( '#tribe-tickets__non-ar-count' ).text( nonMetaCount );
			$notice.removeClass( obj.selectors.hidden.className() );
			$title.show();
		} else {
			$notice.addClass( obj.selectors.hidden.className() );
			$title.hide();
		}
	};

	/* Utility */

	/**
	 * Get the REST endpoint
	 *
	 * @since TBD
	 *
	 * @returns {string} - REST endpoint URL.
	 */
	obj.getRestEndpoint = function() {
		const url = TribeCartEndpoint.url;
		return url;
	};

	/**
	 * Get the tickets IDs.
	 *
	 * @since TBD
	 *
	 * @returns {array} Array of tickets.
	 */
	obj.getTickets = function() {
		const $tickets = $( obj.selectors.item ).map(
			function() {
				return $( this ).data( 'ticket-id' );
			}
		).get();

		return $tickets;
	};

	/**
	 * Maybe display the Opt Out.
	 *
	 * @since TBD
	 *
	 * @param {object} $ticket     The ticket item element.
	 * @param {number} newQuantity The new ticket quantity.
	 */
	obj.maybeShowOptOut = function( $ticket, newQuantity ) {
		const hasOptout = $ticket.has( obj.selectors.itemOptOut ).length;

		if ( hasOptout ) {
			const $item = $ticket.closest( obj.selectors.item );
			if ( 0 < newQuantity ) {
				$item.addClass( 'show-optout' );
			} else {
				$item.removeClass( 'show-optout' );
			}
		}
	};

	/**
	 * Step up the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 *
	 * @param {object} $input        The input field.
	 * @param {number} originalValue The field's original value.
	 */
	obj.stepUp = function( $input, originalValue ) {
		// We use 0 here as a shorthand for no maximum.
		const max = $input.attr( 'max' ) ? Number( $input.attr( 'max' ) ) : -1;
		const step = $input.attr( 'step' ) ? Number( $input.attr( 'step' ) ) : 1;
		let newValue = ( -1 === max || max >= originalValue + step ) ? originalValue + step : max;
		const $parent = $input.closest( obj.selectors.item );

		if ( 'true' === $parent.attr( 'data-has-shared-cap' ) ) {
			const $form = $parent.closest( 'form' );
			newValue = obj.checkSharedCapacity( $form, newValue );
		}

		if ( 0 === newValue ) {
			return;
		}

		if ( 0 > newValue ) {
			$input[ 0 ].value = originalValue + newValue;
			return;
		}

		if ( 'function' === typeof $input[ 0 ].stepUp ) {
			try {
				// Bail if we're already in the max, safari has issues with stepUp() here.
				if ( max < ( originalValue + step ) ) {
					return;
				}
				$input[ 0 ].stepUp();
			} catch ( ex ) {
				$input.val( newValue );
			}
		} else {
			$input.val( newValue );
		}
	};

	/**
	 * Step down the input according to the button that was clicked.
	 * Handles IE/Edge.
	 *
	 * @since TBD
	 *
	 * @param {object} $input        The input field.
	 * @param {number} originalValue The field's original value.
	 */
	obj.stepDown = function( $input, originalValue ) {
		const min = $input.attr( 'min' ) ? Number( $input.attr( 'min' ) ) : 0;
		const step = $input.attr( 'step' ) ? Number( $input.attr( 'step' ) ) : 1;
		const decrease = ( min <= originalValue - step && 0 < originalValue - step ) ? originalValue - step : min;

		if ( 'function' === typeof $input[ 0 ].stepDown ) {
			try {
				$input[ 0 ].stepDown();
			} catch ( ex ) {
				$input[ 0 ].value = decrease;
			}
		} else {
			$input[ 0 ].value = decrease;
		}
	};

	/**
	 * Check tickets availability.
	 *
	 * @since TBD
	 */
	obj.checkAvailability = function() {
		// We're checking availability for all the tickets at once.
		const params = {
			action: 'ticket_availability_check',
			tickets: obj.getTickets(),
		};

		$.post(
			TribeTicketOptions.ajaxurl,
			params,
			function( response ) {
				const success = response.success;

				// Bail if we don't get a successful response.
				if ( ! success ) {
					return;
				}

				// Get the tickets response with availability.
				const tickets = response.data.tickets;

				// Make DOM updates.
				obj.updateAvailability( tickets );
			}
		);

		// Repeat every 60 ( filterable via tribe_tickets_availability_check_interval ) seconds
		if ( 0 < TribeTicketOptions.availability_check_interval ) {
			setTimeout( obj.checkAvailability, TribeTicketOptions.availability_check_interval );
		}
	};

	/**
	 * Check if we're updating the qty of a shared cap ticket and
	 * limits it to the shared cap minus any tickets in cart.
	 *
	 * @since TBD
	 *
	 * @param {object} $form jQuery object that is the form we are checking.
	 * @param {number} qty   The quantity we desire.
	 *
	 * @returns {integer} The quantity, limited by existing shared cap tickets.
	 */
	obj.checkSharedCapacity = function( $form, qty ) {
		let sharedCap = [];
		let currentLoad = [];
		const $sharedTickets = $form.find( obj.selectors.item ).filter( '[data-has-shared-cap="true"]' );
		const $sharedCapTickets = $sharedTickets.find( obj.selectors.itemQuantityInput );

		if ( ! $sharedTickets.length ) {
			return qty;
		}

		$sharedTickets.each(
			function() {
				sharedCap.push( parseInt( $( this ).attr( 'data-shared-cap' ), 10 ) );
			}
		);

		$sharedCapTickets.each(
			function() {
				currentLoad.push( parseInt( $( this ).val(), 10 ) );
			}
		);

		// IE doesn't allow spread operator
		sharedCap = Math.max.apply( this, sharedCap );

		currentLoad = currentLoad.reduce(
			function( a, b ) {
				return a + b;
			},
			0
		);

		const currentAvailable = sharedCap - currentLoad;

		return Math.min( currentAvailable, qty );
	};

	/**
	 * Get the Quantity.
	 *
	 * @since TBD
	 *
	 * @param {object} $cartItem The cart item to update.
	 *
	 * @returns {number} The item quantity.
	 */
	obj.getQty = function( $cartItem ) {
		const qty = parseInt( $cartItem.find( obj.selectors.itemQuantityInput ).val(), 10 );

		return isNaN( qty ) ? 0 : qty;
	};

	/**
	 * Get the Price.
	 *
	 * @since TBD
	 *
	 * @param {object} $cartItem The cart item to update.
	 *
	 * @returns {number} The item price.
	 */
	obj.getPrice = function( $cartItem ) {
		const price = obj.cleanNumber( $cartItem.find( obj.selectors.itemPrice ).first().text() );

		return isNaN( price ) ? 0 : price;
	};

	/**
	 * Get the Currency Formatting for a Provider.
	 *
	 * @since TBD
	 *
	 * @returns {object} The appropriate currency format.
	 */
	obj.getCurrencyFormatting = function() {
		const currency = JSON.parse( TribeCurrency.formatting );

		return currency[ obj.tribe_ticket_provider ];
	};

	/**
	 * Removes separator characters and converts decimal character to '.'
	 * So they play nice with other functions.
	 *
	 * @since TBD
	 *
	 * @param {number} passedNumber - The number to clean.
	 *
	 * @returns {string} - The cleaned number.
	 */
	obj.cleanNumber = function( passedNumber ) {
		let number = passedNumber;
		const format = obj.getCurrencyFormatting();

		if ( 0 === parseInt( format.number_of_decimals ) ) {
			return number;
		}

		// we run into issue when the two symbols are the same -
		// which appears to happen by default with some providers.
		const same = format.thousands_sep === format.decimal_point;

		if ( ! same ) {
			if ( '' !== format.thousands_sep ) {
				number = number.split( format.thousands_sep ).join( '' );
			}
			if ( '' !== format.decimal_point ) {
				number = number.split( format.decimal_point ).join( '.' );
			}
		} else {
			const decPlace = number.length - ( format.number_of_decimals + 1 );
			number = number.substr( 0, decPlace ) + '_' + number.substr( decPlace + 1 );
			if ( '' !== format.thousands_sep ) {
				number = number.split( format.thousands_sep ).join( '' );
			}
			number = number.split( '_' ).join( '.' );
		}

		return number;
	};

	/**
	 * Format the number according to provider settings.
	 * Based off coding from https://stackoverflow.com/a/2901136.
	 *
	 * @since TBD
	 *
	 * @param {number} number - The number to format.
	 *
	 * @returns {string} - The formatted number.
	 */
	obj.numberFormat = function( number ) {
		const format = obj.getCurrencyFormatting();

		if ( ! format ) {
			return false;
		}

		const decimals = format.number_of_decimals;
		const decPoint = format.decimal_point;
		const thousandsSep = format.thousands_sep;
		const n = ! isFinite( +number ) ? 0 : +number;
		const prec = ! isFinite( +decimals ) ? 0 : Math.abs( decimals );
		const sep = ( 'undefined' === typeof thousandsSep ) ? ',' : thousandsSep;
		const dec = ( 'undefined' === typeof decPoint ) ? '.' : decPoint;

		const toFixedFix = function( num, precision ) {
			// Fix for IE parseFloat(0.55).toFixed(0) = 0;
			const k = Math.pow( 10, precision );

			return Math.round( num * k ) / k;
		};

		let s = ( prec ? toFixedFix( n, prec ) : Math.round( n ) ).toString().split( dec );

		// if period is the thousands_sep we have to spilt using the decimal and not the comma as we work
		// with numbers using the period as the decimal in JavaScript
		if ( '.' === format.thousands_sep ) {
			s = ( prec ? toFixedFix( n, prec ) : Math.round( n ) ).toString().split( '.' );
		}

		if ( s[ 0 ].length > 3 ) {
			s[ 0 ] = s[ 0 ].replace( /\B(?=(?:\d{3})+(?!\d))/g, sep );
		}

		if ( ( s[ 1 ] || '' ).length < prec ) {
			s[ 1 ] = s[ 1 ] || '';
			s[ 1 ] += new Array( prec - s[ 1 ].length + 1 ).join( '0' );
		}

		return s.join( dec );
	};

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 * @param {string} input - The selector string for the triggering object.
	 */
	obj.focusTicketBlock = function( input ) {
		$( input )
			.closest( tribe.tickets.modal.selectors.metaItem )
			.addClass( 'tribe-ticket-item__has-focus' );
	};

	/**
	 * Remove focus effect from ticket block.
	 *
	 * @since TBD
	 *
	 * @param {string} input - The selector string for the triggering object.
	 */
	obj.unfocusTicketBlock = function( input ) {
		$( input )
			.closest( tribe.tickets.modal.selectors.metaItem )
			.removeClass( 'tribe-ticket-item__has-focus' );
	};

	obj.disable = function( $element, isDisabled ) {
		if ( isDisabled ) {
			$element.prop( 'disabled', true )
				.attr( {
					'disabled': 'true',
					'aria-disabled': 'true',
				} );
		} else {
			$element.prop( 'disabled', false )
				.removeProp( 'disabled' )
				.removeAttr( 'disabled aria-disabled' );
		}
	};

	/* Pre-fill Handling */

	/**
	 * Init the tickets block pre-fill.
	 *
	 * @since TBD
	 */
	obj.initPrefill = function() {
		obj.prefillTicketsBlock();
	};

	/**
	 * Pre-fill tickets block from cart.
	 *
	 * @since TBD
	 */
	// @TODO: Goes to modal or AR related.
	obj.prefillTicketsBlock = function() {
		$.when(
			tribe.tickets.data.getData( true )
		).then(
			function( data ) {
				const tickets = data.tickets;

				if ( tickets.length ) {
					let $eventCount = 0;

					tickets.forEach( function( ticket ) {
						const $ticketRow = $( '.tribe-tickets__item[data-ticket-id="' + ticket.ticket_id + '"]' );
						if ( 'true' === $ticketRow.attr( 'data-available' ) ) {
							const $field = $ticketRow.find( obj.selectors.itemQuantityInput );
							const $optout = $ticketRow.find( obj.selectors.itemOptOutInput + ticket.ticket_id );

							if ( $field.length ) {
								$field.val( ticket.quantity );
								$field.trigger( 'change' );
								$eventCount += ticket.quantity;
								if ( 1 === parseInt( ticket.optout, 10 ) ) {
									$optout.prop( 'checked', 'true' );
								}
							}
						}
					} );

					if ( 0 < $eventCount ) {
						$( obj.selectors.ticketInCartNotice ).fadeIn();
					}
				}

				tribe.tickets.loader.hide( $document );
			},
			function() {
				const $errorNotice =  $( obj.selectors.ticketInCartNotice );
				$errorNotice
					.removeClass( 'tribe-tickets__notice--barred tribe-tickets__notice--barred-left' )
					.addClass( 'tribe-tickets__notice--error' );
				$errorNotice.find( '.tribe-tickets-notice__title' ).text( TribeMessages.api_error_title );
				$errorNotice.find( '.tribe-tickets-notice__content' ).text( TribeMessages.connection_error );
				$errorNotice.fadeIn();

				tribe.tickets.loader.hide( $document );
			}
		);
	};

	/**
	 * Get ticket data to send to cart.
	 *
	 * @since TBD
	 *
	 * @returns {object} Tickets data object.
	 */
	obj.getTicketsForCart = function() {
		const tickets = [];
		let $cartForm = $( tribe.tickets.modal.selectors.cartForm );

		// Handle non-modal instances.
		if ( ! $cartForm.length ) {
			$cartForm = $( obj.selectors.container );
		}

		const $ticketRows = $cartForm.find( obj.selectors.item );

		$ticketRows.each(
			function() {
				const $row = $( this );
				const ticketId = $row.data( 'ticketId' );
				const qty = $row.find( obj.selectors.itemQuantityInput ).val();
				const $optoutInput = $row.find( '[name="attendee[optout]"]' );
				let optout = $optoutInput.val();

				if ( $optoutInput.is( ':checkbox' ) ) {
					optout = $optoutInput.prop( 'checked' ) ? 1 : 0;
				}

				const data = {};
				data.ticket_id = ticketId;
				data.quantity = qty;
				data.optout = optout;

				tickets.push( data );
			}
		);

		return tickets;
	};

	/* Validation */

	/**
	 * Validates the entire meta form.
	 * Adds errors to the top of the modal.
	 *
	 * @since TBD
	 *
	 * @param {object} $form - jQuery object that is the form we are validating.
	 *
	 * @returns {boolean} - If the form validates.
	 */
	obj.validateForm = function( $form ) {
		const $containers = $form.find( tribe.tickets.modal.selectors.metaItem );
		let formValid = true;
		let invalidTickets = 0;

		$containers.each(
			function() {
				const $container = $( this );
				const validContainer = obj.validateBlock( $container );

				if ( ! validContainer ) {
					invalidTickets++;
					formValid = false;
				}
			}
		);

		return [ formValid, invalidTickets ];
	};

	/**
	 * Validates and adds/removes error classes from a ticket meta block.
	 *
	 * @since TBD
	 *
	 * @param {object} $container - jQuery object that is the block we are validating.
	 *
	 * @returns {boolean} - True if all fields validate, false otherwise.
	 */
	obj.validateBlock = function( $container ) {
		const $fields = $container.find( tribe.tickets.modal.selectors.metaField );
		let validBlock = true;

		$fields.each(
			function() {
				const $field = $( this );
				const isValidField = obj.validateField( $field[ 0 ] );

				if ( ! isValidField ) {
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
	};

	/**
	 * Validate Checkbox/Radio group.
	 * We operate under the assumption that you must check _at least_ one,
	 * but not necessarily all. Also that the checkboxes are all required.
	 *
	 * @since TBD
	 *
	 * @param {object} $group - The jQuery object for the checkbox group.
	 *
	 * @returns {boolean} - Is checkbox valid?
	 */
	obj.validateCheckboxRadioGroup = function( $group ) {
		const $checkboxes = $group.find( tribe.tickets.modal.selectors.metaField );
		let checkboxValid = false;
		let required = true;

		$checkboxes.each(
			function() {
				const $this = $( this );

				if ( $this.is( ':checked' ) ) {
					checkboxValid = true;
				}

				if ( ! $this.prop( 'required' ) ) {
					required = false;
				}
			}
		);

		const valid = ! required || checkboxValid;

		return valid;
	};

	/**
	 * Checks if a horizontal date picker is valid.
	 * Eg: If a month is selected, a year and day must also be selected.
	 *
	 * @since5.0.0
	 *
	 * @param {object} $input the jquery object of the input.
	 * @returns {boolean} True if the horizontal datepicker is valid.
	 */
	obj.validateHorizontalDatePickerValue = function( $input ) {
		// We don't check if the field is value if no day, month or year has been chosen.
		if ( $input.val() === '' || $input.val() === 'null-null-null' ) {
			return true;
		}

		const wrapper = $input.closest( obj.selectors.horizontal_datepicker.container );
		const day = wrapper.find( obj.selectors.horizontal_datepicker.day );
		const month = wrapper.find( obj.selectors.horizontal_datepicker.month );
		const year = wrapper.find( obj.selectors.horizontal_datepicker.year );

		let isValidDatePicker = true;

		[ day, month, year ].forEach( function( el ) {
			if ( isNaN( parseInt( el.val() ) ) || parseInt( el.val() ) <= 0 ) {
				el.addClass( 'ticket-meta__has-error' );

				isValidDatePicker = false;
			} else {
				el.removeClass( 'ticket-meta__has-error' );
			}
		}, isValidDatePicker );

		return isValidDatePicker;
	};

	/**
	 * Adds/removes error classes from a single field.
	 *
	 * @since TBD
	 *
	 * @param {object} input - DOM Object that is the field we are validating.
	 *
	 * @returns {boolean} - Is field valid?
	 */
	obj.validateField = function( input ) {
		let $input = $( input );
		let isValidField = input.checkValidity();

		if ( ! isValidField ) {
			$input = $( input );
			// Got to be careful of required checkbox/radio groups...
			if ( $input.is( ':checkbox' ) || $input.is( ':radio' ) ) {
				const $group = $input.closest( '.tribe-common-form-control-checkbox-radio-group' );

				if ( $group.length ) {
					isValidField = obj.validateCheckboxRadioGroup( $group );
				}
			} else {
				isValidField = false;
			}
		}

		if ( $input.is( obj.selectors.horizontal_datepicker.value ) ) {
			isValidField = obj.validateHorizontalDatePickerValue( $input );
		}

		if ( ! isValidField ) {
			$input.addClass( 'ticket-meta__has-error' );
		} else {
			$input.removeClass( 'ticket-meta__has-error' );
		}

		return isValidField;
	};

	/* Event Handling */

	/**
	 * Handle the number input + and - actions.
	 *
	 * @since TBD
	 */
	obj.document.on(
		'click',
		'.tribe-tickets__item__quantity__remove, .tribe-tickets__item__quantity__add',
		function( e ) {
			e.preventDefault();
			const $input = $( this ).parent().find( 'input[type="number"]' );
			if ( $input.is( ':disabled' ) ) {
				return false;
			}

			const originalValue = Number( $input[ 0 ].value );
			const $modalForm = $input.closest( tribe.tickets.modal.selectors.cartForm );

			// Step up or Step down the input according to the button that was clicked.
			// Handles IE/Edge.
			if ( $( this ).hasClass( 'tribe-tickets__item__quantity__add' ) ) {
				obj.stepUp( $input, originalValue );
			} else {
				obj.stepDown( $input, originalValue );
			}

			obj.updateFooter( $input.closest( 'form' ) );

			// Trigger the on Change for the input ( if it has changed ) as it's not handled via stepUp() || stepDown().
			if ( originalValue !== $input[ 0 ].value ) {
				$input.trigger( 'change' );
			}

			if ( $modalForm.length ) {
				const $item = $input.closest( obj.selectors.item );
				obj.updateTotal( obj.getQty( $item ), obj.getPrice( $item ), $item );
			}
		}
	);

	/**
	 * Remove Item from Cart Modal.
	 *
	 * @since TBD
	 */
	// @TODO: Move to modal
	obj.document.on(
		'click',
		tribe.tickets.modal.selectors.itemRemove,
		function( e ) {
			e.preventDefault();

			const ticket = {};
			const $cart = $( this ).closest( 'form' );
			const $cartItem = $( this ).closest( obj.selectors.item );

			$cartItem.find( obj.selectors.itemQuantity ).val( 0 );
			$cartItem.fadeOut();

			ticket.id = $cartItem.data( 'ticketId' );
			ticket.qty = 0;
			$cartItem.find( obj.selectors.itemQuantityInput ).val( ticket.qty );
			ticket.price = obj.getPrice( $cartItem );

			obj.updateTotal( ticket.qty, ticket.price, $cartItem );
			obj.updateFormTotals( $cart );

			$( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticket.id + '"]' )
				.removeClass( 'tribe-tickets--has-tickets' )
				.find( tribe.tickets.modal.selectors.metaItem ).remove();

			// Short delay to ensure the fadeOut has finished.
			window.setTimeout( obj.maybeShowNonMetaNotice, 500, $cart );

			// Close the modal if we remove the last item
			// Again, short delay to ensure the fadeOut has finished.
			window.setTimeout(
				function() {
					const $items = $cart.find( obj.selectors.item ).filter( ':visible' );
					if ( 0 >= $items.length ) {
						// Get the object ID
						const id = $( obj.selectors.blockSubmit ).attr( 'data-content' );
						const result = 'dialog_obj_' + id.substring( id.lastIndexOf( '-' ) + 1 );

						// Close the dialog
						window[ result ].hide();
						obj.disable( $( obj.selectors.submit ), false );
					}
				},
				500
			);
		}
	);

	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 */
	obj.document.on(
		'focus',
		//'.tribe-ticket .ticket-meta'
		'.tribe-tickets__form-field-input',
		function( e ) {
			const input = e.target;
			obj.focusTicketBlock( input );
		}
	);

	/**
	 * Handles input blur.
	 *
	 * @since TBD
	 */
	obj.document.on(
		'blur',
		//'.tribe-ticket .ticket-meta',
		'.tribe-tickets__form-field-input',
		function( e ) {
			const input = e.target;
			obj.unfocusTicketBlock( input );
		}
	);

	/**
	 * Handle the Ticket form(s).
	 *
	 * @since TBD
	 */
	obj.document.on(
		'change keyup',
		obj.selectors.itemQuantityInput,
		function( e ) {
			const $this = $( e.target );
			const $ticket = $this.closest( obj.selectors.item );
			const $form = $this.closest( 'form' );
			const max = $this.attr( 'max' );
			let maxQty = 0;
			let newQuantity = parseInt( $this.val(), 10 );
			newQuantity = isNaN( newQuantity ) ? 0 : newQuantity;

			if ( max < newQuantity ) {
				newQuantity = max;
				$this.val( max );
			}

			if ( 'true' === $ticket.attr( 'data-has-shared-cap' ) ) {
				maxQty = obj.checkSharedCapacity( $form, newQuantity );
			}

			if ( 0 > maxQty ) {
				newQuantity += maxQty;
				$this.val( newQuantity );
			}

			e.preventDefault();
			obj.maybeShowOptOut( $ticket, newQuantity );
			obj.updateFooter( $form );
			obj.updateFormTotals( $form );
		}
	);

	/**
	 * Stores to sessionStorage onbeforeunload for accidental refreshes, etc.
	 *
	 * @since TBD
	 */
	obj.document.on(
		'beforeunload',
		function() {
			if ( window.tribe.tickets.modal_redirect ) {
				tribe.tickets.data.clearLocal();
				return;
			}

			tribe.tickets.data.storeLocal();
		}
	);

	obj.document.on(
		'keypress',
		tribe.tickets.modal.selectors.form,
		function( e ) {
			if ( e.keyCode === 13 ) {
				const $form = $( e.target ).closest( tribe.tickets.modal.selectors.form );
				// Ensure we're on the modal form
				if ( 'undefined' === $form ) {
					return;
				}

				e.preventDefault();
				e.stopPropagation();

				// Submit to cart. This will trigger validation as well.
				$form.find( '[name="cart-button"]' ).click();
			}
		}
	);

	/**
	 * Handle Modal submission.
	 *
	 * @since TBD
	 */
	obj.document.on(
		'click',
		tribe.tickets.modal.selectors.submit,
		function( e ) {
			e.preventDefault();
			const $button = $( this );
			const $form = $( tribe.tickets.modal.selectors.form );
			const $metaForm = $( tribe.tickets.modal.selectors.metaForm );
			const isValidForm = obj.validateForm( $metaForm );
			const $errorNotice = $( obj.selectors.validationNotice );
			const buttonText = $button.attr( 'name' );
			const provider = $form.data( 'provider' );

			tribe.tickets.loader.show( $form );

			if ( ! isValidForm[ 0 ] ) {
				$errorNotice.find( '.tribe-tickets-notice__title' ).text( TribeMessages.validation_error_title );
				$errorNotice.find( 'p' ).html( TribeMessages.validation_error );
				$( obj.selectors.validationNotice + '__count' ).text( isValidForm[ 1 ] );
				$errorNotice.show();
				tribe.tickets.loader.hide( $form );
				document.getElementById( 'tribe-tickets__notice__attendee-modal' ).scrollIntoView( { behavior: 'smooth', block: 'start' } );
				return false;
			}

			$errorNotice.hide();

			tribe.tickets.loader.show( $form );

			// default to checkout
			let action = TribeTicketsURLs.checkout[ provider ];

			if ( -1 !== buttonText.indexOf( 'cart' ) ) {
				action = TribeTicketsURLs.cart[ provider ];
			}
			$( tribe.tickets.modal.selectors.form ).attr( 'action', action );

			// Save meta and cart.
			const params = {
				tribe_tickets_provider: obj.commerceSelector[ obj.tribe_ticket_provider ],
				tribe_tickets_tickets: obj.getTicketsForCart(),
				tribe_tickets_meta: tribe.tickets.data.getMetaForSave(),
				tribe_tickets_post_id: obj.postId,
			};

			$( '#tribe_tickets_ar_data' ).val( JSON.stringify( params ) );
			// Set a flag to clear sessionStorage
			window.tribe.tickets.modal_redirect = true;
			tribe.tickets.data.clearLocal();

			// Submit the form.
			$form.submit();
		}
	);

	/**
	 * Handle Non-modal submission.
	 *
	 * @since TBD
	 */
	obj.document.on(
		'click',
		obj.selectors.classicSubmit,
		function( e ) {
			e.preventDefault();

			const $form = $( obj.selectors.container );

			// Show the loader.
			tribe.tickets.loader.show( $form );

			// save meta and cart
			const params = {
				tribe_tickets_provider: obj.commerceSelector[ obj.tribe_ticket_provider ],
				tribe_tickets_tickets: obj.getTicketsForCart(),
				tribe_tickets_meta: {},
				tribe_tickets_post_id: obj.postId,
			};

			$( '#tribe_tickets_block_ar_data' ).val( JSON.stringify( params ) );

			$form.submit();
		}
	);

	/**
	 * Handle Enter/Return on the quantity input from the main tickets form.
	 *
	 * @since 4.11.4
	 */
	obj.document.on(
		'keypress',
		obj.selectors.itemQuantityInput,
		function( e ) {
			if ( e.keyCode === 13 ) {
				e.preventDefault();
				e.stopPropagation();
				return;
			}
		}
	);

	obj.init();

	window.addEventListener( 'pageshow', function( event ) {
		if (
			event.persisted ||
			(
				typeof window.performance != 'undefined' &&
				window.performance.navigation.type === 2
			)
		) {
			obj.init();
		}
	} );
} )( jQuery, tribe.tickets.block, window.tribe.dialogs.events );
/* eslint-enable max-len */
