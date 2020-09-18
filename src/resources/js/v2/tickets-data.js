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
 * Configures ET data Object in the Global Tribe variable
 *
 * @since TBD
 *
 * @type   {PlainObject}
 */
tribe.tickets.data = {};

/**
 * Initializes in a Strict env the code that manages the plugin "loader".
 *
 * @since TBD
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} obj tribe.tickets.data
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
	 */
	obj.selectors = {};

	/* sessionStorage ( "local" ) */

	/**
	 * Stores attendee and cart form data to sessionStorage.
	 *
	 * @since TBD
	 */
	obj.storeLocal = function() {
		const meta = obj.getMetaForSave();
		sessionStorage.setItem(
			'tribe_tickets_attendees-' + tribe.tickets.block.postId, // @todo: review this and how to make it container based.
			window.JSON.stringify( meta )
		);

		const tickets = tribe.tickets.block.getTicketsForCart();
		sessionStorage.setItem(
			'tribe_tickets_cart-' + tribe.tickets.block.postId, // @todo: review this and how to make it container based.
			window.JSON.stringify( tickets )
		);
	};

	/**
	 * getMetaForSave()
	 *
	 * @since TBD
	 *
	 * @returns {object} Meta data object.
	 */
	obj.getMetaForSave = function() {
		const $metaForm = $( tribe.tickets.modal.selectors.metaForm );
		const $ticketRows = $metaForm.find( tribe.tickets.modal.selectors.metaItem );
		const meta = [];
		const tempMeta = [];

		$ticketRows.each(
			function() {
				const data = {};
				const $row = $( this );
				const ticketId = $row.data( 'ticketId' );

				const $fields = $row.find( tribe.tickets.modal.selectors.metaField );

				// Skip tickets with no meta fields
				if ( ! $fields.length ) {
					return;
				}

				if ( ! tempMeta[ ticketId ] ) {
					tempMeta[ ticketId ] = {};
					tempMeta[ ticketId ].ticket_id = ticketId;
					tempMeta[ ticketId ].items = [];
				}

				$fields.each(
					function() {
						const $field = $( this );
						let value = $field.val();
						const isRadio = $field.is( ':radio' );
						let name = $field.attr( 'name' );

						// Grab everything after the last bracket `[ `.
						name = name.split( '[' );
						name = name.pop().replace( ']', '' );

						// Skip unchecked radio/checkboxes.
						if ( isRadio || $field.is( ':checkbox' ) ) {
							if ( ! $field.prop( 'checked' ) ) {
								// If empty radio field, if field already has a value, skip setting it as empty.
								if ( isRadio && '' !== data[ name ] ) {
									return;
								}

								value = '';
							}
						}

						data[ name ] = value;
					}
				);

				tempMeta[ ticketId ].items.push( data );
			}
		);

		Object.keys( tempMeta ).forEach( function( index ) {
			const newArr = {
				ticket_id: index,
				items: tempMeta[ index ].items,
			};
			meta.push( newArr );
		} );

		return meta;
	};

	/**
	 * Clears attendee and cart form data for this event from sessionStorage.
	 *
	 * @param {number|string} eventId The ID of the event/post we're on.
	 *
	 * @since TBD
	 */
	obj.clearLocal = function( eventId ) {
		const postId = eventId || tribe.tickets.block.postId;

		sessionStorage.removeItem( 'tribe_tickets_attendees-' + postId );
		sessionStorage.removeItem( 'tribe_tickets_cart-' + postId );
	};

	/**
	 * Gets attendee and cart form data from sessionStorage.
	 *
	 * @since TBD
	 *
	 * @param {number|string} eventId The ID of the event/post we're on.
	 *
	 * @returns {array} An array of the data.
	 */
	obj.getLocal = function( eventId ) {
		const postId = eventId || tribe.tickets.block.postId;
		const meta = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_attendees-' + postId ) );
		const tickets = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_cart-' + postId ) );
		const ret = {};
		ret.meta = meta;
		ret.tickets = tickets;

		return ret;
	};

	/**
	 * Get cart & meta data from sessionStorage, otherwise make an ajax call.
	 * Always loads tickets from API on page load to be sure we keep up to date with the cart.
	 *
	 * This returns a deferred data object ( promise ) So when calling you need to use something like
	 * jQuery's $.when()
	 *
	 * Example:
	 *  $.when(
	 *     obj.getData()
	 *  ).then(
	 *     function( data ) {
	 *         // Do stuff with the data.
	 *     }
	 *  );
	 *
	 * @since TBD
	 *
	 * @param {boolean|string} pageLoad If we are experiencing a page load.
	 *
	 * @returns {object} Deferred data object.
	 */
	obj.getData = function( pageLoad ) {
		let ret = {};
		ret.meta = {};
		ret.tickets = {};
		const deferred = $.Deferred();
		const meta = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_attendees-' + obj.postId ) );

		if ( null !== meta ) {
			ret.meta = meta;
		}

		// If we haven't reloaded the page, assume the cart hasn't changed since we did.
		if ( ! pageLoad ) {
			const tickets = window.JSON.parse( sessionStorage.getItem( 'tribe_tickets_cart-' + obj.postId ) );

			if ( null !== tickets && tickets.length ) {
				ret.tickets = tickets;
			}

			deferred.resolve( ret );
		}

		if ( ! ret.tickets || ! ret.meta ) {
			$.ajax( {
				type: 'GET',
				data: {
					provider: $tribeTicket.data( 'providerId' ),
					post_id: tribe.tickets.block.postId,
				},
				dataType: 'json',
				url: tribe.tickets.block.getRestEndpoint(),
				success: function( data ) {
					// Store for future use.
					if ( null === meta ) {
						sessionStorage.setItem(
							'tribe_tickets_attendees-' + tribe.tickets.block.postId,
							window.JSON.stringify( data.meta )
						);
					}

					sessionStorage.setItem(
						'tribe_tickets_cart-' + tribe.tickets.block.postId,
						window.JSON.stringify( data.tickets )
					);

					ret = {
						meta: data.meta,
						tickets: data.tickets,
					};

					deferred.resolve( ret );
				},
				error: function() {
					deferred.reject( false );
				},
			} );
		}

		return deferred.promise();
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
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.data );
