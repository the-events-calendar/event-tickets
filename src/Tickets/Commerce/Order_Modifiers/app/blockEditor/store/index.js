/**
 * External dependencies
 */
import { createReduxStore, register } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { getTicketIdFromCommonStore } from './common-store-bridge';
import { defaultState } from './default-state';
import { controls } from './controls';
// import {actions} from './actions';
// import {reducer} from './reducer';
// import {selectors} from './selectors';
import { localizedData } from './localized-data';

const storeName = 'tec-tickets-fees';

const reducer = ( state = defaultState, action ) => {
	switch ( action.type ) {
		case 'SET_TICKET_FEES':
			return {
				...state,
				ticketFees: action.ticketFees,
			};

		case 'SET_AUTOMATIC_FEES':
			return {
				...state,
				feesAutomatic: action.feesAutomatic,
			};

		case 'SET_AVAILABLE_FEES':
			return {
				...state,
				feesAvailable: action.feesAvailable,
			};

		default:
			return state;
	}
};

const actions = {
	// Actions that have to do with fees for a ticket.
	setTicketFees( clientId, feesSelected ) {
		return {
			type: 'SET_TICKET_FEES',
			clientId,
			feesSelected
		};
	},

	// Actions that have to do with managing fees separate from tickets.
	setAutomaticFees( feesAutomatic ) {
		return {
			type: 'SET_AUTOMATIC_FEES',
			feesAutomatic,
		};
	},

	setAvailableFees( feesAvailable ) {
		return {
			type: 'SET_AVAILABLE_FEES',
			feesAvailable,
		};
	},

	fetchFeesFromAPI() {
		return {
			type: 'FETCH_FEES_FROM_API',
		};
	},
};

const selectors = {


	getAutomaticFees( state ) {
		return state.feesAutomatic;
	},

	getAvailableFees( state ) {
		return state.feesAvailable;
	},
};

const resolvers = {
	* getFees() {
		const allFees = yield actions.fetchFeesFromAPI();
	},
};


// @see: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/
const store = createReduxStore( storeName, {
	reducer: reducer,
	actions: actions,
	selectors: selectors,
	controls: controls,
	resolvers: resolvers,
} );

register( store );

export { store, storeName };
