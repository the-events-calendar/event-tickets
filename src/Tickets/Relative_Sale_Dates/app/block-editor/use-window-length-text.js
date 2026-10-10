/**
 * Internal dependencies
 */
import { getFormWindowLengthText } from '../window-length';
import { readTicketFormWindowDates } from './common-store-bridge';
import { useCommonStoreValue } from './use-common-store-value';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../server-event-dates' ).EventDates} EventDates */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */

/**
 * Returns the text that says how long a ticket block's window lasts, worked out again whenever the rule, the event
 * dates or the dates the ticket form holds change.
 *
 * @since TBD
 *
 * @param {string}          clientId   The client ID of the ticket block.
 * @param {SaleWindowRule}  rule       The rule the options show.
 * @param {EventDates|null} eventDates The event dates, or `null` when they cannot be read.
 * @param {BlockWindowKind} kind       The window kind.
 *
 * @return {string} The text, or an empty string when the length cannot be worked out.
 */
export function useWindowLengthText( clientId, rule, eventDates, kind ) {
	const formDates = useCommonStoreValue( () => readTicketFormWindowDates( clientId, kind ) );

	return getFormWindowLengthText( rule, eventDates, formDates, kind );
}
