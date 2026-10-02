/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( '../../sale-price-window' ).SalePriceRule} SalePriceRule */

/**
 * @typedef {Object} SalePriceRules
 *
 * @property {SalePriceRule|null|undefined} saved The sale price rule the ticket was loaded or last saved with: `null`
 *                                                for a sale price saved without one, `undefined` when the ticket has
 *                                                not been saved with one yet.
 * @property {SalePriceRule|null|undefined} draft The sale price rule the ticket block is being edited to.
 */

/**
 * @typedef {Object} TicketRules
 *
 * @property {SaleWindowRule|null|undefined} saved       The rule the ticket was loaded or last saved with: `null` when
 *                                                       it has none, `undefined` when the ticket has not been saved
 *                                                       with one yet.
 * @property {SaleWindowRule|null|undefined} draft       The rule the ticket block is being edited to.
 * @property {SalePriceRules}                [salePrice] The ticket's sale price rules, once the store is given one.
 */

/**
 * @typedef {Object<string,TicketRules>} State The rules of each ticket, keyed by the client ID of its block.
 */

/**
 * Returns the state with the rules of one ticket replaced, leaving the other tickets' rules as they were.
 *
 * @since TBD
 *
 * @param {State}       state    The current state.
 * @param {string}      clientId The client ID of the ticket block.
 * @param {TicketRules} rules    The ticket's new rules.
 *
 * @return {State} The new state.
 */
function withRules( state, clientId, rules ) {
	return { ...state, [ clientId ]: rules };
}

/**
 * Returns a saved and draft pair with the draft kept as the saved value.
 *
 * @since TBD
 *
 * @param {TicketRules|SalePriceRules} rules The saved and draft pair.
 *
 * @return {TicketRules|SalePriceRules} The pair, saved.
 */
function withDraftSaved( rules ) {
	return { ...rules, saved: rules.draft };
}

/**
 * Returns a saved and draft pair with the draft restored to the saved value.
 *
 * @since TBD
 *
 * @param {TicketRules|SalePriceRules} rules The saved and draft pair.
 *
 * @return {TicketRules|SalePriceRules} The pair, reset.
 */
function withDraftReset( rules ) {
	return { ...rules, draft: rules.saved };
}

/**
 * Reduces the Relative Sale Dates block editor store.
 *
 * A ticket is saved or cancelled whole, so saving or resetting its drafts acts on its sales window and sale price
 * rules together.
 *
 * @since TBD
 *
 * @param {State}  state  The current state.
 * @param {Object} action The dispatched action.
 *
 * @return {State} The new state.
 */
export default function reducer( state = {}, action ) {
	const current = state[ action.clientId ];

	switch ( action.type ) {
		case 'SET_RULE':
			return withRules( state, action.clientId, { ...current, saved: action.rule, draft: action.rule } );
		case 'SET_DRAFT_RULE':
			return withRules( state, action.clientId, { ...current, draft: action.rule } );
		case 'SET_SALE_PRICE_RULE':
			return withRules( state, action.clientId, {
				...current,
				salePrice: { saved: action.rule, draft: action.rule },
			} );
		case 'SET_DRAFT_SALE_PRICE_RULE':
			return withRules( state, action.clientId, {
				...current,
				salePrice: { ...current?.salePrice, draft: action.rule },
			} );
		case 'SAVE_DRAFT_RULE':
			if ( ! current ) {
				return state;
			}

			return withRules( state, action.clientId, {
				...withDraftSaved( current ),
				...( current.salePrice && { salePrice: withDraftSaved( current.salePrice ) } ),
			} );
		case 'RESET_DRAFT_RULE':
			if ( ! current ) {
				return state;
			}

			return withRules( state, action.clientId, {
				...withDraftReset( current ),
				...( current.salePrice && { salePrice: withDraftReset( current.salePrice ) } ),
			} );
		default:
			return state;
	}
}
