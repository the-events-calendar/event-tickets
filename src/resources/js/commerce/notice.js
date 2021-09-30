/* global tribe, jQuery */
/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Configures ET Tickets Commerce Object in the Global Tribe variable
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Configures ET Tickets Commerce Object in the Global Tribe variable
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.notice = tribe.tickets.commerce.notice || {};

/**
 * Initializes in a Strict env the code that manages the plugin tickets commerce.
 *
 * @since TBD
 *
 * @param  {Object} $   jQuery
 * @param  {Object} obj tribe.tickets.commerce
 *
 * @return {void}
 */
( function( $, obj ) {
	'use strict';
	const $document = $( document );

	/*
	 * Tickets Commerce Selectors.
	 *
	 * @since 5.1.9
	 */
	obj.selectors = {
		hiddenElement: '.tribe-common-a11y-hidden',
		item: '.tribe-tickets__commerce-checkout__notice',
		content: '.tribe-tickets__commerce-checkout__notice__content',
		title: '.tribe-tickets-notice__title',
		container: '[data-js="tec-tickets-commerce-notice"]',
	};

	/**
	 * Display the notice component.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $item Data for notice.
	 */
	obj.show = ( $item ) => {
		if ( ! $item.length  ) {
			return;
		}
		const $container = $item.parents( obj.selectors.container ).eq( 0 );

		$item.trigger( 'beforeShowNotice.tecTicketsCommerce', [ $container ] );

		$item.show();

		$item.trigger( 'aftershowNotice.tecTicketsCommerce', [ $container ] );
	};

	/**
	 * Hide the notice component.
	 *
	 * @since TBD
	 */
	obj.hide = ( $item ) => {
		if ( ! $item.length  ) {
			return;
		}
		const $container = $item.parents( obj.selectors.container ).eq( 0 );

		$item.trigger( 'beforeHideNotice.tecTicketsCommerce', [ $container ] );

		$item.hide();

		$item.trigger( 'afterHideNotice.tecTicketsCommerce', [ $container ] );
	};

	/**
	 * Populate the contents of the notice component.
	 *
	 * @since TBD
	 *
	 * @param {jQuery} $item Data for notice.
	 * @param {string} title Data for notice.
	 * @param {string} content Data for notice.
	 */
	obj.populate = ( $item, title, content ) => {
		const $content = $item.find( obj.selectors.content );
		const $title = $item.find( obj.selectors.title );

		if ( ! $item.length || ! $content.length || ! $title.length ) {
			return;
		}

		const $container = $item.parents( obj.selectors.container ).eq( 0 );

		title = 'undefined' !== typeof title ? title : $container.data( 'noticeDefaultTitle' );
		content = 'undefined' !== typeof content ? content : $container.data( 'noticeDefaultContent' );

		$item.trigger( 'beforePopulateNotice.tecTicketsCommerce', [ $container ] );

		$title.text( title );
		$content.text( content );

		$item.trigger( 'afterPopulateNotice.tecTicketsCommerce', [ $container ] );
	};

	/**
	 * Handles the initialization of the tickets commerce events when Document is ready.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	obj.ready = () => {

	};

	$( obj.ready );

} )( jQuery, tribe.tickets.commerce.notice );
