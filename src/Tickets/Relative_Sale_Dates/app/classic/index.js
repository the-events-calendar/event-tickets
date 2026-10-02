/**
 * The sales window and sale price options of the classic ticket form.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment-timezone';
import { addAction } from '@wordpress/hooks';
import { _n } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MODE_RELATIVE, UNIT_DAYS, UNIT_HOURS, UNIT_MINUTES, UNIT_WEEKS } from '../rule-constants';
import { resolveSaleWindow } from '../sale-window';
import { getWindowError } from '../window-check';
import { readDateTime, readEventDates } from './event-dates';
import { formatHelperText } from './helper-text';
import { readRule, writeRule } from './rule';
import { writeSalePriceRule } from './sale-price-rule';
import { getListText } from './tickets-list';

/**
 * The TEC event fields the event dates are read from.
 *
 * @since TBD
 *
 * @type {string}
 */
const EVENT_FIELDS = '#EventStartDate, #EventStartTime, #EventEndDate, #EventEndTime, #allDayCheckbox, #event-timezone';

/**
 * The sales window fields a relative end is read from.
 *
 * @since TBD
 *
 * @type {string}
 */
const RULE_FIELDS = [ 'start', 'end' ]
	.flatMap( ( key ) => [ 'mode', 'value', 'unit', 'anchor' ].map( ( field ) => `#ticket_sales_${ key }_${ field }` ) )
	.join( ', ' );

/**
 * The name of each unit for a number of it, keyed by the unit in seconds. The msgids match the template's.
 *
 * @since TBD
 *
 * @type {Object<string, function( number ): string>}
 */
const UNIT_NAMES = {
	[ UNIT_MINUTES ]: ( number ) => _n( 'minute', 'minutes', number, 'event-tickets' ),
	[ UNIT_HOURS ]: ( number ) => _n( 'hour', 'hours', number, 'event-tickets' ),
	[ UNIT_DAYS ]: ( number ) => _n( 'day', 'days', number, 'event-tickets' ),
	[ UNIT_WEEKS ]: ( number ) => _n( 'week', 'weeks', number, 'event-tickets' ),
};

/**
 * The fields of the dates typed for a specific start and end.
 *
 * @since TBD
 *
 * @type {string}
 */
const SPECIFIC_DATE_FIELDS = '#ticket_start_date, #ticket_start_time, #ticket_end_date, #ticket_end_time';

/**
 * The id prefixes of the relative fields whose unit names follow the number typed: the ends of the sales window, then
 * the boundaries of the sale price window.
 *
 * @since TBD
 *
 * @type {string[]}
 */
const RELATIVE_FIELD_PREFIXES = [ 'ticket_sales_start', 'ticket_sales_end', 'ticket_sale_start', 'ticket_sale_end' ];

/**
 * Reads the event dates from the TEC event fields.
 *
 * @since TBD
 *
 * @param {Object} settings The data this script is localized with.
 * @param {Object} dynamic  The date formats TEC localizes for its own helper text.
 *
 * @return {import( './event-dates' ).EventDates|null} The event dates, or `null` when they cannot be read.
 */
function getEventDates( settings, dynamic ) {
	const field = ( id ) => document.getElementById( id );

	return readEventDates(
		{
			startDate: field( 'EventStartDate' )?.value,
			startTime: field( 'EventStartTime' )?.value,
			endDate: field( 'EventEndDate' )?.value,
			endTime: field( 'EventEndTime' )?.value,
			allDay: Boolean( field( 'allDayCheckbox' )?.checked ),
			timezone: field( 'event-timezone' )?.value,
		},
		{
			datepickerFormat: dynamic.datepicker_format,
			timezones: settings.timezones,
			allDay: settings.allDay,
		}
	);
}

/**
 * Gets the translated day and month names TEC localizes, for `DateFormatter`.
 *
 * @since TBD
 *
 * @param {Object} dynamic The date formats and names TEC localizes for its own helper text.
 *
 * @return {Object} The names that were localized.
 */
function getDateSettings( dynamic ) {
	// A name list left undefined would replace `DateFormatter`'s English default.
	return Object.fromEntries(
		[ 'days', 'daysShort', 'months', 'monthsShort' ]
			.filter( ( name ) => Array.isArray( dynamic[ name ] ) )
			.map( ( name ) => [ name, dynamic[ name ] ] )
	);
}

