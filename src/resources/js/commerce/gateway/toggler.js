/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
 tribe.tickets = tribe.tickets || {};

 /**
 * Path to this script in the global tribe Object.
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway.toggler = tribe.tickets.commerce.gateway.toggler || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since 5.3.0
 *
 * @type   {Object}
 */
(( $, obj ) => {

	/**
	 * Pull the variables from the PHP backend.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object}
	 */
	obj.text = tecTicketsCommerceCheckoutToggleText;

	/**
	 * Array of gateway elements.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object}
	 */
	obj.gateways = [];

	/**
	 * Object to store the toggle elements.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object}
	 */
	obj.toggles = {};

	/**
	 * Toggler selectors.
	 *
	 * @since 5.3.0
	 *
	 * @type {Object}
	 */
	obj.selectors = {
		gatewayDiv: '.tribe-tickets__commerce-checkout-gateway',
		toggleButton: '.tribe-tickets__commerce-checkout-gateway-toggle-button',
		toggle: '.tribe-tickets__commerce-checkout-gateway-toggle',
		toggleOpen: '.tribe-tickets__commerce-checkout-gateway-toggle--open',
		toggleHidden: '.tribe-common-a11y-hidden',
	};

	/**
	 * Toggler init method.
	 *
	 * @since 5.3.0
	 *
	 * @return
	 */
	obj.init = () => {
		obj.gateways = $( obj.selectors.gatewayDiv );

		// If one or less gateways, go ahead and show and not add toggles.
		if( obj.gateways.length < 2 ){
			obj.gateways.show();
			return;
		}
		obj.addToggles();
		obj.showDefault();
	};

	/**
	 * Shows gateway.
	 *
	 * @since 5.3.0
	 *
	 * @param {Element} gateway Gateway element to show.
	 */
	obj.showGateway = gateway => {
		$( gateway )
			.show()
			.attr( 'aria-expanded', 'true' )
			.removeClass( obj.selectors.toggleHidden.className() );
	};

	/**
	 * Hides gateway.
	 *
	 * @since 5.3.0
	 *
	 * @param {Element} gateway Gateway element to hide.
	 */
	obj.hideGateway = gateway => {
		$( gateway )
			.hide()
			.attr( 'aria-expanded', 'false' )
			.addClass( obj.selectors.toggleHidden.className() );
	};

	/**
	 * Show the default/first gateway.
	 *
	 * @since 5.3.0
	 */
	obj.showDefault = () => {
		obj.showGateway( obj.gateways[0] );
		obj.gateways.each( ( x, gateway ) => {
			if( 0 === x ){
				return;
			}
			obj.hideGateway( gateway );
		});
		obj.toggles.default.addClass( obj.selectors.toggleOpen.className() ).hide().attr( 'aria-hidden', 'true' );
		obj.toggles.additional.removeClass( obj.selectors.toggleOpen.className() ).attr( 'aria-selected', 'false' );
	};

	/**
	 * Show the additional (non-default) gateway(s).
	 *
	 * @since 5.3.0
	 */
	obj.showAdditional = () => {
		// If additional options already open, let's close it and show default.
		if ( obj.toggles.additional.hasClass( obj.selectors.toggleOpen.className() ) ) {
			obj.showDefault();
			return;
		}
		obj.hideGateway( obj.gateways[0] );
		obj.gateways.each( ( x, gateway ) => {
			if( 0 === x ){
				return;
			}
			obj.showGateway( gateway );
		});
		obj.toggles.additional.addClass( obj.selectors.toggleOpen.className() ).attr( 'aria-selected', 'true' );
		obj.toggles.default.removeClass( obj.selectors.toggleOpen.className() ).show().attr( 'aria-hidden', 'false' );
	};

	/**
	 * Add toggle elements to DOM.
	 *
	 * @since 5.3.0
	 */
	obj.addToggles = () => {
		obj.toggles.default = $( obj.getDefaultToggleHTML() );
		obj.toggles.additional = $( obj.getAdditionalToggleHTML() );
		obj.toggles.default.insertBefore( obj.gateways[0] );
		obj.toggles.additional.insertBefore( obj.gateways[1] );
		obj.toggleEvents();
	};

	/**
	 * Get HTML for default gateway toggle.
	 *
	 * @since 5.3.0
     *
     * @return string HTML for toggle.
	 */
	obj.getDefaultToggleHTML = () => {
		return `<div class="${obj.selectors.toggle.className()}">` +
			`<button class="${obj.selectors.toggleButton.className()}">` +
			`${obj.text.default}` +
			`</button></div>`;
	};

	/**
	 * Get HTML for additional gateways toggle.
	 *
	 * @since 5.3.0
     *
     * @return string HTML for toggle.
	 */
	obj.getAdditionalToggleHTML = () => {
		return `<div class="${obj.selectors.toggle.className()}">` +
			`<button class="${obj.selectors.toggleButton.className()}">` +
			`${obj.text.additional}` +
			`</button></div>`;
	};

	/**
	 * Create toggle event handlers.
	 *
	 * @since 5.3.0
	 */
	obj.toggleEvents = () => {
		obj.toggles.default.find( 'button' ).on( 'click', obj.showDefault );
		obj.toggles.additional.find( 'button' ).on( 'click', obj.showAdditional );
	}

    // Initiate the toggles.
	obj.init();
})( jQuery, tribe.tickets.commerce.gateway.toggler );