/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */

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
 * Records the rule the request that creates or updates a ticket carries.
 *
 * @since TBD
 *
 * @param {string}                        clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null|undefined} rule     The rule the request carries: `null` for an empty one, `undefined`
 *                                                 when it carries none.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null|undefined}} The action.
 */
export function setSentRule( clientId, rule ) {
	return { type: 'SET_SENT_RULE', clientId, rule };
}

/**
 * Keeps the rule the last request carried as the saved rule, once the ticket it was sent with has been saved.
 *
 * @since TBD
 *
 * @param {string} clientId The client ID of the ticket block.
 *
 * @return {{type: string, clientId: string}} The action.
 */
export function saveSentRule( clientId ) {
	return { type: 'SAVE_SENT_RULE', clientId };
}

/**
 * Discards the draft, restoring the saved rule.
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