/**
 * Writes, under each relative end of the sales window, the date it works out to for the event dates in the form.
 *
 * @since TBD
 *
 * @return {void}
 */
function updateHelperText() {
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;

	if ( ! settings || ! dynamic || ! document.getElementById( 'ticket_sales_start_helper' ) ) {
		return;
	}

	const rule = readRule( document );
	const eventDates = getEventDates( settings, dynamic );
	const saleWindow = eventDates
		? resolveSaleWindow( rule, eventDates.start, eventDates.end, eventDates.timezone )
		: null;
	const formats = {
		dateWithYear: settings.dateWithYear,
		dateNoYear: settings.dateNoYear,
		timeFormat: settings.timeFormat,
		dateSettings: getDateSettings( dynamic ),
	};

	[ 'start', 'end' ].forEach( ( key ) => {
		const end = rule[ key ];
		const date = saleWindow ? saleWindow[ key ] : null;
		const isRelative = MODE_RELATIVE === end.mode && Number.isInteger( end.value );

		document.getElementById( `ticket_sales_${ key }_helper` ).textContent =
			isRelative && date && date.isValid()
				? formatHelperText( settings.text[ key ], date, formats, moment().year() )
				: '';
	} );
}

/**
 * Names the units of each relative end in the plural form of the number typed for it.
 *
 * @since TBD
 *
 * @return {void}
 */
function updateUnitNames() {
	RELATIVE_FIELD_PREFIXES.forEach( ( prefix ) => {
		const value = document.getElementById( `${ prefix }_value` );
		const unit = document.getElementById( `${ prefix }_unit` );

		if ( ! value || ! unit ) {
			return;
		}

		const number = parseInt( value.value, 10 );

		Array.from( unit.options ).forEach( ( option ) => {
			const name = UNIT_NAMES[ option.value ];

			if ( name ) {
				option.textContent = name( Number.isNaN( number ) ? 2 : number );
			}
		} );
	} );
}

/**
 * Rewrites the sale dates of each listed ticket with a rule for the event dates in the form.
 *
 * @since TBD
 *
 * @return {void}
 */
function updateTicketsList() {
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;
	const rows = document.querySelectorAll( '[data-relative-sale-dates]' );

	if ( ! settings || ! dynamic || ! rows.length ) {
		return;
	}

	const eventDates = getEventDates( settings, dynamic );
	const listSettings = { format: settings.listDateFormat, dateSettings: getDateSettings( dynamic ) };

	rows.forEach( ( row ) => {
		const rule = JSON.parse( row.dataset.relativeSaleDates );
		const saleWindow = eventDates
			? resolveSaleWindow( rule, eventDates.start, eventDates.end, eventDates.timezone )
			: null;

		row.textContent = getListText(
			rule,
			saleWindow,
			{ start: row.dataset.saleStart, end: row.dataset.saleEnd || '' },
			listSettings
		);
	} );
}

/**
 * Shows an error under the sales window, marking its end invalid when the error is about the window, or clears both.
 *
 * The end is marked with `aria-invalid` rather than common's `tribe-validation-error` class: common validates the form
 * again after the save click and strips that class from every field it does not flag itself.
 *
 * @since TBD
 *
 * @param {string}  message        The error, or an empty string to clear it.
 * @param {boolean} [markEnd=true] Whether the error is about the sales window, so its end is marked invalid.
 *
 * @return {void}
 */
function showWindowError( message, markEnd = true ) {
	const error = document.getElementById( 'ticket_sales_window_error' );
	const endMode = document.getElementById( 'ticket_sales_end_mode' );

	if ( ! error || ! endMode ) {
		return;
	}

	error.textContent = message;

	if ( '' === message || ! markEnd ) {
		endMode.removeAttribute( 'aria-invalid' );
		endMode.removeAttribute( 'aria-describedby' );

		return;
	}

	endMode.setAttribute( 'aria-invalid', 'true' );
	endMode.setAttribute( 'aria-describedby', error.id );
}

