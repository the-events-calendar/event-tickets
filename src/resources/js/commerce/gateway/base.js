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
 * @type {{billingLastName: string, billingEmail: string, cardZip: string, billingFirstName: string}}
 */
tribe.tickets.commerce.billing.selectors = {
	billingFirstName: '#tec-tc-gateway-stripe-billing-first-name > input',
	billingLastName: '#tec-tc-gateway-stripe-billing-last-name > input',
	billingEmail: '#tec-tc-gateway-stripe-billing-email > input',
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
	var selectors = tribe.tickets.commerce.billing.selectors;
	var zipCode = document.querySelector( selectors.cardZip );
	var firstName = document.querySelector( selectors.billingFirstName );
	var lastName = document.querySelector( selectors.billingLastName );
	var email = document.querySelector( selectors.billingEmail );

	if ( zipCode && zipCode.value.length > 0 ) { billing_details.address.postal_code = zipCode.value; }
	if ( email && email.value.length > 0 ) { billing_details.email = email.value; }

	billing_details.firstName = firstName.value || '';
	billing_details.lastName = lastName.value || '';

	billing_details.name = billing_details.firstName+' '+billing_details.lastName;
	billing_details.name.trim();

	if ( false === long ) {
		delete billing_details.firstName;
		delete billing_details.lastName;
	}

	return billing_details;
};
