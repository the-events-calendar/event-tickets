// This file will be loaded by the Jest setup file.
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
};
