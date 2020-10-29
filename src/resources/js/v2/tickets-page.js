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
 * Configures ET tickets page Object in the Global Tribe variable
 *
 * @since TBD
 *
 * @type   {PlainObject}
 */
tribe.tickets.page = {};

/**
 * Initializes in a Strict env the code that manages the plugin tickets page.
 *
 * @since TBD
 *
 * @param  {PlainObject} $   jQuery
 * @param  {PlainObject} obj tribe.tickets.page
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/*
	 * Ticket Page Selectors.
	 *
	 * @since TBD
	 */
	obj.selectors = {
		container: '.tribe-tickets__tickets-page-wrapper',
	};

	/**
	 * Binds events for container.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $container jQuery object of object of the tickets page container.
	 *
	 * @return {void}
	 */
	obj.bindEvents = function( $container ) {
		$document.trigger( 'beforeSetup.tribeTicketsPage', [ $container ] );

		$document.trigger( 'afterSetup.tribeTicketsPage', [ $container ] );
	};

	/**
	 * Handles the initialization of the tickets page events when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = function() {
		const $ticketsPage = $document.find( obj.selectors.container );

		// Bind events for each tickets block.
		$ticketsPage.each( function( index, block ) {
			obj.bindEvents( $( block ) );
		} );
	};

	// Configure on document ready.
	$document.ready( obj.ready );
} )( jQuery, tribe.tickets.page );
