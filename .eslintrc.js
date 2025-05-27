const eslintConfig = require( '@wordpress/scripts/config/.eslintrc.js' );

module.exports = {
	...eslintConfig,
	overrides: [
		...eslintConfig.overrides,
	],
	globals: {
		...eslintConfig.globals,
		wp: true,
		jQuery: true,
		tribe: true,
		mount: true,
		shallow: true,
		renderer: true,
		React: true,
		ajaxurl: true,
		Give: true,
		Qs: true,
		TribeCartEndpoint: true,
		TribeCurrency: true,
		TribeMessages: true,
		TribeRsvp: true,
		TribeTicketOptions: true,
		TribeTicketsAdminManager: true,
		TribeTicketsURLs: true,
		tecTicketsCommerceData: true,
		tecTicketsCommerceGatewayPayPalCheckout: true,
		paypal: true
	},
};
