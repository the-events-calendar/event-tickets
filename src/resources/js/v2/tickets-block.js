/* global tribe */
tribe.tickets = tribe.tickets || {};

tribe.tickets.block = {
	num_attendees: 0,
	event: {},
};

( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/*
	 * Ticket Block Selectors.
	 *
	 * @since TBD
	 */
	// @todo: check what we need to remove and/or rename here.
	obj.selectors = {
		containerWrapper: '.tribe-tickets__tickets-wrapper', // @todo: This will be the main container.
		container: '.tribe-tickets',
		blockFooter: '.tribe-tickets__footer',
		blockFooterActive: 'tribe-tickets__footer--active',
		blockFooterAmount: '.tribe-amount',
		blockFooterQuantity: '.tribe-tickets__footer__quantity__number',
		blockSubmit: '#tribe-tickets__submit', // @todo: try to avoid using IDs
		item: '.tribe-tickets__item',
		itemExtraAvailable: '.tribe-tickets__item__extra__available',
		itemExtraAvailableQuantity: '.tribe-tickets__item__extra__available__quantity',
		itemOptOut: '.tribe-tickets-attendees-list-optout--wrapper',
		itemOptOutInput: '#tribe-tickets-attendees-list-optout-',
		itemPrice: '.tribe-tickets__sale_price .tribe-amount',
		itemQuantity: '.tribe-tickets__item__quantity',
		itemQuantityInput: '.tribe-tickets-quantity',
		itemQuantityAdd: '.tribe-tickets__item__quantity__add',
		itemQuantityRemove: '.tribe-tickets__item__quantity__remove',
		submit: '.tribe-tickets__buy',
		classicSubmit: '#tribe-tickets__buy', // @todo: try to avoid using IDs
	};

	/*
	 * Commerce Provider "lookup table".
	 *
	 * @since TBD
	 */
	// @todo: see if we can have basic ET stuff here and modify from ET+.
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

	/**
	 * Make dom updates for the AJAX response.
	 *
	 * @since TBD
	 *
	 * @param {array} tickets Array of tickets to iterate over.
	 *
	 * @return {void}
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
	 * @param {jQuery} $form The form we're updating.
	 *
	 * @return {void}
	 */
	obj.updateFooter = function( $form ) {
		const $footer = $form.find( obj.selectors.blockFooter );

		obj.updateFooterCount( $form );
		obj.updateFooterAmount( $form );

		$footer.addClass( obj.selectors.blockFooterActive.className() );
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
		const $quantities = $form.find( obj.selectors.item + ' ' + obj.selectors.itemQuantityInput );
		let footerCount = 0;

		$quantities.each( function() {
			let newQuantity = parseInt( $( this ).val(), 10 );
			newQuantity = isNaN( newQuantity ) ? 0 : newQuantity;
			footerCount += newQuantity;
		} );

		const disabled = 0 >= footerCount ? true : false;
		tribe.tickets.utils.disable( $form.find( obj.selectors.submit ), disabled );

		if ( 0 > footerCount ) {
			return;
		}

		$field.text( footerCount );
	};

	/**
	 * Get tickets block provider.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $form The form we want to retrieve the provider from.
	 *
	 * @return {string} The provider.
	 */
	obj.getTicketsBlockProvider = function( $form ) {
		return $form.data( 'provider' );
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
		const $quantities = $form.find( obj.selectors.item + ' ' + obj.selectors.itemQuantityInput );
		const provider = obj.getTicketsBlockProvider( $form );
		let footerAmount = 0;

		$quantities.each( function() {
			const $price = $( this ).closest( obj.selectors.item ).find( obj.selectors.itemPrice ).first();
			let quantity = parseInt( $( this ).val(), 10 );
			quantity = isNaN( quantity ) ? 0 : quantity;
			let text = $price.text();
			text = tribe.tickets.utils.cleanNumber( text, provider );
			const cost = text * quantity;
			footerAmount += cost;
		} );

		if ( 0 > footerAmount ) {
			return;
		}

		$field.text( tribe.tickets.utils.numberFormat( footerAmount, provider ) );
	};

	/**
	 * Update form totals.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $form The jQuery form object to update totals.
	 *
	 * @return {void}
	 */
	obj.updateFormTotals = function( $form ) {
		$document.trigger( 'beforeUpdateFormTotals.tribeTicketsBlock', [ $form ] );

		obj.updateFooter( $form );

		$document.trigger( 'afterUpdateFormTotals.tribeTicketsBlock', [ $form ] );
	};

	/**
	 * Get the tickets IDs.
	 *
	 * @since TBD
	 *
	 * @returns {array} Array of tickets IDs.
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
	 * @param {jQuery} $ticket     The ticket item element.
	 * @param {number} newQuantity The new ticket quantity.
	 *
	 * @return {void}
	 */
	obj.maybeShowOptOut = function( $ticket, newQuantity ) {
		const hasOptOut = $ticket.has( obj.selectors.itemOptOut ).length;

		if ( hasOptOut ) {
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
	 * @param {jQuery} $input        The input field.
	 * @param {number} originalValue The field's original value.
	 */
	// @todo: check if we need to handle IE exception as we're no longer supporting IE11.
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
	 * @param {jQuery} $input        The input field.
	 * @param {number} originalValue The field's original value.
	 */
	// @todo: check if we need to handle IE exception as we're no longer supporting IE11.
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

		// Repeat every 60 ( filterable via tribe_tickets_availability_check_interval ) seconds.
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
	 * @param {jQuery} $form jQuery object that is the form we are checking.
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

		// IE doesn't allow spread operator.
		// @todo: check that we're no longer supporting some IE versions.
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
	 * @param {jQuery} $cartItem The cart item to update.
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
	 * @param {jQuery} $cartItem The jQuery object of the cart item to update.
	 *
	 * @returns {number} The item price.
	 */
	obj.getPrice = function( $cartItem ) {
		const $form = $cartItem.closest( 'form' );
		const provider = obj.getTicketsBlockProvider( $form );
		const price = tribe.tickets.utils.cleanNumber( $cartItem.find( obj.selectors.itemPrice ).first().text(), provider );

		return isNaN( price ) ? 0 : price;
	};

	/**
	 * Get ticket data to send to cart.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $form jQuery object of the form container.
	 *
	 * @returns {array} Tickets array of objects.
	 */
	obj.getTicketsForCart = function( $form ) {
		const tickets = [];
		const $ticketRows = $form.find( obj.selectors.item );

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

	/**
	 * Unbinds events for add/remove ticket.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.unbindTicketsAddRemove = function( $container ) {
		const $addRemove = $container.find( obj.selectors.itemQuantityAdd + ', ' + obj.selectors.itemQuantityRemove );

		$addRemove.off();
	};

	/**
	 * Binds events for add/remove ticket.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.bindTicketsAddRemove = function( $container ) {
		const $addRemove = $container.find( obj.selectors.itemQuantityAdd + ', ' + obj.selectors.itemQuantityRemove );

		$addRemove.on(
			'click',
			function( e ) {
				e.preventDefault();
				const $input = $( this ).parent().find( 'input[type="number"]' );

				$document.trigger( 'beforeTicketsAddRemove.tribeTicketsBlock', [ $input ] );

				if ( $input.is( ':disabled' ) ) {
					return false;
				}

				const originalValue = Number( $input[ 0 ].value );

				// Step up or Step down the input according to the button that was clicked.
				// Handles IE/Edge.
				// @todo: check if we still want to support this.
				if ( $( this ).hasClass( obj.selectors.itemQuantityAdd.className() ) ) {
					obj.stepUp( $input, originalValue );
				} else {
					obj.stepDown( $input, originalValue );
				}

				obj.updateFooter( $input.closest( 'form' ) );

				// Trigger the on Change for the input ( if it has changed ) as it's not handled via stepUp() || stepDown().
				if ( originalValue !== $input[ 0 ].value ) {
					$input.trigger( 'change' );
				}

				$document.trigger( 'afterTicketsAddRemove.tribeTicketsBlock', [ $input ] );
			}
		);
	};

	/**
	 * Unbinds events for the quantity input.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.unbindTicketsQuantityInput = function( $container ) {
		const $quantityInput = $container.find( obj.selectors.itemQuantityInput );

		$quantityInput.off();
	};

	/**
	 * Binds events for the quantity input.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.bindTicketsQuantityInput = function( $container ) {
		const $quantityInput = $container.find( obj.selectors.itemQuantityInput );

		// Handle Enter/Return on the quantity input from the main tickets form.
		$quantityInput.on(
			'keypress',
			function( e ) {
				if ( e.keyCode === 13 ) {
					e.preventDefault();
					e.stopPropagation();
					return;
				}
			}
		);

		/**
		 * Handle the Ticket form(s).
		 *
		 * @since TBD
		 */
		$quantityInput.on(
			//'input',
			'change keyup',
			function( e ) {
				const $this = $( e.target );

				$document.trigger( 'beforeTicketsQuantityChange.tribeTicketsBlock', [ $this ] );

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

				$document.trigger( 'afterTicketsQuantityChange.tribeTicketsBlock', [ $this ] );
			}
		);
	};

	/**
	 * Binds events the classic "Submit" (non-modal)
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.bindTicketsSubmit = function( $container ) {
		const $submitButton = $container.find( obj.selectors.classicSubmit );

		$submitButton.on(
			'click',
			function( e ) {
				e.preventDefault();

				const $form = $container.find( obj.selectors.container );
				const postId = $form.data( 'post-id' );
				const ticketProvider = $form.data( 'provider' );

				// Show the loader.
				tribe.tickets.loader.show( $form );

				// Save meta and cart.
				const params = {
					tribe_tickets_provider: obj.commerceSelector[ ticketProvider ],
					tribe_tickets_tickets: obj.getTicketsForCart( $container ),
					tribe_tickets_meta: {},
					tribe_tickets_post_id: postId,
				};

				$document.trigger( 'beforeTicketsSubmit.tribeTicketsBlock', [ $form, params ] );

				$form.submit();

				$document.trigger( 'afterTicketsSubmit.tribeTicketsBlock', [ $form, params ] );
			}
		);
	};

	/**
	 * Binds events for container.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of object of the tickets container.
	 *
	 * @return {void}
	 */
	obj.bindEvents = function( $container ) {
		$document.trigger( 'beforeSetup.tribeTicketsBlock', [ $container ] );

		// Disable the submit button.
		tribe.tickets.utils.disable( $container.find( obj.selectors.submit ), true );

		// Bind container based events.
		obj.bindTicketsAddRemove( $container );
		obj.bindTicketsQuantityInput( $container );
		obj.bindTicketsSubmit( $container );

		$document.trigger( 'afterSetup.tribeTicketsBlock', [ $container ] );
	};

	/**
	 * Handles the initialization of the tickets block events when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		// @todo: Check to see if we can make it container based. (use data-attributes)
		if ( 0 < TribeTicketOptions.availability_check_interval ) {
			obj.checkAvailability();
		}

		const $ticketsBlock = $document.find( obj.selectors.containerWrapper );
		// Bind events for each tickets block.
		$ticketsBlock.each( function( index, block ) {
			obj.bindEvents( $( block ) );
		} );
	};

	// @TODO: Check WTF is this for?
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

	// Configure on document ready.
	$document.ready( obj.ready );

} )( jQuery, tribe.tickets.block );
/* eslint-enable max-len */
