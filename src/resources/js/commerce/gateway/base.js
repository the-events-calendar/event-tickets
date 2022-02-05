/**
 * Makes sure we have all the required levels on the Tribe Object
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets = tribe.tickets || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce = tribe.tickets.commerce || {};

/**
 * Path to this script in the global tribe Object.
 *
 * @since 5.1.9
 *
 * @type   {Object}
 */
tribe.tickets.commerce.gateway = tribe.tickets.commerce.gateway || {};

/**
 * Path to the billing information in the global tribe Object
 *
 * @since TBD
 *
 * @type {Object}
 */
tribe.tickets.commerce.billing = {};

/**
 * Selectors used to store billing information
 *
 * @since TBD
 *
 * @type {{billingEmail: string, cardZip: string, billingName: string}}
 */
tribe.tickets.commerce.billing.selectors = {
	billingName: '#tec-tc-gateway-stripe-billing-name-input',
	billingEmail: '#tec-tc-gateway-stripe-billing-email-input',
	cardZip: '#tec-tc-gateway-stripe-card-zip > input',
}

/**
 * Retrieve billing information from the inputs on the checkout page
 *
 * @param bool long retrieve information in the long form (true) or short form (false)
 *
 * @returns {Object}
 */
tribe.tickets.commerce.billing.getDetails = function( long ) {
	var billing_details = {}
	billing_details.address = {};
	var selectors = tribe.tickets.commerce.billing.selectors;
	var zipCode = document.querySelector( selectors.cardZip );
	var name = document.querySelector( selectors.billingName );
	var nameParts = name.split(' ');
	var firstName = nameParts.shift();
	var lastName = nameParts.join(' ');
	var email = document.querySelector( selectors.billingEmail );

	if ( zipCode && zipCode.value.length > 0 ) { billing_details.address.postal_code = zipCode.value; }
	if ( email && email.value.length > 0 ) { billing_details.email = email.value; }

	billing_details.first_name = firstName.value || '';
	billing_details.last_name = lastName.value || '';

	billing_details.name = billing_details.first_name+' '+billing_details.last_name;
	billing_details.name.trim();

	if ( false === long ) {
		delete billing_details.first_name;
		delete billing_details.last_name;
	}

	return billing_details;
};

(( $, obj ) => {
	obj.toggler = obj.toggler || {};
	obj.toggler.gateways = [];
	obj.toggler.toggles = {};
	obj.toggler.toggleDuration = 250;
	obj.toggler.classes = {
		toggle: 'tribe-tickets__commerce-checkout-gateway-toggle',
		toggleOpen: 'tribe-tickets__commerce-checkout-gateway-toggle--open',
		toggleButton: 'tribe-tickets__commerce-checkout-gateway-toggle-button',
	};
	obj.toggler.selectors = {
		gatewayDiv: '.tribe-tickets__commerce-checkout-gateway',
		toggleButton: '.' + obj.toggler.classes.toggle + ' button',
	};
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
	obj.toggler.showGateway = gateway => {
		$( gateway ).show( obj.toggler.toggleDuration );
	};
	obj.toggler.hideGateway = gateway => {
		$( gateway ).hide( obj.toggler.toggleDuration );
	};
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
	obj.toggler.addToggles = () => {
		obj.toggler.toggles.default = $(obj.toggler.getDefaultToggleHTML());
		obj.toggler.toggles.additional = $(obj.toggler.getAdditionalToggleHTML());
		obj.toggler.toggles.default.insertBefore( obj.toggler.gateways[0] );
		obj.toggler.toggles.additional.insertBefore( obj.toggler.gateways[1] );
		obj.toggler.toggleEvents();
	};
	obj.toggler.getDefaultToggleHTML = () => {
		return `<div class="${obj.toggler.classes.toggle}">` + 
			`<button class="${obj.toggler.classes.toggleButton}">` + 
			`${tecTicketsCommerceCheckoutToggleText.default}` + 
			`</button></div>`;
	};
	obj.toggler.getAdditionalToggleHTML = () => {
		return `<div class="${obj.toggler.classes.toggle}">` + 
			`<button class="${obj.toggler.classes.toggleButton}">` + 
			`${tecTicketsCommerceCheckoutToggleText.additional}` + 
			`</button></div>`;
	};
	obj.toggler.toggleEvents = () => {
		obj.toggler.toggles.default.find('button').on( 'click', obj.toggler.showDefault );
		obj.toggler.toggles.additional.find('button').on( 'click', obj.toggler.showAdditional );
	}
	obj.toggler.init();
})( jQuery, tribe.tickets.commerce.gateway );