/**
 * Checks a ticket block's windows, of every kind, the way the server checks them on save.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { MIN_VALUE } from '../rule-constants';
import {
	ENDS_BEFORE_START,
	RELATIVE_VALUE_OUT_OF_RANGE,
	getFormWindow,
	getOutOfRangeBoundary,
	getWindowError,
	isValidRule,
} from '../window-check';
import { readTicketFormWindowDates, readTicketWindowKept } from './common-store-bridge';
import { useEventDates } from './event-dates';
import { getFormRule, toRequestRule } from './rule';
import { STORE_NAME } from './store/constants';
import { useCommonStoreValue } from './use-common-store-value';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */

/**
 * @typedef {Object} TicketWindowForm
 *
 * @property {{start: string|null, end: string|null}} formDates The start and end dates the ticket's form sends for the
 *                                                              window, `YYYY-MM-DD HH:mm:ss` in the event timezone,
 *                                                              or `null`.
 * @property {boolean}                                isKept    Whether a save of the form keeps the window.
 */

/** @typedef {TicketWindowForm & {rule: SaleWindowRule|null|undefined}} TicketWindow */

/** @typedef {function( BlockWindowKind ): TicketWindow} TicketWindowReader */

/**
 * @typedef {Object} BoundaryErrors
 *
 * @property {string} errorMessage      The window error the boundary is marked with, or an empty string.
 * @property {string} valueErrorMessage The range error of the boundary's relative number, or an empty string.
 */

/**
 * The text of each error, keyed by the key a block kind's `messages` name for it.
 *
 * The msgids are the ones the server rejects a save with where it names the error, so one translation serves both.
 *
 * @since TBD
 *
 * @type {Object<string, function( BlockWindowKind ): string>}
 */
const MESSAGES = {
	invalidWindow: () =>
		__( 'Ticket sales cannot end before they start. Please adjust the sales window.', 'event-tickets' ),
	relativeValueOutOfRange: getRangeMessage,
	salePriceEndsBeforeStart: () =>
		__( 'The sale price cannot end before it starts. Please adjust the sale price window.', 'event-tickets' ),
	salePriceValueOutOfRange: getRangeMessage,
	salePriceOutsideWindow: () =>
		__( 'The sale price window falls outside the ticket sales window. Please adjust the dates.', 'event-tickets' ),
};

/**
 * Gets the message of a relative number out of the range a kind takes.
 *
 * @since TBD
 *
 * @param {BlockWindowKind} kind The window kind.
 *
 * @return {string} The message.
 */
function getRangeMessage( kind ) {
	return sprintf(
		// translators: %1$d is the smallest number a relative sale date takes, %2$d the largest.
		__( 'Enter a number from %1$d to %2$d.', 'event-tickets' ),
		MIN_VALUE,
		kind.maxValue
	);
}

/**
 * Returns a kind followed by the windows it must start inside, its parent first.
 *
 * @since TBD
 *
 * @param {BlockWindowKind} kind The window kind.
 *
 * @return {BlockWindowKind[]} The kind and its ancestors.
 */
function getKindLineage( kind ) {
	return kind.parent ? [ kind, ...getKindLineage( kind.parent ) ] : [ kind ];
}

/**
 * Returns the error of a ticket's window of a kind, or `null` when it is valid or is not judged.
 *
 * A window is judged only when the ticket has a rule for it and a save of its form keeps it. A window with a parent is
 * judged only once its parent is valid, as the server rejects the save for the parent first, and against the parent
 * window the form gives. The rule is judged as the request carries it. Without the event dates, only what the server
 * rejects whatever the dates is judged: a relative number out of range, or a rule it does not take.
 *
 * @since TBD
 *
 * @param {BlockWindowKind}    kind       The window kind.
 * @param {TicketWindowReader} readWindow Reads the draft rule and form of a window of the ticket.
 * @param {EventDates|null}    eventDates The event dates, or `null` when they cannot be read.
 *
 * @return {string|null} The error key, or `null`.
 */
export function getTicketWindowError( kind, readWindow, eventDates ) {
	const { rule, formDates, isKept } = readWindow( kind );
	const { parent } = kind;

	if ( ! rule || ! isKept ) {
		return null;
	}

	if ( parent && null !== getTicketWindowError( parent, readWindow, eventDates ) ) {
		return null;
	}

	// The rule is judged as the request carries it, with integer numbers, or `NaN` for a cleared one.
	const sentRule = toRequestRule( rule, kind );

	// The server rejects a rule it does not take, such as one with a cleared number, before it reads any date.
	if ( ! eventDates ) {
		if ( getOutOfRangeBoundary( sentRule, kind ) ) {
			return RELATIVE_VALUE_OUT_OF_RANGE;
		}

		return isValidRule( sentRule, kind ) ? null : ENDS_BEFORE_START;
	}

	let parentWindow = null;

	if ( parent ) {
		const parentForm = readWindow( parent );

		parentWindow = getFormWindow(
			getFormRule( parentForm.rule, parent ),
			eventDates,
			parentForm.formDates,
			parent
		);
	}

	return getWindowError( sentRule, eventDates, formDates, kind, parentWindow );
}

