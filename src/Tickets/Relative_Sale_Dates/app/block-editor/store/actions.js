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
 * Keeps the draft as the saved rule, once the ticket it was sent with has been saved.
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
