/**
 * The sales window options of the classic ticket form.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment-timezone';
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { resolveSaleWindow } from '../sale-window';
import { readDateTime, readEventDates } from './event-dates';
import { formatHelperText } from './helper-text';
import { readRule, writeRule } from './rule';
import { getOutOfRangeBoundary, getWindowError, RELATIVE_VALUE_OUT_OF_RANGE } from './window-check';

const MODE_RELATIVE = 'relative';

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
 * The fields of the dates typed for a specific start and end.
 *
 * @since TBD
 *
 * @type {string}
 */
const SPECIFIC_DATE_FIELDS = '#ticket_start_date, #ticket_start_time, #ticket_end_date, #ticket_end_time';

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
		// A name list left undefined would replace `DateFormatter`'s English default.
		dateSettings: Object.fromEntries(
			[ 'days', 'daysShort', 'months', 'monthsShort' ]
				.filter( ( name ) => Array.isArray( dynamic[ name ] ) )
				.map( ( name ) => [ name, dynamic[ name ] ] )
		),
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
 * Shows an error under the sales window, marking the field it is about invalid, or clears both.
 *
 * The field is marked with `aria-invalid` rather than common's `tribe-validation-error` class: common validates the
 * form again after the save click and strips that class from every field it does not flag itself.
 *
 * @since TBD
 *
 * @param {string}      message                           The error, or an empty string to clear it.
 * @param {string|null} [fieldId='ticket_sales_end_mode'] The id of the field the error is about, or `null` for an
 *                                                        error that is not about a field.
 *
 * @return {HTMLElement|null} The field marked invalid, or `null` when none is.
 */
function showWindowError( message, fieldId = 'ticket_sales_end_mode' ) {
	const error = document.getElementById( 'ticket_sales_window_error' );

	if ( ! error ) {
		return null;
	}

	error.textContent = message;
	document.querySelectorAll( `[aria-describedby="${ error.id }"]` ).forEach( ( marked ) => {
		marked.removeAttribute( 'aria-invalid' );
		marked.removeAttribute( 'aria-describedby' );
	} );

	const field = '' === message || ! fieldId ? null : document.getElementById( fieldId );

	if ( ! field ) {
		return null;
	}

	field.setAttribute( 'aria-invalid', 'true' );
	field.setAttribute( 'aria-describedby', error.id );

	return field;
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

	const outOfRange = RELATIVE_VALUE_OUT_OF_RANGE === error ? getOutOfRangeBoundary( readRule( document ) ) : null;
	const marked = outOfRange
		? showWindowError( settings.text.relativeValueOutOfRange, `ticket_sales_${ outOfRange }_value` )
		: showWindowError( settings.text.invalidWindow );

	// The save button keeps the focus otherwise, away from the field that blocks the save.
	marked?.focus();
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
	showWindowError( text, text === settings?.text?.invalidWindow ? 'ticket_sales_end_mode' : null );
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

/*
 * `tickets.js` fires `pre-save-ticket.tribe` and `additionalValidation.tribe` on `#tribetickets` right before it
 * saves the ticket. The handlers are bound on that element rather than delegated from the document: Event Tickets Plus
 * stops the save event from bubbling, and the element itself is not replaced when the panels are.
 */
jQuery( () => {
	jQuery( '#tribetickets' )
		.on( 'pre-save-ticket.tribe', () => writeRule( document ) )
		.on( 'additionalValidation.tribe', validateSaleWindow );
	updateHelperText();
} );

jQuery( document ).on( 'change', EVENT_FIELDS, onWindowChange );
// `tickets.js` tells this script of a date picked from the calendar with the namespaced event, which a plain change fires too.
jQuery( document ).on(
	'change.tecRelativeSaleDates input',
	`${ RULE_FIELDS }, ${ SPECIFIC_DATE_FIELDS }`,
	onWindowChange
);

addAction( 'tec.tickets.admin.panels.refreshed', 'tec.tickets.relativeSaleDates', updateHelperText );
addAction( 'tec.tickets.admin.ticketSaveFailed', 'tec.tickets.relativeSaleDates', showServerError );
