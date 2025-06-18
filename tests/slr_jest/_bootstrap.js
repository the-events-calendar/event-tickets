// This file will be loaded by the Jest setup file.
import fs from 'fs';

// Localized data mocks.
global.tec = global.tec || {};
global.tec.tickets = global.tec.tickets || {};
global.tec.tickets.seating = {
	service: {},
	serviceData: {
		baseUrl: 'https://wordpress.test',
		mapsHomeUrl:
			'https://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layouts',
		layoutsHomeUrl:
			'https://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating&tab=layouts',
		associatedEventsUrl:
			'https://wordpress.test/wp-admin/admin.php?page=tec-tickets-seating-events',
	},
	ajax: {},
	ajaxData: {
		ajaxUrl: 'https://wordpress.test/wp-admin/admin-ajax.php',
		ajaxNonce: '1234567890',
		ACTION_FETCH_ATTENDEES: 'tec_tickets_seating_fetch_attendees',
		ACTION_ADD_NEW_LAYOUT: 'tec_tickets_seating_service_add_layout',
		ACTION_SEAT_TYPE_DELETED: 'tec_tickets_seating_seat_type_deleted',
		ACTION_DUPLICATE_LAYOUT: 'tec_tickets_seating_service_duplicate_layout',
		ACTION_GET_SEAT_TYPES_BY_LAYOUT_ID:
			'tec_tickets_seating_get_seat_types_by_layout_id',
	},
	utils: {},
	utilsData: {
		localizedStrings: {
			layouts: {
				'add-failed': 'Failed to add the layout.',
				'delete-confirmation':
					'Are you sure you want to delete this layout? This cannot be undone.',
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
		ticketsBlock: {},
		ticketsBlockData: {
			objectName: 'tribe-tickets-seating-modal',
			seatTypeMap: [
				{
					id: 'general-admission-seat-type-uuid',
					tickets: [
						{
							ticketId: 23,
							name: 'Adult',
							price: '$50',
							description: 'Adult, General Admission',
							dateInRange: true,
							priceValue: 50,
						},
						{
							ticketId: 89,
							name: 'Child',
							price: '$30',
							description: 'Child, General Admission',
							dateInRange: false,
							priceValue: 30,
						},
					],
				},
				{
					id: 'vip-seat-type-uuid',
					tickets: [
						{
							ticketId: 66,
							name: 'VIP',
							price: '$100',
							description: 'Best seats',
							dateInRange: true,
							priceValue: 100,
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
			sessionTimeout: 893,
		},
		session: {},
		sessionData: {
			ajaxUrl: 'https://wordpress.test/wp-admin/admin-ajax.php',
			ajaxNonce: '1234567890',
			checkoutGraceTime: 60,
			ACTION_START: 'tec_tickets_seating_session_start',
			ACTION_SYNC: 'tec_tickets_seating_session_sync',
			ACTION_INTERRUPT_GET_DATA:
				'tec_tickets_seating_session_interrupt_get_data',
			ACTION_INTERRUPT: 'tec_tickets_seating_session_interrupt',
			ACTION_PAUSE_TO_CHECKOUT:
				'tec_tickets_seating_timer_pause_to_checkout',
		},
	},
	currency: {},
	currencyData: {
		decimalSeparator: '.',
		decimalNumbers: 2,
		thousandSeparator: ',',
		position: 'prefix',
		symbol: '$',
	},
	admin: {
		seatsReport: {},
		seatsReportData: {
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
							priceValue: 50,
						},
						{
							ticketId: 89,
							name: 'Child',
							price: 30,
							description: 'Child, General Admission',
							priceValue: 30,
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
							priceValue: 100,
						},
					],
				},
			],
		},
	},
	layouts: {
		addLayoutModal: 'dialog_obj_tec-tickets-seating-layouts-modal',
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
 * @param {string}        documentName The name of the document to return.
 * @param {Function|null} transformer  A function that will be used to transform the HTML of the document if provided.
 *                                     The function will receive the HTML of the document as a parameter and should
 *                                     return the transformed HTML as a string, or the transformed document as a Document
 *                                     object.
 *
 * @return {Document} A real document element, built from the snapshot HTML.
 */
global.getTestDocument = function (documentName, transformer) {
	const validDocumentMap = {
		'layout-edit':
			'/../slr_integration/Admin/__snapshots__/Maps_Layout_Homepage_Test__test_layout_edit__0.snapshot.html',
		'layout-list':
			'/../slr_integration/Admin/__snapshots__/Maps_Layout_Homepage_Test__test_layouts_tab_card_listing__0.snapshot.html',
		'seats-report':
			'/../slr_integration/Orders/__snapshots__/Seats_Report_Test__test_render_page__2_tickets_3_attendees__0.snapshot.html',
		'seats-selection':
			'/../slr_integration/__snapshots__/Frontend_Test__should_replace_ticket_block_when_seating_is_enabled__two tickets__0.snapshot.html',
		'maps-list':
			'/../slr_integration/Admin/__snapshots__/Maps_Layout_Homepage_Test__test_maps_tab_card_listing__0.snapshot.html',
		'map-edit':
			'/../slr_integration/Admin/__snapshots__/Maps_Layout_Homepage_Test__test_map_edit__0.snapshot.html',
		timer: '/../slr_integration/Frontend/__snapshots__/Timer_Test__test_render_to_sync__0.snapshot.html',
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

	if (transformer) {
		const transformed = transformer(sourceHtml);

		if (transformed instanceof Document) {
			return transformed;
		}

		return new DOMParser().parseFromString(transformed, 'text/html');
	}

	return new DOMParser().parseFromString(sourceHtml, 'text/html');
};
