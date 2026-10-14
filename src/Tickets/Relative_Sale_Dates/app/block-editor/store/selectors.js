/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( './reducer' ).State} State */

/**
 * Gets the rule the ticket block is being edited to.
 *
 * @since TBD
 *
 * @param {State}  state    The store state.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {SaleWindowRule|null|undefined} The rule, `null` when the ticket is to have none, or `undefined` when the
 *                                         store was never given one for the ticket.
 */
export function getDraftRule( state, clientId ) {
	return state[ clientId ]?.draft;
}
