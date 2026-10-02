/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../../sale-price-window' ).SalePriceRule} SalePriceRule */

/**
 * Sets the rule a ticket was loaded with, as both its saved rule and its draft.
 *
 * @since TBD
 *
 * @param {string}              clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null} rule     The ticket's stored rule, or `null` when it has none.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null}} The action.
 */
export function setRule( clientId, rule ) {
	return { type: 'SET_RULE', clientId, rule };
}

/**
 * Sets the rule the ticket block is being edited to, without touching the saved one.
 *
 * @since TBD
 *
 * @param {string}              clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null} rule     The edited rule, or `null` to remove the ticket's rule.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null}} The action.
 */
export function setDraftRule( clientId, rule ) {
	return { type: 'SET_DRAFT_RULE', clientId, rule };
}

/**
 * Sets the sale price rule a ticket was loaded with, as both its saved sale price rule and its draft.
 *
 * @since TBD
 *
 * @param {string}                       clientId The client ID of the ticket block.
 * @param {SalePriceRule|null|undefined} rule     The ticket's stored sale price rule, `null` for a sale price stored
 *                                                without one, or `undefined` when the ticket has no sale price.
 *
 * @return {{type: string, clientId: string, rule: SalePriceRule|null|undefined}} The action.
 */
export function setSalePriceRule( clientId, rule ) {
	return { type: 'SET_SALE_PRICE_RULE', clientId, rule };
}

/**
 * Sets the sale price rule the ticket block is being edited to, without touching the saved one.
 *
 * @since TBD
 *
 * @param {string}        clientId The client ID of the ticket block.
 * @param {SalePriceRule} rule     The edited sale price rule.
 *
 * @return {{type: string, clientId: string, rule: SalePriceRule}} The action.
 */
export function setDraftSalePriceRule( clientId, rule ) {
	return { type: 'SET_DRAFT_SALE_PRICE_RULE', clientId, rule };
}

/**
 * Keeps the drafts as the saved rules, once the ticket they were sent with has been saved.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {{type: string, clientId: string}} The action.
 */
export function saveDraftRule( clientId ) {
	return { type: 'SAVE_DRAFT_RULE', clientId };
}

/**
 * Discards the drafts, restoring the saved rules.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {{type: string, clientId: string}} The action.
 */
export function resetDraftRule( clientId ) {
	return { type: 'RESET_DRAFT_RULE', clientId };
}
