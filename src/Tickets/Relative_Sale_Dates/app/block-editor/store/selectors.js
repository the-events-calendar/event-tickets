/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../../sale-price-window' ).SalePriceRule} SalePriceRule */
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

/**
 * Gets the rule the ticket was loaded or last saved with.
 *
 * @since TBD
 *
 * @param {State}  state    The store state.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {SaleWindowRule|null|undefined} The rule, `null` when the ticket has none, or `undefined` when it has not
 *                                         been saved with one yet.
 */
export function getSavedRule( state, clientId ) {
	return state[ clientId ]?.saved;
}

/**
 * Gets the sale price rule the ticket block is being edited to.
 *
 * @since TBD
 *
 * @param {State}  state    The store state.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {SalePriceRule|null|undefined} The sale price rule, `null` for a sale price saved without one, or `undefined`
 *                                        when the store was never given one for the ticket.
 */
export function getDraftSalePriceRule( state, clientId ) {
	return state[ clientId ]?.salePrice?.draft;
}

/**
 * Gets the sale price rule the ticket was loaded or last saved with.
 *
 * @since TBD
 *
 * @param {State}  state    The store state.
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {SalePriceRule|null|undefined} The sale price rule, `null` for a sale price saved without one, or `undefined`
 *                                        when the ticket has not been saved with one yet.
 */
export function getSavedSalePriceRule( state, clientId ) {
	return state[ clientId ]?.salePrice?.saved;
}
