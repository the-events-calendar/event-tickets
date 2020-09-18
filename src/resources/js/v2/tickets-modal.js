/* global tribe */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type   {PlainObject}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Configures ET Modal Object in the Global Tribe variable
 *
 * @since TBD
 *
 * @type   {PlainObject}
 */
tribe.tickets.modal = {};

/**
 * Initializes in a Strict env the code that manages the plugin modal.
 *
 * @since TBD
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} obj tribe.tickets.modal
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 *
	 * @type {PlainObject}

	obj.selectors = {
		hiddenElement: '.tribe-common-a11y-hidden',
	};*/

	/*
	 * AR Cart Modal Selectors.
	 *
	 * Note: some of these have the modal class as well, as the js can
	 * pick up the class from elsewhere in the DOM and grab the wrong data.
	 *
	 * @since TBD
	 */
	obj.selectors = {
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
	};

	/**
	 * Appends AR fields when modal cart quantities are changed.
	 *
	 * @since TBD
	 *
	 * @param {object} $form - The form we are updating.
	 */

	obj.appendARFields = function( $form ) {
		$form.find( tribe.tickets.block.selectors.item ).each(
			function() {
				const $cartItem = $( this );

				if ( $cartItem.is( ':visible' ) ) {
					const ticketID = $cartItem.closest( tribe.tickets.block.selectors.item ).data( 'ticket-id' );
					const $ticketContainer = $( obj.selectors.metaForm ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticketID + '"]' );

					// Ticket does not have meta - no need to jump through hoops ( and throw errors ).
					if ( ! $ticketContainer.length ) {
						return;
					}

					const $existing = $ticketContainer.find( obj.selectors.metaItem );
					const qty = tribe.tickets.block.getQty( $cartItem );

					if ( 0 >= qty ) {
						$ticketContainer.removeClass( 'tribe-tickets--has-tickets' );
						$ticketContainer.find( obj.selectors.metaItem ).remove();

						return;
					}

					if ( $existing.length > qty ) {
						const removeCount = $existing.length - qty;
						$ticketContainer.find( '.tribe-ticket:nth-last-child( -n+' + removeCount + ' )' ).remove();

					} else if ( $existing.length < qty ) {

						const ticketTemplate = window.wp.template( 'tribe-registration--' + ticketID );
						const counter = 0 < $existing.length ? $existing.length + 1 : 1;

						$ticketContainer.addClass( 'tribe-tickets--has-tickets' );

						for ( let i = counter; i <= qty; i++ ) {
							const data = { attendee_id: i };

							$ticketContainer.append( ticketTemplate( data ) );
							obj.maybeHydrateAttendeeBlockFromLocal( $existing.length );
						}
					}
				}
			}
		);

		tribe.tickets.block.maybeShowNonMetaNotice( $form );
		$document.trigger( 'tribe-ar-fields-appended' );
		tribe.tickets.loader.hide( $form );
	};

	/**
	 * Pre-fills the modal AR fields from supplied data.
	 *
	 * @since TBD
	 *
	 * @param {array} meta - Data to fill the form in with.
	 * @param {number} length - Starting pointer for partial fill-ins.
	 */
	obj.preFillModalMetaForm = function( meta ) {
		if ( undefined === meta || 0 >= meta.length ) {
			return;
		}

		const $form = $( obj.selectors.metaForm );
		const $containers = $form.find( '.tribe-tickets__item__attendee__fields__container' );

		$.each( meta, function( idx, ticket ) {
			let current = 0;
			const $currentContainers = $containers.find( obj.selectors.metaItem ).filter( '[data-ticket-id="' + ticket.ticket_id + '"]' );

			if ( ! $currentContainers.length ) {
				return;
			}

			$.each( ticket.items, function( indx, data ) {
				if ( 'object' !== typeof data ) {
					return;
				}

				$.each( data, function( index, value ) {
					const $field = $currentContainers.eq( current ).find( '[name*="' + index + '"]' );

					if ( ! $field.is( ':radio' ) && ! $field.is( ':checkbox' ) ) {
						$field.val( value );
					} else {
						$field.each( function() {
							const $item = $( this );

							if ( value === $item.val() ) {
								$item.prop( 'checked', true );
							}
						} );
					}
				} );

				current++;
			} );
		} );

		tribe.tickets.loader.hide( $form );
	};

	/**
	 * Pre-fill the Cart.
	 *
	 * @since TBD
	 *
	 * @param {object} $form - The form we're updating.
	 */
	obj.preFillModalCartForm = function( $form ) {
		$form.find( tribe.tickets.block.selectors.item ).hide();

		const $tribeTicket = $form.closest( tribe.tickets.block.selectors.container );
		const $items = $tribeTicket.find( tribe.tickets.block.selectors.item );

		// Override the data with what's in the tickets block.
		$.each( $items, function( index, item ) {
			const $blockItem = $( item );
			const $item = $form.find( '[data-ticket-id="' + $blockItem.attr( 'data-ticket-id' ) + '"]' );

			if ( $item ) {
				const quantity = $blockItem.find( '.tribe-tickets-quantity' ).val();
				if ( 0 < quantity ) {
					$item.fadeIn();
				}
			}
		} );

		obj.appendARFields( $form );

		tribe.tickets.loader.hide( $form );
	};

	/**
	 * Init the form pre-fills ( cart and AR forms ).
	 *
	 * @since TBD
	 */
	obj.initModalFormPreFills = function() {
		// @todo: Fix how this is handling stuff (use container based).
		const $tribeTicket = $form.closest( tribe.tickets.block.selectors.container );


		tribe.tickets.loader.show( $document );
		$.when(
			obj.getData()
		).then(
			function( data ) {
				obj.preFillModalCartForm( $( obj.selectors.cartForm ) );

				if ( data.meta ) {
					$.each( data.meta, function( ticket ) {
						const $matches = $tribeTicket.find( '[data-ticket-id="' + ticket.ticket_id + '"]' );

						if ( $matches.length ) {
							obj.preFillModalMetaForm( data.meta );

							return;
						}
					} );
				}

				// If we didn't get meta from the API, let's fill with sessionStorage.
				const local = tribe.tickets.data.getLocal();

				if ( local.meta ) {
					obj.preFillModalMetaForm( local.meta );
				}

				// @todo: review this and use the tickets.loader.hide
				window.setTimeout( obj.loaderHide, 500, tribe.tickets.modal.selectors.loader );
			}
		);

		tribe.tickets.loader.hide( $document );
	};

	/**
	 * Attempts to hydrate a dynamically-created attendee form "block" from sessionStorage data.
	 *
	 * @since TBD
	 *
	 * @param {number} length The "skip" index.
	 */
	obj.maybeHydrateAttendeeBlockFromLocal = function( length ) {
		$.when(
			tribe.tickets.data.getData()
		).then(
			function( data ) {
				if ( ! data.meta ) {
					return;
				}

				const cartSkip = data.meta.length;
				if ( length < cartSkip ) {
					obj.modal.preFillModalMetaForm( data.meta );
					return;
				}
				const $attendeeForm = $( obj.selectors.metaForm );
				const $newBlocks = $attendeeForm.find( obj.selectors.metaItem ).slice( length - 1 );

				if ( ! $newBlocks ) {
					return;
				}

				$newBlocks.find( obj.selectors.metaField ).each(
					function() {
						const $this = $( this );
						const name = $this.attr( 'name' );
						const storedVal = data[ name ];

						if ( storedVal ) {
							$this.val( storedVal );
						}
					}
				);
			}
		);
	};

	/**
	 * Handles the initialization of the scripts when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		// Silence is golden.

		/**
		 * Handles storing data to local storage
		 */
		$( tribe.dialogs.events ).on(
			'tribe_dialog_close_ar_modal', // @todo: see how to do something more container based.
			function() {
				tribe.tickets.data.storeLocal();
			}
		);

		/**
		 * When "Get Tickets" is clicked, update the modal.
		 *
		 * @since TBD
		 */
		$( tribe.dialogs.events ).on(
			'tribe_dialog_show_ar_modal',
			function() {

				console.log( $( this ) );

				const $form = $( obj.selectors.form );
				const $modalCart = $( obj.selectors.cartForm );
				// @todo: Fix how this is handling stuff (use container based).

				const $tribeTicket = $( tribe.tickets.block.selectors.container );

				const $cartItems = $tribeTicket.find( tribe.tickets.block.selectors.item );

				tribe.tickets.loader.show( $form );

				$cartItems.each(
					function() {
						const $blockCartItem = $( this );
						const id = $blockCartItem.data( 'ticketId' );
						const $modalCartItem = $modalCart.find( '[data-ticket-id="' + id + '"]' );

						if ( ! $modalCartItem ) {
							return;
						}

						tribe.tickets.block.updateItem( id, $modalCartItem, $blockCartItem );
					}
				);

				obj.initModalFormPreFills();

				tribe.tickets.block.updateFormTotals( $modalCart );

				tribe.tickets.loader.hide( $form );
			}
		);

	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.modal );
