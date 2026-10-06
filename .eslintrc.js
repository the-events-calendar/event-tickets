const eslintConfig = require( '@wordpress/scripts/config/.eslintrc.js' );

module.exports = {
	...eslintConfig,
	env: {
		...eslintConfig.env,
		browser: true,
	},
	overrides: [
		...eslintConfig.overrides,
	],
	/*
	 * Legacy scripts use PHP-localized snake_case names, console debugging and native confirm()/alert() on purpose.
	 * Released msgids use three dots (see lang/*.pot and the PHP views), so i18n-ellipsis would change them.
	 */
	rules: {
		...eslintConfig.rules,
		'no-alert': 'off',
		camelcase: 'off',
		'no-console': 'off',
		'@wordpress/i18n-ellipsis': 'off',
		'import/no-unresolved': [ 'error', { ignore: [ '^@wordpress/', '^@tec/', '\\.pcss$' ] } ],
	},
	/* React is a WordPress-provided external (window.React / window.ReactDOM), so it is not a package dependency. */
	settings: {
		...eslintConfig.settings,
		'import/core-modules': [ ...( eslintConfig.settings?.[ 'import/core-modules' ] || [] ), 'react', 'react-dom' ],
	},
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
		paypal: true,
		_: true,
		tribe_move_tickets_data: true,
		TecRsvp: true,
		HeaderImageData: true,
		tec: true,
		tribe_l10n_datatables: true,
		tecTicketsCommerceCheckoutToggleText: true,
		tb_show: true,
		tribe_global_stock_admin_ui: true,
		tribe_ticket_notices: true,
		price_format: true
	},
};
