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

	obj.selector = {
		footerQuantity     : '.tribe-tickets__footer__quantity__number',
		footerAmount       : '.tribe-tickets__footer__total .tribe-amount',
		checkout           : '.tribe-tickets__registration__checkout',
		checkoutButton     : '.tribe-tickets__item__registration__submit',
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
		form               : '#tribe-tickets__registration__form',
		item               : '.tribe-tickets__item',
		itemPrice          : '.tribe-amount',
		itemQuantity       : '.tribe-ticket-quantity',
		loader             : '.tribe-common-c-loader',
		metaField          : '.ticket-meta',
		metaItem           : '.tribe-ticket',
		metaForm           : '.tribe-tickets__registration__content',
		miniCart           : '#tribe-tickets__mini-cart',
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
	obj.provider = $tribe_registration.data( 'provider' );
	obj.providerId = obj.commerceSelector[ obj.provider ];

	/* Data Formatting / API Handling */

	/**
	 *
	 *
	 * @since TBD
	 *
	 * @return obj Meta data object.
	 */
	obj.getMetaForSave = function() {
		var $metaForm     = $( obj.selector.metaForm );
		var $ticketRows = $metaForm.find( obj.selector.metaItem );
		var meta    = [];
		var tempMeta    = [];
		$ticketRows.each(
			function() {
				var data      = {};
				var $row      = $( this );
				var ticket_id = $row.data( 'ticketId' );

				var $fields = $row.find( obj.selector.metaField );

				// Skip tickets with no meta fields
				if ( ! $fields.length ) {
					return;
				}

				if ( ! tempMeta[ ticket_id ] ) {
					tempMeta[ ticket_id ] = {};
					tempMeta[ ticket_id ]['ticket_id'] = ticket_id;
					tempMeta[ ticket_id ][ 'items' ] = [];
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
		});

		return meta;
	}


	/**
	 * Get ticket data to send to cart.
	 *
	 * @since TBD
	 *
	 * @return obj Tickets data object.
	 */
	obj.getTicketsForSave = function() {
		var tickets   = [];
		var $cartForm = $( obj.selector.miniCart );

		// Handle non-modal instances
		if ( ! $cartForm.length ) {
			$cartForm = $( obj.selector.container );
		}

		var $ticketRows = $cartForm.find( obj.selector.item );

		$ticketRows.each(
			function() {
				var $row        = $( this );
				var ticket_id    = $row.data( 'ticketId' );
				var qty          = $row.find( obj.selector.itemQuantity ).text();

				var data          = {};
				data['ticket_id'] = ticket_id;
				data['quantity']  = qty;

				tickets.push( data );
			}
		);

		return tickets;
	}

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
			data    : {
				provider: obj.providerId,
				post_id: obj.postId,
			},
			dataType: 'json',
			url     : obj.getRestEndpoint(),
			success : function ( data ) {
				if ( data.tickets ) {
					obj.prefillCartForm( $(obj.selector.miniCart), data.tickets );
				}

				if ( data.meta ) {
					obj.appendARFields( data );
					obj.prefillMetaForm( data );
				}
			},
			complete: function() {
				obj.loaderHide();
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
		var nonMetaCount = 0;
		var metaCount    = 0;

		$.each( tickets, function( index, ticket ) {
			var ticket_meta       = meta.filter( obj => { return obj.ticket_id === ticket.ticket_id; } );
			var ticketTemplate    = window.wp.template( 'tribe-registration--' + ticket.ticket_id );
			var $ticket_container = $tribe_registration.find( '.tribe-tickets__item__attendee__fields__container[data-ticket-id="' + ticket.ticket_id + '"]' );
			var counter           = 1;

			if ( ! $ticket_container.length ) {
				nonMetaCount += ticket.quantity;
			} else {
				metaCount += ticket.quantity;
			}

			$ticket_container.addClass( 'tribe-tickets--has-tickets' );

			for ( var i = counter; i <= ticket.quantity; ++i ) {
				var data = { 'attendee_id': i };
				try {
					$ticket_container.append( ticketTemplate( data ) );
				} catch( error ) {
					// template doesn't exist - the ticket has no meta.
				}

			}

		} );

		obj.maybeShowNonMetaNotice( nonMetaCount, metaCount );
	}

	obj.maybeShowNonMetaNotice = function( nonMetaCount, metaCount ) {
		var $notice = $( '.tribe-tickets__notice--non-ar' );
		if ( 0 < nonMetaCount && 0 < metaCount ) {
			$( '#tribe-tickets__non-ar-count' ).text( nonMetaCount );
			$notice.removeClass( 'tribe-common-a11y-hidden' );
		} else {
			$notice.addClass( 'tribe-common-a11y-hidden' );
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

		var $form = $tribe_registration;
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

				var $ticket_containers = $current_containers.find( '.tribe-ticket' );
				$.each( data, function( index, value ) {
					var $field = $ticket_containers.eq( current ).find( `[name*="${index}"]` );
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
	 * Update all the footer info.
	 *
	 * @since TBD
	 */
	obj.updateFooter = function() {
		obj.updateFooterCount();
		obj.updateFooterAmount();
	}

	/**
	 * Adjust the footer count for +/-.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.updateFooterCount = function() {
		var $form       = $( obj.selector.miniCart );
		var $field      = $form.find( obj.selector.footerQuantity );
		var footerCount = 0;
		var $qtys       = $form.find( obj.selector.itemQuantity );

		$qtys.each(function(){
			var new_quantity = parseInt( $(this).text(), 10 );
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
	 * @return void
	 */
	obj.updateFooterAmount = function() {
		var $form        = $( obj.selector.miniCart );
		var $field       = $form.find( obj.selector.footerAmount );
		var footerAmount = 0;
		var $qtys        = $form.find( obj.selector.itemQuantity );

		$qtys.each( function() {
			var $qty = $( this );
			var $price   = $qty.closest( obj.selector.item ).find( obj.selector.itemPrice ).first(0);
			var quantity = parseInt( $qty.text(), 10 );
			quantity     = isNaN( quantity ) ? 0 : quantity;
			var cost     = obj.cleanNumber( $price.text() ) * quantity;
			footerAmount += cost;
		} );

		if ( 0 > footerAmount ) {
			return;
		}

		$field.text( obj.numberFormat ( footerAmount ) );
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
				var pricePer = $item.find( '.tribe-tickets__item__extra__price .tribe-amount').text();
				$item.find( '.tribe-ticket-quantity' ).html( value.quantity );
				var price = value.quantity * obj.cleanNumber( pricePer );
				price = obj.numberFormat( price);
				$item.find( '.tribe-tickets__item__total .tribe-amount' ).html( price );
			}
		} );

		obj.updateFooter();

	};


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
		var $containers     = $form.find( obj.selector.metaItem );
		var formValid       = true;
		var invalidTickets  = 0;

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
		var $fields = $container.find( obj.selector.metaField );
		var validBlock = true;
		$fields.each(
			function() {
				var $field = $( this );
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
		var $checkboxes   = $group.find( obj.selector.metaField );
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

	/**
	 * Show the loader/spinner.
	 *
	 * @since TBD
	 */
	obj.loaderShow = function() {
		$( obj.selector.loader ).removeClass( 'tribe-common-a11y-hidden' );
	}

	/**
	 * Hide the loader/spinner.
	 *
	 * @since TBD
	 */
	obj.loaderHide = function() {
		$( obj.selector.loader ).addClass( 'tribe-common-a11y-hidden' );
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
	 * Get the Currency Formatting for a Provider.
	 *
	 * @since TBD
	 *
	 * @returns {*}
	 */
	obj.getCurrencyFormatting = function () {
		var currency = JSON.parse( TribeCurrency.formatting );
		var format   = currency[ obj.commerceSelector[ obj.providerId ] ];
		return format;
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
		var same = format.thousands_sep === format.decimal_point;

		if ( ! same ) {
			number = number.split( format.thousands_sep ).join( '' );
			number = number.split( format.decimal_point ).join( '.' );
		} else {
			var dec_place = number.length - ( format.number_of_decimals + 1 );
			number = number.substr( 0, dec_place ) + '_' + number.substr( dec_place + 1 );
			number = number.split( format.thousands_sep ).join( '' );
			number = number.split( '_' ).join( '.' );
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

		var s = ( prec ? toFixedFix( n, prec ) : Math.round( n ) ).toString().split( dec );

		if ( s[0].length > 3 ) {
			s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep );
		}

		if ( ( s[1] || '' ).length < prec ) {
			s[1] = s[1] || '';
			s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
		}

		return s.join( dec );
	}

	/* Event Handlers */

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
	 * Handle AR submission.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	obj.document.on(
		'click',
		obj.selector.checkoutButton,
		function( e ) {
			e.preventDefault();
			var $button      = $( this );
			var $metaForm    = $( obj.selector.metaForm );
			var $errorNotice = $( '.tribe-tickets__notice--error' );
			var isValidForm  = obj.validateForm( $metaForm );

			if ( ! isValidForm[ 0 ] ) {
				$([document.documentElement, document.body]).animate(
					{ scrollTop: $( '.tribe-tickets__registration' ).offset().top },
					'slow'
				);


				$( '.tribe-tickets__notice--error__count' ).text( isValidForm[ 1 ] );
				$errorNotice.show();

				return false;
			}

			$errorNotice.hide();

			obj.loaderShow();

			// save meta and cart
			var params = {
				tribe_tickets_provider: obj.commerceSelector[ obj.tribe_ticket_provider ],
				tribe_tickets_tickets : obj.getTicketsForSave(),
				tribe_tickets_meta    : obj.getMetaForSave(),
				tribe_tickets_post_id : obj.postId,
			};

			$( '#tribe_tickets_ar_data' ).val( JSON.stringify( params ) );

			// Submit the form.
			$( obj.selector.form ).submit();
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
		obj.loaderShow();
		obj.initFormPrefills();
	}

	obj.document.on( 'ready', function( $ ) {
		obj.init();
	});

})(jQuery, tribe.tickets.registration);
