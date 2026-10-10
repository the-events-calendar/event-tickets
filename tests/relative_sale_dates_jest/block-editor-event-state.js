/**
 * Sets the globals the Ticket block script reads the event dates and its settings from, as the block editor has them.
 *
 * @since TBD
 */
const { combineReducers, createStore } = require( 'redux' );
const legacyTicketReducer = require( '@moderntribe/tickets/data/blocks/ticket/reducer' ).default;
const legacyActions = require( '@moderntribe/tickets/data/blocks/ticket/actions' );
const { UNIT_HOURS, UNIT_WEEKS } = require( '@tec/tickets/relative-sale-dates/rule-constants' );

/**
 * A timed event in a timezone other than the browser's, far enough ahead that its dates show the year.
 *
 * @type {{start: string, end: string, allDay: boolean, timeZone: string}}
 */
const DEFAULT_EVENT = {
	start: '2040-10-20 19:00:00',
	end: '2040-10-20 22:30:00',
	allDay: false,
	timeZone: 'America/Sao_Paulo',
};

/**
 * The data the server localizes the Ticket block script with, with the site's default formats and all-day times.
 *
 * @type {Object}
 */
const DEFAULT_DATA = {
	windowDefaults: {
		sales: {
			start: { mode: 'default', value: 2, unit: UNIT_WEEKS, anchor: 'start' },
			end: { mode: 'default', value: 1, unit: UNIT_HOURS, anchor: 'start' },
		},
		sale_price: {
			start: { mode: 'now', value: 2, unit: UNIT_WEEKS },
			end: { mode: 'relative', value: 1, unit: UNIT_WEEKS },
		},
	},
	timezones: {},
	allDay: { start: '00:00:00', end: '23:59:59', endDays: 0 },
	formats: { dateWithYear: 'F j, Y', dateNoYear: 'F j', time: 'g:i a' },
};

/**
 * Localizes the Ticket block script's data.
 *
 * @param {Object} overrides The data to change from the defaults.
 *
 * @return {void}
 */
function setBlockEditorData( overrides = {} ) {
	window.tec = window.tec || {};
	window.tec.tickets = { relativeSaleDates: { blockEditorData: { ...DEFAULT_DATA, ...overrides } } };
}

/**
 * Sets the event dates in the common store, with The Events Calendar's datetime selectors to read them.
 *
 * The selectors read the state the way The Events Calendar's do; its own are not installed here. The store holds the
 * legacy ticket state too, as the block editor's does.
 *
 * @param {Object} datetime The event `start`, `end`, `allDay` and `timeZone`.
 *
 * @return {Object} The common store, to change the event dates with `dispatch( { type: 'SET_DATETIME', datetime } )`.
 */
function setEventState( datetime ) {
	const events = ( state = { blocks: { datetime } }, action ) =>
		'SET_DATETIME' === action.type ? { blocks: { datetime: action.datetime } } : state;
	const reducer = combineReducers( {
		events,
		tickets: combineReducers( { blocks: combineReducers( { ticket: legacyTicketReducer } ) } ),
	} );
	const read = ( key ) => ( state ) => state.events.blocks.datetime[ key ];

	window.__tribe_common_store__ = createStore( reducer );

	window.tec = window.tec || {};
	window.tec.events = {
		app: {
			main: {
				data: {
					blocks: {
						datetime: {
							selectors: {
								getStart: read( 'start' ),
								getEnd: read( 'end' ),
								getAllDay: read( 'allDay' ),
								getTimeZone: read( 'timeZone' ),
							},
						},
					},
				},
			},
		},
	};

	return window.__tribe_common_store__;
}

/**
 * Registers a ticket block in the common store with the sale dates its form sends.
 *
 * @param {Object} store    The common store `setEventState()` returned.
 * @param {string} clientId The client ID of the ticket block.
 * @param {string} start    The sale start the form sends, `YYYY-MM-DD HH:mm:ss`.
 * @param {string} end      The sale end the form sends, `YYYY-MM-DD HH:mm:ss`.
 *
 * @return {void}
 */
function setTicketFormDates( store, clientId, start, end ) {
	const [ startDate, startTime ] = start.split( ' ' );
	const [ endDate, endTime ] = end.split( ' ' );

	store.dispatch( legacyActions.registerTicketBlock( clientId ) );
	store.dispatch( legacyActions.setTicketTempStartDate( clientId, startDate ) );
	store.dispatch( legacyActions.setTicketTempStartTime( clientId, startTime ) );
	store.dispatch( legacyActions.setTicketTempEndDate( clientId, endDate ) );
	store.dispatch( legacyActions.setTicketTempEndTime( clientId, endTime ) );
}

/**
 * Removes the globals the other helpers set.
 *
 * @return {void}
 */
function clearBlockEditorGlobals() {
	delete window.tec;
	delete window.__tribe_common_store__;
}

module.exports = { DEFAULT_EVENT, clearBlockEditorGlobals, setBlockEditorData, setEventState, setTicketFormDates };
