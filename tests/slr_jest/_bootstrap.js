// This file will be loaded by the Jest setup file.

// Localized data mocks.
global.tec = global.tec || {};
global.tec.tickets = global.tec.tickets || {};
global.tec.tickets.seating = {
	service: {
		baseUrl: 'https://wordpress.test',
		mapsHomeUrl:
			'https://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layouts',
		layoutsHomeUrl:
			'https://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layouts',
		ajaxUrl: 'https://wordpress.test/wp-admin/admin-ajax.php',
		ajaxNonce: '1234567890',
	},
	frontend: {
		ticketsBlock: {
			objectName: 'tribe-tickets-seating-modal',
			seatTypeMap: {},
			labels: {},
			providerClass: 'TEC\\Tickets\\Commerce\\Module',
			postId: 23,
		},
	},
	currency: {
		decimalSeparator: '.',
		decimalNumbers: 2,
		thousandSeparator: ',',
		position: 'prefix',
		symbol: '$',
	},
};
