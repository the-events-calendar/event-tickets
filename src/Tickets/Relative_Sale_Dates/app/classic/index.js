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
import { _nx } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MODE_RELATIVE, UNIT_DAYS, UNIT_HOURS, UNIT_MINUTES, UNIT_WEEKS } from '../rule-constants';
import { resolveSaleWindow } from '../sale-window';
import {
	ENDS_BEFORE_START,
	getFormWindow,
	getOutOfRangeBoundary,
	getWindowError,
	OUTSIDE_PARENT,
	RELATIVE_VALUE_OUT_OF_RANGE,
} from '../window-check';
import { SALES_WINDOW, WINDOW_KINDS } from '../window-kinds';
import { getWindowLengthText } from '../window-length';
import { readDateTime, readEventDates } from './event-dates';
import { formatHelperText } from './helper-text';
import { readRule, writeRule } from './rule';
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
 * The name of each unit for a number of it, keyed by the unit in seconds. The msgids and context match the template's.
 *
 * @since TBD
 *
 * @type {Object<string, function( number ): string>}
 */
const UNIT_NAMES = {
	[ UNIT_MINUTES ]: ( number ) =>
		_nx( 'minute', 'minutes', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_HOURS ]: ( number ) =>
		_nx( 'hour', 'hours', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_DAYS ]: ( number ) => _nx( 'day', 'days', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
	[ UNIT_WEEKS ]: ( number ) =>
		_nx( 'week', 'weeks', number, 'Unit of a relative ticket sale date.', 'event-tickets' ),
};

/**
 * The number fields of the relative boundaries of every window, whose unit names follow the number typed.
 *
 * @since TBD
 *
 * @type {string}
 */
const RELATIVE_VALUE_FIELDS = WINDOW_KINDS.flatMap( ( kind ) =>
	[ 'start', 'end' ].map( ( key ) => `#${ kind.fieldPrefix }_${ key }_value` )
).join( ', ' );

/**
 * The key of each error's text among a kind's localized texts: the server's own key for an error it rejects a save
 * with.
 *
 * @since TBD
 *
 * @type {Object<string, string>}
 */
const TEXT_KEYS = Object.freeze( {
	[ ENDS_BEFORE_START ]: 'endsBeforeStart',
	[ OUTSIDE_PARENT ]: 'outsideParent',
	[ RELATIVE_VALUE_OUT_OF_RANGE ]: 'valueOutOfRange',
} );

/**
 * The keys of the texts of the errors the server rejects a save with.
 *
 * @since TBD
 *
 * @type {string[]}
 */
const SERVER_TEXT_KEYS = [ 'endsBeforeStart', 'outsideParent' ];

/**
 * The kinds whose window the form says the length of.
 *
 * @since TBD
 *
 * @type {import( '../window-kinds' ).WindowKind[]}
 */
const LENGTH_KINDS = WINDOW_KINDS.filter( ( kind ) => kind.lengthId );

/**
 * Gets the selector of every field a window of a kind is read from: the mode and relative fields of each boundary, the
 * fields of its specific date, and the field that adds the window to the ticket.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind The kind of the window.
 *
 * @return {string} The selector.
 */
function getFieldSelector( kind ) {
	const ruleFields = kind.takesAnchor ? [ 'mode', 'value', 'unit', 'anchor' ] : [ 'mode', 'value', 'unit' ];

	return [ 'start', 'end' ]
		.flatMap( ( key ) => [
			...ruleFields.map( ( field ) => `${ kind.fieldPrefix }_${ key }_${ field }` ),
			kind.dateFields[ key ].date,
			kind.dateFields[ key ].time,
		] )
		.concat( kind.enabledFieldId )
		.filter( Boolean )
		.map( ( id ) => `#${ id }` )
		.join( ', ' );
}

/**
 * Reads the dates the form sends for the specific boundaries of a window of a kind.
 *
 * A disabled field, like the date a Now start hides, is not sent, so the server does not judge it either. A date sent
 * that cannot be read is told apart from one not sent: the server rejects it rather than leave the boundary without a
 * date.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind    The kind of the window.
 * @param {Object}                                 dynamic The date formats TEC localizes for its own helper text.
 *
 * @return {{start: string|null|false, end: string|null|false}} Each date, `YYYY-MM-DD HH:mm:ss`, `null` when it is not
 *                                                               sent, or `false` when it cannot be read.
 */
function readFormDates( kind, dynamic ) {
	const field = ( id ) => {
		const input = id ? document.getElementById( id ) : null;

		return input && ! input.disabled ? input.value : undefined;
	};
	const read = ( key ) => {
		const date = field( kind.dateFields[ key ].date );

		if ( '' === ( date || '' ).trim() ) {
			return null;
		}

		return readDateTime( date, field( kind.dateFields[ key ].time ), dynamic.datepicker_format ) ?? false;
	};

	return { start: read( 'start' ), end: read( 'end' ) };
}

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
 * Writes how long the window of a kind lasts for the event dates in the form.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind The kind of the window.
 *
 * @return {void}
 */
function updateWindowLength( kind ) {
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;
	const length = document.getElementById( kind.lengthId );

	if ( ! settings || ! dynamic || ! length ) {
		return;
	}

	length.textContent = getWindowLengthText(
		getFormWindow(
			readRule( document, kind ),
			getEventDates( settings, dynamic ),
			readFormDates( kind, dynamic ),
			kind
		),
		kind
	);
}

/**
 * Writes how long the window of every kind that shows its length lasts.
 *
 * @since TBD
 *
 * @return {void}
 */
function updateWindowLengths() {
	LENGTH_KINDS.forEach( updateWindowLength );
}

/**
 * Names the units of each relative end of every window in the plural form of the number typed for it.
 *
 * @since TBD
 *
 * @return {void}
 */
function updateUnitNames() {
	WINDOW_KINDS.forEach( ( kind ) => {
		[ 'start', 'end' ].forEach( ( key ) => {
			const value = document.getElementById( `${ kind.fieldPrefix }_${ key }_value` );
			const unit = document.getElementById( `${ kind.fieldPrefix }_${ key }_unit` );

			if ( ! value || ! unit ) {
				return;
			}

			const number = parseInt( value.value, 10 );

			Array.from( unit.options )
				.filter( ( option ) => kind.units.includes( Number( option.value ) ) )
				.forEach( ( option ) => {
					option.textContent = UNIT_NAMES[ option.value ]( Number.isNaN( number ) ? 2 : number );
				} );
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
 * Shows an error under a window of a kind, marking the field it is about invalid, or clears both.
 *
 * The field is marked with `aria-invalid` rather than common's `tribe-validation-error` class: common validates the
 * form again after the save click and strips that class from every field it does not flag itself.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind           The kind of the window.
 * @param {string}                                 message        The error, or an empty string to clear it.
 * @param {string|null}                            [fieldId=null] The id of the field the error is about, or `null`
 *                                                                for an error that is not about a field.
 *
 * @return {HTMLElement|null} The field marked invalid, or `null` when none is.
 */
function showError( kind, message, fieldId = null ) {
	const error = document.getElementById( kind.errorId );

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
 * Clears the error of a window of a kind, and of every window judged against it.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind The kind of the window.
 *
 * @return {void}
 */
function clearErrors( kind ) {
	WINDOW_KINDS.filter( ( other ) => other === kind || other.parent === kind ).forEach( ( other ) =>
		showError( other, '' )
	);
}

/**
 * Gets the parent window of a window of a kind as the form gives it.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind       The kind of the window.
 * @param {import( './event-dates' ).EventDates}   eventDates The event dates.
 * @param {Object}                                 dynamic    The date formats TEC localizes for its own helper text.
 *
 * @return {import( '../window-check' ).FormWindow|null} The parent window, or `null` for a kind without one or a form
 *                                                       without its fields.
 */
function getParentWindow( kind, eventDates, dynamic ) {
	const { parent } = kind;

	if ( ! parent || ! document.getElementById( `${ parent.fieldPrefix }_start_mode` ) ) {
		return null;
	}

	return getFormWindow( readRule( document, parent ), eventDates, readFormDates( parent, dynamic ), parent );
}

/**
 * Blocks the ticket save when a window of a kind is one the server rejects: its relative number is out of range, it
 * does not end after it starts, or it starts outside its parent window.
 *
 * `tickets.js` keeps only the last answer of the `additionalValidation.tribe` handlers, and Event Tickets Plus answers
 * with the value it was passed. So an invalid window stops the handlers after this one, and a valid one keeps the
 * answer of a handler before it. The handlers are bound in the order the server judges the kinds, so a window judged
 * against its parent is not judged once the parent is invalid. A window the save does not keep is not judged, and its
 * error is cleared.
 *
 * @since TBD
 *
 * @param {import( '../window-kinds' ).WindowKind} kind  The kind of the window.
 * @param {jQuery.Event}                           event The validation event.
 * @param {boolean}                                valid Whether the ticket is valid so far.
 *
 * @return {boolean} Whether the ticket can be saved.
 */
function validateWindow( kind, event, valid ) {
	const answer = undefined === event.result ? valid : event.result;
	const settings = window.tec?.tickets?.relativeSaleDates?.classicData;
	const dynamic = window.tribe_dynamic_help_text;

	if ( ! settings || ! dynamic || ! document.getElementById( kind.errorId ) ) {
		return answer;
	}

	if ( ! kind.isEnabled( document ) ) {
		showError( kind, '' );

		return answer;
	}

	const eventDates = getEventDates( settings, dynamic );

	// Without event dates the window cannot be judged here; the server still checks it.
	if ( ! eventDates ) {
		return answer;
	}

	const rule = readRule( document, kind );
	const error = getWindowError(
		rule,
		eventDates,
		readFormDates( kind, dynamic ),
		kind,
		getParentWindow( kind, eventDates, dynamic )
	);

	if ( ! error ) {
		showError( kind, '' );

		return answer;
	}

	const fieldId =
		RELATIVE_VALUE_OUT_OF_RANGE === error
			? `${ kind.fieldPrefix }_${ getOutOfRangeBoundary( rule, kind ) }_value`
			: `${ kind.fieldPrefix }_end_mode`;
	const marked = showError( kind, settings.text.windows[ kind.id ][ TEXT_KEYS[ error ] ], fieldId );

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
	const texts = window.tec?.tickets?.relativeSaleDates?.classicData?.text?.windows ?? {};
	// The server answers with the text only, so the window an error is about is told apart by its text.
	const kind = WINDOW_KINDS.find( ( candidate ) =>
		SERVER_TEXT_KEYS.some( ( key ) => texts[ candidate.id ]?.[ key ] === text )
	);

	if ( kind ) {
		showError( kind, text, `${ kind.fieldPrefix }_end_mode` );

		return;
	}

	// Another reason is shown under the sales window, the ticket's own, without marking a field invalid.
	showError( SALES_WINDOW, text );
}

/**
 * Updates everything that follows the event dates once an event field changes.
 *
 * @since TBD
 *
 * @return {void}
 */
function onEventChange() {
	WINDOW_KINDS.forEach( clearErrors );
	updateHelperText();
	updateWindowLengths();
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
	updateWindowLengths();
	updateTicketsList();
}

/*
 * `tickets.js` fires `pre-save-ticket.tribe` and `additionalValidation.tribe` on `#tribetickets` right before it
 * saves the ticket. The handlers are bound on that element rather than delegated from the document: Event Tickets Plus
 * stops the save event from bubbling, and the element itself is not replaced when the panels are.
 */
jQuery( () => {
	const tickets = jQuery( '#tribetickets' );

	tickets.on( 'pre-save-ticket.tribe', () => WINDOW_KINDS.forEach( ( kind ) => writeRule( document, kind ) ) );
	WINDOW_KINDS.forEach( ( kind ) =>
		tickets.on( 'additionalValidation.tribe', ( event, valid ) => validateWindow( kind, event, valid ) )
	);
	onPanelsRefreshed();
} );

jQuery( document ).on( 'change', EVENT_FIELDS, onEventChange );
// `tickets.js` tells this script of a date picked from the calendar with the namespaced event, which a plain change fires too.
WINDOW_KINDS.forEach( ( kind ) =>
	jQuery( document ).on( 'change.tecRelativeSaleDates input', getFieldSelector( kind ), () => clearErrors( kind ) )
);
jQuery( document ).on( 'change.tecRelativeSaleDates input', getFieldSelector( SALES_WINDOW ), updateHelperText );
LENGTH_KINDS.forEach( ( kind ) =>
	jQuery( document ).on( 'change.tecRelativeSaleDates input', getFieldSelector( kind ), () =>
		updateWindowLength( kind )
	)
);
jQuery( document ).on( 'change input', RELATIVE_VALUE_FIELDS, updateUnitNames );

addAction( 'tec.tickets.admin.panels.refreshed', 'tec.tickets.relativeSaleDates', onPanelsRefreshed );
addAction( 'tec.tickets.admin.ticketSaveFailed', 'tec.tickets.relativeSaleDates', showServerError );
