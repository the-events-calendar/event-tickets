/**
 * Checks a ticket block's windows, of every kind, the way the server checks them on save.
 *
 * @since TBD
 */

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getShownError } from '../live-error';
import { RELATIVE_VALUE_OUT_OF_RANGE, getOutOfRangeBoundary, judgeWindow } from '../window-check';
import { getLegacyState } from './common-store-bridge';
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

/** @typedef {import( '../window-check' ).WindowReader} TicketWindowReader */

/**
 * @typedef {Object} BoundaryErrors
 *
 * @property {string} errorMessage      The window error the boundary is marked with, or an empty string.
 * @property {string} valueErrorMessage The range error of the boundary's relative number, or an empty string.
 */

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
	return getKindLineage( kind ).some( ( each ) => null !== judgeWindow( each, readWindow, eventDates ) );
}

/**
 * Builds the reader of a ticket's windows: each one's draft rule, as the request carries it and as the options show it,
 * with its form as another reader gives it.
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
	return ( kind ) => {
		const draft = selectStore( STORE_NAME ).getDraftRule( clientId, kind );

		return {
			// The rule is judged as the request carries it, with integer numbers, or `NaN` for a cleared one.
			rule: draft ? toRequestRule( draft, kind ) : draft,
			// Read only for a parent window, as the rule a window inside it is judged against.
			get formRule() {
				return getFormRule( draft, kind );
			},
			...readForm( kind ),
		};
	};
}

/**
 * Reads the form of a ticket's window of a kind from the legacy ticket state.
 *
 * @since TBD
 *
 * @param {string}          clientId The client ID of the ticket block.
 * @param {BlockWindowKind} kind     The window kind.
 * @param {Object}          [state]  The legacy ticket state, such as the one the dashboard is rendered with; the common
 *                                   store's when left out.
 *
 * @return {TicketWindowForm} The form.
 */
export function readTicketWindowForm( clientId, kind, state = getLegacyState() ) {
	return { formDates: kind.readFormDates( state, clientId ), isKept: kind.isKept( state, clientId ) };
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
			judgeWindow(
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
function getWindowErrorMessage( key, kind ) {
	const text = key ? kind.errorTexts[ key ] : null;

	return text ? text( kind ) : '';
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

/**
 * Returns the error a ticket block's window shows while the admin edits it: a new error waits until the admin stops
 * typing, and an error already shown follows every keystroke.
 *
 * @since TBD
 *
 * @param {string|null} error    The error key the window has now, or `null`.
 * @param {boolean}     isTyping Whether the admin is typing in a window field of the ticket.
 *
 * @return {string|null} The error key to show, or `null`.
 */
export function useShownError( error, isTyping ) {
	const [ shown, setShown ] = useState( null );
	const next = getShownError( shown, error, isTyping );

	if ( next !== shown ) {
		setShown( next );
	}

	return next;
}

/**
 * Returns the messages each boundary of a ticket block's window shows for its error, checked again as the form
 * changes, a new error held back while the admin types.
 *
 * @since TBD
 *
 * @param {string}          clientId The client ID of the ticket block.
 * @param {BlockWindowKind} kind     The window kind.
 * @param {SaleWindowRule}  formRule The rule the options show.
 * @param {boolean}         isTyping Whether the admin is typing in a window field of the ticket.
 *
 * @return {{start: BoundaryErrors, end: BoundaryErrors}} The messages, keyed by boundary.
 */
export function useBoundaryErrors( clientId, kind, formRule, isTyping ) {
	return getBoundaryErrors( useShownError( useWindowError( clientId, kind ), isTyping ), formRule, kind );
}
