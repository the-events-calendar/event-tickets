/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * @typedef {Object} TicketRules
 *
 * @property {SaleWindowRule|null|undefined} saved The rule the ticket was loaded or last saved with: `null` when it has
 *                                                 none, `undefined` when the ticket has not been saved with one yet.
 * @property {SaleWindowRule|null|undefined} draft The rule the ticket block is being edited to.
 * @property {SaleWindowRule|null|undefined} sent  The rule the last create or update request carried: `null` for an
 *                                                 empty one, `undefined` when it carried none.
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
 * Reduces the Relative Sale Dates block editor store.
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
			return withRules( state, action.clientId, { saved: action.rule, draft: action.rule } );
		case 'SET_DRAFT_RULE':
			return withRules( state, action.clientId, { ...current, draft: action.rule } );
		case 'SET_SENT_RULE':
			return withRules( state, action.clientId, { ...current, sent: action.rule } );
		case 'SAVE_SENT_RULE':
			// A request that carried no rule leaves the stored one, so the saved rule stays as it was.
			if ( undefined === current?.sent ) {
				return state;
			}

			return withRules( state, action.clientId, { ...current, saved: current.sent, sent: undefined } );
		case 'SAVE_CONFIRMED_RULE':
			if ( ! current ) {
				return state;
			}

			return withRules( state, action.clientId, { ...current, saved: action.rule, sent: undefined } );
		case 'RESET_DRAFT_RULE':
			if ( ! current ) {
				return state;
			}

			return withRules( state, action.clientId, { ...current, draft: current.saved } );
		default:
			return state;
	}
}
