/**
 * Internal dependencies
 */
import { SALES_WINDOW } from '../../window-kinds';

/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../../window-kinds' ).WindowKind} WindowKind */
/** @typedef {import( './reducer' ).State} State */

/**
 * Gets the rule the ticket block is being edited to.
 *
 * @since TBD
 *
 * @param {State}      state    The store state.
 * @param {string}     clientId The client ID of the ticket block.
 * @param {WindowKind} kind     The window kind.
 *
 * @return {SaleWindowRule|null|undefined} The rule, `null` when the ticket is to have none, or `undefined` when the
 *                                         store was never given one for the ticket.
 */
export function getDraftRule( state, clientId, kind = SALES_WINDOW ) {
	return state[ clientId ]?.[ kind.id ]?.draft;
}
