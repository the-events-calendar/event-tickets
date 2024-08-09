// This file will be loaded by the Jest setup file.
import fs from 'fs';

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
		ACTION_FETCH_ATTENDEES: 'tec_tickets_seating_fetch_attendees',
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
			ACTION_RESERVATION_CREATED:
				'tec_tickets_seating_reservation_created',
			ACTION_RESERVATION_UPDATED:
				'tec_tickets_seating_reservation_updated',
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
	admin: {
		seatsReport: {
			postId: 17,
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
		},
	},
};

// Utility functions

/**
 * @type {Object<string, string>} The map of memoized HTML files from document name to their HTML.
 */
const memoizedHtml = {};

/**
 * Returns a document built from an HTML snapshot provided by the `tests/slr_integration` suite.
 *
 * Note: this is flaky **by design**. The intention of this function is to use HTML snapshots that would
 * normally be used and updated by PHP tests to make sure the Javascript code working on them will keep working.
 * The side effect of changing something in PHP, or in a PHP test, and breaking Javascript tests is **desired**.
 * Resist the temptation to change this function to point to perfect HTML snapshots that would not be updated
 * as part of the normal PHP tests: it would make the purpose of this function moot.
 *
 * @param {string} documentName The name of the document to return.
 *
 * @return {Document} A real document element, built from the snapshot HTML.
 */
global.getTestDocument = function (documentName) {
	const validDocumentMap = {
		'layout-edit':
			'/../slr_integration/Admin/__snapshots__/Maps_Layout_Homepage_Test__test_empty_layouts_tab__0.snapshot.html',
	};

	if (!validDocumentMap[documentName]) {
		throw new Error(
			`Invalid document name: ${documentName}; Valid names are: ${Object.keys(
				validDocumentMap
			).join(', ')}`
		);
	}

	let sourceHtml;
	if (memoizedHtml[documentName]) {
		sourceHtml = memoizedHtml[documentName];
	} else {
		const sourceHtmlFile = validDocumentMap[documentName];
		sourceHtml = fs.readFileSync(__dirname + sourceHtmlFile, 'utf8');
	}
	memoizedHtml[documentName] = sourceHtml;

	return new DOMParser().parseFromString(sourceHtml, 'text/html');
};