/**
 * Blocks the ticket save when the sales window does not start before it ends.
 *
 * `tickets.js` keeps only the last answer of the `additionalValidation.tribe` handlers, and Event Tickets Plus answers
 * with the value it was passed. So an invalid window stops the handlers after this one, and a valid one keeps the
 * answer of a handler before it.
 *
 * @since TBD
 *
 * @param {jQuery.Event} event The validation event.
 * @param {boolean}      valid Whether the ticket is valid so far.
 *
 * @return {boolean} Whether the ticket can be saved.
 */
function validateSaleWindow( event, valid ) {
	const answer = undefined === event.result ? valid : event.result;
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;

	if ( ! settings || ! dynamic || ! document.getElementById( 'ticket_sales_window_error' ) ) {
		return answer;
	}

	const eventDates = getEventDates( settings, dynamic );

	// Without event dates the window cannot be judged here; the server still checks it.
	if ( ! eventDates ) {
		return answer;
	}

	// A disabled field, like the date a Now start hides, is not sent, so the server does not judge it either.
	const field = ( id ) => {
		const input = document.getElementById( id );

		return input && ! input.disabled ? input.value : undefined;
	};
	const error = getWindowError( readRule( document ), eventDates, {
		start: readDateTime( field( 'ticket_start_date' ), field( 'ticket_start_time' ), dynamic.datepicker_format ),
		end: readDateTime( field( 'ticket_end_date' ), field( 'ticket_end_time' ), dynamic.datepicker_format ),
	} );

	if ( ! error ) {
		showWindowError( '' );

		return answer;
	}

	showWindowError( settings.text.invalidWindow );
	event.stopImmediatePropagation();

	return false;
}

/**
 * Shows the message of a ticket save the server rejected.
 *
 * @since TBD
 *
 * @param {Object} response The `tribe-ticket-add` response.
 *
 * @return {void}
 */
function showServerError( response ) {
	const message = response?.data?.message;

	if ( 'string' !== typeof message || '' === message ) {
		return;
	}

	// The server escapes the message for HTML; the error element takes text.
	const text = new window.DOMParser().parseFromString( message, 'text/html' ).documentElement.textContent;
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;

	// Only the sales window error is about the window; another reason is shown without marking the window invalid.
	showWindowError( text, text === settings?.text?.invalidWindow );
}

/**
 * Updates the helper text and clears a sales window error once a field the window depends on changes.
 *
 * @since TBD
 *
 * @return {void}
 */
function onWindowChange() {
	showWindowError( '' );
	updateHelperText();
}

/**
 * Updates everything that follows the event dates once an event field changes.
 *
 * @since TBD
 *
 * @return {void}
 */
function onEventChange() {
	onWindowChange();
	updateTicketsList();
}

/**
 * Updates everything that follows the event dates once the editor replaces the panels.
 *
 * @since TBD
 *
 * @return {void}
 */
function onPanelsRefreshed() {
	updateUnitNames();
	updateHelperText();
	updateTicketsList();
}

/*
 * `tickets.js` fires `pre-save-ticket.tribe` and `additionalValidation.tribe` on `#tribetickets` right before it
 * saves the ticket. The handlers are bound on that element rather than delegated from the document: Event Tickets Plus
 * stops the save event from bubbling, and the element itself is not replaced when the panels are.
 */
jQuery( () => {
	jQuery( '#tribetickets' )
		.on( 'pre-save-ticket.tribe', () => {
			writeRule( document );
			writeSalePriceRule( document );
		} )
		.on( 'additionalValidation.tribe', validateSaleWindow );
	onPanelsRefreshed();
} );

jQuery( document ).on( 'change', EVENT_FIELDS, onEventChange );
jQuery( document ).on( 'change input', `${ RULE_FIELDS }, ${ SPECIFIC_DATE_FIELDS }`, onWindowChange );
jQuery( document ).on(
	'change input',
	RELATIVE_FIELD_PREFIXES.map( ( prefix ) => `#${ prefix }_value` ).join( ', ' ),
	updateUnitNames
);

addAction( 'tec.tickets.admin.panels.refreshed', 'tec.tickets.relativeSaleDates', onPanelsRefreshed );
addAction( 'tec.tickets.admin.ticketSaveFailed', 'tec.tickets.relativeSaleDates', showServerError );
