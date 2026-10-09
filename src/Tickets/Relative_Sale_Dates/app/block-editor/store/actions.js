/**
 * Internal dependencies
 */
import { SALES_WINDOW } from '../../window-kinds';

/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../../window-kinds' ).WindowKind} WindowKind */

/**
 * Sets the rule a ticket was loaded with, as both its saved rule and its draft.
 *
 * @since TBD
 *
 * @param {string}                        clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null|undefined} rule     The ticket's stored rule, `null` when it has none, or `undefined` for
 *                                                 a window the ticket does not have.
 * @param {WindowKind}                    kind     The window kind.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null|undefined, kindId: string}} The action.
 */
export function setRule( clientId, rule, kind = SALES_WINDOW ) {
	return { type: 'SET_RULE', clientId, rule, kindId: kind.id };
}

/**
 * Sets the rule the ticket block is being edited to, without touching the saved one.
 *
 * @since TBD
 *
 * @param {string}              clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null} rule     The edited rule, or `null` to remove the ticket's rule.
 * @param {WindowKind}          kind     The window kind.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null, kindId: string}} The action.
 */
export function setDraftRule( clientId, rule, kind = SALES_WINDOW ) {
	return { type: 'SET_DRAFT_RULE', clientId, rule, kindId: kind.id };
}

/**
 * Records the rule the request that creates or updates a ticket carries.
 *
 * @since TBD
 *
 * @param {string}                        clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null|undefined} rule     The rule the request carries: `null` for an empty one, `undefined`
 *                                                 when it carries none.
 * @param {WindowKind}                    kind     The window kind.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null|undefined, kindId: string}} The action.
 */
export function setSentRule( clientId, rule, kind = SALES_WINDOW ) {
	return { type: 'SET_SENT_RULE', clientId, rule, kindId: kind.id };
}

/**
 * Keeps the rules the last request carried as the saved rules, once the ticket they were sent with has been saved.
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
 * Keeps as a ticket's saved rule the one the server answered a save with.
 *
 * Saves can overlap, and the rule the latest request carried is not the one an earlier answer confirms.
 *
 * @since TBD
 *
 * @param {string}                        clientId The client ID of the ticket block.
 * @param {SaleWindowRule|null|undefined} rule     The rule the server stored, `null` for none, or `undefined` for a
 *                                                 window the ticket no longer has.
 * @param {WindowKind}                    kind     The window kind.
 *
 * @return {{type: string, clientId: string, rule: SaleWindowRule|null|undefined, kindId: string}} The action.
 */
export function saveConfirmedRule( clientId, rule, kind = SALES_WINDOW ) {
	return { type: 'SAVE_CONFIRMED_RULE', clientId, rule, kindId: kind.id };
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
