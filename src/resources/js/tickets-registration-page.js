// For compatibility purposes we add this
if ('undefined' === typeof tribe) {
	tribe = {};
}

if ('undefined' === typeof tribe.tickets) {
	tribe.tickets = {};
}

tribe.tickets.registration = {};

( function( $, obj ) {
	'use strict';

	/* Variables */

	obj.document = $( document );

	obj.hasChanges = {};

	obj.formClasses = {
		woo: 'tribe-tickets__item__attendee__fields__form--woo',
		edd: 'tribe-tickets__item__attendee__fields__form--edd',
	}

	obj.selector = {
		checkout           : '.tribe-tickets__registration__checkout',
		checkoutButton     : '.tribe-tickets__registration__checkout__submit',
		container          : '.tribe-tickets__registration',
		eventContainer     : '.tribe-tickets__registration__event',
		field              : {
			text    : '.tribe-tickets__item__attendee__field__text',
			checkbox: '.tribe-tickets__item__attendee__field__checkbox',
			select  : '.tribe-tickets__item__attendee__field__select',
			radio   : '.tribe-tickets__item__attendee__field__radio',
		},
		fields             : '.tribe-tickets__item__attendee__fields',
		fieldsError        : '.tribe-tickets__item__attendee__fields__error',
		fieldsErrorAjax    : '.tribe-tickets__item__attendee__fields__error--ajax',
		fieldsErrorRequired: '.tribe-tickets__item__attendee__fields__error--required',
		fieldsSuccess      : '.tribe-tickets__item__attendee__fields__success',
		form               : '.tribe-tickets__item__attendee__fields__form',
		loader             : '.tribe-tickets__item__attendee__fields__loader',
		metaField          : '.ticket-meta',
		metaItem           : '.tribe-ticket',
		status             : '.tribe-tickets__registration__status',
		toggler            : '.tribe-tickets__registration__toggle__handler',
	};

	var $tribe_registration = $(obj.selector.container);

	// Bail if there are no tickets on the current event/page/post
	if ( ! $( obj.selector.eventContainer ).length ) {
		return;
	}

	/*
	 * Commerce Provider Selectors.
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
		tribe_eddticket                                  : 'edd',
		tribe_tpp_attendees                              : 'tpp',
		tribe_wooticket                                  : 'woo',
		woo                                              : 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main',
	};

	// Get the current provider & ID.
	obj.provider = $( obj.selector.container ).data( 'provider' );
	obj.providerId = obj.commerceSelector[ obj.provider ];


	/**
	 * Check if the required fields have data
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.validateEventAttendees = function($form) {
		var is_valid = true;
		var $fields = $form.find('.tribe-tickets-meta-required');

		$fields.each(function() {
			var $field = $(this);
			var val = '';

			if (
				$field.is(obj.selector.field.radio) ||
				$field.is(obj.selector.field.checkbox)
			) {
				val = $field.find('input:checked').length ? 'checked' : '';
			} else if ($field.is(obj.selector.field.select)) {
				val = $field.find('select').val();
			} else {
				val = $field.find('input, textarea').val().trim();
			}

			if (0 === val.length) {
				is_valid = false;
			}

		});

		return is_valid;
	};

	/**
	 * Update container status to complete
	 *
	 * @since 4.10.1
	 *
	 * @return void
	 */
	obj.updateStatusToComplete = function($event) {
		$event.find(obj.selector.status).removeClass('incomplete');
		$event.find(obj.selector.status).find('i').removeClass('dashicons-edit');
		$event.find(obj.selector.status).find('i').addClass('dashicons-yes');
	};

	/**
	 * Update container status to incomplete
	 *
	 * @since 4.10.1
	 *
	 * @return void
	 */
	obj.updateStatusToIncomplete = function($event) {
		$event.find(obj.selector.status).addClass('incomplete');
		$event.find(obj.selector.status).find('i').addClass('dashicons-edit');
		$event.find(obj.selector.status).find('i').removeClass('dashicons-yes');
	};

	obj.handleTppSaveSubmission = function(e) {
		var $form = $(this);
		var $fields = $form.closest(obj.selector.fields);

		// hide all messages
		$fields.find(obj.selector.fieldsErrorRequired).hide();

		if (!obj.validateEventAttendees($form)) {
			e.preventDefault();
			$fields.find(obj.selector.fieldsErrorRequired).show();
		}
	};

	/**
	 * Handle save attendees info form submission via ajax.
	 * Display a message if there are required fields missing.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.handleSaveSubmission = function(e) {
		e.preventDefault();
		var $form = $(this);
		var $fields = $form.closest(obj.selector.fields);
		var $event = $fields.closest(obj.selector.eventContainer);

		// hide all messages
		$fields.find(obj.selector.fieldsErrorRequired).hide();
		$fields.find(obj.selector.fieldsErrorAjax).hide();
		$fields.find(obj.selector.fieldsSuccess).hide();

		if (!obj.validateEventAttendees($form)) {
			$fields.find(obj.selector.fieldsErrorRequired).show();
			obj.updateStatusToIncomplete($event)
		} else {
			$fields.find(obj.selector.loader).show();

			var ajaxurl = '';
			var nonce = '';

			if (typeof TribeTicketsPlus === 'object') {
				ajaxurl = TribeTicketsPlus.ajaxurl;
				nonce = TribeTicketsPlus.save_attendee_info_nonce;
			}

			var eventId = $event.data('event-id');
			var params = $form.serializeArray();
			params.push({ name: 'event_id', value: eventId });
			params.push({ name: 'action', value: 'tribe-tickets-save-attendee-info' });
			params.push({ name: 'nonce', value: nonce });

			$.post(
				ajaxurl,
				params,
				function(response) {
					if (response.success) {
						obj.updateStatusToComplete($event)
						obj.hasChanges[eventId] = false;
						$fields.find(obj.selector.fieldsSuccess).show();

						if (response.data.meta_up_to_date) {
							$(obj.selector.checkoutButton).removeAttr('disabled');
						}
					}
				}
			).fail(function() {
				$fields.find(obj.selector.fieldsErrorAjax).show();
			}).always(function() {
				$fields.find(obj.selector.loader).hide();
			});
		}
	};

	/**
	 * Handle checkout form submission.
	 * Display a confirm if there are any changes to the attendee info that have not been saved
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.handleCheckoutSubmission = function(e) {
		var eventIds = Object.keys(obj.hasChanges);
		var hasChanges = eventIds.reduce(function(hasChanges, eventId) {
			return hasChanges || obj.hasChanges[eventId];
		}, false);

		if (hasChanges && !confirm(tribe_l10n_datatables.registration_prompt)) {
			e.preventDefault();
			return;
		}
	};

	/**
	 * Sets hasChanges flag to true for given eventId
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.setHasChanges = function(eventId) {
		return function() {
			obj.hasChanges[eventId] = true;
		};
	};

	/**
	 * Bind event handlers to each form field
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.bindFormFields = function($event) {
		// set up hasChanges flag for event
		var eventId = $event.data('event-id');
		obj.hasChanges[eventId] = false;

		var $fields = [
			$event.find(obj.selector.field.text),
			$event.find(obj.selector.field.checkbox),
			$event.find(obj.selector.field.radio),
			$event.find(obj.selector.field.select),
		];

		$fields.forEach(function($field) {
			var $formElement;

			if (
				$field.is(obj.selector.field.radio) ||
				$field.is(obj.selector.field.checkbox)
			) {
				$formElement = $field.find('input');
			} else if ($field.is(obj.selector.field.select)) {
				$formElement = $field.find('select');
			} else {
				$formElement = $field.find('input, textarea');
			}

			$formElement.change(obj.setHasChanges(eventId));
		});
	};

	/**
	 * Bind event handlers to checkout form
	 */
	obj.bindCheckout = function() {
		$(obj.selector.checkout).submit(obj.handleCheckoutSubmission);
	};

	/**
	 * Bind event handlers
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.bindEvents = function() {
		obj.bindCheckout();
	};

	/* Prefill Functions */

	/**
	 * Init the form prefills (cart and AR forms).
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.initFormPrefills = function() {
		$.ajax( {
			type    : 'GET',
			data    : { 'provider': obj.providerId },
			dataType: 'json',
			url     : '/wp-json/tribe/tickets/v1/cart',
			success : function ( data ) {
				if ( data.tickets ) {
					obj.prefillCartForm( $tribe_registration, data.tickets );
				}

				if ( data.meta ) {
					obj.appendARFields( data );
					obj.prefillMetaForm( data );
				}
			}
		} );
	}

	/**
	 * Appends AR fields on page load.
	 *
	 * @since TBD
	 *
	 * @param obj meta The ticket meta we are usign to add "blocks".
	 */
	obj.appendARFields = function ( data ) {
		var tickets      = data.tickets;
		var meta         = data.meta;
		var nonMetaCount = tickets.length;

		$.each( tickets, function( index, ticket ) {
			var ticket_meta       = meta.filter( obj => { return obj.ticket_id === ticket.ticket_id; } );
			var tickets_length    = ticket_meta[0].items.length;
			var ticketTemplate    = window.wp.template( 'tribe-registration--' + ticket.ticket_id );
			var $ticket_container = $( obj.selector.container ).find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticket.ticket_id + '"]' );
			var counter           = 1;

			$ticket_container.addClass( 'tribe-tickets--has-tickets' );

			for ( var i = counter; i <= tickets_length; ++i ) {
				var data = { 'attendee_id': i };

				$ticket_container.append( ticketTemplate( data ) );
			}

			nonMetaCount -= tickets_length;
		} );

		var $notice = $( '.tribe-tickets-notice--non-ar' );
		if ( nonMetaCount ) {
			$( '#tribe-tickets__non-ar-count' ).text( nonMetaCount );
			$notice.show();
		} else {
			$notice.hide();
		}
	}

	/**
	 * Prefills the AR fields from supplied data.
	 *
	 * @since TBD
	 *
	 * @param meta Data to fill the form in with.
	 * @param length Starting pointer for partial fill-ins.
	 *
	 * @return void
	 */
	obj.prefillMetaForm = function( data, length ) {
		if ( undefined === data || 0 >= data.length ) {
			return;
		}

		if ( undefined === length ) {
			var length = 0;
		}

		var $form = $( obj.selector.container );
		var $containers = $form.find( '.tribe-tickets__item__attendee__fields__container' );
		var meta = data.meta;
		if ( 0 < length ) {
			var meta = meta.splice( 0, length - 1 );
		}

		$.each( meta, function( index, ticket ) {
			var $current_containers = $containers.filter( `[data-ticket-id="${ticket.ticket_id}"]` );

			if ( ! $current_containers.length ) {
				return;
			}

			var current = 0;
			$.each( ticket.items, function( index, data ) {
				if ( 'object' !== typeof data ) {
					return;
				}

				$.each( data, function( index, value ) {
					var $field = $current_containers.eq( current ).find( `[name*="${index}"]`);
					if ( ! $field.is( ':radio' ) && ! $field.is( ':checkbox' ) ) {
						$field.val( value);
					} else {
						$field.each( function( index ) {
							var $item = $( this );
							if ( value === $item.val() ) {
								$item.prop( 'checked', true );
							}
						});
					}
				});

				current++;
			});
		});
	}

	/**
	 * Prefill the Mini-Cart.
	 *
	 * @since TBD
	 *
	 * @returns {*}
	 */
	obj.prefillCartForm = function ( $form, tickets ) {
		$.each( tickets, function ( index, value ) {
			var $item = $form.find( '[data-ticket-id="' + value.ticket_id + '"]' );
			if ( $item ) {
				$item.find( '.tribe-ticket-quantity' ).val( value.quantity );
			}
		} );

	};

	/* DOM Manipulation */


	/**
	 * Adds focus effect to ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.focusTicketBlock = function( input ) {
		$( input ).closest( obj.selector.metaItem ).addClass( 'tribe-ticket-item__has-focus' );
	}

	/**
	 * Remove focus effect from ticket block.
	 *
	 * @since TBD
	 *
	 */
	obj.unfocusTicketBlock = function( input ) {
		$( input ).closest( obj.selector.metaItem ).removeClass( 'tribe-ticket-item__has-focus' );
	}

	/* Utility */


	/* Event Handlers */

	/**
	 * Handle the toggle for each event
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	$(obj.selector.eventContainer).on(
		'click',
		obj.selector.toggler,
		function(e) {
			e.preventDefault();

			var $this = $(this);
			var $event = $this.closest(obj.selector.eventContainer);

			$event.find(obj.selector.fields).toggle();
			$this.toggleClass('open');

	});

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
			var input      = e.target;
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
			var input      = e.target;
			obj.unfocusTicketBlock( input );
		}
	);

	/**
	 * Init the tickets registration script
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	obj.init = function() {
		obj.bindEvents();
		obj.initFormPrefills();
	}

	obj.document.on( 'ready', function( $ ) {
		obj.init();
	});

})(jQuery, tribe.tickets.registration);
