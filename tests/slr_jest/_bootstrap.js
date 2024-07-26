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
	},
	ajax: {
		ajaxUrl: 'https://wordpress.test/wp-admin/admin-ajax.php',
		ajaxNonce: '1234567890',
	},
	utils: {
		localizedStrings: {
			layouts: {
				'delete-confirmation':
					'Are you sure you want to delete this layout?',
				'delete-failed': 'Failed to delete the layout.',
				'edit-confirmation':
					'This layout is associated with {count} events. Changes will impact all existing events and may affect the seating assignment of active ticket holders.',
			},
			'capacity-table': {
				'seats-row-label': 'Assigned seating',
			},
		},
	},
	frontend: {
		ticketsBlock: {
			objectName: 'tribe-tickets-seating-modal',
			seatTypeMap: [
				{
					id: 'general-admission-seat-type-uuid',
					tickets: [
						{
							ticketId: 23,
							name: 'Adult',
							price: 50,
							description: 'Adult, General Admission',
						},
						{
							ticketId: 89,
							name: 'Child',
							price: 30,
							description: 'Child, General Admission',
						},
					],
				},
				{
					id: 'vip-seat-type-uuid',
					tickets: [
						{
							ticketId: 66,
							name: 'VIP',
							price: 100,
							description: 'Best seats',
						},
					],
				},
			],
			labels: {
				oneTicket: '1 Ticket',
				multipleTickets: '{count} Tickets',
			},
			providerClass: 'TEC\\Tickets\\Commerce\\Module',
			postId: 23,
			ajaxUrl: 'https://wordpress.test/wp-admin/admin-ajax.php',
			ajaxNonce: '1234567890',
			ACTION_POST_RESERVATIONS: 'tec_tickets_seating_post_reservations',
			ACTION_CLEAR_RESERVATIONS: 'tec_tickets_seating_clear_reservations',
		},
		session: {
			ajaxUrl: 'https://wordpress.test/wp-admin/admin-ajax.php',
			ajaxNonce: '1234567890',
			ACTION_START: 'tec_tickets_seating_session_start',
			ACTION_SYNC: 'tec_tickets_seating_session_sync',
			ACTION_INTERRUPT_GET_DATA:
				'tec_tickets_seating_session_interrupt_get_data',
			ACTION_INTERRUPT: 'tec_tickets_seating_session_interrupt',
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
