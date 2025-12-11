/**
 * RSVP V2 Tooltip JavaScript
 *
 * Handles tooltip functionality for RSVP V2 using tooltipster.
 *
 * @since TBD
 */

/* global jQuery, tribe */

/**
 * Makes sure we have all the required levels on the Tribe Object.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets = tribe.tickets || {};
tribe.tickets.rsvp = tribe.tickets.rsvp || {};
tribe.tickets.rsvp.v2 = tribe.tickets.rsvp.v2 || {};

/**
 * Configures RSVP V2 Tooltip Object in the Global Tribe variable.
 *
 * @since TBD
 * @type {Object}
 */
tribe.tickets.rsvp.v2.tooltip = {};

/**
 * Initializes in a Strict env the code that manages the RSVP V2 Tooltip.
 *
 * @since TBD
 * @param {Object} $   jQuery
 * @param {Object} obj tribe.tickets.rsvp.v2.tooltip
 * @return {void}
 */
( function ( $, obj ) {
	'use strict';

	const $document = $( document );

	/**
	 * Config used for tooltip setup.
	 *
	 * @since TBD
	 * @type {Object}
	 */
	obj.config = {
		delayHoverIn: 300,
		delayHoverOut: 300,
	};

	/**
	 * Selectors used for configuration and setup.
	 *
	 * @since TBD
	 * @type {Object}
	 */
	obj.selectors = {
		container: '.tribe-tickets__rsvp-v2-wrapper',
		tooltipTrigger: '[data-rsvp-v2-tooltip]',
		tooltipTriggerHoverClass: '.tribe-tickets__rsvp-v2-tooltip-trigger--hover',
		tooltipThemeClass: '.tribe-tickets__rsvp-v2-tooltip-theme',
		tooltipThemeHoverClass: '.tribe-tickets__rsvp-v2-tooltip-theme--hover',
		tribeCommonClass: '.tribe-common',
		tribeTicketsClass: '.event-tickets',
	};

	/**
	 * Handle tooltip focus event.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleOriginFocus = function ( event ) {
		setTimeout( function () {
			if (
				event.data.target.is( ':focus' ) ||
				event.data.target.hasClass( obj.selectors.tooltipTriggerHoverClass.replace( '.', '' ) )
			) {
				event.data.target.tooltipster( 'open' );
			}
		}, obj.config.delayHoverIn );
	};

	/**
	 * Handle tooltip blur event.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleOriginBlur = function ( event ) {
		event.data.target.tooltipster( 'close' );
	};

	/**
	 * Handle origin mouseenter and touchstart events.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleOriginHoverIn = function ( event ) {
		event.data.target.addClass( obj.selectors.tooltipTriggerHoverClass.replace( '.', '' ) );
	};

	/**
	 * Handle origin mouseleave and touchleave events.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleOriginHoverOut = function ( event ) {
		event.data.target.removeClass( obj.selectors.tooltipTriggerHoverClass.replace( '.', '' ) );
	};

	/**
	 * Handle tooltip mouseenter and touchstart event.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleTooltipHoverIn = function ( event ) {
		event.data.target.addClass( obj.selectors.tooltipThemeHoverClass.replace( '.', '' ) );
	};

	/**
	 * Handle tooltip mouseleave and touchleave events.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleTooltipHoverOut = function ( event ) {
		event.data.target.removeClass( obj.selectors.tooltipThemeHoverClass.replace( '.', '' ) );
	};

	/**
	 * Handle tooltip instance closing event.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleInstanceClose = function ( event ) {
		const $origin = event.data.origin;
		const $tooltip = $( event.tooltip );

		// if trigger is focused, hovered, or tooltip is hovered, do not close tooltip
		if (
			$origin.is( ':focus' ) ||
			$origin.hasClass( obj.selectors.tooltipTriggerHoverClass.replace( '.', '' ) ) ||
			$tooltip.hasClass( obj.selectors.tooltipThemeHoverClass.replace( '.', '' ) )
		) {
			event.stop();
		}
	};

	/**
	 * Handle tooltip instance close event.
	 *
	 * @since TBD
	 * @param {Event} event event object
	 * @return {void}
	 */
	obj.handleInstanceClosing = function ( event ) {
		$( event.tooltip )
			.off( 'mouseenter touchstart', obj.handleTooltipHoverIn )
			.off( 'mouseleave touchleave', obj.handleTooltipHoverOut );
	};

	/**
	 * Override of the `functionInit` tooltipster method.
	 * A custom function to be fired only once at instantiation.
	 *
	 * @since TBD
	 * @param {Function} instance instance of Tooltipster
	 * @param {Object}   helper   helper object with tooltip origin
	 * @return {void}
	 */
	obj.onFunctionInit = function ( instance, helper ) {
		const $origin = $( helper.origin );
		$origin
			.on( 'focus', { target: $origin }, obj.handleOriginFocus )
			.on( 'blur', { target: $origin }, obj.handleOriginBlur )
			.on( 'mouseenter touchstart', { target: $origin }, obj.handleOriginHoverIn )
			.on( 'mouseleave touchleave', { target: $origin }, obj.handleOriginHoverOut );
		instance
			.on( 'close', { origin: $origin }, obj.handleInstanceClose )
			.on( 'closing', { origin: $origin }, obj.handleInstanceClosing );
	};

	/**
	 * Override of the `functionReady` tooltipster method.
	 * A custom function to be fired when the tooltip and its contents have been added to the DOM.
	 *
	 * @since TBD
	 * @param {Function} instance instance of Tooltipster
	 * @param {Object}   helper   helper object with tooltip origin
	 * @return {void}
	 */
	obj.onFunctionReady = function ( instance, helper ) {
		const $tooltip = $( helper.tooltip );
		$tooltip
			.on( 'mouseenter touchstart', { target: $tooltip }, obj.handleTooltipHoverIn )
			.on( 'mouseleave touchleave', { target: $tooltip }, obj.handleTooltipHoverOut );
	};

	/**
	 * Deinitialize accessible tooltips via tooltipster.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of view container.
	 * @return {void}
	 */
	obj.deinitTooltips = function ( $container ) {
		$container.find( obj.selectors.tooltipTrigger ).each( function ( index, trigger ) {
			const $trigger = $( trigger );

			// Check if tooltipster is initialized.
			if ( typeof $trigger.tooltipster === 'function' ) {
				const instance = $trigger.tooltipster( 'instance' );

				if ( instance ) {
					instance.off();
				}
			}

			$trigger.off();
		} );
	};

	/**
	 * Initialize accessible tooltips via tooltipster.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of RSVP container.
	 * @return {void}
	 */
	obj.initTooltips = function ( $container ) {
		// Check if tooltipster is available.
		if ( typeof $.fn.tooltipster !== 'function' ) {
			return;
		}

		const theme = $container.data( 'rsvp-v2-tooltip-theme' );

		$container.find( obj.selectors.tooltipTrigger ).each( function ( index, trigger ) {
			$( trigger ).tooltipster( {
				animationDuration: 0,
				interactive: true,
				delay: [ obj.config.delayHoverIn, obj.config.delayHoverOut ],
				delayTouch: [ obj.config.delayHoverIn, obj.config.delayHoverOut ],
				theme,
				functionInit: obj.onFunctionInit,
				functionReady: obj.onFunctionReady,
			} );
		} );
	};

	/**
	 * Initialize tooltip theme.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of RSVP container.
	 * @return {void}
	 */
	obj.initTheme = function ( $container ) {
		$container.trigger( 'beforeTooltipInitTheme.tribeTicketsRsvpV2', [ $container ] );

		const theme = [
			obj.selectors.tooltipThemeClass.replace( '.', '' ),
			obj.selectors.tribeCommonClass.replace( '.', '' ),
			obj.selectors.tribeTicketsClass.replace( '.', '' ),
		];
		$container.data( 'rsvp-v2-tooltip-theme', theme );

		$container.trigger( 'afterTooltipInitTheme.tribeTicketsRsvpV2', [ $container ] );
	};

	/**
	 * Deinitialize tooltip JS.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of the RSVP container.
	 * @return {void}
	 */
	obj.deinit = function ( $container ) {
		obj.deinitTooltips( $container );
	};

	/**
	 * Initialize tooltips JS for a single container.
	 *
	 * @since TBD
	 * @param {jQuery} $container jQuery object of view container.
	 * @return {void}
	 */
	obj.init = function ( $container ) {
		obj.initTheme( $container );
		obj.initTooltips( $container );
	};

	/**
	 * Handles the initialization of the scripts when Document is ready.
	 *
	 * @since TBD
	 * @return {void}
	 */
	obj.ready = function () {
		$( tribe.tickets.rsvp.v2.block.selectors.container ).each( function () {
			obj.init( $( this ) );
		} );
	};

	// Initialize on document ready.
	$document.ready( obj.ready );

	// Allow re-initialization via WordPress hooks.
	if ( typeof wp !== 'undefined' && wp.hooks ) {
		wp.hooks.addAction( 'tec.tickets.rsvp.v2.init', 'tec.tickets.tooltip', obj.ready );
	}

} )( jQuery, tribe.tickets.rsvp.v2.tooltip );
