/** @typedef {import( '../../sale-window' ).SaleWindowRule} SaleWindowRule */

/**
 * @typedef {Object} WindowRules
 *
 * @property {SaleWindowRule|null|undefined} saved The rule the ticket was loaded or last saved with: `null` when it has
 *                                                 none, `undefined` when the ticket has not been saved with one yet.
 * @property {SaleWindowRule|null|undefined} draft The rule the ticket block is being edited to.
 * @property {SaleWindowRule|null|undefined} sent  The rule the last create or update request carried: `null` for an
 *                                                 empty one, `undefined` when it carried none.
 */

/**
 * @typedef {Object<string,WindowRules>} TicketRules The rules of each window of a ticket, keyed by the window kind's ID.
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
 * Returns the state with the rules of one window of a ticket replaced, leaving its other windows as they were.
 *
 * @since TBD
 *
 * @param {State}       state    The current state.
 * @param {string}      clientId The client ID of the ticket block.
 * @param {string}      kindId   The window kind's ID.
 * @param {WindowRules} rules    The window's new rules.
 *
 * @return {State} The new state.
 */
function withWindowRules( state, clientId, kindId, rules ) {
	return withRules( state, clientId, { ...state[ clientId ], [ kindId ]: rules } );
}

/**
 * Returns the state with every window of a ticket changed the same way: a ticket is saved or cancelled whole.
 *
 * @since TBD
 *
 * @param {State}                                state    The current state.
 * @param {string}                               clientId The client ID of the ticket block.
 * @param {function( WindowRules ): WindowRules} change   Changes the rules of one window.
 *
 * @return {State} The new state.
 */
function withEveryWindow( state, clientId, change ) {
	const current = state[ clientId ];

	if ( ! current ) {
		return state;
	}

	return withRules(
		state,
		clientId,
		Object.fromEntries( Object.entries( current ).map( ( [ kindId, rules ] ) => [ kindId, change( rules ) ] ) )
	);
}

/**
 * Returns a window's rules with the rule the last request carried kept as the saved one.
 *
 * A request that carried no rule leaves the stored one, so the saved rule stays as it was.
 *
 * @since TBD
 *
 * @param {WindowRules} rules The window's rules.
 *
 * @return {WindowRules} The rules, saved.
 */
function withSentSaved( rules ) {
	return undefined === rules.sent ? rules : { ...rules, saved: rules.sent, sent: undefined };
}

/**
 * Returns a window's rules with the draft restored to the saved rule.
 *
 * @since TBD
 *
 * @param {WindowRules} rules The window's rules.
 *
 * @return {WindowRules} The rules, reset.
 */
function withDraftReset( rules ) {
	return { ...rules, draft: rules.saved };
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
	const current = state[ action.clientId ]?.[ action.kindId ];

	switch ( action.type ) {
		case 'SET_RULE':
			return withWindowRules( state, action.clientId, action.kindId, { saved: action.rule, draft: action.rule } );
		case 'SET_DRAFT_RULE':
			return withWindowRules( state, action.clientId, action.kindId, { ...current, draft: action.rule } );
		case 'SET_SENT_RULE':
			return withWindowRules( state, action.clientId, action.kindId, { ...current, sent: action.rule } );
		case 'SAVE_SENT_RULE':
			return withEveryWindow( state, action.clientId, withSentSaved );
		case 'SAVE_CONFIRMED_RULE':
			if ( ! current ) {
				return state;
			}

			return withWindowRules( state, action.clientId, action.kindId, {
				...current,
				saved: action.rule,
				sent: undefined,
			} );
		case 'RESET_DRAFT_RULE':
			return withEveryWindow( state, action.clientId, withDraftReset );
		default:
			return state;
	}
}
