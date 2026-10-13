/**
 * Judges each window of the classic ticket form as the server judges a ticket save, as the admin edits it and on the
 * save click, and shows why the server rejected a save.
 *
 * @since TBD
 */

/**
 * Internal dependencies
 */
import { createTypingTracker, getShownError } from '../live-error';
import { MODE_SPECIFIC } from '../rule-constants';
import { ENDS_BEFORE_START, getOutOfRangeBoundary, judgeWindow, RELATIVE_VALUE_OUT_OF_RANGE } from '../window-check';
import { ERROR_TEXT_KEYS } from '../window-errors';
import { showError } from './error-display';
import { getContext, getEventDates, readFormDates } from './form-context';
import { keepsSalePriceWithoutRule, readRule } from './rule';
import { CLASSIC_SALES_WINDOW, CLASSIC_WINDOW_KINDS } from './window-kinds';

/** @typedef {import( './window-kinds' ).ClassicWindowKind} ClassicWindowKind */
/** @typedef {import( './form-context' ).FormContext} FormContext */
/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * Gets the id of the field an error of a window is about.
 *
 * A number out of range is about that number. An order or a start outside the parent window is about the end: its date
 * when the end is specific, where the admin typed the date that does not fit, and its mode otherwise.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind  The kind of the window.
 * @param {SaleWindowRule}    rule  The rule the form expresses.
 * @param {string}            error The error.
 *
 * @return {string} The id of the field.
 */
function getErrorFieldId( kind, rule, error ) {
	if ( RELATIVE_VALUE_OUT_OF_RANGE === error ) {
		return `${ kind.fieldPrefix }_${ getOutOfRangeBoundary( rule, kind ) }_value`;
	}

	return MODE_SPECIFIC === rule.end.mode ? kind.dateFields.end.date : `${ kind.fieldPrefix }_end_mode`;
}

/**
 * Reads what the form holds for a window of a kind.
 *
 * A sale price saved before relative dates, still on its own dates, is saved without a rule, so the server judges it
 * as it did then.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind    The kind of the window.
 * @param {FormContext}       context The data this script is localized with.
 *
 * @return {import( '../window-check' ).WindowReading} What the form holds for the window.
 */
function readWindow( kind, context ) {
	if ( ! document.getElementById( `${ kind.fieldPrefix }_start_mode` ) ) {
		return { rule: null, formRule: null, formDates: { start: null, end: null }, isKept: false };
	}

	const formRule = readRule( document, kind );

	return {
		rule: keepsSalePriceWithoutRule( document, kind ) ? null : formRule,
		formRule,
		formDates: readFormDates( kind, context ),
		isKept: kind.isEnabled( document ),
	};
}

/**
 * Reads why a window of a kind in the form cannot be saved: its relative number is out of range, it does not end after
 * it starts, or it starts outside its parent window.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind The kind of the window.
 *
 * @return {{message: string, fieldId: string}|null} The error and the id of the field it is about, or `null` for a
 *                                                   window that can be saved, is not kept by the save, is saved
 *                                                   without a rule, or waits on its parent window.
 */
function readWindowError( kind ) {
	const context = getContext();

	if ( ! context || ! document.getElementById( kind.errorId ) ) {
		return null;
	}

	const error = judgeWindow( kind, ( each ) => readWindow( each, context ), getEventDates( context ) );

	if ( ! error ) {
		return null;
	}

	return {
		message: context.settings.text.windows[ kind.id ][ ERROR_TEXT_KEYS[ error ] ],
		fieldId: getErrorFieldId( kind, readRule( document, kind ), error ),
	};
}

/**
 * Blocks the ticket save when a window of a kind is one the server rejects.
 *
 * `tickets.js` keeps only the last answer of the `additionalValidation.tribe` handlers, and Event Tickets Plus answers
 * with the value it was passed. So an invalid window stops the handlers after this one, and a valid one keeps the
 * answer of a handler before it. The handlers are bound in the order the server judges the kinds, so a window judged
 * against its parent is not judged once the parent is invalid. A window the save does not keep is not judged, and its
 * error is cleared.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind  The kind of the window.
 * @param {jQuery.Event}      event The validation event.
 * @param {boolean}           valid Whether the ticket is valid so far.
 *
 * @return {boolean} Whether the ticket can be saved.
 */
export function validateWindow( kind, event, valid ) {
	const answer = undefined === event.result ? valid : event.result;
	const error = readWindowError( kind );

	if ( ! error ) {
		showError( kind, '' );

		return answer;
	}

	// The save button keeps the focus otherwise, away from the field that blocks the save.
	showError( kind, error.message, error.fieldId )?.focus();
	event.stopImmediatePropagation();

	return false;
}

/**
 * Checks a window of a kind again, showing why it cannot be saved or clearing its error, so the error shows as the
 * admin makes it rather than on the save click.
 *
 * The focus stays where the admin is typing; only the save click moves it to the field that blocks the save.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind The kind of the window.
 *
 * @return {void}
 */
export function checkWindow( kind ) {
	const error = getShownError(
		document.getElementById( kind.errorId )?.textContent,
		readWindowError( kind ),
		typing.isTyping()
	);

	showError( kind, error ? error.message : '', error?.fieldId );
}

/**
 * Whether the admin is typing in a window field, checking every window again once they stop.
 *
 * @since TBD
 *
 * @type {{type: function(): void, settle: function(): void, isTyping: function(): boolean}}
 */
export const typing = createTypingTracker( () => CLASSIC_WINDOW_KINDS.forEach( checkWindow ) );

/**
 * Checks a window of a kind again, and every window judged against it, once a field they depend on changes.
 *
 * @since TBD
 *
 * @param {ClassicWindowKind} kind The kind of the window.
 *
 * @return {void}
 */
export function checkWindows( kind ) {
	CLASSIC_WINDOW_KINDS.filter( ( other ) => other === kind || other.parent === kind ).forEach( checkWindow );
}

/**
 * Whether a change of a window field ends the admin's edit: an option picked, or a date or time picked, which the
 * pickers fire themselves.
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
export function endsEdit( event ) {
	return ! event.originalEvent || ! [ 'text', 'number' ].includes( event.target.type );
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
export function showServerError( response ) {
	const message = response?.data?.message;

	if ( 'string' !== typeof message || '' === message ) {
		return;
	}

	// The server escapes the message for HTML; the error element takes text.
	const text = new window.DOMParser().parseFromString( message, 'text/html' ).documentElement.textContent;
	const codes = getContext()?.settings.errorCodes ?? {};
	const code = response?.data?.code;
	// The code the server rejects the save with tells the window the error is about.
	const kind = CLASSIC_WINDOW_KINDS.find(
		( candidate ) => Boolean( code ) && Object.values( codes[ candidate.id ] ?? {} ).includes( code )
	);

	if ( kind ) {
		showError( kind, text, getErrorFieldId( kind, readRule( document, kind ), ENDS_BEFORE_START ) );

		return;
	}

	// Another reason is shown under the sales window, the ticket's own, without marking a field invalid.
	showError( CLASSIC_SALES_WINDOW, text );
}
