/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since TBD
 *
 * @type   {Object}
 */
 tribe.tickets = tribe.tickets || {};
 
 /**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since TBD
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * This script Object for public usage of the methods.
 *
 * @since TBD
 *
 * @type   {Object}
 */
(( $, obj ) => {

	/**
	 * Add the toggler object.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.toggler = {};

	/**
	 * Array of gateway elements.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.toggler.gateways = [];

	/**
	 * Object to store the toggle elements.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.toggler.toggles = {};

	/**
	 * Delay used for toggle show/hide effect.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.toggler.toggleDuration = 250;

	/**
	 * Toggler classes to be added/removed from different elements.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.toggler.classes = {
		toggle: 'tribe-tickets__commerce-checkout-gateway-toggle',
		toggleOpen: 'tribe-tickets__commerce-checkout-gateway-toggle--open',
		toggleButton: 'tribe-tickets__commerce-checkout-gateway-toggle-button',
	};

	/**
	 * Toggler selectors.
	 *
	 * @since TBD
	 *
	 * @type {Object}
	 */
	obj.toggler.selectors = {
		gatewayDiv: '.tribe-tickets__commerce-checkout-gateway',
		toggleButton: '.' + obj.toggler.classes.toggle + ' button',
	};

	/**
	 * Toggler init method.
	 *
	 * @since TBD
	 *
	 * @return 
	 */
	obj.toggler.init = () => {
		obj.toggler.gateways = $( obj.toggler.selectors.gatewayDiv );
		
		// If one or less gateways, go ahead and show and not add toggles.
		if( obj.toggler.gateways.length < 2 ){
			obj.toggler.gateways.show();
			return;
		}
		obj.toggler.addToggles();
		obj.toggler.showDefault();
	};

	/**
	 * Shows gateway.
	 *
	 * @since TBD
	 *
	 * @param {Element} gateway Gateway element to show.
	 */
	obj.toggler.showGateway = gateway => {
		$( gateway ).show( obj.toggler.toggleDuration );
	};

	/**
	 * Hides gateway.
	 *
	 * @since TBD
	 *
	 * @param {Element} gateway Gateway element to hide.
	 */
	obj.toggler.hideGateway = gateway => {
		$( gateway ).hide( obj.toggler.toggleDuration );
	};

	/**
	 * Show the default/first gateway.
	 *
	 * @since TBD
	 */
	obj.toggler.showDefault = () => {
		obj.toggler.showGateway( obj.toggler.gateways[0] );
		obj.toggler.gateways.each( ( x, gateway ) => {
			if( 0 === x ){
				return;
			}
			obj.toggler.hideGateway( gateway );
		});
		obj.toggler.toggles.default.addClass(obj.toggler.classes.toggleOpen).hide();
		obj.toggler.toggles.additional.removeClass(obj.toggler.classes.toggleOpen);
	};

	/**
	 * Show the additional (non-default) gateway(s).
	 *
	 * @since TBD
	 */
	obj.toggler.showAdditional = () => {
		obj.toggler.hideGateway( obj.toggler.gateways[0] );
		obj.toggler.gateways.each( ( x, gateway ) => {
			if( 0 === x ){
				return;
			}
			obj.toggler.showGateway( gateway );
		});
		obj.toggler.toggles.additional.addClass(obj.toggler.classes.toggleOpen);
		obj.toggler.toggles.default.removeClass(obj.toggler.classes.toggleOpen).show();
	};

	/**
	 * Add toggle elements to DOM.
	 *
	 * @since TBD
	 */
	obj.toggler.addToggles = () => {
		obj.toggler.toggles.default = $(obj.toggler.getDefaultToggleHTML());
		obj.toggler.toggles.additional = $(obj.toggler.getAdditionalToggleHTML());
		obj.toggler.toggles.default.insertBefore( obj.toggler.gateways[0] );
		obj.toggler.toggles.additional.insertBefore( obj.toggler.gateways[1] );
		obj.toggler.toggleEvents();
	};

	/**
	 * Get HTML for default gateway toggle.
	 *
	 * @since TBD
     * 
     * @return string HTML for toggle.
	 */
	obj.toggler.getDefaultToggleHTML = () => {
		return `<div class="${obj.toggler.classes.toggle}">` + 
			`<button class="${obj.toggler.classes.toggleButton}">` + 
			`${tecTicketsCommerceCheckoutToggleText.default}` + 
			`</button></div>`;
	};

	/**
	 * Get HTML for additional gateways toggle.
	 *
	 * @since TBD
     * 
     * @return string HTML for toggle.
	 */
	obj.toggler.getAdditionalToggleHTML = () => {
		return `<div class="${obj.toggler.classes.toggle}">` + 
			`<button class="${obj.toggler.classes.toggleButton}">` + 
			`${tecTicketsCommerceCheckoutToggleText.additional}` + 
			`</button></div>`;
	};

	/**
	 * Create toggle event handlers.
	 *
	 * @since TBD
	 */
	obj.toggler.toggleEvents = () => {
		obj.toggler.toggles.default.find('button').on( 'click', obj.toggler.showDefault );
		obj.toggler.toggles.additional.find('button').on( 'click', obj.toggler.showAdditional );
	}

    // Initiate the toggles.
	obj.toggler.init();
})( jQuery, tribe.tickets.commerce.gateway );