/**
 * Returns whether the server would reject a save of a ticket's window of a kind: the window or one it must start
 * inside has an error.
 *
 * @since TBD
 *
 * @param {BlockWindowKind}    kind       The window kind.
 * @param {TicketWindowReader} readWindow Reads the draft rule and form of a window of the ticket.
 * @param {EventDates|null}    eventDates The event dates, or `null` when they cannot be read.
 *
 * @return {boolean} Whether the save would be rejected.
 */
export function hasTicketWindowSaveError( kind, readWindow, eventDates ) {
	return getKindLineage( kind ).some( ( each ) => null !== getTicketWindowError( each, readWindow, eventDates ) );
}

/**
 * Builds the reader of a ticket's windows: each one's draft rule, with its form as another reader gives it.
 *
 * @since TBD
 *
 * @param {string}                                        clientId    The client ID of the ticket block.
 * @param {Function}                                      selectStore The `select()` of `@wordpress/data`, or the one
 *                                                                    `useSelect()` passes.
 * @param {function( BlockWindowKind ): TicketWindowForm} readForm    Reads the form of a window of the ticket.
 *
 * @return {TicketWindowReader} The reader.
 */
export function getTicketWindowReader( clientId, selectStore, readForm ) {
	return ( kind ) => ( { rule: selectStore( STORE_NAME ).getDraftRule( clientId, kind ), ...readForm( kind ) } );
}

/**
 * Reads the form of a ticket's window of a kind from the common store.
 *
 * @since TBD
 *
 * @param {string}          clientId The client ID of the ticket block.
 * @param {BlockWindowKind} kind     The window kind.
 *
 * @return {TicketWindowForm} The form.
 */
export function readTicketWindowForm( clientId, kind ) {
	return { formDates: readTicketFormWindowDates( clientId, kind ), isKept: readTicketWindowKept( clientId, kind ) };
}

/**
 * Returns the error of a ticket block's window of a kind, checked again whenever the rules, the event dates or what
 * the ticket form holds change, the parent window's included.
 *
 * @since TBD
 *
 * @param {string}          clientId The client ID of the ticket block.
 * @param {BlockWindowKind} kind     The window kind.
 *
 * @return {string|null} The error key, or `null`.
 */
export function useWindowError( clientId, kind ) {
	const eventDates = useEventDates();
	const lineage = useMemo( () => getKindLineage( kind ), [ kind ] );
	const forms = useCommonStoreValue( () => lineage.map( ( each ) => readTicketWindowForm( clientId, each ) ) );

	return useSelect(
		( select ) =>
			getTicketWindowError(
				kind,
				getTicketWindowReader( clientId, select, ( each ) => forms[ lineage.indexOf( each ) ] ),
				eventDates
			),
		[ clientId, kind, lineage, forms, eventDates ]
	);
}

/**
 * Gets the message of a window error, as the kind words it.
 *
 * @since TBD
 *
 * @param {string|null}     key  The error key, or `null`.
 * @param {BlockWindowKind} kind The window kind.
 *
 * @return {string} The message, or an empty string without an error the kind can have.
 */
export function getWindowErrorMessage( key, kind ) {
	const textKey = key ? kind.messages[ key ] : null;

	return textKey ? MESSAGES[ textKey ]( kind ) : '';
}

/**
 * Gets the messages each boundary of a window shows for its error: a relative number out of range on that number, and
 * any other error under the end.
 *
 * @since TBD
 *
 * @param {string|null}     error The error key, or `null`.
 * @param {SaleWindowRule}  rule  The rule the options show.
 * @param {BlockWindowKind} kind  The window kind.
 *
 * @return {{start: BoundaryErrors, end: BoundaryErrors}} The messages, keyed by boundary.
 */
export function getBoundaryErrors( error, rule, kind ) {
	const outOfRange = RELATIVE_VALUE_OUT_OF_RANGE === error ? getOutOfRangeBoundary( rule, kind ) : null;
	const message = getWindowErrorMessage( error, kind );
	const forBoundary = ( name ) => ( {
		errorMessage: 'end' === name && ! outOfRange ? message : '',
		valueErrorMessage: name === outOfRange ? message : '',
	} );

	return { start: forBoundary( 'start' ), end: forBoundary( 'end' ) };
}
