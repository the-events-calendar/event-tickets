/**
 * The sales window options of the classic ticket form.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import moment from 'moment';
import { addAction } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import '../zone-data';
import { createTypingTracker, getShownError } from '../live-error';
import { resolveSaleWindow } from '../sale-window';
import { updateDateLimit } from './date-limits';
import { readDateTime, readEventDates } from './event-dates';
import { formatHelperText } from './helper-text';
import { readRule, writeRule } from './rule';
import { getListText } from './tickets-list';
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
 * The fields hold the dates as the admin last set them, saved or not, so the window is judged against the event the
 * form shows. The server judges a ticket save against the event as saved: a ticket saved before the event's new dates
 * can be accepted here and rejected there, which shows the server's message.
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
 * Returns whether the number of a relative end is one the save accepts, within the range its field allows.
 *
 * @since TBD
 *
 * @param {string} key   The end of the window, `start` or `end`.
 * @param {number} value The number the rule holds.
 *
 * @return {boolean} Whether the number is a whole number within the field's `min` and `max`.
 */
function isInRange( key, value ) {
	const field = document.getElementById( `ticket_sales_${ key }_value` );

	return (
		Number.isInteger( value ) && Boolean( field ) && value >= Number( field.min ) && value <= Number( field.max )
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
		// A number the save rejects has no date to show.
		const isRelative = MODE_RELATIVE === end.mode && isInRange( key, end.value );

		// The year is now's in the event timezone, as the block editor reads it.
		const text =
			isRelative && date && date.isValid()
				? formatHelperText( settings.text[ key ], date, formats, moment().utcOffset( date.utcOffset() ).year() )
				: '';
		const helper = document.getElementById( `ticket_sales_${ key }_helper` );

		// The helper is a live region: writing the same text again can announce it again.
		if ( helper.textContent !== text ) {
			helper.textContent = text;
		}
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
 * Removes an id from a field's `aria-describedby`, keeping every other description, such as its helper text.
 *
 * @since TBD
 *
 * @param {HTMLElement} field The field.
 * @param {string}      id    The id to remove.
 *
 * @return {void}
 */
function removeDescription( field, id ) {
	const ids = ( field.getAttribute( 'aria-describedby' ) || '' )
		.split( /\s+/ )
		.filter( ( token ) => token && token !== id );

	if ( ids.length ) {
		field.setAttribute( 'aria-describedby', ids.join( ' ' ) );
	} else {
		field.removeAttribute( 'aria-describedby' );
	}
}

/**
 * Adds an id to a field's `aria-describedby`, ahead of its other descriptions so the error is read first.
 *
 * @since TBD
 *
 * @param {HTMLElement} field The field.
 * @param {string}      id    The id to add.
 *
 * @return {void}
 */
function addDescription( field, id ) {
	removeDescription( field, id );
	field.setAttribute(
		'aria-describedby',
		[ id, field.getAttribute( 'aria-describedby' ) ].filter( Boolean ).join( ' ' )
	);
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
	document.querySelectorAll( `[aria-describedby~="${ error.id }"]` ).forEach( ( marked ) => {
		marked.removeAttribute( 'aria-invalid' );
		removeDescription( marked, error.id );
	} );

	const field = '' === message || ! fieldId ? null : document.getElementById( fieldId );

	if ( ! field ) {
		return null;
	}

	field.setAttribute( 'aria-invalid', 'true' );
	addDescription( field, error.id );

	return field;
}

/**
 * Reads why the sales window in the form cannot be saved.
 *
 * @since TBD
 *
 * @return {{message: string, fieldId: string}|null} The error and the id of the field it is about, or `null` for a
 *                                                   window that can be saved or cannot be judged here.
 */
function readSaleWindowError() {
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;

	if ( ! settings || ! dynamic || ! document.getElementById( 'ticket_sales_window_error' ) ) {
		return null;
	}

	const eventDates = getEventDates( settings, dynamic );

	// Without event dates the window cannot be judged here; the server still checks it.
	if ( ! eventDates ) {
		return null;
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
		return null;
	}

	const outOfRange = RELATIVE_VALUE_OUT_OF_RANGE === error ? getOutOfRangeBoundary( readRule( document ) ) : null;

	return outOfRange
		? { message: settings.text.relativeValueOutOfRange, fieldId: `ticket_sales_${ outOfRange }_value` }
		: { message: settings.text.invalidWindow, fieldId: 'ticket_sales_end_mode' };
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
	const error = readSaleWindowError();

	if ( ! error ) {
		showWindowError( '' );

		return answer;
	}

	// The save button keeps the focus otherwise, away from the field that blocks the save.
	showWindowError( error.message, error.fieldId )?.focus();
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
	const isWindowError = Boolean( response?.data?.code ) && response.data.code === settings?.errorCodes?.invalidWindow;

	showWindowError( text, isWindowError ? 'ticket_sales_end_mode' : null );
}

/**
 * Whether the admin is typing in a sales window field, checking the window again once they stop.
 *
 * @since TBD
 *
 * @type {{type: function(): void, settle: function(): void, isTyping: function(): boolean}}
 */
const typing = createTypingTracker( onWindowChange );

/**
 * Whether a change of a window field ends the admin's edit: an option picked, or a time picked, which the picker fires
 * itself.
 *
 * A typed field fires its own change once it loses the focus, which does not: a new error shown then moves the form
 * under the click that took the focus away, and the click misses.
 *
 * @since TBD
 *
 * @param {jQuery.Event} event The change event.
 *
 * @return {boolean} Whether the change ends the edit.
 */
function endsEdit( event ) {
	return ! event.originalEvent || ! [ 'text', 'number' ].includes( event.target.type );
}

/**
 * Updates the helper text and checks the sales window again once a field the window depends on changes, so an error
 * shows as the admin makes it rather than on the save click.
 *
 * The focus stays where the admin is typing; only the save click moves it to the field that blocks the save.
 *
 * @since TBD
 *
 * @return {void}
 */
function onWindowChange() {
	const shown = getShownError(
		document.getElementById( 'ticket_sales_window_error' )?.textContent,
		readSaleWindowError(),
		typing.isTyping()
	);

	showWindowError( shown ? shown.message : '', shown?.fieldId );
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
		.on( 'pre-save-ticket.tribe', () => writeRule( document ) )
		.on( 'additionalValidation.tribe', validateSaleWindow )
		.on( 'change.tribe', 'select', ( event ) => updateDateLimit( document, event.target.id ) );
	onPanelsRefreshed();
} );

jQuery( document ).on( 'change', EVENT_FIELDS, onEventChange );
jQuery( document ).on( 'input', `${ RULE_FIELDS }, ${ SPECIFIC_DATE_FIELDS }`, () => {
	typing.type();
	onWindowChange();
} );
jQuery( document ).on( 'change', `${ RULE_FIELDS }, ${ SPECIFIC_DATE_FIELDS }`, ( event ) => {
	if ( endsEdit( event ) ) {
		typing.settle();
	}

	onWindowChange();
} );

addAction( 'tec.tickets.admin.panels.refreshed', 'tec.tickets.relativeSaleDates', onPanelsRefreshed );
addAction( 'tec.tickets.admin.ticketSaveFailed', 'tec.tickets.relativeSaleDates', showServerError );
// A date picked from the calendar ends the edit of its field.
addAction( 'tec.tickets.admin.ticketDatePicked', 'tec.tickets.relativeSaleDates', ( input ) => {
	if ( input?.matches?.( SPECIFIC_DATE_FIELDS ) ) {
		typing.settle();
		onWindowChange();
	}
} );
